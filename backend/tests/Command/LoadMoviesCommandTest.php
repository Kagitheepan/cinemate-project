<?php

namespace App\Tests\Command;

use App\Command\LoadMoviesCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class LoadMoviesCommandTest extends TestCase
{
    public function testExecuteLoadMoviesSuccessfully(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);

        $command = new LoadMoviesCommand($em);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:load-movies'));
        
        $commandTester->execute([]);
        
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Movies loaded successfully into MySQL!', $output);
    }
}
