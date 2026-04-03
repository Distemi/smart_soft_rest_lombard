<?php

namespace App\Repository;

use App\Entity\Client;
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

    public function findById(int $id): ?Workplace
    {
        return $this->find($id);
    }

    public function findByIdsNotIn(array $ids): array
    {
        $qb = $this->createQueryBuilder('w');

        if ($ids !== []) {
            $qb->where('w.id NOT IN (:ids)')
                ->setParameter('ids', $ids);
        }

        return $qb->getQuery()->getResult();
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

    public function findUniqueClientsPage(int $workplaceId, int $page, int $limit): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('c')
            ->addSelect('(SELECT SUM(CASE WHEN pt_sub.status IN (:openStatuses) THEN 1 ELSE 0 END) FROM App\Entity\PawnTicket pt_sub WHERE pt_sub.client = c.id AND pt_sub.workplace = :workplaceId) AS HIDDEN openTicketsCount')
            ->from(Client::class, 'c')
            ->where('EXISTS (SELECT 1 FROM App\Entity\PawnTicket pt WHERE pt.client = c.id AND pt.workplace = :workplaceId)')
            ->setParameter('workplaceId', $workplaceId)
            ->setParameter('openStatuses', PawnTicket::OPEN_STATUSES)
            ->orderBy('openTicketsCount', 'DESC')
            ->addOrderBy('c.surname', 'ASC')
            ->addOrderBy('c.name', 'ASC')
            ->addOrderBy('c.patronymic', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getPawnTicketsCount(int $workplaceId): int
    {
        return (int) $this->getEntityManager()->createQueryBuilder()
            ->select('COUNT(pt.ticketNumber)')
            ->from(PawnTicket::class, 'pt')
            ->where('pt.workplace = :workplaceId')
            ->setParameter('workplaceId', $workplaceId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findPawnTicketsPage(int $workplaceId, int $page, int $limit): array
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('pt')
            ->from(PawnTicket::class, 'pt')
            ->where('pt.workplace = :workplaceId')
            ->setParameter('workplaceId', $workplaceId)
            ->orderBy('pt.issueDate', 'DESC')
            ->addOrderBy('pt.ticketNumber', 'ASC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
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
