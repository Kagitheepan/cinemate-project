<?php

namespace App\Tests\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CsrfProtectionSubscriberTest extends WebTestCase
{
    public function testGetRequestIsAllowed(): void
    {
        $client = static::createClient();
        
        // GET request to /api/profile (should be 401 Unauthorized since we are not logged in, but NOT 403 CSRF)
        $client->request('GET', '/api/profile');
        
        $this->assertResponseStatusCodeSame(401);
    }

    public function testPostRequestWithoutCsrfIsBlocked(): void
    {
        $client = static::createClient();
        
        // PUT to a protected endpoint
        $client->request('PUT', '/api/profile');
        
        $this->assertResponseStatusCodeSame(403);
        $this->assertStringContainsString('CSRF token invalide ou manquant', $client->getResponse()->getContent());
    }

    public function testPostRequestWithMismatchedCsrfIsBlocked(): void
    {
        $client = static::createClient();
        
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'token_A'));
        
        $client->request('PUT', '/api/profile', [], [], ['HTTP_X_CSRF_TOKEN' => 'token_B']);
        
        $this->assertResponseStatusCodeSame(403);
    }

    public function testPostRequestWithValidCsrfIsAllowedBySubscriber(): void
    {
        $client = static::createClient();
        
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'valid_token'));
        
        // It will pass CSRF check (priority 10), then fail at Security check (401) or routing
        $client->request('PUT', '/api/profile', [], [], ['HTTP_X_CSRF_TOKEN' => 'valid_token']);
        
        // As long as it is NOT 403 from the CSRF subscriber
        $this->assertNotSame(403, $client->getResponse()->getStatusCode());
    }
}
