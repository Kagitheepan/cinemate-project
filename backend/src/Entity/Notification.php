<?php

namespace App\Entity;

use App\Repository\NotificationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationRepository::class)]
class Notification
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255)]
    private ?string $message = null;

    #[ORM\Column(length: 50)]
    private ?string $type = 'reminder'; // reminder, info, alert

    #[ORM\Column]
    private bool $isRead = false;

    #[ORM\Column]
    private bool $emailSent = false;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $movieId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $eventId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $eventDate = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getMessage(): ?string { return $this->message; }
    public function setMessage(string $message): static { $this->message = $message; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function isRead(): bool { return $this->isRead; }
    public function setIsRead(bool $isRead): static { $this->isRead = $isRead; return $this; }

    public function isEmailSent(): bool { return $this->emailSent; }
    public function setEmailSent(bool $emailSent): static { $this->emailSent = $emailSent; return $this; }

    public function getMovieId(): ?string { return $this->movieId; }
    public function setMovieId(?string $movieId): static { $this->movieId = $movieId; return $this; }

    public function getEventId(): ?string { return $this->eventId; }
    public function setEventId(?string $eventId): static { $this->eventId = $eventId; return $this; }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getEventDate(): ?\DateTimeInterface { return $this->eventDate; }
    public function setEventDate(?\DateTimeInterface $eventDate): static { $this->eventDate = $eventDate; return $this; }
}
