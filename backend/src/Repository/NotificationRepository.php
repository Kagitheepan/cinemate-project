<?php

namespace App\Repository;

use App\Entity\Notification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notification::class);
    }

    /**
     * Find unread notifications for a user.
     */
    public function findUnreadByUser(int $userId): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(50)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all notifications for a user (recent first).
     */
    public function findByUser(int $userId, int $limit = 20): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Count unread notifications for a user.
     */
    public function countUnreadByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :userId')
            ->andWhere('n.isRead = false')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Check if a reminder already exists for a specific event.
     */
    public function existsForEvent(int $userId, string $eventId): bool
    {
        $count = (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->andWhere('n.user = :userId')
            ->andWhere('n.eventId = :eventId')
            ->setParameter('userId', $userId)
            ->setParameter('eventId', $eventId)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }
}
