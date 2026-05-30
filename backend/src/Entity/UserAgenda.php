<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Agenda')]
class UserAgenda
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'agendas')]
    #[ORM\JoinColumn(name: 'Id_Utilisateur', referencedColumnName: 'Id_Utilisateur', nullable: false)]
    private ?User $user = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Movie::class)]
    #[ORM\JoinColumn(name: 'Id_Film', referencedColumnName: 'Id_Film', nullable: false)]
    private ?Movie $movie = null;

    #[ORM\Column(name: 'event_date', type: 'datetime')]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(name: 'time_slot', length: 100, nullable: true)]
    private ?string $timeSlot = null;

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(?Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(\DateTimeInterface $eventDate): static
    {
        $this->eventDate = $eventDate;

        return $this;
    }

    public function getTimeSlot(): ?string
    {
        return $this->timeSlot;
    }

    public function setTimeSlot(?string $timeSlot): static
    {
        $this->timeSlot = $timeSlot;

        return $this;
    }
}
