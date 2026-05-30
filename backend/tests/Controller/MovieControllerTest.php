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
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class MovieControllerTest extends TestCase
{
    private string $cacheFile;

    protected function setUp(): void
    {
        $this->cacheFile = sys_get_temp_dir() . '/cinemate_movies_list_v2.json';
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

    public function testListGeneratesCacheWhenMissing(): void
    {
        $movie = (new Movie())->setTitle('NoCacheMovie')
            ->setTmdbId(100)
            ->setRating(9.0)
            ->setRuntime(120)
            ->setDirector('Test Director');

        $reflection = new \ReflectionClass(Movie::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($movie, 1);

        $genre = (new \App\Entity\Genre())->setGenreName('Horror');
        $movie->addGenre($genre);

        $repo = $this->createMock(MovieRepository::class);
        $repo->method('findBy')->willReturn([$movie]);

        $controller = new MovieController($this->createMock(EntityManagerInterface::class));
        $response = $controller->list($repo, new Request());

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertSame('NoCacheMovie', $data[0]['title']);
        self::assertSame('Horror', $data[0]['genres'][0]);
        
        // Assert the cache file was actually written
        self::assertFileExists($this->cacheFile);
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
            ->setRuntime(136);

        $genre1 = new \App\Entity\Genre();
        $genre1->setGenreName('Science Fiction');
        $movie->addGenre($genre1);

        $genre2 = new \App\Entity\Genre();
        $genre2->setGenreName('Action');
        $movie->addGenre($genre2);

        $platform = new \App\Entity\Platform();
        $platform->setPlatformName('Netflix');
        $movie->addPlatform($platform);

        $casting = new \App\Entity\Casting();
        $casting->setName('Keanu Reeves');
        $casting->setProfilePath('/keanu.jpg');
        $movieCasting = new \App\Entity\MovieCasting();
        $movieCasting->setCasting($casting);
        $movieCasting->setCharacterName('Neo');
        $movieCasting->setCastOrder(0);
        $movie->addMovieCasting($movieCasting);

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
            'cast' => [[
                'name' => 'Keanu Reeves',
                'profile_path' => '/keanu.jpg',
                'character' => 'Neo',
                'order' => 0
            ]],
            'availableOn' => ['Netflix'],
            'trailerKey' => null,
        ], $payload);
    }

    public function testShowReturns404IfNotFound(): void
    {
        $repo = $this->createMock(MovieRepository::class);
        $repo->method('find')->willReturn(null);
        $repo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $controller = $this->createControllerWithUser(null, $em);
        $response = $controller->show('999', $repo);

        self::assertSame(404, $response->getStatusCode());
    }

    public function testShowReturnsMovieById(): void
    {
        $movie = (new Movie())->setTitle('MById');
        $repo = $this->createMock(MovieRepository::class);
        $repo->method('find')->with(123)->willReturn($movie);

        $em = $this->createMock(EntityManagerInterface::class);
        $controller = $this->createControllerWithUser(null, $em);
        $response = $controller->show('123', $repo);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertSame('MById', $data['title']);
    }

    public function testShowReturnsMovieByTmdbId(): void
    {
        $movie = (new Movie())->setTitle('MByTmdbId');
        $repo = $this->createMock(MovieRepository::class);
        $repo->method('find')->willReturn(null);
        $repo->method('findOneBy')->with(['tmdbId' => 456])->willReturn($movie);

        $em = $this->createMock(EntityManagerInterface::class);
        $controller = $this->createControllerWithUser(null, $em);
        $response = $controller->show('456', $repo);

        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        self::assertSame('MByTmdbId', $data['title']);
    }

    private function createControllerWithUser(?\App\Entity\User $user, EntityManagerInterface $em): MovieController
    {
        $controller = new MovieController($em);
        $container = new Container();

        if ($user) {
            $token = $this->createMock(TokenInterface::class);
            $token->method('getUser')->willReturn($user);
            
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
            $tokenStorage->method('getToken')->willReturn($token);
            
            $container->set('security.token_storage', $tokenStorage);
        } else {
            $tokenStorage = $this->createMock(TokenStorageInterface::class);
            $tokenStorage->method('getToken')->willReturn(null);
            $container->set('security.token_storage', $tokenStorage);
        }

        $controller->setContainer($container);
        return $controller;
    }

    public function testGetRecommendationsRequiresAuth(): void
    {
        $controller = $this->createControllerWithUser(null, $this->createMock(EntityManagerInterface::class));
        $response = $controller->getRecommendations($this->createMock(EntityManagerInterface::class));
        self::assertSame(401, $response->getStatusCode());
    }

    public function testGetRecommendationsReturnsScoredMovies(): void
    {
        $user = new \App\Entity\User();
        
        $genreAction = (new \App\Entity\Genre())->setGenreName('Action');
        $user->addFavoriteGenre($genreAction);
        
        $platformNetflix = (new \App\Entity\Platform())->setPlatformName('Netflix');
        $user->addPlatform($platformNetflix);

        $movie1 = new Movie();
        $movie1->setTitle('M1')->setRating(10);
        $reflection = new \ReflectionClass(Movie::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($movie1, 101);
        $movie1->addGenre($genreAction);
        $movie1->addPlatform($platformNetflix);

        $movie2 = new Movie();
        $movie2->setTitle('M2');
        $property->setValue($movie2, 102);
        
        $watchlist = new \App\Entity\UserWatchlist();
        $watchlist->setMovie($movie2);
        $user->addWatchlistRelation($watchlist);

        $repo = $this->createMock(MovieRepository::class);
        $repo->method('findBy')->willReturn([$movie2]);
        $repo->method('findAll')->willReturn([$movie1, $movie2]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Movie::class)->willReturn($repo);

        $controller = $this->createControllerWithUser($user, $em);
        $response = $controller->getRecommendations($em);

        self::assertSame(200, $response->getStatusCode());
        
        $data = json_decode($response->getContent(), true);
        self::assertCount(1, $data); // Movie 2 is excluded, only Movie 1 returned
        self::assertSame('101', $data[0]['id']);
        self::assertSame('M1', $data[0]['title']);
        self::assertGreaterThanOrEqual(9.0, $data[0]['score']);
    }
}
