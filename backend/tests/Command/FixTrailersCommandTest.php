<?php

namespace App\Tests\Command;

use App\Entity\Movie;
use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class FixTrailersCommandTest extends KernelTestCase
{
    public function testExecuteFixesTrailers(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $em = static::getContainer()->get('doctrine')->getManager();
        
        $movie = new Movie();
        $movie->setTitle('Trailer Test Movie');
        $movie->setTmdbId(777888);
        $movie->setTrailerKey(null);
        $em->persist($movie);
        $em->flush();

        $tmdbServiceMock = $this->createMock(TmdbService::class);
        $tmdbServiceMock->method('getVideos')
            ->willReturn([
                [
                    'site' => 'YouTube',
                    'iso_639_1' => 'fr',
                    'type' => 'Trailer',
                    'key' => 'new_fr_trailer_key'
                ]
            ]);

        static::getContainer()->set(TmdbService::class, $tmdbServiceMock);

        $command = $application->find('app:fix-trailers');
        $commandTester = new CommandTester($command);
        
        $commandTester->execute([]);
        
        $commandTester->assertCommandIsSuccessful();
        
        $movie = $em->getRepository(Movie::class)->findOneBy(['tmdbId' => 777888]);
        $this->assertSame('new_fr_trailer_key', $movie->getTrailerKey());
    }
}
