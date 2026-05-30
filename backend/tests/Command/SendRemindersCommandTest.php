<?php

namespace App\Tests\Command;

use App\Command\SendRemindersCommand;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Mailer\MailerInterface;

class SendRemindersCommandTest extends TestCase
{
    public function testExecuteSendsReminders(): void
    {
        $user = new User();
        $user->setUsername('alice')->setEmail('alice@example.com');
        $reflection = new \ReflectionClass(User::class);
        $property = $reflection->getProperty('id');
        $property->setAccessible(true);
        $property->setValue($user, 42);

        $agenda = new \App\Entity\UserAgenda();
        $agenda->setEventDate((new \DateTime())->modify('+12 hours'));
        $agenda->setTimeSlot('Evening');
        
        $movie = new \App\Entity\Movie();
        $movie->setTitle('Inception');
        
        $movieReflection = new \ReflectionClass(\App\Entity\Movie::class);
        $movieProperty = $movieReflection->getProperty('id');
        $movieProperty->setAccessible(true);
        $movieProperty->setValue($movie, 101);
        
        $agenda->setMovie($movie);
        
        // Not setting ID on UserAgenda anymore because it doesn't have an id property
        
        $user->addAgenda($agenda);

        $userRepo = $this->createMock(EntityRepository::class);
        $userRepo->method('findAll')->willReturn([$user]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->with(User::class)->willReturn($userRepo);
        
        $em->expects(self::once())->method('persist');
        $em->expects(self::once())->method('flush');

        $notifRepo = $this->createMock(NotificationRepository::class);
        $notifRepo->method('existsForEvent')->with(42, '101')->willReturn(false);

        $mailer = $this->createMock(MailerInterface::class);
        $mailer->expects(self::once())->method('send');

        $command = new SendRemindersCommand($em, $notifRepo, $mailer);
        $application = new Application();
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:send-reminders'));
        $commandTester->execute([]);

        $commandTester->assertCommandIsSuccessful();
        $output = $commandTester->getDisplay();
        self::assertStringContainsString('Email envoyé à alice@example.com pour "Inception"', $output);
        self::assertStringContainsString('Terminé : 1 notification(s) créée(s), 1 email(s) envoyé(s)', $output);
    }
}
