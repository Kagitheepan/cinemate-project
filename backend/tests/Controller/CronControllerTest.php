<?php

namespace App\Tests\Controller;

use App\Controller\CronController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CronControllerTest extends WebTestCase
{
    public function testSendRemindersUnauthorized(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        
        // Pass a wrong token
        $client->request('GET', '/api/cron/reminders?token=wrong_token');
        
        $this->assertResponseStatusCodeSame(401);
    }

    public function testSendRemindersAuthorized(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        
        $token = $_ENV['CRON_SECRET'] ?? 'secret_par_defaut';
        
        // Pass the right token
        $client->request('GET', '/api/cron/reminders?token=' . $token);
        
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertTrue($data['success']);
    }
}
