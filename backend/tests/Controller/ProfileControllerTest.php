<?php

namespace App\Tests\Controller;

use App\Controller\ProfileController;
use App\Entity\User;
use App\Entity\Platform;
use App\Entity\Genre;
use App\Entity\Movie;
use App\Entity\UserWatchlist;
use App\Entity\UserAgenda;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProfileControllerTest extends TestCase
{
    private function createController(?User $user): ProfileController
    {
        $controller = new ProfileController();
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

    public function testShowReturns404IfUnauthenticated(): void
    {
        $controller = $this->createController(null);
        $response = $controller->show();
        self::assertSame(404, $response->getStatusCode());
    }

    public function testShowReturnsFormattedProfile(): void
    {
        $user = (new User())->setUsername('alice')->setEmail('a@a.com');
        
        $platform = (new Platform())->setPlatformName('Netflix');
        $user->addPlatform($platform);
        
        $genre = (new Genre())->setGenreName('Action');
        $user->addFavoriteGenre($genre);
        
        $movie = (new Movie())->setTitle('Inception');
        $reflection = new \ReflectionClass(Movie::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($movie, 123);
        
        $watchlist = (new UserWatchlist())->setMovie($movie)->setStatut('a_voir');
        $user->addWatchlistRelation($watchlist);
        
        $agenda = (new UserAgenda())->setMovie($movie)->setEventDate(new \DateTime('2026-05-18'))->setTimeSlot('Evening');
        $user->addAgenda($agenda);
        
        $controller = $this->createController($user);
        $response = $controller->show();
        
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        
        self::assertSame('alice', $data['username']);
        self::assertSame(['Netflix'], $data['platforms']);
        self::assertSame(['Action'], $data['favoriteGenres']);
        self::assertSame(['123'], $data['watchlist']['toWatch']);
        self::assertCount(1, $data['agenda']);
        self::assertSame('Inception', $data['agenda'][0]['title']);
    }

    public function testUpdateSavesNewData(): void
    {
        $user = new User();
        $user->setUsername('alice')->setEmail('a@a.com');
        
        $oldPlatform = (new Platform())->setPlatformName('OldPlatform');
        $user->addPlatform($oldPlatform);
        
        $oldGenre = (new Genre())->setGenreName('OldGenre');
        $user->addFavoriteGenre($oldGenre);
        
        $movie = new Movie();
        $movie->setTitle('M');
        $reflection = new \ReflectionClass(Movie::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($movie, 123);
        
        $oldWatchlist = (new UserWatchlist())->setMovie($movie)->setStatut('vu');
        $user->addWatchlistRelation($oldWatchlist);
        
        $oldAgenda = (new UserAgenda())->setMovie($movie)->setEventDate(new \DateTime('2026-05-18'))->setTimeSlot('Evening');
        $user->addAgenda($oldAgenda);
        
        $controller = $this->createController($user);
        
        $request = new Request([], [], [], [], [], [], json_encode([
            'platforms' => ['Netflix', 'Amazon'],
            'favoriteGenres' => ['Action', 'Comedy'],
            'email' => 'new@a.com',
            'watchlist' => ['toWatch' => ['123']], // also cover toWatch
            'agenda' => [['movieId' => 123]] // no date, no timeSlot
        ]));
        
        $em = $this->createMock(EntityManagerInterface::class);
        
        $platformRepo = $this->createMock(EntityRepository::class);
        $platformRepo->method('findOneBy')->willReturnCallback(function($crit) {
            if ($crit['platformName'] === 'Netflix') return (new Platform())->setPlatformName('Netflix');
            return null;
        });
        
        $genreRepo = $this->createMock(EntityRepository::class);
        $genreRepo->method('findOneBy')->willReturnCallback(function($crit) {
            if ($crit['genreName'] === 'Action') return (new Genre())->setGenreName('Action');
            return null;
        });
        
        $movieRepo = $this->createMock(EntityRepository::class);
        $movieRepo->method('find')->willReturn($movie);
        
        $em->method('getRepository')->willReturnCallback(function($class) use ($platformRepo, $genreRepo, $movieRepo) {
            if ($class === Platform::class) return $platformRepo;
            if ($class === Genre::class) return $genreRepo;
            if ($class === Movie::class) return $movieRepo;
            return $this->createMock(EntityRepository::class);
        });
        
        $em->expects(self::any())->method('remove');
        $em->expects(self::atLeastOnce())->method('persist'); 
        $em->expects(self::once())->method('flush');
        
        $response = $controller->update($request, $em);
        
        self::assertSame(200, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        
        self::assertSame('new@a.com', $data['email']);
        self::assertSame(['Netflix', 'Amazon'], $data['platforms']);
        self::assertSame(['Action', 'Comedy'], $data['favoriteGenres']);
        self::assertSame(['123'], $data['watchlist']['toWatch']);
        self::assertCount(1, $data['agenda']);
    }

    public function testDeleteRemovesUser(): void
    {
        $user = new User();
        $controller = $this->createController($user);
        
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('remove')->with($user);
        $em->expects(self::once())->method('flush');
        
        $response = $controller->delete($em);
        self::assertSame(200, $response->getStatusCode());
    }

    public function testDeleteReturns404IfUnauthenticated(): void
    {
        $controller = $this->createController(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $response = $controller->delete($em);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testUpdateReturns404IfUnauthenticated(): void
    {
        $controller = $this->createController(null);
        $em = $this->createMock(EntityManagerInterface::class);
        $request = new Request([], [], [], [], [], [], json_encode([]));
        $response = $controller->update($request, $em);
        self::assertSame(404, $response->getStatusCode());
    }

    public function testUpdateWatchlistObjectStructure(): void
    {
        $user = new User();
        $user->setUsername('alice')->setEmail('a@a.com');
        $controller = $this->createController($user);
        
        $request = new Request([], [], [], [], [], [], json_encode([
            'watchlist' => ['watched' => ['123']]
        ]));
        
        $em = $this->createMock(EntityManagerInterface::class);
        $movieRepo = $this->createMock(EntityRepository::class);
        $movie = new Movie();
        $movie->setTitle('M');
        $reflection = new \ReflectionClass(Movie::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($movie, 123);
        $movieRepo->method('find')->willReturn($movie);
        
        $em->method('getRepository')->willReturn($movieRepo);
        $em->expects(self::once())->method('persist');
        
        $response = $controller->update($request, $em);
        $data = json_decode($response->getContent(), true);
        self::assertSame(['123'], $data['watchlist']['watched']);
    }
}
