<?php

namespace App\Tests\Service;

use App\Service\TmdbService;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TmdbServiceTest extends TestCase
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

    public function testGetGenresTransformsArrayCorrectly(): void
    {
        $payload = [
            'genres' => [
                ['id' => 28, 'name' => 'Action'],
                ['id' => 35, 'name' => 'Comedy']
            ]
        ];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $genres = $service->getGenres();
        self::assertSame([
            28 => 'Action',
            35 => 'Comedy'
        ], $genres);
    }

    public function testGetPopularMoviesReturnsResultsArray(): void
    {
        $payload = [
            'results' => [
                ['id' => 1, 'title' => 'Movie 1'],
                ['id' => 2, 'title' => 'Movie 2']
            ]
        ];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $movies = $service->getPopularMovies(1);
        self::assertCount(2, $movies);
        self::assertSame(1, $movies[0]['id']);
    }

    public function testDiscoverStreamingMoviesReturnsResultsArray(): void
    {
        $payload = [
            'results' => [
                ['id' => 3, 'title' => 'Streaming Movie']
            ]
        ];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $movies = $service->discoverStreamingMovies(1);
        self::assertCount(1, $movies);
    }

    public function testGetMovieDetailsReturnsData(): void
    {
        $payload = ['id' => 500, 'title' => 'Detail Movie'];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $movie = $service->getMovieDetails(500);
        self::assertSame('Detail Movie', $movie['title']);
    }

    public function testGetMovieDetailsHandlesExceptions(): void
    {
        $client = $this->createMockClient(null, new \Exception('API Error'));
        $service = new TmdbService($client, 'dummy-key');

        $movie = $service->getMovieDetails(999);
        self::assertSame([], $movie);
    }

    public function testGetCreditsReturnsData(): void
    {
        $payload = [
            'cast' => [['name' => 'Actor 1']],
            'crew' => [['name' => 'Director 1']]
        ];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $credits = $service->getCredits(500);
        self::assertCount(1, $credits['cast']);
    }

    public function testGetCreditsHandlesExceptions(): void
    {
        $client = $this->createMockClient(null, new \Exception('API Error'));
        $service = new TmdbService($client, 'dummy-key');

        $credits = $service->getCredits(999);
        self::assertSame(['cast' => [], 'crew' => []], $credits);
    }

    public function testGetKeywordsReturnsData(): void
    {
        $payload = ['keywords' => [['id' => 10, 'name' => 'future']]];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $keywords = $service->getKeywords(500);
        self::assertSame([['id' => 10, 'name' => 'future']], $keywords);
    }

    public function testGetKeywordsHandlesExceptions(): void
    {
        $client = $this->createMockClient(null, new \Exception('API Error'));
        $service = new TmdbService($client, 'dummy-key');

        $keywords = $service->getKeywords(999);
        self::assertSame([], $keywords);
    }

    public function testGetWatchProvidersReturnsData(): void
    {
        $payload = ['results' => ['FR' => ['link' => 'https...']]];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $providers = $service->getWatchProviders(500);
        self::assertSame(['FR' => ['link' => 'https...']], $providers);
    }

    public function testGetWatchProvidersHandlesExceptions(): void
    {
        $client = $this->createMockClient(null, new \Exception('API Error'));
        $service = new TmdbService($client, 'dummy-key');

        $providers = $service->getWatchProviders(999);
        self::assertSame([], $providers);
    }

    public function testGetVideosReturnsData(): void
    {
        $payload = ['results' => [['key' => 'xyz']]];

        $client = $this->createMockClient($payload);
        $service = new TmdbService($client, 'dummy-key');

        $videos = $service->getVideos(500);
        self::assertSame([['key' => 'xyz']], $videos);
    }

    public function testGetVideosHandlesExceptions(): void
    {
        $client = $this->createMockClient(null, new \Exception('API Error'));
        $service = new TmdbService($client, 'dummy-key');

        $videos = $service->getVideos(999);
        self::assertSame([], $videos);
    }
}
