<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Vérifie la protection CSRF sur les requêtes mutantes (POST, PUT, PATCH, DELETE).
 *
 * Mécanisme : Double-Submit Cookie
 * - Le cookie CSRF-TOKEN est défini via GET /api/csrf-cookie
 * - Le frontend lit ce cookie et le renvoie en header X-CSRF-TOKEN
 * - Ce subscriber vérifie que les deux valeurs correspondent
 */
class CsrfProtectionSubscriber implements EventSubscriberInterface
{
    /**
     * Routes exemptées de la vérification CSRF.
     * - login_check : géré par le firewall Symfony (json_login)
     * - register : l'utilisateur n'a pas encore de cookie CSRF
     * - csrf-cookie : c'est l'endpoint qui génère le cookie
     */
    private const EXCLUDED_ROUTES = [
        '/api/login_check',
        '/api/register',
        '/api/csrf-cookie',
        '/api/cron',
        '/api/logout',
    ];

    private const MUTATING_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 10],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $method = $request->getMethod();
        $path = $request->getPathInfo();

        // Ne vérifier que les requêtes mutantes sur /api/*
        if (!in_array($method, self::MUTATING_METHODS, true)) {
            return;
        }

        if (!str_starts_with($path, '/api')) {
            return;
        }

        // Exempter certaines routes
        foreach (self::EXCLUDED_ROUTES as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return;
            }
        }

        $cookieToken = $request->cookies->get('CSRF-TOKEN');
        $headerToken = $request->headers->get('X-CSRF-TOKEN');

        if (!$cookieToken || !$headerToken || !hash_equals($cookieToken, $headerToken)) {
            $event->setResponse(new JsonResponse(
                ['message' => 'CSRF token invalide ou manquant.'],
                403
            ));
        }
    }
}
