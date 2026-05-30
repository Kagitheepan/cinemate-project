<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\LoginSubscriber;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

class LoginSubscriberTest extends TestCase
{
    public function testGetSubscribedEvents(): void
    {
        $subscribedEvents = LoginSubscriber::getSubscribedEvents();
        self::assertArrayHasKey(Events::AUTHENTICATION_SUCCESS, $subscribedEvents);
    }

    public function testOnAuthenticationSuccessDoesNothingIfNoConsent(): void
    {
        $dm = $this->createMock(DocumentManager::class);
        $requestStack = $this->createMock(RequestStack::class);
        
        $request = new Request();
        // no consent header
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $dm->expects(self::never())->method('persist');
        
        $subscriber = new LoginSubscriber($dm, $requestStack);

        $user = $this->createMock(UserInterface::class);
        $event = new AuthenticationSuccessEvent([], $user, $this->createMock(\Symfony\Component\HttpFoundation\Response::class));

        $subscriber->onAuthenticationSuccess($event);
    }

    public function testOnAuthenticationSuccessLogsConnectionIfConsentGiven(): void
    {
        $dm = $this->createMock(DocumentManager::class);
        $requestStack = $this->createMock(RequestStack::class);
        
        $request = new Request();
        $request->headers->set('x-consent-tracking', 'true');
        $requestStack->method('getCurrentRequest')->willReturn($request);

        $dm->expects(self::once())->method('persist');
        $dm->expects(self::once())->method('flush');

        $subscriber = new LoginSubscriber($dm, $requestStack);

        $user = $this->createMock(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn('testuser');

        $event = new AuthenticationSuccessEvent([], $user, $this->createMock(\Symfony\Component\HttpFoundation\Response::class));

        $subscriber->onAuthenticationSuccess($event);
    }
}
