<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AuthControllerTest extends WebTestCase
{
    public function testLogoutClearsBearerCookie(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/logout');

        $this->assertResponseIsSuccessful();
        
        $response = $client->getResponse();
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);
        
        $cookie = $cookies[0];
        $this->assertSame('BEARER', $cookie->getName());
        $this->assertTrue($cookie->isCleared());
        $this->assertSame('/', $cookie->getPath());
    }
}
