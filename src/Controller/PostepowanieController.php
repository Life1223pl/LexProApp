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


    private function denyUnlessAccessible(Postepowanie $postepowanie, PostepowanieRepository $repo): void
    {
        /** @var Pracownik $user */
        $user = $this->getUser();

        if (!$repo->isAccessibleForUser($postepowanie, $user)) {
            throw $this->createAccessDeniedException();
        }
    }


}
