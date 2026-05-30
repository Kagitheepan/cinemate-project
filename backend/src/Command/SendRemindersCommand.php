<?php

namespace App\Command;

use App\Entity\Notification;
use App\Entity\User;
use App\Repository\NotificationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-reminders',
    description: 'Check user agendas and send reminder notifications + emails for upcoming events',
)]
class SendRemindersCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NotificationRepository $notificationRepository,
        private MailerInterface $mailer
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Checking for upcoming agenda events...');

        $userRepo = $this->entityManager->getRepository(User::class);
        $users = $userRepo->findAll();

        $now = new \DateTime();
        $notifCreated = 0;
        $emailsSent = 0;

        foreach ($users as $user) {
            $agenda = $user->getAgendas();
            if (empty($agenda)) continue;

            foreach ($agenda as $event) {
                /** @var \App\Entity\UserAgenda $event */
                $eventStart = clone $event->getEventDate();
                $eventId = (string)($event->getMovie() ? $event->getMovie()->getId() : '');
                $movieTitle = $event->getMovie() ? $event->getMovie()->getTitle() : 'Film';

                if (empty($eventId)) continue;

                // Calculate time difference
                $diff = $now->diff($eventStart);
                $hoursUntil = ($diff->days * 24) + $diff->h;
                $isPast = $eventStart < $now;

                if ($isPast) continue;

                // Skip if already notified for this event
                if ($this->notificationRepository->existsForEvent($user->getId(), $eventId)) {
                    continue;
                }

                // Trigger: event is within the next 24 hours
                if ($hoursUntil <= 24) {
                    $timeLabel = $hoursUntil <= 1
                        ? "dans moins d'1 heure"
                        : "dans {$hoursUntil}h";

                    $message = "🎬 Rappel : \"{$movieTitle}\" est prévu {$timeLabel} !";

                    // Create in-app notification
                    $notification = new Notification();
                    $notification->setUser($user);
                    $notification->setMessage($message);
                    $notification->setType('reminder');
                    $notification->setMovieId($event->getMovie() ? $event->getMovie()->getId() : null);
                    $notification->setEventId($eventId);
                    $notification->setEventDate($eventStart);

                    // Send email
                    $emailAddress = $user->getEmail();
                    if ($emailAddress) {
                        try {
                            $email = (new Email())
                                ->from('noreply@cinemate.app')
                                ->to($emailAddress)
                                ->subject("🎬 Cinemate - Rappel : {$movieTitle}")
                                ->html($this->buildEmailHtml($movieTitle, $eventStart, $timeLabel, $user->getUsername()));

                            $this->mailer->send($email);
                            $notification->setEmailSent(true);
                            $emailsSent++;
                            $io->text("  ✉ Email envoyé à {$emailAddress} pour \"{$movieTitle}\"");
                        } catch (\Exception $e) {
                            $io->warning("  ✗ Email échoué pour {$emailAddress}: " . $e->getMessage());
                            $notification->setEmailSent(false);
                        }
                    }

                    $this->entityManager->persist($notification);
                    $notifCreated++;
                }
            }
        }

        $this->entityManager->flush();

        $io->success("Terminé : {$notifCreated} notification(s) créée(s), {$emailsSent} email(s) envoyé(s).");

        return Command::SUCCESS;
    }

    private function buildEmailHtml(string $movieTitle, \DateTimeInterface $eventDate, string $timeLabel, string $username): string
    {
        $dateFormatted = $eventDate->format('d/m/Y à H:i');

        return <<<HTML
        <div style="font-family: 'Segoe UI', Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #1a1a2e; color: #ffffff; border-radius: 12px; overflow: hidden;">
            <div style="background: linear-gradient(135deg, #7c3aed, #ec4899); padding: 30px; text-align: center;">
                <h1 style="margin: 0; font-size: 28px; font-weight: 800;">🎬 Cinemate</h1>
                <p style="margin: 8px 0 0; opacity: 0.9; font-size: 14px;">Votre rappel de visionnage</p>
            </div>
            <div style="padding: 30px;">
                <p style="font-size: 16px; color: #d1d5db;">Bonjour <strong style="color: #a78bfa;">{$username}</strong>,</p>
                <div style="background: #16213e; border-left: 4px solid #7c3aed; padding: 20px; border-radius: 8px; margin: 20px 0;">
                    <h2 style="margin: 0 0 8px; color: #ffffff; font-size: 20px;">{$movieTitle}</h2>
                    <p style="margin: 0; color: #9ca3af;">📅 Prévu le <strong style="color: #e5e7eb;">{$dateFormatted}</strong></p>
                    <p style="margin: 8px 0 0; color: #a78bfa; font-weight: 600;">⏰ {$timeLabel}</p>
                </div>
                <p style="color: #9ca3af; font-size: 14px;">Préparez le popcorn, c'est bientôt l'heure ! 🍿</p>
            </div>
            <div style="background: #0f0f23; padding: 15px; text-align: center;">
                <p style="margin: 0; color: #6b7280; font-size: 12px;">Cinemate — Votre compagnon cinématographique</p>
            </div>
        </div>
        HTML;
    }
}
