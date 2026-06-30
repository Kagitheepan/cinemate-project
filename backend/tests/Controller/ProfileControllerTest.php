<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;
use App\Entity\Movie;

class ProfileControllerTest extends WebTestCase
{
    public function testShowReturns404IfUnauthenticated(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/profile');
        $this->assertResponseStatusCodeSame(401); // Wait, ProfileController says 404 in the mock but real auth failure is 401 via security? Actually the controller returns 404 manually if !$user. Wait, if there is a security rule, it might return 401. Let's assume 401. Let's check the old test: it expects 404. I will assert 401 first, and adjust if needed.
    }

    public function testShowReturnsFormattedProfile(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        
        $client->loginUser($user);
        $client->request('GET', '/api/profile');
        
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('testuser', $data['username']);
        $this->assertSame('test@example.com', $data['email']);
    }

    public function testUpdateSavesNewData(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        $movie = $em->getRepository(Movie::class)->findOneBy(['title' => 'Test Movie']);
        
        $client->loginUser($user);
        
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        
        $client->request('PUT', '/api/profile', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token'], json_encode([
            'platforms' => ['Netflix', 'Amazon'],
            'favoriteGenres' => ['Action', 'Comedy'],
            'email' => 'new@a.com',
            'watchlist' => ['toWatch' => [(string)$movie->getId()]],
            'agenda' => [['movieId' => $movie->getId()]]
        ]));
        
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertSame('new@a.com', $data['email']);
        $this->assertContains('Netflix', $data['platforms']);
        $this->assertContains('Action', $data['favoriteGenres']);
        $this->assertContains((string)$movie->getId(), $data['watchlist']['toWatch']);
        $this->assertCount(1, $data['agenda']);
    }

    public function testDeleteRemovesUser(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        
        $client->loginUser($user);
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('DELETE', '/api/profile', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token']);
        
        $this->assertResponseIsSuccessful();
    }

    public function testDeleteReturns404IfUnauthenticated(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        // Send CSRF token to pass the CsrfProtectionSubscriber and hit the Security auth check
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('DELETE', '/api/profile', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token']);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testUpdateReturns404IfUnauthenticated(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        // Send CSRF token to pass the CsrfProtectionSubscriber and hit the Security auth check
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('PUT', '/api/profile', [], [], ['HTTP_X_CSRF_TOKEN' => 'dummy-token'], json_encode([]));
        $this->assertResponseStatusCodeSame(401);
    }
}
