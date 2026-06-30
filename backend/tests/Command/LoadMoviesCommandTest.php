<?php

namespace App\Tests\Command;

use App\Entity\Movie;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class LoadMoviesCommandTest extends KernelTestCase
{
    public function testExecuteLoadsMovies(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        
        $command = $application->find('app:load-movies');
        $commandTester = new CommandTester($command);
        
        $commandTester->execute([]);
        
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        
        $this->assertStringContainsString('Movies loaded successfully', $output);

        // Verify in DB
        $em = static::getContainer()->get('doctrine')->getManager();
        $movie = $em->getRepository(Movie::class)->findOneBy(['title' => 'Inception']);
        
        $this->assertNotNull($movie);
        $this->assertSame(27205, $movie->getTmdbId());
    }
}
