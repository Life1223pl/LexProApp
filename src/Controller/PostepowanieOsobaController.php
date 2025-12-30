<?php

namespace App\Controller;

use App\Entity\Postepowanie;
use App\Entity\PostepowanieOsoba;
use App\Entity\Pracownik;
use App\Form\PostepowanieOsobaType;
use App\Repository\PostepowanieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/postepowanie/{id}/osoby', name: 'app_postepowanie_osoby_')]
final class PostepowanieOsobaController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Postepowanie $postepowanie, PostepowanieRepository $repo): Response
    {
        $this->denyUnlessAccessible($postepowanie, $repo);

        return $this->render('postepowanie/osoby/index.html.twig', [
            'postepowanie' => $postepowanie,
            'osoby' => $postepowanie->getOsoby(),
        ]);
    }

    #[Route('/add', name: 'add', methods: ['GET', 'POST'])]
    public function add(
        Postepowanie $postepowanie,
        PostepowanieRepository $repo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $repo);

        $po = new PostepowanieOsoba();
        $po->setPostepowanie($postepowanie);

        $form = $this->createForm(PostepowanieOsobaType::class, $po);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $em->persist($po);
            $em->flush();

            $this->addFlash('success', 'Osoba została dodana do postępowania.');
            return $this->redirectToRoute('app_postepowanie_osoby_index', ['id' => $postepowanie->getId()]);
        }

        return $this->render('postepowanie/osoby/add.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }

    #[Route('/{poId}/remove', name: 'remove', methods: ['POST'])]
    public function remove(
        Postepowanie $postepowanie,
        int $poId,
        PostepowanieRepository $repo,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $repo);

        if (!$this->isCsrfTokenValid('remove_po_'.$poId, (string)$request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        $po = $em->getRepository(PostepowanieOsoba::class)->find($poId);
        if (!$po) {
            throw $this->createNotFoundException();
        }


        if ($po->getPostepowanie()->getId() !== $postepowanie->getId()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($po);
        $em->flush();

        $this->addFlash('success', 'Usunięto osobę z postępowania (odpięcie roli).');
        return $this->redirectToRoute('app_postepowanie_osoby_index', ['id' => $postepowanie->getId()]);
    }

    private function denyUnlessAccessible(Postepowanie $postepowanie, PostepowanieRepository $repo): void
    {
        /** @var Pracownik $user */
        $user = $this->getUser();

        $allowed = $repo->findAccessibleForUser($user);
        foreach ($allowed as $p) {
            if ($p->getId() === $postepowanie->getId()) {
                return;
            }
        }

        throw $this->createAccessDeniedException();
    }
    #[Route('/{osobaId}/delete', name: 'delete', methods: ['POST'])]
    public function deleteFromPostepowanie(
        Postepowanie $postepowanie,
        int $osobaId,
        Request $request,
        EntityManagerInterface $em,
        PostepowanieRepository $postepowanieRepository
    ): Response {
        $this->denyUnlessAccessible($postepowanie, $postepowanieRepository);

        $rel = $em->getRepository(\App\Entity\PostepowanieOsoba::class)->find($osobaId);
        if (!$rel || $rel->getPostepowanie()->getId() !== $postepowanie->getId()) {
            throw $this->createNotFoundException();
        }

        if (!$this->isCsrfTokenValid('delete_postepowanie_osoba_' . $rel->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        $em->remove($rel);
        $em->flush();

        $this->addFlash('success', 'Usunięto osobę z postępowania.');

        return $this->redirectToRoute('app_postepowanie_osoby_index', ['id' => $postepowanie->getId()]);
    }


}
