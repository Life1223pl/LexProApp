<?php

namespace App\Repository;

use App\Entity\Postepowanie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Pracownik;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Postepowanie>
 */
class PostepowanieRepository extends ServiceEntityRepository
{
    public function findAccessibleForUser(Pracownik $user): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.przypisania', 'pp')
            ->leftJoin('pp.pracownik', 'assigned')
            ->innerJoin('p.prowadzacy', 'lead')
            ->andWhere('lead = :me OR assigned = :me OR lead.przelozony = :me')
            ->setParameter('me', $user)
            ->distinct()
            ->orderBy('p.id', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function isAccessibleForUser(Postepowanie $p, $user): bool
    {
        // 🔥 ADMIN zawsze może
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // 🔥 przełożony → swoje + podwładni
        if (in_array('ROLE_SUPERVISOR', $user->getRoles(), true)) {
            return $p->getProwadzacy() === $user
                || $p->getProwadzacy()?->getPrzelozony()?->getId() === $user->getId();
        }

        // 🔥 zwykły user → tylko swoje
        return $p->getProwadzacy() === $user;
    }


    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Postepowanie::class);
    }
    public function findAccessibleForUserFiltered($user, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('p');

        //  ADMIN widzi wszystko
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            // brak ograniczeń
        }
        //  PRZEŁOŻONY widzi:
        // - swoje
        // - swoich podwładnych
        elseif (in_array('ROLE_SUPERVISOR', $user->getRoles(), true)) {
            $qb->andWhere('p.prowadzacy = :me OR p.prowadzacy IN (:podwladni)')
                ->setParameter('me', $user)
                ->setParameter('podwladni', $user->getPodwladni()->toArray());
        }
        // zwykły user → tylko swoje
        else {
            $qb->andWhere('p.prowadzacy = :me')
                ->setParameter('me', $user);
        }

        if ($status === 'active') {
            $qb->andWhere('p.status != :closed')
                ->setParameter('closed', 'ZAKONCZONE');
        }

        if ($status === 'closed') {
            $qb->andWhere('p.status = :closed')
                ->setParameter('closed', 'ZAKONCZONE');
        }

        return $qb->getQuery()->getResult();
    }



}
