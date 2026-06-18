<?php

namespace App\Tests\Command;

use App\Command\FixPlatformsCommand;
use App\Entity\Movie;
use App\Entity\Platform;
use App\Service\TmdbService;
use App\Service\StreamingAvailabilityService;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class FixPlatformsCommandTest extends TestCase
{
    public function testExecuteFixesPlatformsViaTmdb(): void
    {
        $conn = $this->createMock(Connection::class);
        
        $statement = $this->createMock(\Doctrine\DBAL\Result::class);
        $statement->method('fetchAllAssociative')->willReturn([
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ]);
        
        $conn->method('executeQuery')->willReturn($statement);

        $movie1 = new Movie();
        $movie2 = new Movie();
        $movie3 = new Movie();
        
        $reflection = new \ReflectionClass(Movie::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($movie1, 1);
        $property->setValue($movie2, 2);
        $property->setValue($movie3, 3);
        
        $movie1->setTmdbId(101); // fixed via TMDB
        $movie2->setTmdbId(102); // fixed via Streaming API
        $movie3->setTmdbId(103); // fixed via Production Companies

        $movieRepo = $this->createMock(EntityRepository::class);
        $movieRepo->method('find')->willReturnMap([
            [1, null, null, $movie1],
            [2, null, null, $movie2],
            [3, null, null, $movie3],
        ]);

        $platformRepo = $this->createMock(EntityRepository::class);
        $platformRepo->method('findOneBy')->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getConnection')->willReturn($conn);
        $em->method('getRepository')->willReturnCallback(function($class) use ($movieRepo, $platformRepo) {
            if ($class === Movie::class) return $movieRepo;
            if ($class === \App\Entity\Platform::class) return $platformRepo;
            return $this->createMock(EntityRepository::class);
        });
        
        $em->expects(self::atLeastOnce())->method('persist');
        $em->expects(self::atLeastOnce())->method('flush');

        $tmdb = $this->createMock(TmdbService::class);
        $tmdb->method('getWatchProviders')->willReturnMap([
            [101, ['FR' => [
                'flatrate' => [['provider_name' => 'Netflix']],
                'ads' => [['provider_name' => 'Pluto TV']],
            ]]],
            [102, ['FR' => ['rent' => [['provider_name' => 'VOD']]]]],
            [103, []],
        ]);
        
        $tmdb->method('getMovieDetails')->willReturnCallback(function($id) {
            if ($id === 103) return ['production_companies' => [
                ['name' => 'Marvel Studios'],
                ['name' => 'Netflix'],
                ['name' => 'Amazon Studios'],
                ['name' => 'Apple']
            ]];
            return [];
        });

        $streaming = $this->createMock(StreamingAvailabilityService::class);
        $streaming->method('getStreamingByTmdbId')->willReturnCallback(function($id) {
            if ($id === 102) return ['Amazon Prime'];
            return [];
        });

        $command = new FixPlatformsCommand($em, $tmdb, $streaming);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:fix-platforms'));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        self::assertStringContainsString('Total: 2 movies fixed!', $commandTester->getDisplay());
    }
}
