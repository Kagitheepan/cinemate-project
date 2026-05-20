<?php

namespace App\EventSubscriber;

use App\Document\ConnectionLog;
use Doctrine\ODM\MongoDB\DocumentManager;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Lexik\Bundle\JWTAuthenticationBundle\Events;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LoginSubscriber implements EventSubscriberInterface
{
    private DocumentManager $dm;
    private RequestStack $requestStack;

    public function __construct(DocumentManager $dm, RequestStack $requestStack)
    {
        $this->dm = $dm;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            Events::AUTHENTICATION_SUCCESS => 'onAuthenticationSuccess',
        ];
    }

    public function onAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        // Vérification du consentement RGPD (Cookies analytiques / Tracking)
        $request = $this->requestStack->getCurrentRequest();
        $hasConsent = $request && $request->headers->get('x-consent-tracking') === 'true';

        // Ne pas logger la connexion si l'utilisateur n'a pas explicitement consenti
        if (!$hasConsent) {
            return;
        }

        $log = new ConnectionLog();
        
        if (method_exists($user, 'getUserIdentifier')) {
            $log->setUsername($user->getUserIdentifier());
        } elseif (method_exists($user, 'getUsername')) {
            $log->setUsername($user->getUsername());
        }

        $log->setConnectedAt(new \DateTime());

        $this->dm->persist($log);
        $this->dm->flush();
    }
}
