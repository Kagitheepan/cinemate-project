<?php

namespace App\Tests\Command;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use App\Entity\User;

class CreateUserCommandTest extends KernelTestCase
{
    public function testExecuteCreatesUser(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        
        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);
        
        // Ensure user does not exist first (clean up from previous tests)
        $em = static::getContainer()->get('doctrine')->getManager();
        $repo = $em->getRepository(User::class);
        
        $commandTester->execute([
            'username' => 'new_admin_user',
            'password' => 'super_password',
        ]);
        
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('successfully created', $output);
        
        $user = $repo->findOneBy(['username' => 'new_admin_user']);
        $this->assertNotNull($user);
    }

    public function testExecuteFailsIfUserExists(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        
        $command = $application->find('app:create-user');
        $commandTester = new CommandTester($command);
        
        $commandTester->execute([
            'username' => 'testuser', // Created by fixtures
            'password' => 'any_password',
        ]);
        
        $this->assertEquals(1, $commandTester->getStatusCode());
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('already exists', $output);
    }
}
