<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CsrfCookieControllerTest extends WebTestCase
{
    public function testGetCsrfCookie(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        
        $client->request('GET', '/api/csrf-cookie');
        
        $this->assertResponseIsSuccessful();
        
        $cookies = $client->getResponse()->headers->getCookies();
        $csrfCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'CSRF-TOKEN') {
                $csrfCookie = $cookie;
                break;
            }
        }
        
        $this->assertNotNull($csrfCookie);
        $this->assertFalse($csrfCookie->isHttpOnly());
        $this->assertSame('none', $csrfCookie->getSameSite());
        
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('CSRF cookie set', $data['message']);
    }
}
