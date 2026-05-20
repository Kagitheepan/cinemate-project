<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class AuthController extends AbstractController
{
    #[Route('/api/logout', name: 'api_logout_cookie', methods: ['POST'])]
    public function logout(Request $request): JsonResponse
    {
        $response = $this->json(['success' => true]);
        $response->headers->clearCookie('BEARER', '/', null, $request->isSecure(), true, Cookie::SAMESITE_LAX);

        return $response;
    }
}
