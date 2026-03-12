<?php

namespace App\Repository;

use App\Entity\PawnTicket;
use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PawnTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PawnTicket::class);
    }

    public function findByExternalId(int $externalId): ?PawnTicket
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    public function findByTicketNumber(string $ticketNumber): ?PawnTicket
    {
        return $this->findOneBy(['ticketNumber' => $ticketNumber]);
    }

    public function findOpenTicketsByClient(Client $client): array
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.client = :client')
            ->andWhere('pt.status IN (:statuses)')
            ->setParameter('client', $client)
            ->setParameter('statuses', [2, 3, 4])
            ->orderBy('pt.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOpen(): array
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.status IN (:statuses)')
            ->setParameter('statuses', [2, 3, 4])
            ->orderBy('pt.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllByClientOrdered(Client $client): array
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.client = :client')
            ->setParameter('client', $client)
            ->orderBy('pt.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByStatus(): array
    {
        return $this->createQueryBuilder('pt')
            ->addSelect('CASE WHEN pt.status IN (:openStatuses) THEN 0 ELSE 1 END AS HIDDEN sortOrder')
            ->setParameter('openStatuses', [2, 3, 4])
            ->orderBy('sortOrder', 'ASC')
            ->addOrderBy('pt.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function save(PawnTicket $pawnTicket, bool $flush = false): void
    {
        $this->getEntityManager()->persist($pawnTicket);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(PawnTicket $pawnTicket, bool $flush = false): void
    {
        $this->getEntityManager()->remove($pawnTicket);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
