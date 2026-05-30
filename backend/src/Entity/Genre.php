<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Genre')]
class Genre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Genre')]
    private ?int $id = null;

    #[ORM\Column(name: 'genre_name', length: 100)]
    private ?string $genreName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGenreName(): ?string
    {
        return $this->genreName;
    }

    public function setGenreName(string $genreName): static
    {
        $this->genreName = $genreName;

        return $this;
    }
}
