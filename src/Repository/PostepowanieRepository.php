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
    public function isAccessibleForUser(\App\Entity\Postepowanie $postepowanie, Pracownik $user): bool
    {
        $count = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->leftJoin('p.przypisania', 'pp')
            ->leftJoin('pp.pracownik', 'assigned')
            ->innerJoin('p.prowadzacy', 'lead')
            ->andWhere('p = :p')
            ->andWhere('lead = :me OR assigned = :me OR lead.przelozony = :me')
            ->setParameter('p', $postepowanie)
            ->setParameter('me', $user)
            ->getQuery()
            ->getSingleScalarResult();

        return ((int)$count) > 0;
    }


    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Postepowanie::class);
    }
    public function findAccessibleForUserFiltered(\App\Entity\Pracownik $user, ?string $filter): array
    {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.prowadzacy', 'lead')
            ->leftJoin('p.przypisania', 'pp')
            ->leftJoin('pp.pracownik', 'assigned')
            ->andWhere('lead = :me OR assigned = :me OR lead.przelozony = :me')
            ->setParameter('me', $user)
            ->distinct()
            ->orderBy('p.id', 'DESC');

        $today = new \DateTimeImmutable('today');

        if ($filter === 'active') {
            $qb->andWhere('p.dataZakonczenia IS NULL OR p.dataZakonczenia >= :today')
                ->setParameter('today', $today);
        } elseif ($filter === 'closed') {
            $qb->andWhere('p.dataZakonczenia IS NOT NULL AND p.dataZakonczenia < :today')
                ->setParameter('today', $today);
        }

        return $qb->getQuery()->getResult();
    }


}
