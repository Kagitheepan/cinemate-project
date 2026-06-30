<?php

namespace App\Tests\Command;

use App\Entity\Movie;
use App\Entity\User;
use App\Entity\UserAgenda;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class SendRemindersCommandTest extends KernelTestCase
{
    public function testExecuteCommandSuccessfully(): void
    {
        $kernel = self::bootKernel();
        $application = new Application($kernel);
        $em = static::getContainer()->get('doctrine')->getManager();
        
        $user = new User();
        $user->setUsername('reminder_test_user');
        $user->setEmail('reminder@test.com');
        $user->setPassword('test');
        $em->persist($user);

        $movie = new Movie();
        $movie->setTitle('Reminder Movie');
        $movie->setTmdbId(888999);
        $em->persist($movie);
        $em->flush();
        
        $agenda = new UserAgenda();
        $agenda->setUser($user);
        $agenda->setMovie($movie);
        // Set event date to 5 hours from now
        $agenda->setEventDate(new \DateTime('+5 hours'));
        $em->persist($agenda);

        $em->flush();
        $em->clear(); // Clear so the command fetches fresh data from DB

        $command = $application->find('app:send-reminders');
        $commandTester = new CommandTester($command);
        
        $commandTester->execute([]);
        
        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Terminé', $output);
        $this->assertStringContainsString('Email envoyé', $output);
    }
}
