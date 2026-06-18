<?php

namespace App\Tests\Controller;

use App\Controller\CronController;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;

class CronControllerTest extends TestCase
{
    public function testSendRemindersUnauthorized(): void
    {
        $controller = new CronController();
        $container = new \Symfony\Component\DependencyInjection\Container();
        $controller->setContainer($container);
        
        $request = new Request(['token' => 'wrong_token']);
        $kernel = $this->createMock(KernelInterface::class);
        
        $response = $controller->sendReminders($request, $kernel);
        
        self::assertSame(401, $response->getStatusCode());
    }
}
