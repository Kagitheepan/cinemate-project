<?php

namespace App\Tests\Command;

use App\Command\AnalyzePlatformsCommand;
use App\Entity\Movie;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class AnalyzePlatformsCommandTest extends TestCase
{
    public function testExecuteAnalyzesPlatformsSuccessfully(): void
    {
        $movie1 = (new Movie())->setTitle('Inception')->setReleaseDate(new \DateTime('-10 days'))->setTmdbId(101);
        $movie2 = (new Movie())->setTitle('Matrix')->setReleaseDate(new \DateTime('-10 years'))->setTmdbId(102);

        $movie3 = (new Movie())->setTitle('BuyAndAds')->setTmdbId(103);
        $movie4 = (new Movie())->setTitle('Empty')->setTmdbId(104);

        $repo = $this->createMock(EntityRepository::class);
        $repo->method('findAll')->willReturn([$movie1, $movie2, $movie3, $movie4]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(Movie::class)->willReturn($repo);

        $tmdb = $this->createMock(TmdbService::class);
        $tmdb->method('getWatchProviders')->willReturnCallback(function($id) {
            if ($id === 101) {
                return ['FR' => ['flatrate' => [['provider_name' => 'Netflix']], 'rent' => [['provider_name' => 'Apple TV']]]];
            }
            if ($id === 103) {
                return ['FR' => ['buy' => [['provider_name' => 'Amazon']], 'ads' => [['provider_name' => 'Tubi']]]];
            }
            return [];
        });

        $command = new AnalyzePlatformsCommand($em, $tmdb);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:analyze-platforms'));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Total movies: 4', $output);
        self::assertStringContainsString('Currently in theaters (< 90 days): 1', $output);
    }
}
