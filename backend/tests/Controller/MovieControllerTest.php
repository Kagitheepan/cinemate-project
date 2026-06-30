<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Entity\Movie;
use App\Entity\User;

class MovieControllerTest extends WebTestCase
{
    private function clearCache(): void
    {
        @unlink(sys_get_temp_dir() . '/cinemate_movies_list_v2.json');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        static::ensureKernelShutdown();
    }

    public function testListReturnsCachedMoviesWithCacheHeaders(): void
    {
        $this->clearCache();
        $cacheFile = sys_get_temp_dir() . '/cinemate_movies_list_v2.json';
        $movies = [['id' => '1', 'title' => 'Matrix', 'year' => '1999']];
        file_put_contents($cacheFile, json_encode($movies));

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/movies');

        $this->assertResponseIsSuccessful();
        $this->assertJsonStringEqualsJsonString(json_encode($movies), $client->getResponse()->getContent());
        $this->assertTrue($client->getResponse()->headers->hasCacheControlDirective('public'));
    }

    public function testListReturnsNotModifiedWhenEtagMatchesCache(): void
    {
        $this->clearCache();
        $cacheFile = sys_get_temp_dir() . '/cinemate_movies_list_v2.json';
        $jsonContent = json_encode([['id' => '1', 'title' => 'Matrix']]);
        file_put_contents($cacheFile, $jsonContent);

        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/movies', [], [], [
            'HTTP_If-None-Match' => '"' . md5($jsonContent) . '"'
        ]);

        $this->assertResponseStatusCodeSame(304);
        $this->assertEmpty($client->getResponse()->getContent());
    }

    public function testListGeneratesCacheWhenMissing(): void
    {
        $this->clearCache();
        $cacheFile = sys_get_temp_dir() . '/cinemate_movies_list_v2.json';
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/movies');

        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertGreaterThan(0, count($data));
        $this->assertSame('Test Movie', $data[0]['title']);
        
        $this->assertFileExists($cacheFile);
    }

    public function testShowReturns404IfNotFound(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/movies/99999');
        $this->assertResponseStatusCodeSame(404);
    }

    public function testShowReturnsMovieById(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $movie = $em->getRepository(Movie::class)->findOneBy(['title' => 'Test Movie']);
        
        $client->request('GET', '/api/movies/' . $movie->getId());
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Test Movie', $data['title']);
    }

    public function testShowReturnsMovieByTmdbId(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/movies/101');
        
        $this->assertResponseIsSuccessful();
        $data = json_decode($client->getResponse()->getContent(), true);
        $this->assertSame('Test Movie', $data['title']);
    }

    public function testGetRecommendationsRequiresAuth(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/api/movies/recommendations/for-you');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testGetRecommendationsReturnsScoredMovies(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $em = $client->getContainer()->get('doctrine')->getManager();
        $user = $em->getRepository(User::class)->findOneBy(['username' => 'testuser']);
        
        $client->loginUser($user);
        $client->request('GET', '/api/movies/recommendations/for-you');
        
        $this->assertResponseIsSuccessful();
    }
}
