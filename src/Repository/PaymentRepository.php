<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Payment>
 */
class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function save(Payment $payment, bool $flush = false): void
    {
        $this->getEntityManager()->persist($payment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Payment $payment, bool $flush = false): void
    {
        $this->getEntityManager()->remove($payment);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
