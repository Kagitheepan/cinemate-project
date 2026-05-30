<?php

namespace App\Tests\Controller;

use App\Controller\AuthController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\Container;

class AuthControllerTest extends TestCase
{
    public function testLogoutClearsBearerCookie(): void
    {
        $controller = new AuthController();
        $controller->setContainer(new Container());

        $request = new Request();
        $response = $controller->logout($request);

        self::assertSame(200, $response->getStatusCode());
        
        $cookies = $response->headers->getCookies();
        self::assertCount(1, $cookies);
        
        $cookie = $cookies[0];
        self::assertSame('BEARER', $cookie->getName());
        self::assertTrue($cookie->isCleared());
        self::assertSame('/', $cookie->getPath());
    }
}
