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
        $movieCasting = new \App\Entity\MovieCasting();
        $movieCasting->setCasting($casting);
        $movieCasting->setCharacterName('Neo');
        $movie->addMovieCasting($movieCasting);

        self::assertNull($movie->getId());
        self::assertSame(603, $movie->getTmdbId());
        self::assertSame('Matrix', $movie->getTitle());
        self::assertSame('A hacker discovers the truth about his world.', $movie->getDescription());
        self::assertSame($releaseDate, $movie->getReleaseDate());
        self::assertSame('/poster.jpg', $movie->getPoster());
        self::assertSame('/backdrop.jpg', $movie->getBackdrop());
        self::assertSame('Lana Wachowski', $movie->getDirector());
        self::assertSame(8.7, $movie->getRating());
        
        $genres = [];
        foreach ($movie->getGenres() as $g) {
            $genres[] = $g->getGenreName();
        }
        self::assertSame(['Science Fiction', 'Action'], $genres);
        
        $platforms = [];
        foreach ($movie->getPlatforms() as $p) {
            $platforms[] = $p->getPlatformName();
        }
        self::assertSame(['Netflix'], $platforms);
        
        $cast = [];
        foreach ($movie->getMovieCastings() as $mc) {
            $cast[] = [
                'name' => $mc->getCasting()->getName(),
                'role' => $mc->getCharacterName()
            ];
        }
        self::assertSame([['name' => 'Keanu Reeves', 'role' => 'Neo']], $cast);
        
        self::assertSame(136, $movie->getRuntime());

        $movie->setTrailerKey('abcdef123');
        self::assertSame('abcdef123', $movie->getTrailerKey());

        $movie->removeGenre($genre1);
        self::assertCount(1, $movie->getGenres());

        $movie->removePlatform($platform);
        self::assertCount(0, $movie->getPlatforms());

        $movie->removeMovieCasting($movieCasting);
        self::assertCount(0, $movie->getMovieCastings());

        $watchlist = new \App\Entity\UserWatchlist();
        $movie->addWatchlist($watchlist);
        self::assertCount(1, $movie->getWatchlists());
        
        $movie->removeWatchlist($watchlist);
        self::assertCount(0, $movie->getWatchlists());
    }
}
