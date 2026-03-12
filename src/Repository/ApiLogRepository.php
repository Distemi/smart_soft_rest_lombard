<?php

namespace App\Repository;

use App\Entity\ApiLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiLog>
 */
class ApiLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiLog::class);
    }

    public function save(ApiLog $apiLog, bool $flush = false): void
    {
        $this->getEntityManager()->persist($apiLog);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ApiLog $apiLog, bool $flush = false): void
    {
        $this->getEntityManager()->remove($apiLog);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        return $this->createQueryBuilder('al')
            ->delete()
            ->where('al.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute();
    }

    /**
     * @return array<ApiLog>
     */
    public function findPaginatedOrderedByCreatedAt(int $page, int $perPage): array
    {
        return $this->createQueryBuilder('al')
            ->orderBy('al.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $perPage)
            ->setMaxResults($perPage)
            ->getQuery()
            ->getResult();
    }

    public function getTotalCount(): int
    {
        return (int) $this->createQueryBuilder('al')
            ->select('COUNT(al.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
