<?php

namespace App\Controller;

use App\Entity\Osoba;
use App\Form\OsobaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/osoby', name: 'app_osoba_')]
final class OsobaController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $osoby = $em->getRepository(Osoba::class)->findBy([], ['id' => 'DESC']);

        return $this->render('osoba/index.html.twig', [
            'osoby' => $osoby,
            'postepowanieId' => $request->query->get('postepowanie'),
            'referer' => $request->headers->get('referer'),
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $osoba = new Osoba();
        $form = $this->createForm(OsobaType::class, $osoba);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($osoba);
            $em->flush();

            $this->addFlash('success', 'Osoba została dodana.');

            $postepowanieId = $request->query->get('postepowanie');
            if ($postepowanieId) {
                // ✅ wracamy do listy osób w postępowaniu
                return $this->redirectToRoute('app_postepowanie_osoby_index', [
                    'id' => $postepowanieId,
                ]);
            }

            return $this->redirectToRoute('app_osoba_index');
        }

        return $this->render('osoba/new.html.twig', [
            'form' => $form,
            'postepowanieId' => $request->query->get('postepowanie'),
            'referer' => $request->headers->get('referer'),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Osoba $osoba, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $form = $this->createForm(OsobaType::class, $osoba);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Zapisano zmiany.');

            $postepowanieId = $request->query->get('postepowanie');
            if ($postepowanieId) {
                // ✅ wracamy do listy osób w postępowaniu
                return $this->redirectToRoute('app_postepowanie_osoby_index', [
                    'id' => $postepowanieId,
                ]);
            }

            return $this->redirectToRoute('app_osoba_index');
        }

        return $this->render('osoba/edit.html.twig', [
            'form' => $form,
            'osoba' => $osoba,
            'postepowanieId' => $request->query->get('postepowanie'),
            'referer' => $request->headers->get('referer'),
        ]);
    }
}
