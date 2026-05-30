<?php

namespace App\Tests\Service;

use App\Service\OmdbService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class OmdbServiceTest extends TestCase
{
    private function createMockClient(array $responseData = null, \Throwable $exception = null): HttpClientInterface
    {
        $response = $this->createMock(ResponseInterface::class);

        if ($exception) {
            $response->method('toArray')->willThrowException($exception);
        } else {
            $response->method('toArray')->willReturn($responseData ?? []);
        }

        $client = $this->createMock(HttpClientInterface::class);
        
        if ($exception && !$responseData) {
             $client->method('request')->willThrowException($exception);
        } else {
             $client->method('request')->willReturn($response);
        }
       
        return $client;
    }

    public function testGetRuntimeByTitleReturnsInteger(): void
    {
        $client = $this->createMockClient(['Runtime' => '148 min']);
        $service = new OmdbService($client, 'dummy-key');

        $runtime = $service->getRuntimeByTitle('Inception', '2010');
        self::assertSame(148, $runtime);
    }

    public function testGetRuntimeByTitleReturnsNullIfNA(): void
    {
        $client = $this->createMockClient(['Runtime' => 'N/A']);
        $service = new OmdbService($client, 'dummy-key');

        $runtime = $service->getRuntimeByTitle('Unknown Movie');
        self::assertNull($runtime);
    }
    
    public function testGetRuntimeByTitleReturnsNullIfMissing(): void
    {
        $client = $this->createMockClient(['Title' => 'Unknown Movie']);
        $service = new OmdbService($client, 'dummy-key');

        $runtime = $service->getRuntimeByTitle('Unknown Movie');
        self::assertNull($runtime);
    }

    public function testGetRuntimeByTitleHandlesExceptions(): void
    {
        $client = $this->createMockClient(null, new \Exception('Network error'));
        $service = new OmdbService($client, 'dummy-key');

        $runtime = $service->getRuntimeByTitle('Matrix');
        self::assertNull($runtime);
    }
}
