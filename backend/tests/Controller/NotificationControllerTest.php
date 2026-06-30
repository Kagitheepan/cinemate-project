<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Notification;

class NotificationControllerTest extends WebTestCase
{
    public function testListRequiresAuth(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/notifications');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testListReturnsNotifications(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);

        $notif = new Notification();
        $notif->setUser($user);
        $notif->setMessage('Hello');
        $notif->setType('info');
        $notif->setIsRead(false);
        $notif->setCreatedAt(new \DateTime('2026-05-18T10:00:00+00:00'));
        $em->persist($notif);
        $em->flush();

        $client->loginUser($user);
        $client->request('GET', '/api/notifications');

        $this->assertResponseIsSuccessful();
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(1, $data['unreadCount']);
        $this->assertCount(1, $data['notifications']);
        $this->assertSame('Hello', $data['notifications'][0]['message']);
    }

    public function testMarkAsReadRequiresAuth(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('PATCH', '/api/notifications/read/100', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token']);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMarkAsReadReturns404IfNotFoundOrWrongUser(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        
        $client->loginUser($user);
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('PATCH', '/api/notifications/read/99999', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token']);
        
        $this->assertResponseStatusCodeSame(404);
    }

    public function testMarkAsReadUpdatesStatusAndFlushes(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);

        $notif = new Notification();
        $notif->setUser($user);
        $notif->setMessage('Read me');
        $notif->setType('info');
        $notif->setIsRead(false);
        $notif->setCreatedAt(new \DateTime('2026-05-18T10:00:00+00:00'));
        $em->persist($notif);
        $em->flush();

        $client->loginUser($user);
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('PATCH', '/api/notifications/read/' . $notif->getId(), [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token']);

        $this->assertResponseIsSuccessful();
        
        $em->clear();
        $updatedNotif = $em->getRepository(Notification::class)->find($notif->getId());
        $this->assertTrue($updatedNotif->isRead());
    }

    public function testMarkAllAsReadRequiresAuth(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('PATCH', '/api/notifications/read-all', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token']);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testMarkAllAsReadUpdatesStatusAndFlushes(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);

        $notif1 = new Notification();
        $notif1->setUser($user)->setMessage('A')->setType('info')->setIsRead(false)->setCreatedAt(new \DateTime());
        $em->persist($notif1);

        $notif2 = new Notification();
        $notif2->setUser($user)->setMessage('B')->setType('info')->setIsRead(false)->setCreatedAt(new \DateTime());
        $em->persist($notif2);
        
        $em->flush();

        $client->loginUser($user);
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('PATCH', '/api/notifications/read-all', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token']);

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame(2, $data['marked']);
        
        $em->clear();
        $updatedNotif1 = $em->getRepository(Notification::class)->find($notif1->getId());
        $updatedNotif2 = $em->getRepository(Notification::class)->find($notif2->getId());
        
        $this->assertTrue($updatedNotif1->isRead());
        $this->assertTrue($updatedNotif2->isRead());
    }
}
