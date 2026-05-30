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
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'fr', 'key' => 'FR_KEY_123']
        ]);

        $command = new FixTrailersCommand($em, $tmdb);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:fix-trailers'));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        self::assertSame('FR_KEY_123', $movie->getTrailerKey());
        self::assertStringContainsString('Successfully fixed 1 trailers', $commandTester->getDisplay());
    }
}
