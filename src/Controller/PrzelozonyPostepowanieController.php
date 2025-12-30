<?php

namespace App\Controller;

use App\Entity\Postepowanie;
use App\Entity\PostepowaniePracownik;
use App\Entity\Pracownik;
use App\Form\AdminPostepowanieApproveType;
use App\Repository\PostepowanieRepository;
use App\Repository\PracownikRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/przelozony/postepowania', name: 'przelozony_postepowania_')]
final class PrzelozonyPostepowanieController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(PostepowanieRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERVISOR');

        /** @var Pracownik $me */
        $me = $this->getUser();


        $waiting = $repo->createQueryBuilder('p')
            ->innerJoin('p.prowadzacy', 'lead')
            ->andWhere('p.status = :status')
            ->andWhere('lead.przelozony = :me')
            ->setParameter('status', Postepowanie::STATUS_WAITING_APPROVAL)
            ->setParameter('me', $me)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('przelozony/postepowania/index.html.twig', [
            'waiting' => $waiting,
        ]);
    }

    #[Route('/przydzialy', name: 'assignments', methods: ['GET'])]
    public function assignments(PostepowanieRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERVISOR');

        /** @var Pracownik $me */
        $me = $this->getUser();

        $approved = $repo->createQueryBuilder('p')
            ->innerJoin('p.prowadzacy', 'lead')
            ->leftJoin('p.przypisania', 'pp')
            ->leftJoin('pp.pracownik', 'assigned')
            ->addSelect('pp', 'assigned')
            ->andWhere('p.status = :status')
            ->andWhere('lead.przelozony = :me')
            ->setParameter('status', Postepowanie::STATUS_APPROVED)
            ->setParameter('me', $me)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('przelozony/postepowania/assignments.html.twig', [
            'postepowania' => $approved,
        ]);
    }

    #[Route('/wnioski-usuniecia', name: 'delete_requests', methods: ['GET'])]
    public function deleteRequests(PostepowanieRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERVISOR');

        /** @var Pracownik $me */
        $me = $this->getUser();

        $waiting = $repo->createQueryBuilder('p')
            ->innerJoin('p.prowadzacy', 'lead')
            ->andWhere('p.status = :status')
            ->andWhere('lead.przelozony = :me')
            ->setParameter('status', Postepowanie::STATUS_WAITING_DELETE_APPROVAL)
            ->setParameter('me', $me)
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('przelozony/postepowania/delete_requests.html.twig', [
            'waiting' => $waiting,
        ]);
    }

    #[Route('/{id}/approve-delete', name: 'approve_delete', methods: ['POST'])]
    public function approveDelete(Postepowanie $postepowanie, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERVISOR');

        /** @var Pracownik $me */
        $me = $this->getUser();

        // Wariant A: tylko jeśli aktualny prowadzący jest moim podwładnym
        $lead = $postepowanie->getProwadzacy();
        if (!$lead || !$lead->getPrzelozony() || $lead->getPrzelozony()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('approve_delete_'.$postepowanie->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        if ($postepowanie->getStatus() !== Postepowanie::STATUS_WAITING_DELETE_APPROVAL) {
            $this->addFlash('success', 'To postępowanie nie czeka na usunięcie.');
            return $this->redirectToRoute('przelozony_postepowania_delete_requests');
        }

        $postepowanie->setDeleteApprovedBy($me);
        $postepowanie->setDeleteApprovedAt(new \DateTimeImmutable());

        // Finalnie usuń z bazy
        $em->remove($postepowanie);
        $em->flush();

        $this->addFlash('success', 'Usunięcie zatwierdzone. Postępowanie usunięte z bazy.');
        return $this->redirectToRoute('przelozony_postepowania_delete_requests');
    }

    #[Route('/{id}/reject-delete', name: 'reject_delete', methods: ['POST'])]
    public function rejectDelete(Postepowanie $postepowanie, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERVISOR');

        /** @var Pracownik $me */
        $me = $this->getUser();

        $lead = $postepowanie->getProwadzacy();
        if (!$lead || !$lead->getPrzelozony() || $lead->getPrzelozony()->getId() !== $me->getId()) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('reject_delete_'.$postepowanie->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Nieprawidłowy token CSRF.');
        }

        if ($postepowanie->getStatus() !== Postepowanie::STATUS_WAITING_DELETE_APPROVAL) {
            $this->addFlash('success', 'To postępowanie nie czeka na usunięcie.');
            return $this->redirectToRoute('przelozony_postepowania_delete_requests');
        }

        $postepowanie->setStatus(Postepowanie::STATUS_DELETE_REJECTED);
        $em->flush();

        $this->addFlash('success', 'Wniosek o usunięcie odrzucony.');
        return $this->redirectToRoute('przelozony_postepowania_delete_requests');
    }

    #[Route('/{id}/approve', name: 'approve', methods: ['GET', 'POST'])]
    public function approve(
        Postepowanie $postepowanie,
        Request $request,
        PracownikRepository $pracownikRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_SUPERVISOR');

        /** @var Pracownik $me */
        $me = $this->getUser();


        $lead = $postepowanie->getProwadzacy();
        if (
            !$lead ||
            !$lead->getPrzelozony() ||
            $lead->getPrzelozony()->getId() !== $me->getId()
        ) {
            throw $this->createAccessDeniedException(
                'Możesz zatwierdzać tylko postępowania prowadzone przez Twoich podwładnych.'
            );
        }

        if ($postepowanie->getStatus() !== Postepowanie::STATUS_WAITING_APPROVAL) {
            $this->addFlash('success', 'To postępowanie nie jest w statusie oczekującym.');
            return $this->redirectToRoute('przelozony_postepowania_index');
        }

        // Lista pracowników do przypisania: supervisor + jego podwładni
        $pracownicy = $pracownikRepository->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->andWhere('p.isVerified = :verified')
            ->andWhere('p = :me OR p.przelozony = :me')
            ->setParameter('active', true)
            ->setParameter('verified', true)
            ->setParameter('me', $me)
            ->orderBy('p.nazwisko', 'ASC')
            ->getQuery()
            ->getResult();

        $form = $this->createForm(AdminPostepowanieApproveType::class, null, [
            'pracownicy' => $pracownicy,
            'default_prowadzacy' => $postepowanie->getProwadzacy(),
            'default_assigned' => [$postepowanie->getProwadzacy()],
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Pracownik[] $assigned */
            $assigned = $form->get('assignedPracownicy')->getData()->toArray();

            /** @var Pracownik $newProwadzacy */
            $newProwadzacy = $form->get('prowadzacy')->getData();

            $assignedIds = array_map(fn(Pracownik $p) => $p->getId(), $assigned);
            if (!in_array($newProwadzacy->getId(), $assignedIds, true)) {
                $this->addFlash('success', 'Prowadzący musi być wśród przypisanych pracowników.');
                return $this->redirectToRoute('przelozony_postepowania_approve', ['id' => $postepowanie->getId()]);
            }


            foreach ($postepowanie->getPrzypisania() as $pp) {
                $postepowanie->removePrzypisanie($pp);
                $em->remove($pp);
            }


            $em->flush();

            foreach ($assigned as $pracownik) {
                $pp = new PostepowaniePracownik();
                $pp->setPostepowanie($postepowanie);
                $pp->setPracownik($pracownik);

                $pp->setRola(
                    $pracownik->getId() === $newProwadzacy->getId()
                        ? PostepowaniePracownik::ROLA_PROWADZACY
                        : PostepowaniePracownik::ROLA_WSPOLPROWADZACY
                );

                $em->persist($pp);
                $postepowanie->addPrzypisanie($pp);
            }

            $postepowanie->setProwadzacy($newProwadzacy);
            $postepowanie->setStatus(Postepowanie::STATUS_APPROVED);
            $postepowanie->setApprovedBy($me);
            $postepowanie->setApprovedAt(new \DateTimeImmutable());

            $em->flush();

            $this->addFlash('success', 'Postępowanie zatwierdzone.');
            return $this->redirectToRoute('przelozony_postepowania_index');
        }

        return $this->render('przelozony/postepowania/approve.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(
        Postepowanie $postepowanie,
        Request $request,
        PracownikRepository $pracownikRepository,
        EntityManagerInterface $em
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_SUPERVISOR');

        /** @var Pracownik $me */
        $me = $this->getUser();

        // supervisor może edytować tylko, jeśli aktualny prowadzący jest jego podwładnym
        $lead = $postepowanie->getProwadzacy();
        if (
            !$lead ||
            !$lead->getPrzelozony() ||
            $lead->getPrzelozony()->getId() !== $me->getId()
        ) {
            throw $this->createAccessDeniedException();
        }

        // lista pracowników
        $pracownicy = $pracownikRepository->createQueryBuilder('p')
            ->andWhere('p.isActive = true')
            ->andWhere('p.isVerified = true')
            ->andWhere('p = :me OR p.przelozony = :me')
            ->setParameter('me', $me)
            ->orderBy('p.nazwisko', 'ASC')
            ->getQuery()
            ->getResult();


        $defaultAssigned = [];
        foreach ($postepowanie->getPrzypisania() as $pp) {
            $defaultAssigned[] = $pp->getPracownik();
        }

        $form = $this->createForm(AdminPostepowanieApproveType::class, null, [
            'pracownicy' => $pracownicy,
            'default_prowadzacy' => $postepowanie->getProwadzacy(),
            'default_assigned' => $defaultAssigned,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var Pracownik[] $assigned */
            $assigned = $form->get('assignedPracownicy')->getData()->toArray();

            /** @var Pracownik $newProwadzacy */
            $newProwadzacy = $form->get('prowadzacy')->getData();

            $assignedIds = array_map(fn(Pracownik $p) => $p->getId(), $assigned);
            if (!in_array($newProwadzacy->getId(), $assignedIds, true)) {
                $this->addFlash('success', 'Prowadzący musi być wśród przypisanych.');
                return $this->redirectToRoute('przelozony_postepowania_edit', ['id' => $postepowanie->getId()]);
            }


            foreach ($postepowanie->getPrzypisania() as $pp) {
                $postepowanie->removePrzypisanie($pp);
                $em->remove($pp);
            }
            $em->flush();


            foreach ($assigned as $pracownik) {
                $pp = new PostepowaniePracownik();
                $pp->setPostepowanie($postepowanie);
                $pp->setPracownik($pracownik);
                $pp->setRola(
                    $pracownik->getId() === $newProwadzacy->getId()
                        ? PostepowaniePracownik::ROLA_PROWADZACY
                        : PostepowaniePracownik::ROLA_WSPOLPROWADZACY
                );
                $em->persist($pp);
                $postepowanie->addPrzypisanie($pp);
            }

            $postepowanie->setProwadzacy($newProwadzacy);

            $em->flush();

            $this->addFlash('success', 'Postępowanie zaktualizowane.');
            return $this->redirectToRoute('przelozony_postepowania_assignments');
        }

        return $this->render('przelozony/postepowania/edit.html.twig', [
            'postepowanie' => $postepowanie,
            'form' => $form,
        ]);
    }
}
