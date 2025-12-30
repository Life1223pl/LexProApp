<?php

namespace App\Repository;

use App\Entity\CzynnoscUczestnik;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CzynnoscUczestnikRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CzynnoscUczestnik::class);
    }
}
