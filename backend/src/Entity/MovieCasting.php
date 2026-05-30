<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Composition_Film')]
class MovieCasting
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Movie::class, inversedBy: 'movieCastings')]
    #[ORM\JoinColumn(name: 'Id_Film', referencedColumnName: 'Id_Film', nullable: false)]
    private ?Movie $movie = null;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Casting::class)]
    #[ORM\JoinColumn(name: 'Id_Casting', referencedColumnName: 'Id_Casting', nullable: false)]
    private ?Casting $casting = null;

    #[ORM\Column(name: 'character_name', length: 255, nullable: true)]
    private ?string $characterName = null;

    #[ORM\Column(name: 'cast_order', nullable: true)]
    private ?int $castOrder = null;

    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    public function setMovie(?Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }

    public function getCasting(): ?Casting
    {
        return $this->casting;
    }

    public function setCasting(?Casting $casting): static
    {
        $this->casting = $casting;

        return $this;
    }

    public function getCharacterName(): ?string
    {
        return $this->characterName;
    }

    public function setCharacterName(?string $characterName): static
    {
        $this->characterName = $characterName;

        return $this;
    }

    public function getCastOrder(): ?int
    {
        return $this->castOrder;
    }

    public function setCastOrder(?int $castOrder): static
    {
        $this->castOrder = $castOrder;

        return $this;
    }
}
