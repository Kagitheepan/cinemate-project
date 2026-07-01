<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CsrfCookieController extends AbstractController
{
    #[Route('/api/csrf-cookie', name: 'api_csrf_cookie', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $token = bin2hex(random_bytes(32));

        $response = new JsonResponse(['message' => 'CSRF cookie set', 'token' => $token]);
        $response->headers->setCookie(
            Cookie::create('CSRF-TOKEN')
                ->withValue($token)
                ->withPath('/')
                ->withSecure(true) // Requis par les navigateurs modernes pour SameSite=None
                ->withHttpOnly(false) // Le JS doit pouvoir lire ce cookie
                ->withSameSite(Cookie::SAMESITE_NONE)
        );

        return $response;
    }
}
