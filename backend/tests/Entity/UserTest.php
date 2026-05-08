<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserStoresProfileAndSecurityData(): void
    {
        $agenda = [
            [
                'id' => 'event-1',
                'movieId' => '42',
                'title' => 'Movie night',
                'start' => '2026-05-08T20:00:00+00:00',
                'end' => '2026-05-08T22:00:00+00:00',
            ],
        ];

        $user = (new User())
            ->setUsername('alice')
            ->setEmail('alice@example.com')
            ->setPassword('hashed-password')
            ->setRoles(['ROLE_ADMIN'])
            ->setPlatforms(['Netflix', 'Prime Video'])
            ->setFavoriteGenres(['Action', 'Comedy'])
            ->setWatchlist(['12', '34'])
            ->setAgenda($agenda);

        self::assertNull($user->getId());
        self::assertSame('alice', $user->getUsername());
        self::assertSame('alice', $user->getUserIdentifier());
        self::assertSame('alice@example.com', $user->getEmail());
        self::assertSame('hashed-password', $user->getPassword());
        self::assertSame(['ROLE_ADMIN', 'ROLE_USER'], $user->getRoles());
        self::assertSame(['Netflix', 'Prime Video'], $user->getPlatforms());
        self::assertSame(['Action', 'Comedy'], $user->getFavoriteGenres());
        self::assertSame(['12', '34'], $user->getWatchlist());
        self::assertSame($agenda, $user->getAgenda());
    }

    public function testUserAlwaysHasRoleUserOnlyOnce(): void
    {
        $user = (new User())->setRoles(['ROLE_USER']);

        self::assertSame(['ROLE_USER'], $user->getRoles());
    }
}
