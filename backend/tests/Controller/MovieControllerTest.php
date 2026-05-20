<?php

namespace App\Tests\Controller;

use App\Controller\MovieController;
use App\Entity\Movie;
use App\Repository\MovieRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use Symfony\Component\HttpFoundation\Request;

class MovieControllerTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/cinemate_movies_list.json';
        @unlink($this->cacheFile);
    }

    protected function tearDown(): void
    {
        @unlink($this->cacheFile);
    }

    public function testListReturnsCachedMoviesWithCacheHeaders(): void
    {
        $movies = [
            [
                'id' => '1',
                'title' => 'Matrix',
                'year' => '1999',
            ],
        ];
        file_put_contents($this->cacheFile, json_encode($movies));

        $controller = new MovieController($this->createMock(EntityManagerInterface::class));
        $response = $controller->list(
            $this->createMock(MovieRepository::class),
            new Request()
        );

        self::assertSame(200, $response->getStatusCode());
        self::assertSame($movies, json_decode($response->getContent(), true));
        self::assertTrue($response->headers->hasCacheControlDirective('public'));
        self::assertSame('60', $response->headers->getCacheControlDirective('max-age'));
        self::assertSame('300', $response->headers->getCacheControlDirective('stale-while-revalidate'));
        self::assertNotEmpty($response->headers->get('ETag'));
    }

    public function testListReturnsNotModifiedWhenEtagMatchesCache(): void
    {
        $jsonContent = json_encode([['id' => '1', 'title' => 'Matrix']]);
        file_put_contents($this->cacheFile, $jsonContent);

        $request = new Request();
        $request->headers->set('If-None-Match', '"' . md5($jsonContent) . '"');

        $controller = new MovieController($this->createMock(EntityManagerInterface::class));
        $response = $controller->list(
            $this->createMock(MovieRepository::class),
            $request
        );

        self::assertSame(304, $response->getStatusCode());
        self::assertSame('{}', $response->getContent());
        self::assertTrue($response->headers->hasCacheControlDirective('public'));
        self::assertSame('60', $response->headers->getCacheControlDirective('max-age'));
        self::assertSame('300', $response->headers->getCacheControlDirective('stale-while-revalidate'));
    }

    public function testSerializeMovieReturnsFullDetailPayload(): void
    {
        $movie = (new Movie())
            ->setTmdbId(603)
            ->setTitle('Matrix')
            ->setDescription('A hacker discovers the truth about his world.')
            ->setReleaseDate(new DateTimeImmutable('1999-03-31'))
            ->setPoster('/poster.jpg')
            ->setBackdrop('/backdrop.jpg')
            ->setDirector('Lana Wachowski')
            ->setRating(8.7)
            ->setGenres(['Science Fiction', 'Action'])
            ->setPlatforms(['Netflix'])
            ->setCast([['name' => 'Keanu Reeves', 'role' => 'Neo']])
            ->setRuntime(136);

        $controller = new MovieController($this->createMock(EntityManagerInterface::class));
        $method = new ReflectionMethod(MovieController::class, 'serializeMovie');
        $method->setAccessible(true);

        $payload = $method->invoke($controller, $movie, true);

        self::assertSame([
            'id' => '',
            'tmdbId' => 603,
            'title' => 'Matrix',
            'year' => '1999',
            'releaseDate' => '1999-03-31',
            'rating' => 8.7,
            'imageUrl' => 'https://image.tmdb.org/t/p/w500/poster.jpg',
            'genres' => ['Science Fiction', 'Action'],
            'category' => 'Science Fiction',
            'duration' => 136,
            'description' => 'A hacker discovers the truth about his world.',
            'backdropUrl' => 'https://image.tmdb.org/t/p/original/backdrop.jpg',
            'director' => 'Lana Wachowski',
            'cast' => [['name' => 'Keanu Reeves', 'role' => 'Neo']],
            'availableOn' => ['Netflix'],
            'trailerKey' => null,
        ], $payload);
    }
}
