<?php

namespace App\Tests\Controller;

use App\Controller\NotificationController;
use App\Entity\User;
use App\Entity\Notification;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class NotificationControllerTest extends TestCase
{
    private function createController(?User $user): NotificationController
    {
        $controller = new NotificationController();
        $container = new Container();

        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
            $tokenStorage->method('getToken')->willReturn($token);
            
            $container->set('security.token_storage', $tokenStorage);
        } else {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
            $tokenStorage->method('getToken')->willReturn(null);
            $container->set('security.token_storage', $tokenStorage);
        }

        $controller->setContainer($container);
        return $controller;
    }

    public function testListRequiresAuth(): void
    {
        $controller = $this->createController(null);
        $repo = $this->createMock(NotificationRepository::class);
        
        $response = $controller->list($repo);
        self::assertSame(401, $response->getStatusCode());
    }

    public function testListReturnsNotifications(): void
    {
        $user = (new User())->setUsername('alice');
        $reflection = new \ReflectionClass(User::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 42);

        $notif = (new Notification())
            ->setUser($user)
            ->setMessage('Hello')
            ->setType('info')
            ->setIsRead(false)
            ->setCreatedAt(new \DateTimeImmutable('2026-05-18T10:00:00+00:00'));

        $reflectionNotif = new \ReflectionClass(Notification::class);
        $notifIdProp = $reflectionNotif->getProperty('id');
        $notifIdProp->setAccessible(true);
        $notifIdProp->setValue($notif, 100);

        $repo = $this->createMock(NotificationRepository::class);
        $repo->method('findByUser')->with(42)->willReturn([$notif]);
        $repo->method('countUnreadByUser')->with(42)->willReturn(1);

        $controller = $this->createController($user);
        $response = $controller->list($repo);

        self::assertSame(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        self::assertSame(1, $data['unreadCount']);
        self::assertCount(1, $data['notifications']);
        self::assertSame('Hello', $data['notifications'][0]['message']);
        self::assertSame(100, $data['notifications'][0]['id']);
        self::assertSame('2026-05-18T10:00:00+00:00', $data['notifications'][0]['createdAt']);
    }

    public function testMarkAsReadRequiresAuth(): void
    {
        $controller = $this->createController(null);
        $response = $controller->markAsRead(100, $this->createMock(NotificationRepository::class), $this->createMock(EntityManagerInterface::class));
        self::assertSame(401, $response->getStatusCode());
    }

    public function testMarkAsReadReturns404IfNotFoundOrWrongUser(): void
    {
        $user = new User();
        $repo = $this->createMock(NotificationRepository::class);
        $repo->method('find')->willReturn(null);

        $controller = $this->createController($user);
        $response = $controller->markAsRead(100, $repo, $this->createMock(EntityManagerInterface::class));
        
        self::assertSame(404, $response->getStatusCode());
    }

    public function testMarkAsReadUpdatesStatusAndFlushes(): void
    {
        $user = new User();
        $reflection = new \ReflectionClass(User::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 42);

        $notif = (new Notification())->setUser($user)->setIsRead(false);

        $repo = $this->createMock(NotificationRepository::class);
        $repo->method('find')->with(100)->willReturn($notif);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $controller = $this->createController($user);
        $response = $controller->markAsRead(100, $repo, $em);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($notif->isRead());
    }

    public function testMarkAllAsReadRequiresAuth(): void
    {
        $controller = $this->createController(null);
        $response = $controller->markAllAsRead($this->createMock(NotificationRepository::class), $this->createMock(EntityManagerInterface::class));
        self::assertSame(401, $response->getStatusCode());
    }

    public function testMarkAllAsReadUpdatesStatusAndFlushes(): void
    {
        $user = new User();
        $reflection = new \ReflectionClass(User::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 42);

        $notif1 = (new Notification())->setUser($user)->setIsRead(false);
        $notif2 = (new Notification())->setUser($user)->setIsRead(false);

        $repo = $this->createMock(NotificationRepository::class);
        $repo->method('findUnreadByUser')->with(42)->willReturn([$notif1, $notif2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('flush');

        $controller = $this->createController($user);
        $response = $controller->markAllAsRead($repo, $em);

        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($notif1->isRead());
        self::assertTrue($notif2->isRead());
        
        $data = json_decode($response->getContent(), true);
        self::assertSame(2, $data['marked']);
    }
}
