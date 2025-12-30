<?php

namespace App\Controller\Admin;

use App\Entity\Osoba;
use App\Form\OsobaType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/osoby', name: 'admin_osoba_')]
final class OsobaAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $osoby = $em->getRepository(Osoba::class)->findBy([], ['id' => 'DESC']);

        return $this->render('admin/osoba/index.html.twig', [
            'osoby' => $osoby,
        ]);
    }

    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $osoba = new Osoba();
        $form = $this->createForm(OsobaType::class, $osoba);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($osoba);
            $em->flush();

            $this->addFlash('success', 'Osoba została dodana.');
            return $this->redirectToRoute('admin_osoba_index');
        }

        return $this->render('admin/osoba/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Osoba $osoba, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $form = $this->createForm(OsobaType::class, $osoba);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Zapisano zmiany.');
            return $this->redirectToRoute('admin_osoba_index');
        }

        return $this->render('admin/osoba/edit.html.twig', [
            'form' => $form,
            'osoba' => $osoba,
        ]);
    }

    #[Route('/{id}', name: 'delete', methods: ['POST'])]
    public function delete(Osoba $osoba, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('delete_osoba_'.$osoba->getId(), (string)$request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        $em->remove($osoba);
        $em->flush();

        $this->addFlash('success', 'Osoba została usunięta.');
        return $this->redirectToRoute('admin_osoba_index');
    }
}
