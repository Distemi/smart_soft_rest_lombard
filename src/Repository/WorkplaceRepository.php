<?php

namespace App\Repository;

use App\Entity\PawnTicket;
use App\Entity\Workplace;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Workplace>
 */
class WorkplaceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Workplace::class);
    }

    public function save(Workplace $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Workplace $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByExternalId(int $externalId): ?Workplace
    {
        return $this->findOneBy(['externalId' => $externalId]);
    }

    public function findAllActive(): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('w.title', 'ASC')
            ->addOrderBy('w.city', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function getUniqueClientsCount(int $workplaceId): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(DISTINCT pt.client)')
            ->from(PawnTicket::class, 'pt')
            ->where('pt.workplace = :workplaceId')
            ->andWhere('pt.client IS NOT NULL')
            ->setParameter('workplaceId', $workplaceId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getUniqueClientsCountByWorkplace(): array
    {
        $results = $this->getEntityManager()->createQueryBuilder()
            ->select('IDENTITY(pt.workplace) as workplace_id, COUNT(DISTINCT pt.client) as clients_count')
            ->from(PawnTicket::class, 'pt')
            ->where('pt.workplace IS NOT NULL')
            ->andWhere('pt.client IS NOT NULL')
            ->groupBy('pt.workplace')
            ->getQuery()
            ->getResult();

        $stats = [];
        foreach ($results as $row) {
            $stats[$row['workplace_id']] = (int) $row['clients_count'];
        }

        return $stats;
    }
}
