<?php

namespace App\Controller;

use App\Entity\Postepowanie;
use App\Entity\PostepowaniePracownik;
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




#[Route('/postepowanie')]
final class PostepowanieController extends AbstractController
{
    #[Route(name: 'app_postepowanie_index', methods: ['GET'])]
    public function index(PostepowanieRepository $postepowanieRepository, Request $request): Response
    {
        /** @var Pracownik $user */
        $user = $this->getUser();

        $filter = $request->query->get('filter', 'active'); // active|closed|all
        if (!in_array($filter, ['active', 'closed', 'all'], true)) {
            $filter = 'active';
        }

        $postepowania = $postepowanieRepository->findAccessibleForUserFiltered($user, $filter === 'all' ? null : $filter);

        return $this->render('postepowanie/index.html.twig', [
            'postepowanies' => $postepowania,
            'filter' => $filter,
        ]);
    }


    #[Route('/new', name: 'app_postepowanie_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var Pracownik $user */
        $user = $this->getUser();

        $postepowanie = new Postepowanie();

        // Użytkownik tworzy -> zawsze czeka na zatwierdzenie
        $postepowanie->setStatus(Postepowanie::STATUS_WAITING_APPROVAL);


        $postepowanie->setProwadzacy($user);

        // Zatwierdzający i data zatwierdzenia są puste do czasu akceptacji przez supervisor
        $postepowanie->setApprovedBy(null);
        $postepowanie->setApprovedAt(null);

        $form = $this->createForm(PostepowanieType::class, $postepowanie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($postepowanie);
            $entityManager->flush();

            $this->addFlash('success', 'Postępowanie utworzone i wysłane do zatwierdzenia.');

            return $this->redirectToRoute('app_postepowanie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('postepowanie/new.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }


    #[Route('/{id}', name: 'app_postepowanie_show', methods: ['GET'])]
    public function show(
        Postepowanie $postepowanie,
        PostepowanieRepository $postepowanieRepository
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $postepowanieRepository);


        $przypisaneOsoby = $postepowanie->getOsoby();


        return $this->render('postepowanie/show.html.twig', [
            'postepowanie' => $postepowanie,
            'przypisaneOsoby' => $przypisaneOsoby,
        ]);
    }



    #[Route('/{id}/edit', name: 'app_postepowanie_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Postepowanie $postepowanie, PostepowanieRepository $postepowanieRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyUnlessAccessible($postepowanie, $postepowanieRepository);

        /** @var Pracownik $user */
        $user = $this->getUser();

        if (
            !$this->isGranted('ROLE_SUPERVISOR') &&
            $postepowanie->getStatus() === Postepowanie::STATUS_WAITING_DELETE_APPROVAL
        ) {
            $this->addFlash('success', 'To postępowanie oczekuje na usunięcie i nie może być edytowane.');
            return $this->redirectToRoute('app_postepowanie_show', ['id' => $postepowanie->getId()]);
        }


        $form = $this->createForm(PostepowanieType::class, $postepowanie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_postepowanie_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('postepowanie/edit.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_postepowanie_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Postepowanie $postepowanie,
        PostepowanieRepository $postepowanieRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $postepowanieRepository);

        /** @var Pracownik $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete'.$postepowanie->getId(), $request->getPayload()->getString('_token'))) {
            return $this->redirectToRoute('app_postepowanie_index');
        }

        // Supervisor może usuwać od razu
        if ($this->isGranted('ROLE_SUPERVISOR')) {
            $entityManager->remove($postepowanie);
            $entityManager->flush();
            $this->addFlash('success', 'Postępowanie usunięte.');
            return $this->redirectToRoute('app_postepowanie_index');
        }

        // Pracownik: wniosek o usunięcie
        $postepowanie->setStatus(Postepowanie::STATUS_WAITING_DELETE_APPROVAL);
        $postepowanie->setDeleteRequestedBy($user);
        $postepowanie->setDeleteRequestedAt(new \DateTimeImmutable());

        $entityManager->flush();

        $this->addFlash('success', 'Wniosek o usunięcie wysłany do przełożonego.');

        return $this->redirectToRoute('app_postepowanie_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/postepowanie/{id}/historia', name: 'app_postepowanie_history', methods: ['GET'])]
    public function history(
        Postepowanie $postepowanie,
        PostepowanieRepository $postepowanieRepository,
        HistoryLogRepository $historyLogRepository,
        PracownikRepository $pracownikRepository,
        Request $request
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $postepowanieRepository);

        $filters = [
            'from'   => $request->query->get('from'),
            'to'     => $request->query->get('to'),
            'action' => $request->query->get('action'),
            'entity' => $request->query->get('entity'),
            'user'   => $request->query->get('user'),
        ];

        // wyczyść puste wartości
        $filters = array_filter($filters, static fn($v) => $v !== null && $v !== '');

        // logi wg filtrów
        $logs = $historyLogRepository->findForPostepowanieFiltered($postepowanie, $filters, 300);

        // użytkownicy do selecta
        $users = $pracownikRepository->findAll();

        // obiekty (encje) do selecta - wyciągnięte z bazy dla tego postępowania
        $entityChoices = $historyLogRepository->findDistinctEntityClassesForPostepowanie($postepowanie);

        $relatedLabels = $historyLogRepository->buildRelatedLabelsForLogs($logs);

        return $this->render('postepowanie/history.html.twig', [
            'postepowanie'  => $postepowanie,
            'logs'          => $logs,
            'filters'       => $filters,
            'users'         => $users,
            'entityChoices' => $entityChoices,
            'relatedLabels' => $relatedLabels,

        ]);
    }



    private function denyUnlessAccessible(Postepowanie $postepowanie, PostepowanieRepository $repo): void
    {
        /** @var Pracownik $user */
        $user = $this->getUser();

        if (!$repo->isAccessibleForUser($postepowanie, $user)) {
            throw $this->createAccessDeniedException();
        }
    }
    #[Route('/postepowanie/{id}/approve', name: 'app_postepowanie_approve', methods: ['POST'])]
    public function approve(Postepowanie $postepowanie, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        if (!$user instanceof \App\Entity\Pracownik) {
            throw $this->createAccessDeniedException();
        }

        // musi czekać na zatwierdzenie
        if ($postepowanie->getStatus() !== \App\Entity\Postepowanie::STATUS_WAITING_APPROVAL) {
            $this->addFlash('warning', 'To postępowanie nie czeka na zatwierdzenie.');
            return $this->redirectToRoute('app_postepowanie_index');
        }

        // HYBRYDA: przełożony prowadzącego albo admin
        $isSupervisor = $postepowanie->getProwadzacy()?->getPrzelozony()?->getId() === $user->getId();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        if (!$isSupervisor && !$isAdmin) {
            throw $this->createAccessDeniedException('Brak uprawnień do zatwierdzania tego postępowania.');
        }

        $postepowanie->setStatus(\App\Entity\Postepowanie::STATUS_APPROVED);
        $postepowanie->setApprovedBy($user);
        $postepowanie->setApprovedAt(new \DateTimeImmutable());

        $em->flush();

        $this->addFlash('success', 'Postępowanie zostało zatwierdzone.');
        return $this->redirectToRoute('app_postepowanie_index');
    }



}
