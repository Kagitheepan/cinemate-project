<?php

namespace App\Tests\Service;

use App\Service\StreamingAvailabilityService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class StreamingAvailabilityServiceTest extends TestCase
{
    private function createMockClient(?array $responseData = null, ?\Throwable $exception = null): HttpClientInterface
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

    public function testReturnsEmptyIfApiKeyIsMissing(): void
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects(self::never())->method('request'); 

        $service = new StreamingAvailabilityService($client, 'YOUR_API_KEY_HERE');
        self::assertSame([], $service->getStreamingByTmdbId(603));

        $serviceEmpty = new StreamingAvailabilityService($client, '');
        self::assertSame([], $serviceEmpty->getStreamingByTmdbId(603));
    }

    public function testPrioritizesFrenchPlatforms(): void
    {
        $payload = [
            'streamingOptions' => [
                'us' => [
                    ['service' => ['name' => 'Hulu'], 'type' => 'subscription']
                ],
                'fr' => [
                    ['service' => ['name' => 'Netflix'], 'type' => 'subscription'],
                    ['service' => ['name' => 'Canal+'], 'type' => 'addon']
                ]
            ]
        ];

        $client = $this->createMockClient($payload);
        $service = new StreamingAvailabilityService($client, 'valid_key');

        $result = $service->getStreamingByTmdbId(603);
        self::assertSame(['Netflix', 'Canal+'], $result);
    }

    public function testFallsBackToOtherCountriesInPriorityOrder(): void
    {
        $payload = [
            'streamingOptions' => [
                'de' => [
                    ['service' => ['name' => 'Wow'], 'type' => 'subscription']
                ],
                'us' => [
                    ['service' => ['name' => 'Hulu'], 'type' => 'subscription']
                ]
            ]
        ];

        $client = $this->createMockClient($payload);
        $service = new StreamingAvailabilityService($client, 'valid_key');

        $result = $service->getStreamingByTmdbId(603);
        self::assertSame(['Hulu'], $result);
    }
    
    public function testFallsBackToAnyCountryAsLastResort(): void
    {
        $payload = [
            'streamingOptions' => [
                'jp' => [
                    ['service' => ['name' => 'U-NEXT'], 'type' => 'subscription']
                ],
            ]
        ];

        $client = $this->createMockClient($payload);
        $service = new StreamingAvailabilityService($client, 'valid_key');

        $result = $service->getStreamingByTmdbId(603);
        self::assertSame(['U-NEXT'], $result);
    }

    public function testExtractsPayantVodIfNoSubscription(): void
    {
        $payload = [
            'streamingOptions' => [
                'fr' => [
                    ['service' => ['name' => 'Apple TV'], 'type' => 'buy'],
                    ['service' => ['name' => 'Google Play Movies'], 'type' => 'rent']
                ]
            ]
        ];

        $client = $this->createMockClient($payload);
        $service = new StreamingAvailabilityService($client, 'valid_key');

        $result = $service->getStreamingByTmdbId(603);
        self::assertSame(['VOD (payant)'], $result);
    }
    
    public function testReturnsEmptyIfNoStreamingOptionsArray(): void
    {
        $payload = ['someOtherKey' => 'value'];
        $client = $this->createMockClient($payload);
        $service = new StreamingAvailabilityService($client, 'valid_key');

        $result = $service->getStreamingByTmdbId(603);
        self::assertSame([], $result);
    }

    public function testHandlesExceptions(): void
    {
        $client = $this->createMockClient(null, new \Exception('Network error'));
        $service = new StreamingAvailabilityService($client, 'valid_key');

        $result = $service->getStreamingByTmdbId(603);
        self::assertSame([], $result);
    }
}
