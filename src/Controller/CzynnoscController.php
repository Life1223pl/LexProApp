<?php

namespace App\Controller;

use App\Entity\Czynnosc;
use App\Entity\Postepowanie;
use App\Form\CzynnoscType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;



#[Route('/postepowania/{postepowanie}/czynnosci', name: 'app_postepowanie_czynnosc_')]
final class CzynnoscController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Postepowanie $postepowanie): Response
    {
        return $this->render('czynnosc/index.html.twig', [
            'postepowanie' => $postepowanie,
            'czynnosci' => $postepowanie->getCzynnosci(),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Postepowanie $postepowanie, Request $request, EntityManagerInterface $em): Response
    {
        $czynnosc = new Czynnosc();
        $czynnosc->setPostepowanie($postepowanie);

        $form = $this->createForm(CzynnoscType::class, $czynnosc, [
            'postepowanie' => $postepowanie,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($czynnosc);
            $em->flush();

            $this->addFlash('success', 'Dodano czynność.');
            return $this->redirectToRoute('app_postepowanie_czynnosc_index', [
                'postepowanie' => $postepowanie->getId(),
            ]);
        }

        return $this->render('czynnosc/new.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Postepowanie $postepowanie, Czynnosc $czynnosc, Request $request, EntityManagerInterface $em): Response
    {
        // bezpieczeństwo: czynność musi należeć do tego postępowania
        if ($czynnosc->getPostepowanie()->getId() !== $postepowanie->getId()) {
            throw $this->createNotFoundException('Czynność nie należy do tego postępowania.');
        }

        $form = $this->createForm(CzynnoscType::class, $czynnosc, [
            'postepowanie' => $postepowanie,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Zapisano zmiany.');
            return $this->redirectToRoute('app_postepowanie_czynnosc_index', [
                'postepowanie' => $postepowanie->getId(),
            ]);
        }

        return $this->render('czynnosc/edit.html.twig', [
            'postepowanie' => $postepowanie,
            'czynnosc' => $czynnosc,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Postepowanie $postepowanie, Czynnosc $czynnosc, Request $request, EntityManagerInterface $em): Response
    {
        if ($czynnosc->getPostepowanie()->getId() !== $postepowanie->getId()) {
            throw $this->createNotFoundException('Czynność nie należy do tego postępowania.');
        }

        if ($this->isCsrfTokenValid('delete_czynnosc_'.$czynnosc->getId(), (string) $request->request->get('_token'))) {
            $em->remove($czynnosc);
            $em->flush();
            $this->addFlash('success', 'Usunięto czynność.');
        }

        return $this->redirectToRoute('app_postepowanie_czynnosc_index', [
            'postepowanie' => $postepowanie->getId(),
        ]);
    }
    #[Route('/{id}/spis', name: 'spis_edit', methods: ['GET', 'POST'])]
    public function spisEdit(Postepowanie $postepowanie, Czynnosc $czynnosc, Request $request, EntityManagerInterface $em): Response
    {
        if ($czynnosc->getPostepowanie()->getId() !== $postepowanie->getId()) {
            throw $this->createNotFoundException('Czynność nie należy do tego postępowania.');
        }

        if (!$czynnosc->isSpisRzeczyDozwolony()) {
            throw $this->createAccessDeniedException('Dla tego typu czynności nie można dodać spisu rzeczy.');
        }

        if ($request->isMethod('POST')) {
            // Oczekujemy JSON z frontu
            $payload = $request->request->get('spisRzeczyJson');
            $decoded = null;

            if (is_string($payload) && $payload !== '') {
                $decoded = json_decode($payload, true);
                if (!is_array($decoded)) {
                    $decoded = null;
                }
            }

            $czynnosc->setSpisRzeczy($decoded);
            $em->flush();

            $this->addFlash('success', 'Zapisano spis i opis rzeczy.');
            return $this->redirectToRoute('app_postepowanie_czynnosc_edit', [
                'postepowanie' => $postepowanie->getId(),
                'id' => $czynnosc->getId(),
            ]);
        }

        return $this->render('czynnosc/spis.html.twig', [
            'postepowanie' => $postepowanie,
            'czynnosc' => $czynnosc,
            'spis' => $czynnosc->getSpisRzeczy() ?? [],
        ]);
    }


}
