<?php

namespace App\Tests\Command;

use App\Command\FixTrailersCommand;
use App\Entity\Movie;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class FixTrailersCommandTest extends TestCase
{
    public function testExecuteUpdatesTrailers(): void
    {
        $movie = new Movie();
        $movie->setTmdbId(101);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findAll')->willReturn([$movie]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Movie::class)->willReturn($repo);
        $em->expects(self::once())->method('persist')->with($movie);
        $em->expects(self::once())->method('flush');

        $tmdb = $this->createMock(TmdbService::class);
        $tmdb->method('getVideos')->with(101)->willReturn([
            ['site' => 'Vimeo'],
            ['site' => 'YouTube', 'type' => 'Featurette', 'key' => 'ANY_VIDEO'],
            ['site' => 'YouTube', 'type' => 'Teaser', 'iso_639_1' => 'it', 'key' => 'ANY_TEASER'],
            ['site' => 'YouTube', 'type' => 'Teaser', 'iso_639_1' => 'en', 'key' => 'EN_TEASER'],
            ['site' => 'YouTube', 'type' => 'Teaser', 'iso_639_1' => 'fr', 'key' => 'FR_TEASER'],
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'it', 'key' => 'ANY_TRAILER'],
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'en', 'key' => 'EN_TRAILER'],
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'fr', 'key' => 'FR_TRAILER'],
            // Duplicates to trigger the !$frTrailer false conditions
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'fr', 'key' => 'FR_TRAILER_2'],
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'en', 'key' => 'EN_TRAILER_2'],
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'it', 'key' => 'ANY_TRAILER_2'],
            ['site' => 'YouTube', 'type' => 'Teaser', 'iso_639_1' => 'fr', 'key' => 'FR_TEASER_2'],
            ['site' => 'YouTube', 'type' => 'Teaser', 'iso_639_1' => 'en', 'key' => 'EN_TEASER_2'],
            ['site' => 'YouTube', 'type' => 'Teaser', 'iso_639_1' => 'it', 'key' => 'ANY_TEASER_2'],
            ['site' => 'YouTube', 'type' => 'Featurette', 'key' => 'ANY_VIDEO_2'],
        ]);

        $command = new FixTrailersCommand($em, $tmdb);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:fix-trailers'));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        self::assertSame('FR_TRAILER', $movie->getTrailerKey());
        self::assertStringContainsString('Successfully fixed 1 trailers', $commandTester->getDisplay());
    }
}
