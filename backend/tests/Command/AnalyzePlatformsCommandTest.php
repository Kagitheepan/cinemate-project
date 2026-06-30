<?php

namespace App\Tests\Command;

use App\Entity\Movie;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class AnalyzePlatformsCommandTest extends KernelTestCase
{
    public function testExecuteAnalyzesPlatforms(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $em = static::getContainer()->get('doctrine')->getManager();
        
        // Add a movie with no platforms
        $movie = new Movie();
        $movie->setTitle('No Platform Movie');
        $movie->setTmdbId(111222);
        $movie->setReleaseDate(new \DateTime('-1 year'));
        $em->persist($movie);
        $em->flush();

        // Create Mock for TmdbService
        $tmdbServiceMock = $this->createMock(TmdbService::class);
        $tmdbServiceMock->method('getWatchProviders')
            ->willReturn([
                'FR' => [
                    'flatrate' => [],
                    'rent' => [['provider_name' => 'VOD']],
                    'buy' => [],
                    'ads' => []
                ]
            ]);

        // Replace the service in the test container
        static::getContainer()->set(TmdbService::class, $tmdbServiceMock);

        $command = $application->find('app:analyze-platforms');
        $commandTester = new CommandTester($command);
        
        $commandTester->execute([]);
        
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        
        $this->assertStringContainsString('RESULTS', $output);
        $this->assertStringContainsString('Have rent on TMDB:', $output);
        $this->assertStringContainsString('FIXABLE MOVIES', $output);
    }
}
