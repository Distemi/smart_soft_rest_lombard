<?php

namespace App\Repository;

use App\Entity\PawnTicket;
use App\Entity\Client;
use App\Entity\Workplace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PawnTicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PawnTicket::class);
    }

    public function findByTicketNumber(string $ticketNumber): ?PawnTicket
    {
        return $this->findOneBy(['ticketNumber' => $ticketNumber]);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('pt')
            ->select('COUNT(pt.ticketNumber)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOpen(): int
    {
        return (int) $this->createQueryBuilder('pt')
            ->select('COUNT(pt.ticketNumber)')
            ->where('pt.status IN (:statuses)')
            ->setParameter('statuses', PawnTicket::OPEN_STATUSES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findClientByTicketAndName(
        string $ticketNumber,
        string $surname,
        string $name,
        string $patronymic = ''
    ): ?Client {
        $qb = $this->createQueryBuilder('pt')
            ->innerJoin('pt.client', 'c')
            ->where('pt.ticketNumber = :ticketNumber')
            ->andWhere('LOWER(c.surname) = LOWER(:surname)')
            ->andWhere('LOWER(c.name) = LOWER(:name)')
            ->setParameter('ticketNumber', $ticketNumber)
            ->setParameter('surname', $surname)
            ->setParameter('name', $name)
            ->select('c')
            ->setMaxResults(1);

        if ($patronymic !== '') {
            $qb->andWhere('LOWER(COALESCE(c.patronymic, \'\')) = LOWER(:patronymic)')
                ->setParameter('patronymic', $patronymic);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findByExternalId(int $externalId): ?PawnTicket
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    public function findOneByWorkplaceAndTicketNumber(Workplace $workplace, string $ticketNumber): ?PawnTicket
    {
        return $this->findOneBy([
            'workplace' => $workplace,
            'ticketNumber' => $ticketNumber,
        ]);
    }

    public function findOpenTicketsByClient(Client $client): array
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.client = :client')
            ->andWhere('pt.status IN (:statuses)')
            ->setParameter('client', $client)
            ->setParameter('statuses', PawnTicket::OPEN_STATUSES)
            ->orderBy('pt.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOpen(): array
    {
        return $this->createQueryBuilder('pt')
            ->where('pt.status IN (:statuses)')
            ->setParameter('statuses', PawnTicket::OPEN_STATUSES)
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
            ->setParameter('openStatuses', PawnTicket::OPEN_STATUSES)
            ->orderBy('sortOrder', 'ASC')
            ->addOrderBy('pt.issueDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByWorkplaceMissingTicketNumbers(Workplace $workplace, array $ticketNumbers): array
    {
        $qb = $this->createQueryBuilder('pt')
            ->where('pt.workplace = :workplace')
            ->setParameter('workplace', $workplace);

        if ($ticketNumbers !== []) {
            $qb->andWhere('pt.ticketNumber NOT IN (:ticketNumbers)')
                ->setParameter('ticketNumbers', $ticketNumbers);
        }

        return $qb->getQuery()->getResult();
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
