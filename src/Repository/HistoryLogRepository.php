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
    public function findForPostepowanieFiltered(
        Postepowanie $postepowanie,
        array $filters,
        int $limit = 300
    ): array {
        $qb = $this->createQueryBuilder('h')
            ->andWhere('h.postepowanie = :p')
            ->setParameter('p', $postepowanie)
            ->orderBy('h.occurredAt', 'DESC')
            ->setMaxResults($limit);

        if (!empty($filters['entity'])) {
            $entity = trim((string) $filters['entity']);


            $entity = str_replace('\\\\', '\\', $entity);

            if (str_contains($entity, '\\')) {

                $qb->andWhere('h.entityClass = :entity')
                    ->setParameter('entity', $entity);
            } else {

                $qb->andWhere('h.entityClass LIKE :entityLike')
                    ->setParameter('entityLike', '%\\\\' . $entity);

            }
        }


        if (!empty($filters['user'])) {
            $qb->andWhere('h.user = :user')
                ->setParameter('user', (int)$filters['user']);
        }

        if (!empty($filters['from'])) {
            // od 00:00:00
            $from = new \DateTimeImmutable($filters['from'] . ' 00:00:00');
            $qb->andWhere('h.occurredAt >= :from')
                ->setParameter('from', $from);
        }

        if (!empty($filters['to'])) {
            // do 23:59:59
            $to = new \DateTimeImmutable($filters['to'] . ' 23:59:59');
            $qb->andWhere('h.occurredAt <= :to')
                ->setParameter('to', $to);
        }
        if (!empty($filters['action'])) {
            $action = strtoupper(trim((string) $filters['action']));

            // porównanie bez względu na wielkość liter
            $qb->andWhere('UPPER(h.action) = :action')
                ->setParameter('action', $action);
        }


        return $qb->getQuery()->getResult();
    }

    public function findDistinctEntityClassesForPostepowanie(\App\Entity\Postepowanie $postepowanie): array
    {
        $rows = $this->createQueryBuilder('h')
            ->select('DISTINCT h.entityClass AS cls')
            ->andWhere('h.postepowanie = :p')
            ->setParameter('p', $postepowanie)
            ->orderBy('cls', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_values(array_map(static fn(array $r) => $r['cls'], $rows));
    }
    public function buildRelatedLabelsForLogs(array $logs): array
    {
        $ids = [];

        foreach ($logs as $log) {
            $changes = $log->getChanges();
            if (!is_array($changes)) {
                continue;
            }

            foreach ($changes as $pair) {
                if (!is_array($pair)) {
                    continue;
                }

                foreach (['old', 'new'] as $side) {
                    $v = $pair[$side] ?? null;

                    if (is_array($v)
                        && ($v['class'] ?? null) === \App\Entity\PostepowanieOsoba::class
                        && isset($v['id'])
                    ) {
                        $ids[] = (int) $v['id'];
                    }
                }
            }
        }

        $ids = array_values(array_unique($ids));
        if (!$ids) {
            return [];
        }

        // WAŻNE: pobieramy tylko encje PostepowanieOsoba jako obiekty
        $pos = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('po', 'o')
            ->from(\App\Entity\PostepowanieOsoba::class, 'po')
            ->leftJoin('po.osoba', 'o')
            ->andWhere('po.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        $map = [];

        foreach ($pos as $po) {

            if (is_array($po) && isset($po[0])) {
                $po = $po[0];
            }

            if (!$po instanceof \App\Entity\PostepowanieOsoba) {
                continue;
            }

            $osoba = $po->getOsoba();
            $name = $osoba ? (string) $osoba : 'Osoba';

            $map[\App\Entity\PostepowanieOsoba::class . '#' . $po->getId()]
                = $name . ' (ID: ' . $po->getId() . ')';
        }

        return $map;
    }




}
