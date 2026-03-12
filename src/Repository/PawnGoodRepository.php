<?php

namespace App\Repository;

use App\Entity\PawnGood;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PawnGood>
 */
class PawnGoodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PawnGood::class);
    }

    public function save(PawnGood $pawnGood, bool $flush = false): void
    {
        $this->getEntityManager()->persist($pawnGood);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PawnGood $pawnGood, bool $flush = false): void
    {
        $this->getEntityManager()->remove($pawnGood);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
