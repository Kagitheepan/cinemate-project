<?php

namespace App\Tests\Entity;

use App\Entity\Movie;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class MovieTest extends TestCase
{
    public function testMovieStoresDetails(): void
    {
        $releaseDate = new DateTimeImmutable('1999-03-31');
        $cast = [
            ['name' => 'Keanu Reeves', 'role' => 'Neo'],
        ];

        $movie = (new Movie())
            ->setTmdbId(603)
            ->setTitle('Matrix')
            ->setDescription('A hacker discovers the truth about his world.')
            ->setReleaseDate($releaseDate)
            ->setPoster('/poster.jpg')
            ->setBackdrop('/backdrop.jpg')
            ->setDirector('Lana Wachowski')
            ->setRating(8.7)
            ->setGenres(['Science Fiction', 'Action'])
            ->setPlatforms(['Netflix'])
            ->setCast($cast)
            ->setRuntime(136);

        self::assertNull($movie->getId());
        self::assertSame(603, $movie->getTmdbId());
        self::assertSame('Matrix', $movie->getTitle());
        self::assertSame('A hacker discovers the truth about his world.', $movie->getDescription());
        self::assertSame($releaseDate, $movie->getReleaseDate());
        self::assertSame('/poster.jpg', $movie->getPoster());
        self::assertSame('/backdrop.jpg', $movie->getBackdrop());
        self::assertSame('Lana Wachowski', $movie->getDirector());
        self::assertSame(8.7, $movie->getRating());
        self::assertSame(['Science Fiction', 'Action'], $movie->getGenres());
        self::assertSame(['Netflix'], $movie->getPlatforms());
        self::assertSame($cast, $movie->getCast());
        self::assertSame(136, $movie->getRuntime());
    }
}
