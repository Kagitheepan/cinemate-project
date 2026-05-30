<?php

namespace App\Tests\Command;

use App\Command\FixAllocineCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class FixAllocineCommandTest extends TestCase
{
    public function testExecuteFixAllocineCommand(): void
    {
        $command = new FixAllocineCommand();
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('FixAllocineCommand'));
        $commandTester->execute([
            'arg1' => 'test_argument',
            '--option1' => true
        ]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('You passed an argument: test_argument', $output);
        self::assertStringContainsString('You have a new command!', $output);
    }
}
