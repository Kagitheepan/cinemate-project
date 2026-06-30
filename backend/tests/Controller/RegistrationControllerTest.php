<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    public function testRegisterRejectsMissingFields(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        
        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'alice',
            'email' => 'alice@example.com',
            // Missing password
        ]));

        $this->assertResponseStatusCodeSame(400);
        $this->assertSame(['message' => 'Missing fields'], json_decode($client->getResponse()->getContent(), true));
    }

    public function testRegisterRejectsAlreadyUsedEmail(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'alice_new',
            'email' => 'test@example.com', // Already exists in fixtures
            'password' => 'password123',
            'platforms' => [],
            'favoriteGenres' => [],
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Erreurs de validation.', $data['message']);
        $this->assertArrayHasKey('email', $data['errors']);
    }

    public function testRegisterRejectsAlreadyUsedUsername(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'testuser', // Already exists in fixtures
            'email' => 'newemail@example.com',
            'password' => 'password123',
            'platforms' => [],
            'favoriteGenres' => [],
        ]));

        $this->assertResponseStatusCodeSame(422);
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Erreurs de validation.', $data['message']);
        $this->assertArrayHasKey('username', $data['errors']);
    }

    public function testRegisterCreatesUserWhenPayloadIsValid(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->request('POST', '/api/register', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode([
            'username' => 'alice',
            'email' => 'alice@example.com',
            'password' => 'plain-password',
            'platforms' => ['Netflix'],
            'favoriteGenres' => ['Action'],
        ]));

        $this->assertResponseStatusCodeSame(201);
        $data = json_decode($client->getResponse()->getContent(), true);
        
        $this->assertSame('User created successfully', $data['message']);
        $this->assertSame('alice', $data['user']['username']);
        $this->assertSame('alice@example.com', $data['user']['email']);
        $this->assertContains('Netflix', $data['user']['platforms']);
    }
}
