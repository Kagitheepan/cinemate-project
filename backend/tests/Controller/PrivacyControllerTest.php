<?php

namespace App\Tests\Controller;

use App\Controller\PrivacyController;
use App\Document\CookieConsent;
use App\Entity\User;
use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class PrivacyControllerTest extends TestCase
{
    private function createController(?User $user): PrivacyController
    {
        $controller = new PrivacyController();
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

    public function testSaveConsentRejectsInvalidChoice(): void
    {
        $controller = $this->createController(null);
        $request = new Request([], [], [], [], [], [], json_encode(['choice' => 'maybe']));
        
        $response = $controller->saveConsent($request, $this->createMock(DocumentManager::class));
        
        self::assertSame(400, $response->getStatusCode());
    }

    public function testSaveConsentSavesDocumentAndSetsCookieWithUser(): void
    {
        $user = (new User())->setUsername('alice');
        $controller = $this->createController($user);
        
        $request = new Request([], [], [], [], [], [], json_encode(['choice' => 'accepted']));
        
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects(self::once())
           ->method('persist')
           ->with(self::callback(function (CookieConsent $consent): bool {
               return $consent->getChoice() === 'accepted' 
                   && $consent->getUsername() === 'alice'
                   && $consent->getPolicyVersion() === '2026-05-18';
           }));
        $dm->expects(self::once())->method('flush');
        
        $response = $controller->saveConsent($request, $dm);
        
        self::assertSame(200, $response->getStatusCode());
        
        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        self::assertSame('cinemate_consent_id', $cookies[0]->getName());
        self::assertTrue($cookies[0]->isHttpOnly());
    }
    
    public function testSaveConsentWorksWithoutUserAndReusesCookie(): void
    {
        $controller = $this->createController(null);
        
        $request = new Request([], [], [], ['cinemate_consent_id' => 'existing-id'], [], [], json_encode(['choice' => 'refused']));
        
        $dm = $this->createMock(DocumentManager::class);
        $dm->expects(self::once())
           ->method('persist')
           ->with(self::callback(function (CookieConsent $consent): bool {
               return $consent->getChoice() === 'refused' 
                   && $consent->getUsername() === null
                   && $consent->getConsentId() === 'existing-id';
           }));
        $dm->expects(self::once())->method('flush');
        
        $response = $controller->saveConsent($request, $dm);
        
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertSame('refused', $data['choice']);
    }
}
