<?php

namespace App\Controller;

use App\Entity\Postepowanie;
use App\Entity\Pracownik;
use App\Form\PostepowanieType;
use App\Repository\PostepowanieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\HistoryLogRepository;
use App\Repository\PracownikRepository;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/postepowanie', name: 'app_postepowanie_')]
final class PostepowanieController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        PostepowanieRepository $repo,
        Request $request,
        PaginatorInterface $paginator,
        PracownikRepository $pracownikRepository
    ): Response {
        $user = $this->getUser();

        $sort = $request->query->get('sort', 'dataWszczecia');
        $direction = $request->query->get('direction', 'desc');
        $filter = $request->query->get('filter', 'active');
        $search = $request->query->get('search');
        $prowadzacyId = $request->query->get('prowadzacy');
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        // 🔥 DOSTĘP
        if ($this->isGranted('ROLE_ADMIN')) {
            $postepowania = $repo->findAll();
        } elseif ($this->isGranted('ROLE_SUPERVISOR')) {
            $postepowania = $repo->createQueryBuilder('p')
                ->join('p.prowadzacy', 'pr')
                ->where('pr.przelozony = :user')
                ->setParameter('user', $user)
                ->orderBy('p.id', 'DESC')
                ->getQuery()
                ->getResult();
        } else {
            $postepowania = $repo->createQueryBuilder('p')
                ->where('p.prowadzacy = :user')
                ->setParameter('user', $user)
                ->orderBy('p.id', 'DESC')
                ->getQuery()
                ->getResult();
        }

        //  SEARCH
        if ($search) {
            $searchLower = mb_strtolower($search);

            $postepowania = array_filter($postepowania, function ($p) use ($searchLower) {
                return str_contains(mb_strtolower($p->getNumer()), $searchLower)
                    || str_contains(mb_strtolower($p->getOpis() ?? ''), $searchLower);
            });
        }

        // 👤 FILTR PROWADZĄCEGO
        if ($prowadzacyId) {
            $postepowania = array_filter($postepowania, fn($p) =>
                $p->getProwadzacy()?->getId() == $prowadzacyId
            );
        }

        // FILTR DATY
        if ($from || $to) {
            $postepowania = array_filter($postepowania, function ($p) use ($from, $to) {
                $data = $p->getDataWszczecia();
                if (!$data) return false;

                if ($from && $data < new \DateTime($from)) return false;
                if ($to && $data > new \DateTime($to)) return false;

                return true;
            });
        }

        // 🔄 SORT
        usort($postepowania, function ($a, $b) use ($sort, $direction) {
            $valueA = $sort === 'numer'
                ? (int)$a->getNumer()
                : $a->getDataWszczecia();

            $valueB = $sort === 'numer'
                ? (int)$b->getNumer()
                : $b->getDataWszczecia();

            if ($valueA == $valueB) return 0;

            return $direction === 'asc'
                ? ($valueA < $valueB ? -1 : 1)
                : ($valueA > $valueB ? -1 : 1);
        });

        $pagination = $paginator->paginate(
            $postepowania,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('postepowanie/index.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'search' => $search,
            'prowadzacySelected' => $prowadzacyId,
            'pracownicy' => $pracownikRepository->findAll(),
            'from' => $from,
            'to' => $to,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $postepowanie = new Postepowanie();
        $postepowanie->setStatus(Postepowanie::STATUS_WAITING_APPROVAL);
        $postepowanie->setProwadzacy($user);

        $form = $this->createForm(PostepowanieType::class, $postepowanie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($postepowanie);
            $em->flush();

            return $this->redirectToRoute('app_postepowanie_index');
        }

        return $this->render('postepowanie/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(
        Postepowanie $postepowanie,
        PostepowanieRepository $repo
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $repo);

        return $this->render('postepowanie/show.html.twig', [
            'postepowanie' => $postepowanie,
            'przypisaneOsoby' => $postepowanie->getOsoby(),
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Postepowanie $postepowanie,
        PostepowanieRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $repo);

        if (!$this->isCsrfTokenValid('delete'.$postepowanie->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_postepowanie_index');
        }

        if ($this->isGranted('ROLE_SUPERVISOR')) {
            $em->remove($postepowanie);
            $em->flush();
        } else {
            $postepowanie->setStatus(Postepowanie::STATUS_WAITING_DELETE_APPROVAL);
            $postepowanie->setDeleteRequestedBy($this->getUser());
            $postepowanie->setDeleteRequestedAt(new \DateTimeImmutable());
            $em->flush();
        }

        return $this->redirectToRoute('app_postepowanie_index');
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(
        Postepowanie $postepowanie,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('approve'.$postepowanie->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('app_postepowanie_index');
        }

        $user = $this->getUser();

        if (!$user instanceof Pracownik) {
            throw $this->createAccessDeniedException();
        }

        if ($postepowanie->getStatus() !== Postepowanie::STATUS_WAITING_APPROVAL) {
            return $this->redirectToRoute('app_postepowanie_index');
        }

        $isSupervisor = $postepowanie->getProwadzacy()?->getPrzelozony()?->getId() === $user->getId();

        if (!$isSupervisor && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $postepowanie->setStatus(Postepowanie::STATUS_APPROVED);
        $postepowanie->setApprovedBy($user);
        $postepowanie->setApprovedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->redirectToRoute('app_postepowanie_index');
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Postepowanie $postepowanie,
        PostepowanieRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $repo);

        $form = $this->createForm(PostepowanieType::class, $postepowanie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('app_postepowanie_index');
        }

        return $this->render('postepowanie/edit.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }

    private function denyUnlessAccessible(Postepowanie $postepowanie, PostepowanieRepository $repo): void
    {
        if (!$repo->isAccessibleForUser($postepowanie, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
    }
}
