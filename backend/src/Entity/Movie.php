<?php

namespace App\Entity;

use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MovieRepository::class)]
#[ORM\Table(name: 'Film')]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'Id_Film')]
    private ?int $id = null;

    #[ORM\Column(type: Types::INTEGER, unique: true)]
    private ?int $tmdbId = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $releaseDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $poster = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $backdrop = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $director = null;

    #[ORM\Column(nullable: true)]
    private ?float $rating = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trailerKey = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $runtime = null;

    #[ORM\ManyToMany(targetEntity: Genre::class)]
    #[ORM\JoinTable(name: 'Catégorie')]
    #[ORM\JoinColumn(name: 'Id_Film', referencedColumnName: 'Id_Film')]
    #[ORM\InverseJoinColumn(name: 'Id_Genre', referencedColumnName: 'Id_Genre')]
    private Collection $genres;

    #[ORM\ManyToMany(targetEntity: Platform::class)]
    #[ORM\JoinTable(name: 'En_Streaming_Sur')]
    #[ORM\JoinColumn(name: 'Id_Film', referencedColumnName: 'Id_Film')]
    #[ORM\InverseJoinColumn(name: 'Id_Plateforme', referencedColumnName: 'Id_Plateforme')]
    private Collection $platforms;

    #[ORM\OneToMany(mappedBy: 'movie', targetEntity: MovieCasting::class, cascade: ['persist', 'remove'])]
    private Collection $movieCastings;

    #[ORM\OneToMany(mappedBy: 'movie', targetEntity: UserWatchlist::class, cascade: ['remove'])]
    private Collection $watchlists;

    public function __construct()
    {
        $this->genres = new ArrayCollection();
        $this->platforms = new ArrayCollection();
        $this->movieCastings = new ArrayCollection();
        $this->watchlists = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTmdbId(): ?int
    {
        return $this->tmdbId;
    }

    public function setTmdbId(int $tmdbId): static
    {
        $this->tmdbId = $tmdbId;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getReleaseDate(): ?\DateTimeInterface
    {
        return $this->releaseDate;
    }

    public function setReleaseDate(?\DateTimeInterface $releaseDate): static
    {
        $this->releaseDate = $releaseDate;

        return $this;
    }

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(?string $poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    public function getBackdrop(): ?string
    {
        return $this->backdrop;
    }

    public function setBackdrop(?string $backdrop): static
    {
        $this->backdrop = $backdrop;

        return $this;
    }

    public function getDirector(): ?string
    {
        return $this->director;
    }

    public function setDirector(?string $director): static
    {
        $this->director = $director;

        return $this;
    }

    public function getRating(): ?float
    {
        return $this->rating;
    }

    public function setRating(?float $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getRuntime(): ?int
    {
        return $this->runtime;
    }

    public function setRuntime(?int $runtime): static
    {
        $this->runtime = $runtime;

        return $this;
    }

    public function getTrailerKey(): ?string
    {
        return $this->trailerKey;
    }

    public function setTrailerKey(?string $trailerKey): static
    {
        $this->trailerKey = $trailerKey;

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        $this->genres->removeElement($genre);

        return $this;
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
     * @return Collection<int, MovieCasting>
     */
    public function getMovieCastings(): Collection
    {
        return $this->movieCastings;
    }

    public function addMovieCasting(MovieCasting $movieCasting): static
    {
        if (!$this->movieCastings->contains($movieCasting)) {
            $this->movieCastings->add($movieCasting);
            $movieCasting->setMovie($this);
        }

        return $this;
    }

    public function removeMovieCasting(MovieCasting $movieCasting): static
    {
        if ($this->movieCastings->removeElement($movieCasting)) {
            // set the owning side to null (unless already changed)
            if ($movieCasting->getMovie() === $this) {
                $movieCasting->setMovie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, UserWatchlist>
     */
    public function getWatchlists(): Collection
    {
        return $this->watchlists;
    }

    public function addWatchlist(UserWatchlist $watchlist): static
    {
        if (!$this->watchlists->contains($watchlist)) {
            $this->watchlists->add($watchlist);
            $watchlist->setMovie($this);
        }

        return $this;
    }

    public function removeWatchlist(UserWatchlist $watchlist): static
    {
        if ($this->watchlists->removeElement($watchlist)) {
            // set the owning side to null (unless already changed)
            if ($watchlist->getMovie() === $this) {
                $watchlist->setMovie(null);
            }
        }

        return $this;
    }
}
