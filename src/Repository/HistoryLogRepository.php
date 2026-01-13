<?php

namespace App\Repository;

use App\Entity\HistoryLog;
use App\Entity\Postepowanie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HistoryLog>
 */
class HistoryLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HistoryLog::class);
    }

    /**
     * @return HistoryLog[]
     */
    public function findForPostepowanie(Postepowanie $postepowanie, int $limit = 200): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.postepowanie = :p')
            ->setParameter('p', $postepowanie)
            ->orderBy('h.occurredAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
