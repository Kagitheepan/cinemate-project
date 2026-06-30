<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\User;

class PrivacyControllerTest extends WebTestCase
{
    public function testSaveConsentRejectsInvalidChoice(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('POST', '/api/privacy/consent', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => 'dummy-token'], json_encode([
            'choice' => 'maybe'
        ]));
        
        $this->assertResponseStatusCodeSame(400);
    }

    public function testSaveConsentSavesDocumentAndSetsCookieWithUser(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        
        $client->loginUser($user);
        
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        $client->request('POST', '/api/privacy/consent', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => 'dummy-token'], json_encode([
            'choice' => 'accepted'
        ]));
        
        $this->assertResponseIsSuccessful();
        $response = $client->getResponse();
        
        $cookies = $response->headers->getCookies();
        // The controller sets `cinemate_consent_id`
        $consentCookie = null;
        foreach ($cookies as $cookie) {
            if ($cookie->getName() === 'cinemate_consent_id') {
                $consentCookie = $cookie;
                break;
            }
        }
        $this->assertNotNull($consentCookie);
        $this->assertTrue($consentCookie->isHttpOnly());
    }
    
    public function testSaveConsentWorksWithoutUserAndReusesCookie(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        
        // Pass existing cookie
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('cinemate_consent_id', 'existing-id'));
        $client->getCookieJar()->set(new \Symfony\Component\BrowserKit\Cookie('CSRF-TOKEN', 'dummy-token'));
        
        $client->request('POST', '/api/privacy/consent', [], [], ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => 'dummy-token'], json_encode([
            'choice' => 'refused'
        ]));
        
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('refused', $data['choice']);
    }
}
