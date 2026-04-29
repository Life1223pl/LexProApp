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
        PostepowanieRepository $postepowanieRepository,
        Request $request,
        PaginatorInterface $paginator,
        PracownikRepository $pracownikRepository
    ): Response {
        $user = $this->getUser();
        $sort = $request->query->get('sort', 'dataWszczecia');
        $direction = $request->query->get('direction', 'desc');

        $filter = $request->query->get('filter', 'active');
        if (!in_array($filter, ['active', 'closed', 'all'], true)) {
            $filter = 'active';
        }

        $search = $request->query->get('search');
        $prowadzacyId = $request->query->get('prowadzacy');

        // NOWE: zakres dat
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $postepowania = $postepowanieRepository->findAccessibleForUserFiltered(
            $user,
            $filter === 'all' ? null : $filter
        );

        // SEARCH
        if ($search) {
            $searchLower = mb_strtolower($search);

            $postepowania = array_filter($postepowania, function ($p) use ($searchLower) {
                $numer = (string)$p->getNumer();
                $opis = mb_strtolower($p->getOpis() ?? '');

                if (ctype_digit($searchLower)) {
                    return $numer === $searchLower;
                }

                return str_contains(mb_strtolower($numer), $searchLower)
                    || str_contains($opis, $searchLower);
            });
        }

        // FILTR PROWADZĄCEGO
        if ($prowadzacyId) {
            $postepowania = array_filter($postepowania, function ($p) use ($prowadzacyId) {
                return $p->getProwadzacy()?->getId() == $prowadzacyId;
            });
        }

        // NOWE: FILTR DATY WSZCZĘCIA
        if ($from || $to) {
            $postepowania = array_filter($postepowania, function ($p) use ($from, $to) {

                $data = $p->getDataWszczecia();

                if (!$data) {
                    return false;
                }

                if ($from && $data < new \DateTime($from)) {
                    return false;
                }

                if ($to && $data > new \DateTime($to)) {
                    return false;
                }

                return true;
            });
        }

        // SORTOWANIE
        usort($postepowania, function ($a, $b) use ($sort, $direction) {

            $valueA = null;
            $valueB = null;

            switch ($sort) {
                case 'numer':
                    $valueA = (int)$a->getNumer();
                    $valueB = (int)$b->getNumer();
                    break;

                case 'dataWszczecia':
                    $valueA = $a->getDataWszczecia();
                    $valueB = $b->getDataWszczecia();
                    break;

                default:
                    return 0;
            }

            if ($valueA == $valueB) {
                return 0;
            }

            if ($direction === 'asc') {
                return $valueA < $valueB ? -1 : 1;
            }

            return $valueA > $valueB ? -1 : 1;
        });


        // PAGINACJA
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

            // NOWE: przekazanie do Twig
            'from' => $from,
            'to' => $to,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();

        $postepowanie = new Postepowanie();
        $postepowanie->setStatus(Postepowanie::STATUS_WAITING_APPROVAL);
        $postepowanie->setProwadzacy($user);

        // bezpieczne generowanie numeru
        $all = $entityManager->getRepository(Postepowanie::class)
            ->createQueryBuilder('p')
            ->select('p.numer')
            ->getQuery()
            ->getScalarResult();

        $max = 0;

        foreach ($all as $row) {
            $num = (int)$row['numer'];
            if ($num > $max) {
                $max = $num;
            }
        }

        $postepowanie->setNumer((string)($max + 1));

        $form = $this->createForm(PostepowanieType::class, $postepowanie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($postepowanie);
            $entityManager->flush();

            return $this->redirectToRoute('app_postepowanie_index');
        }

        return $this->render('postepowanie/new.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Postepowanie $postepowanie, PostepowanieRepository $repo): Response
    {
        $this->denyUnlessAccessible($postepowanie, $repo);

        return $this->render('postepowanie/show.html.twig', [
            'postepowanie' => $postepowanie,
            'przypisaneOsoby' => $postepowanie->getOsoby(),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Postepowanie $postepowanie, PostepowanieRepository $repo, EntityManagerInterface $em): Response
    {
        $this->denyUnlessAccessible($postepowanie, $repo);

        if (
            !$this->isGranted('ROLE_SUPERVISOR') &&
            $postepowanie->getStatus() === Postepowanie::STATUS_WAITING_DELETE_APPROVAL
        ) {
            return $this->redirectToRoute('app_postepowanie_show', ['id' => $postepowanie->getId()]);
        }

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

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Postepowanie $postepowanie, PostepowanieRepository $repo, EntityManagerInterface $em): Response
    {
        $this->denyUnlessAccessible($postepowanie, $repo);

        if (!$this->isCsrfTokenValid('delete'.$postepowanie->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_postepowanie_index');
        }

        if ($this->isGranted('ROLE_SUPERVISOR')) {
            $em->remove($postepowanie);
            $em->flush();
            return $this->redirectToRoute('app_postepowanie_index');
        }

        $postepowanie->setStatus(Postepowanie::STATUS_WAITING_DELETE_APPROVAL);
        $postepowanie->setDeleteRequestedBy($this->getUser());
        $postepowanie->setDeleteRequestedAt(new \DateTimeImmutable());

        $em->flush();

        return $this->redirectToRoute('app_postepowanie_index');
    }

    #[Route('/{id}/historia', name: 'history', methods: ['GET'])]
    public function history(Postepowanie $postepowanie, PostepowanieRepository $repo, HistoryLogRepository $historyRepo, PracownikRepository $pracownikRepo, Request $request): Response
    {
        $this->denyUnlessAccessible($postepowanie, $repo);

        $filters = array_filter([
            'from' => $request->query->get('from'),
            'to' => $request->query->get('to'),
            'action' => $request->query->get('action'),
            'entity' => $request->query->get('entity'),
            'user' => $request->query->get('user'),
        ]);

        $logs = $historyRepo->findForPostepowanieFiltered($postepowanie, $filters, 300);

        return $this->render('postepowanie/history.html.twig', [
            'postepowanie' => $postepowanie,
            'logs' => $logs,
            'filters' => $filters,
            'users' => $pracownikRepo->findAll(),
            'entityChoices' => $historyRepo->findDistinctEntityClassesForPostepowanie($postepowanie),
            'relatedLabels' => $historyRepo->buildRelatedLabelsForLogs($logs),
        ]);
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(Postepowanie $postepowanie, EntityManagerInterface $em): Response
    {
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

    private function denyUnlessAccessible(Postepowanie $postepowanie, PostepowanieRepository $repo): void
    {
        if (!$repo->isAccessibleForUser($postepowanie, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
    }
}
