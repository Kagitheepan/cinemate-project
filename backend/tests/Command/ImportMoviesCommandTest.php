<?php

namespace App\Tests\Command;

use App\Command\ImportMoviesCommand;
use App\Entity\Movie;
use App\Entity\Genre;
use App\Entity\Platform;
use App\Entity\Casting;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ImportMoviesCommandTest extends TestCase
{
    public function testExecuteFailsOnGenreError(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $tmdb = $this->createMock(TmdbService::class);
        $tmdb->method('getGenres')->willThrowException(new \Exception('API Error'));

        $command = new ImportMoviesCommand($em, $tmdb);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:import-movies'));
        $commandTester->execute([]);

        self::assertSame(1, $commandTester->getStatusCode());
        self::assertStringContainsString('Failed to fetch genres: API Error', $commandTester->getDisplay());
    }

    public function testExecuteImportsMoviesSuccessfully(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        
        $movieRepo = $this->createMock(EntityRepository::class);
        $movieRepo->method('findOneBy')->willReturn(null); // No existing movie
        
        $genreRepo = $this->createMock(EntityRepository::class);
        $genreRepo->method('findOneBy')->willReturn(null);
        
        $platformRepo = $this->createMock(EntityRepository::class);
        $platformRepo->method('findOneBy')->willReturn(null);
        
        $castingRepo = $this->createMock(EntityRepository::class);
        $castingRepo->method('findOneBy')->willReturn(null);
        
        $em->method('getRepository')->willReturnCallback(function($class) use ($movieRepo, $genreRepo, $platformRepo, $castingRepo) {
            if ($class === Movie::class) return $movieRepo;
            if ($class === Genre::class) return $genreRepo;
            if ($class === Platform::class) return $platformRepo;
            if ($class === Casting::class) return $castingRepo;
            return $this->createMock(EntityRepository::class);
        });
        
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $tmdb = $this->createMock(TmdbService::class);
        $tmdb->method('getGenres')->willReturn([28 => 'Action']);
        $tmdb->method('discoverStreamingMovies')->willReturn([
            [
                'id' => 123,
                'title' => 'Test Movie',
                'release_date' => '2020-01-01',
                'genre_ids' => [28]
            ]
        ]);
        $tmdb->method('getWatchProviders')->willReturn([
            'FR' => [
                'flatrate' => [['provider_name' => 'Netflix']]
            ]
        ]);
        $tmdb->method('getVideos')->willReturn([
            ['site' => 'YouTube', 'type' => 'Trailer', 'iso_639_1' => 'fr', 'key' => 'abc123fr']
        ]);
        $tmdb->method('getMovieDetails')->willReturn(['runtime' => 120]);
        $tmdb->method('getCredits')->willReturn([
            'crew' => [['job' => 'Director', 'name' => 'Nolan']],
            'cast' => [['name' => 'Actor 1', 'character' => 'Hero', 'profile_path' => null]]
        ]);

        $command = new ImportMoviesCommand($em, $tmdb);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:import-movies'));
        $commandTester->execute(['--pages' => 1]);

        $commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('Successfully imported/updated 1 movies!', $commandTester->getDisplay());
    }
}
