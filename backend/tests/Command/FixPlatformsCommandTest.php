<?php

namespace App\Tests\Command;

use App\Entity\Movie;
use App\Service\TmdbService;
use App\Service\StreamingAvailabilityService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FixPlatformsCommandTest extends KernelTestCase
{
    public function testExecuteFixesPlatforms(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $em = static::getContainer()->get('doctrine')->getManager();
        
        // Add a movie with no platforms
        $movie = new Movie();
        $movie->setTitle('Fix Platform Movie');
        $movie->setTmdbId(555666);
        $em->persist($movie);
        $em->flush();

        // Create Mock for TmdbService
        $tmdbServiceMock = $this->createMock(TmdbService::class);
        $tmdbServiceMock->method('getWatchProviders')
            ->willReturn([
                'FR' => [
                    'flatrate' => [['provider_name' => 'Netflix']]
                ]
            ]);

        // Create Mock for StreamingAvailabilityService (unused here because TMDB succeeds)
        $streamingServiceMock = $this->createMock(StreamingAvailabilityService::class);

        // Replace the services in the test container
        static::getContainer()->set(TmdbService::class, $tmdbServiceMock);
        static::getContainer()->set(StreamingAvailabilityService::class, $streamingServiceMock);

        $command = $application->find('app:fix-platforms');
        $commandTester = new CommandTester($command);
        
        $commandTester->execute([]);
        
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        
        $this->assertStringContainsString('Fixed via TMDB', $output);

        // Verify in DB
        $movie = $em->getRepository(Movie::class)->findOneBy(['tmdbId' => 555666]);
        $this->assertCount(1, $movie->getPlatforms());
        $this->assertSame('Netflix', $movie->getPlatforms()->first()->getPlatformName());
    }
}
