<?php

namespace App\Tests\Repository;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Doctrine\ORM\EntityManagerInterface;

class NotificationRepositoryTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private NotificationRepository $repository;
    private User $testUser;
    private User $otherUser;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->entityManager->getRepository(Notification::class);
        
        // We assume testuser and admin exist via AppFixtures
        $this->testUser = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        $this->otherUser = new User();
        $this->otherUser->setUsername('other_user_for_test_' . uniqid());
        $this->otherUser->setEmail('other_test@example.com');
        $this->otherUser->setPassword('test');
        $this->entityManager->persist($this->otherUser);

        // Clear existing notifications for testUser
        $existing = $this->repository->findByUser($this->testUser->getId());
        foreach ($existing as $n) {
            $this->entityManager->remove($n);
        }
        $this->entityManager->flush();
    }

    public function testFindUnreadByUser(): void
    {
        $notif1 = new Notification();
        $notif1->setUser($this->testUser)->setMessage('Unread 1')->setType('info')->setIsRead(false)->setCreatedAt(new \DateTime());
        $this->entityManager->persist($notif1);

        $notif2 = new Notification();
        $notif2->setUser($this->testUser)->setMessage('Read')->setType('info')->setIsRead(true)->setCreatedAt(new \DateTime());
        $this->entityManager->persist($notif2);

        $notif3 = new Notification();
        $notif3->setUser($this->otherUser)->setMessage('Unread Other')->setType('info')->setIsRead(false)->setCreatedAt(new \DateTime());
        $this->entityManager->persist($notif3);

        $this->entityManager->flush();

        $unread = $this->repository->findUnreadByUser($this->testUser->getId());
        $this->assertCount(1, $unread);
        $this->assertSame('Unread 1', $unread[0]->getMessage());
        
        $count = $this->repository->countUnreadByUser($this->testUser->getId());
        $this->assertSame(1, $count);
    }

    public function testExistsForEvent(): void
    {
        $notif = new Notification();
        $notif->setUser($this->testUser)->setMessage('Reminder')->setType('reminder')->setEventId('evt-123')->setIsRead(false)->setCreatedAt(new \DateTime());
        $this->entityManager->persist($notif);
        $this->entityManager->flush();

        $this->assertTrue($this->repository->existsForEvent($this->testUser->getId(), 'evt-123'));
        $this->assertFalse($this->repository->existsForEvent($this->testUser->getId(), 'evt-456'));
        $this->assertFalse($this->repository->existsForEvent($this->otherUser->getId(), 'evt-123'));
    }
}
