<?php

namespace App\Tests\Command;

use App\Entity\Movie;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ImportMoviesCommandTest extends KernelTestCase
{
    public function testExecuteImportsMovie(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        
        // Create Mock for TmdbService
        $tmdbServiceMock = $this->createMock(TmdbService::class);
        
        $tmdbServiceMock->method('getGenres')
            ->willReturn([28 => 'Action', 878 => 'Science Fiction']);
            
        $tmdbServiceMock->method('discoverStreamingMovies')
            ->willReturn([
                [
                    'id' => 999991,
                    'title' => 'Mocked Movie',
                    'original_title' => 'Mocked Movie Orig',
                    'overview' => 'Mocked overview',
                    'release_date' => '2020-01-01',
                    'poster_path' => '/mocked_poster.jpg',
                    'backdrop_path' => '/mocked_backdrop.jpg',
                    'vote_average' => 8.5,
                    'genre_ids' => [28, 878]
                ]
            ]);
            
        $tmdbServiceMock->method('getWatchProviders')
            ->willReturn([
                'FR' => [
                    'flatrate' => [['provider_name' => 'Netflix']],
                    'ads' => [['provider_name' => 'Pluto TV']]
                ]
            ]);
            
        $tmdbServiceMock->method('getVideos')
            ->willReturn([
                [
                    'site' => 'YouTube',
                    'iso_639_1' => 'fr',
                    'type' => 'Trailer',
                    'key' => 'mocked_youtube_key'
                ]
            ]);
            
        $tmdbServiceMock->method('getMovieDetails')
            ->willReturn(['runtime' => 120]);
            
        $tmdbServiceMock->method('getCredits')
            ->willReturn([
                'crew' => [
                    ['job' => 'Director', 'name' => 'Mock Director']
                ],
                'cast' => [
                    ['name' => 'Mock Actor 1', 'profile_path' => '/actor1.jpg', 'character' => 'Hero'],
                    ['name' => 'Mock Actor 2', 'profile_path' => '/actor2.jpg', 'character' => 'Villain']
                ]
            ]);

        // Replace the service in the test container
        static::getContainer()->set(TmdbService::class, $tmdbServiceMock);

        $command = $application->find('app:import-movies');
        $commandTester = new CommandTester($command);
        
        $commandTester->execute([
            '--pages' => 1
        ]);
        
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Successfully imported', $output);

        // Verify in DB
        $em = static::getContainer()->get('doctrine')->getManager();
        $movieRepo = $em->getRepository(Movie::class);
        $movie = $movieRepo->findOneBy(['tmdbId' => 999991]);
        
        $this->assertNotNull($movie);
        $this->assertSame('Mocked Movie', $movie->getTitle());
        $this->assertSame('Mock Director', $movie->getDirector());
        $this->assertCount(2, $movie->getGenres());
        $this->assertCount(2, $movie->getPlatforms());
        $this->assertCount(2, $movie->getMovieCastings());
        $this->assertSame('mocked_youtube_key', $movie->getTrailerKey());
    }
}
