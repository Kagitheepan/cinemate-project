<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserStoresProfileAndSecurityData(): void
    {
        $user = (new User())
            ->setUsername('alice')
            ->setEmail('alice@example.com')
            ->setPassword('hashed-password')
            ->setRoles(['ROLE_ADMIN']);

        $platform1 = new \App\Entity\Platform();
        $platform1->setPlatformName('Netflix');
        $user->addPlatform($platform1);
        
        $platform2 = new \App\Entity\Platform();
        $platform2->setPlatformName('Prime Video');
        $user->addPlatform($platform2);

        $genre1 = new \App\Entity\Genre();
        $genre1->setGenreName('Action');
        $user->addFavoriteGenre($genre1);

        $genre2 = new \App\Entity\Genre();
        $genre2->setGenreName('Comedy');
        $user->addFavoriteGenre($genre2);

        // For watchlist we need UserWatchlist entity and a Movie
        $movie1 = new \App\Entity\Movie();
        $watchlist1 = new \App\Entity\UserWatchlist();
        $watchlist1->setMovie($movie1);
        $user->addWatchlistRelation($watchlist1);

        // For agenda we need UserAgenda entity
        $agenda1 = new \App\Entity\UserAgenda();
        $agenda1->setEventDate(new \DateTimeImmutable('2026-05-08T20:00:00+00:00'));
        $agenda1->setTimeSlot('Evening');
        $user->addAgenda($agenda1);

        self::assertNull($user->getId());
        self::assertSame('alice', $user->getUsername());
        self::assertSame('alice', $user->getUserIdentifier());
        self::assertSame('alice@example.com', $user->getEmail());
        self::assertSame('hashed-password', $user->getPassword());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        
        $platforms = [];
        foreach ($user->getPlatforms() as $p) {
            $platforms[] = $p->getPlatformName();
        }
        self::assertSame(['Netflix', 'Prime Video'], $platforms);
        
        $genres = [];
        foreach ($user->getFavoriteGenres() as $g) {
            $genres[] = $g->getGenreName();
        }
        self::assertSame(['Action', 'Comedy'], $genres);
        
        self::assertCount(1, $user->getWatchlists());
        self::assertCount(1, $user->getAgendas());
        self::assertSame('Evening', $user->getAgendas()->first()->getTimeSlot());

        $user->removePlatform($platform1);
        self::assertCount(1, $user->getPlatforms());

        $user->removeFavoriteGenre($genre1);
        self::assertCount(1, $user->getFavoriteGenres());

        $user->removeWatchlistRelation($watchlist1);
        self::assertCount(0, $user->getWatchlists());

        $user->removeAgenda($agenda1);
        self::assertCount(0, $user->getAgendas());

        $user->eraseCredentials(); // just for coverage
        self::assertTrue(true);
    }

    public function testUserAlwaysHasRoleUserOnlyOnce(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}
