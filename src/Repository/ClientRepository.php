<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findByExternalId(int $externalId): ?Client
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    public function findIndexedByExternalIds(array $externalIds): array
    {
        $normalizedIds = array_values(array_unique(array_map(
            static fn (mixed $id): int => (int) $id,
            $externalIds
        )));

        if ($normalizedIds === []) {
            return [];
        }

        $clients = $this->createQueryBuilder('c')
            ->where('c.externalId IN (:externalIds)')
            ->setParameter('externalIds', $normalizedIds)
            ->getQuery()
            ->getResult();

        $indexed = [];
        foreach ($clients as $client) {
            $indexed[$client->getExternalId()] = $client;
        }

        return $indexed;
    }

    public function findByFullName(string $surname, string $name, ?string $patronymic = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.surname = :surname')
            ->andWhere('c.name = :name')
            ->setParameter('surname', $surname)
            ->setParameter('name', $name);

        if ($patronymic) {
            $qb->andWhere('c.patronymic = :patronymic')
               ->setParameter('patronymic', $patronymic);
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneByFullNameAndTicketNumber(
        string $surname,
        string $name,
        ?string $patronymic,
        string $ticketNumber
    ): ?Client {
        $qb = $this->createQueryBuilder('c')
            ->innerJoin('c.pawnTickets', 'pt')
            ->where('c.surname = :surname')
            ->andWhere('c.name = :name')
            ->andWhere('pt.ticketNumber = :ticketNumber')
            ->setParameter('surname', $surname)
            ->setParameter('name', $name)
            ->setParameter('ticketNumber', $ticketNumber)
            ->setMaxResults(1);

        if ($patronymic !== null) {
            $qb->andWhere('c.patronymic = :patronymic')
                ->setParameter('patronymic', $patronymic);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function save(Client $client, bool $flush = false): void
    {
        $this->getEntityManager()->persist($client);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Client $client, bool $flush = false): void
    {
        $this->getEntityManager()->remove($client);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
