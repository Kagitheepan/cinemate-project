<?php

namespace App\Tests\Repository;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\TestCase;

class NotificationRepositoryTest extends TestCase
{
    private NotificationRepository $repository;

    protected function setUp(): void
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $em = $this->createMock(EntityManagerInterface::class);
        
        $em->method('getClassMetadata')->willReturn(new ClassMetadata(Notification::class));
        
        $qb = $this->createMock(\Doctrine\ORM\QueryBuilder::class);
        $qb->method('select')->willReturnSelf();
        $qb->method('from')->willReturnSelf();
        $qb->method('where')->willReturnSelf();
        $qb->method('andWhere')->willReturnSelf();
        $qb->method('setParameter')->willReturnSelf();
        $qb->method('setParameters')->willReturnSelf();
        $qb->method('orderBy')->willReturnSelf();
        $qb->method('setMaxResults')->willReturnSelf();
        
        $query = $this->createMock(\Doctrine\ORM\Query::class);
        $query->method('getResult')->willReturn(['mocked_result']);
        $query->method('getSingleScalarResult')->willReturn(42);
        
        $qb->method('getQuery')->willReturn($query);
        $em->method('createQueryBuilder')->willReturn($qb);

        $registry->method('getManagerForClass')->willReturn($em);

        $this->repository = new NotificationRepository($registry);
    }

    public function testFindUnreadByUser(): void
    {
        $result = $this->repository->findUnreadByUser(1);
        self::assertSame(['mocked_result'], $result);
    }

    public function testFindByUser(): void
    {
        $result = $this->repository->findByUser(1);
        self::assertSame(['mocked_result'], $result);
    }

    public function testCountUnreadByUser(): void
    {
        $result = $this->repository->countUnreadByUser(1);
        self::assertSame(42, $result);
    }

    public function testExistsForEvent(): void
    {
        $result = $this->repository->existsForEvent(1, 'event_id');
        self::assertTrue($result);
    }
}
