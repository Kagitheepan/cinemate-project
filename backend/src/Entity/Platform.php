<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'Plateforme')]
class Platform
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Plateforme')]
    private ?int $id = null;

    #[ORM\Column(name: 'platform_name', length: 100)]
    private ?string $platformName = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlatformName(): ?string
    {
        return $this->platformName;
    }

    public function setPlatformName(string $platformName): static
    {
        $this->platformName = $platformName;

        return $this;
    }
}
