<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $email = null;



    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var list<string> Platforms available to the user (e.g. ['Netflix', 'Amazon Prime'])
     */
    #[ORM\Column(type: Types::JSON)]
    private array $platforms = [];

    /**
     * @var list<string> Favorite genres (e.g. ['Action', 'Sci-Fi'])
     */
    #[ORM\Column(type: Types::JSON)]
    private array $favoriteGenres = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

 
 

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user has at least ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getPlatforms(): array
    {
        return $this->platforms;
    }

    public function setPlatforms(array $platforms): self
    {
        $this->platforms = $platforms;

        return $this;
    }

    public function getFavoriteGenres(): array
    {
        return $this->favoriteGenres;
    }

    public function setFavoriteGenres(array $favoriteGenres): self
    {
        $this->favoriteGenres = $favoriteGenres;

        return $this;
    }

    /**
     * @var list<string> Movie IDs in watchlist
     */
    #[ORM\Column(type: Types::JSON)]
    private array $watchlist = [];

    /**
     * @var list<array> Agenda events 
     */
    #[ORM\Column(type: Types::JSON)]
    private array $agenda = [];

    public function getWatchlist(): array
    {
        return $this->watchlist;
    }

    public function setWatchlist(array $watchlist): self
    {
        $this->watchlist = $watchlist;

        return $this;
    }

    public function getAgenda(): array
    {
        return $this->agenda;
    }

    public function setAgenda(array $agenda): self
    {
        $this->agenda = $agenda;

        return $this;
    }
}
