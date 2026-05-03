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
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/pracownicy', name: 'admin_pracownicy_')]
#[IsGranted('ROLE_ADMIN')]
class PracownikAdminController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PracownikRepository $repo): Response
    {
        return $this->render('admin/pracownicy/index.html.twig', [
            'oczekujacy' => $repo->findBy(['isActive' => false], ['id' => 'DESC']),
            'aktywni' => $repo->findBy(['isActive' => true], ['id' => 'DESC']),
        ]);
    }



    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Pracownik $pracownik,
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $form = $this->createForm(AdminPracownikType::class, $pracownik, [
            'pracownicy' => $em->getRepository(Pracownik::class)->findAll(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // ROLE (ręczne)
            $roles = $request->request->all('roles') ?? [];
            if (!in_array('ROLE_USER', $roles)) {
                $roles[] = 'ROLE_USER';
            }
            $pracownik->setRoles($roles);

            // AKTYWNOŚĆ
            $pracownik->setIsActive($form->get('isActive')->getData());

            // HASŁO
            $newPassword = $form->get('plainPassword')->getData();
            if ($newPassword) {
                $pracownik->setPassword(
                    $passwordHasher->hashPassword($pracownik, $newPassword)
                );
            }

            $em->flush();

            $this->addFlash('success', 'Zapisano zmiany.');

            return $this->redirectToRoute('admin_pracownicy_index');
        }

        return $this->render('admin/pracownicy/edit.html.twig', [
            'pracownik' => $pracownik,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/activate', name: 'activate', methods: ['POST'])]
    public function activate(
        Pracownik $pracownik,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        if (!$this->isCsrfTokenValid('activate_'.$pracownik->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        $pracownik->setIsActive(true);
        $em->flush();

        $this->addFlash('success', 'Użytkownik został aktywowany.');

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

        $this->addFlash('success', 'Użytkownik został zablokowany.');

        return $this->redirectToRoute('admin_pracownicy_index');
    }
}
