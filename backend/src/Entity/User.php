<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'Utilisateur')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé.')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Utilisateur')]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 180,
        minMessage: 'Le nom d\'utilisateur doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $username = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est obligatoire.')]
    #[Assert\Email(message: 'L\'adresse email "{{ value }}" n\'est pas valide.')]
    private ?string $email = null;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    private ?string $password = null;

    #[ORM\ManyToMany(targetEntity: Platform::class)]
    #[ORM\JoinTable(name: 'Plateforme_Favoris_User')]
    #[ORM\JoinColumn(name: 'Id_Utilisateur', referencedColumnName: 'Id_Utilisateur')]
    #[ORM\InverseJoinColumn(name: 'Id_Plateforme', referencedColumnName: 'Id_Plateforme')]
    private Collection $platforms;

    #[ORM\ManyToMany(targetEntity: Genre::class)]
    #[ORM\JoinTable(name: 'Genre_Favoris')]
    #[ORM\JoinColumn(name: 'Id_Utilisateur', referencedColumnName: 'Id_Utilisateur')]
    #[ORM\InverseJoinColumn(name: 'Id_Genre', referencedColumnName: 'Id_Genre')]
    private Collection $favoriteGenres;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserAgenda::class, cascade: ['persist', 'remove'])]
    private Collection $agendas;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserWatchlist::class, cascade: ['persist', 'remove'])]
    private Collection $watchlists;

    public function __construct()
    {
        $this->platforms = new ArrayCollection();
        $this->favoriteGenres = new ArrayCollection();
        $this->agendas = new ArrayCollection();
        $this->watchlists = new ArrayCollection();
    }

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

    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;
        return $this;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
    }

    /**
     * @return Collection<int, Platform>
     */
    public function getPlatforms(): Collection
    {
        return $this->platforms;
    }

    public function addPlatform(Platform $platform): static
    {
        if (!$this->platforms->contains($platform)) {
            $this->platforms->add($platform);
        }

        return $this;
    }

    public function removePlatform(Platform $platform): static
    {
        $this->platforms->removeElement($platform);

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getFavoriteGenres(): Collection
    {
        return $this->favoriteGenres;
    }

    public function addFavoriteGenre(Genre $genre): static
    {
        if (!$this->favoriteGenres->contains($genre)) {
            $this->favoriteGenres->add($genre);
        }

        return $this;
    }

    public function removeFavoriteGenre(Genre $genre): static
    {
        $this->favoriteGenres->removeElement($genre);

        return $this;
    }

    /**
     * @return Collection<int, UserWatchlist>
     */
    public function getWatchlists(): Collection
    {
        return $this->watchlists;
    }

    public function addWatchlistRelation(UserWatchlist $watchlist): static
    {
        if (!$this->watchlists->contains($watchlist)) {
            $this->watchlists->add($watchlist);
            $watchlist->setUser($this);
        }

        return $this;
    }

    public function removeWatchlistRelation(UserWatchlist $watchlist): static
    {
        if ($this->watchlists->removeElement($watchlist)) {
            if ($watchlist->getUser() === $this) {
                $watchlist->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserAgenda>
     */
    public function getAgendas(): Collection
    {
        return $this->agendas;
    }

    public function addAgenda(UserAgenda $agenda): static
    {
        if (!$this->agendas->contains($agenda)) {
            $this->agendas->add($agenda);
            $agenda->setUser($this);
        }

        return $this;
    }

    public function removeAgenda(UserAgenda $agenda): static
    {
        if ($this->agendas->removeElement($agenda)) {
            // set the owning side to null (unless already changed)
            if ($agenda->getUser() === $this) {
                $agenda->setUser(null);
            }
        }

        return $this;
    }
}
