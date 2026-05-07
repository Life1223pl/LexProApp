<?php

namespace App\Controller;

use App\Entity\Postepowanie;
use App\Entity\Pracownik;
use App\Form\PostepowanieType;
use App\Repository\PostepowanieRepository;
use App\Repository\HistoryLogRepository;
use App\Repository\PracownikRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/postepowanie', name: 'app_postepowanie_')]
class PostepowanieController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(
        PostepowanieRepository $repo,
        Request $request,
        PaginatorInterface $paginator,
        PracownikRepository $pracownikRepository
    ): Response {
        $sort = $request->query->get('sort', 'dataWszczecia');
        $direction = $request->query->get('direction', 'desc');
        $user = $this->getUser();

        $filter = $request->query->get('filter', 'active');
        $prowadzacyId = $request->query->get('prowadzacy');

        $postepowania = $repo->findAccessibleForUserFiltered(
            $user,
            $filter === 'all' ? null : $filter
        );

        // 🔥 filtr prowadzącego
        if ($prowadzacyId) {
            $postepowania = array_filter($postepowania, function ($p) use ($prowadzacyId) {
                return $p->getProwadzacy()?->getId() == $prowadzacyId;
            });
        }

        $pagination = $paginator->paginate(
            $postepowania,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('postepowanie/index.html.twig', [
            'pagination' => $pagination,
            'filter' => $filter,
            'pracownicy' => $pracownikRepository->findAll(),
            'prowadzacySelected' => $prowadzacyId,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $postepowanie = new Postepowanie();
        $postepowanie->setProwadzacy($user);

        $form = $this->createForm(PostepowanieType::class, $postepowanie, [
            'pracownicy' => $em->getRepository(Pracownik::class)->findAll(),
            'user' => $user,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $prowadzacy = $form->get('prowadzacy')->getData();

            if ($prowadzacy) {
                $postepowanie->setProwadzacy($prowadzacy);
            }

            $em->persist($postepowanie);
            $em->flush();

            return $this->redirectToRoute('app_postepowanie_index');
        }

        return $this->render('postepowanie/new.html.twig', [
            'form' => $form,
            'postepowanie' => $postepowanie,
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
    public function edit(
        Request $request,
        Postepowanie $postepowanie,
        PostepowanieRepository $repo,
        EntityManagerInterface $em
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $repo);

        $form = $this->createForm(PostepowanieType::class, $postepowanie, [
            'pracownicy' => $em->getRepository(Pracownik::class)->findAll(),
            'user' => $this->getUser(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $prowadzacy = $form->get('prowadzacy')->getData();

            // 🔥 ADMIN → dowolny
            if ($this->isGranted('ROLE_ADMIN')) {
                $postepowanie->setProwadzacy($prowadzacy);
            }
            // 🔥 PRZEŁOŻONY → tylko podwładni + siebie
            elseif ($this->isGranted('ROLE_SUPERVISOR')) {

                $allowed = $this->getUser()->getPodwladni()->toArray();
                $allowed[] = $this->getUser();

                if (!in_array($prowadzacy, $allowed, true)) {
                    throw $this->createAccessDeniedException('Nie możesz przypisać tego użytkownika');
                }

                $postepowanie->setProwadzacy($prowadzacy);
            }
            // 🔥 USER → brak zmiany
            else {
                $postepowanie->setProwadzacy($this->getUser());
            }

            $em->flush();

            $this->addFlash('success', 'Zapisano zmiany');

            return $this->redirectToRoute('app_postepowanie_index');
        }

        return $this->render('postepowanie/edit.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
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

        if ($this->isCsrfTokenValid('delete'.$postepowanie->getId(), $request->request->get('_token'))) {
            $em->remove($postepowanie);
            $em->flush();
        }

        return $this->redirectToRoute('app_postepowanie_index');
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['POST'])]
    public function approve(Postepowanie $postepowanie, EntityManagerInterface $em): Response
    {
        if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_SUPERVISOR')) {
            throw $this->createAccessDeniedException();
        }

        $postepowanie->setStatus('APPROVED');
        $postepowanie->setApprovedAt(new \DateTimeImmutable());
        $postepowanie->setApprovedBy($this->getUser());

        $em->flush();

        return $this->redirectToRoute('app_postepowanie_index');
    }

    #[Route('/{id}/history', name: 'history', methods: ['GET'])]
    public function history(
        Postepowanie $postepowanie,
        PostepowanieRepository $repo,
        HistoryLogRepository $historyRepo,
        PracownikRepository $pracownikRepo
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $repo);

        $logs = $historyRepo->findForPostepowanieFiltered($postepowanie, [], 200);

        return $this->render('postepowanie/history.html.twig', [
            'postepowanie' => $postepowanie,
            'logs' => $logs,
            'users' => $pracownikRepo->findAll(),
            'entityChoices' => array_unique(array_map(fn($log) => $log->getEntityClass(), $logs)),
            'entityNiceNames' => [
                'App\Entity\Postepowanie' => 'Postępowanie',
                'App\Entity\Osoba' => 'Osoba',
                'App\Entity\Czynnosc' => 'Czynność',
                'App\Entity\PostepowanieOsoba' => 'Osoba w postępowaniu',
            ],
        ]);
    }

    private function denyUnlessAccessible(Postepowanie $postepowanie, PostepowanieRepository $repo): void
    {
        if (!$repo->isAccessibleForUser($postepowanie, $this->getUser())) {
            throw $this->createAccessDeniedException();
        }
    }
}
