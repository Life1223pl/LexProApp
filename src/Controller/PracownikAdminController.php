<?php

namespace App\Controller;

use App\Entity\Pracownik;
use App\Form\AdminPracownikType;
use App\Repository\PracownikRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/pracownicy', name: 'admin_pracownicy_')]
class PracownikAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PracownikRepository $repo): Response
    {
        return $this->render('admin/pracownicy/index.html.twig', [
            'niezweryfikowani' => $repo->findBy(['isVerified' => false], ['id' => 'DESC']),
            'wszyscy' => $repo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Pracownik $pracownik,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(AdminPracownikType::class, $pracownik);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Zapisano zmiany.');
            return $this->redirectToRoute('admin_pracownicy_index');
        }

        return $this->render('admin/pracownicy/edit.html.twig', [
            'pracownik' => $pracownik,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/verify', name: 'verify', methods: ['POST'])]
    public function verify(
        Pracownik $pracownik,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('verify_'.$pracownik->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        $pracownik->setIsVerified(true);
        $em->flush();

        $this->addFlash('success', 'Konto zatwierdzone.');
        return $this->redirectToRoute('admin_pracownicy_index');
    }

    #[Route('/{id}/deactivate', name: 'deactivate', methods: ['POST'])]
    public function deactivate(
        Pracownik $pracownik,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('deactivate_'.$pracownik->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        $pracownik->setIsActive(false);
        $em->flush();

        $this->addFlash('success', 'Konto dezaktywowane.');
        return $this->redirectToRoute('admin_pracownicy_index');
    }
}
