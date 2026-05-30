<?php

namespace App\Command;

use App\Entity\Movie;
use App\Entity\Genre;
use App\Entity\Platform;
use App\Entity\Casting;
use App\Entity\MovieCasting;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-movies',
    description: 'Imports movies from TMDB API',
)]
class ImportMoviesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TmdbService $tmdbService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('pages', 'p', InputOption::VALUE_OPTIONAL, 'Number of pages to import', 5)
            ->addOption('start-page', 's', InputOption::VALUE_OPTIONAL, 'Starting page', 1)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pages = (int) $input->getOption('pages');
        $startPage = (int) $input->getOption('start-page');

        $io->title('Importing movies from TMDB to MySQL...');

        // 1. Fetch Genres Map
        $io->section('Fetching genres...');
        try {
            $genreMap = $this->tmdbService->getGenres();
            $io->text('Found ' . count($genreMap) . ' genres.');
        } catch (\Exception $e) {
            $io->error('Failed to fetch genres: ' . $e->getMessage());
            return Command::FAILURE;
        }

        // 2. Fetch Movies
        $io->section("Fetching $pages pages of popular movies...");
        $importedCount = 0;

        $repository = $this->entityManager->getRepository(Movie::class);

        $io->progressStart($pages);
        $endPage = $startPage + $pages - 1;

        for ($page = $startPage; $page <= $endPage; $page++) {
            $io->text("Processing page $page...");
            
            try {
                $moviesData = $this->tmdbService->discoverStreamingMovies($page);
            } catch (\Exception $e) {
                $io->warning("Failed to fetch page $page: " . $e->getMessage());
                continue;
            }

            foreach ($moviesData as $movieData) {
                $tmdbId = $movieData['id'];
                $title = $movieData['title'];
                $originalTitle = $movieData['original_title'] ?? $title;

                // Fetch Watch Providers First to verify if we should import
                $providers = $this->tmdbService->getWatchProviders($tmdbId);
                $platformsNames = [];
                $frData = $providers['FR'] ?? [];
                
                if (isset($frData['flatrate'])) {
                    foreach ($frData['flatrate'] as $provider) {
                        $platformsNames[] = $provider['provider_name'];
                    }
                }
                if (isset($frData['ads'])) {
                    foreach ($frData['ads'] as $provider) {
                        $name = $provider['provider_name'];
                        if (!in_array($name, $platformsNames)) {
                            $platformsNames[] = $name . ' (gratuit)';
                        }
                    }
                }
                if (empty($platformsNames)) {
                    $hasRentOrBuy = !empty($frData['rent']) || !empty($frData['buy']);
                    if ($hasRentOrBuy) {
                        $platformsNames[] = 'VOD (payant)';
                    }
                }
                
                $releaseDate = !empty($movieData['release_date']) ? new \DateTime($movieData['release_date']) : null;
                $isReleased = $releaseDate && $releaseDate <= new \DateTime();
                
                // Fetch Trailer
                $videos = $this->tmdbService->getVideos($tmdbId);
                $frTrailer = null; $enTrailer = null;
                
                foreach ($videos as $video) {
                    if ($video['site'] !== 'YouTube') continue;
                    $lang = $video['iso_639_1'] ?? '';
                    $type = $video['type'] ?? '';
                    
                    if ($type === 'Trailer') {
                        if ($lang === 'fr' && !$frTrailer) $frTrailer = $video['key'];
                        elseif ($lang === 'en' && !$enTrailer) $enTrailer = $video['key'];
                    }
                }
                
                // On rejette le film s'il n'est pas sorti, sans plateforme, ou sans trailer FR/EN
                if (!$isReleased || empty($platformsNames) || (!$frTrailer && !$enTrailer)) {
                    $movie = $repository->findOneBy(['tmdbId' => $tmdbId]);
                    if ($movie && $movie->getId()) {
                        $this->entityManager->remove($movie);
                        $this->entityManager->flush();
                    }
                    continue;
                }

                // Check if exists
                $movie = $repository->findOneBy(['tmdbId' => $tmdbId]);
                if (!$movie) {
                    $movie = new Movie();
                    $movie->setTmdbId($tmdbId);
                } else {
                    // Clear existing relations
                    foreach ($movie->getGenres() as $g) { $movie->removeGenre($g); }
                    foreach ($movie->getPlatforms() as $p) { $movie->removePlatform($p); }
                    foreach ($movie->getMovieCastings() as $mc) {
                        $movie->removeMovieCasting($mc);
                        $this->entityManager->remove($mc);
                    }
                    $this->entityManager->flush(); // Flush des suppressions pour éviter l'EntityIdentityCollisionException
                }

                $movie->setTitle($title);
                $movie->setDescription($movieData['overview'] ?? null);
                if ($releaseDate) $movie->setReleaseDate($releaseDate);
                $movie->setPoster($movieData['poster_path'] ?? null);
                $movie->setBackdrop($movieData['backdrop_path'] ?? null);
                $movie->setRating($movieData['vote_average'] ?? null);

                // Fetch Movie Details (for Runtime)
                $movieDetails = $this->tmdbService->getMovieDetails($tmdbId);
                $runtime = $movieDetails['runtime'] ?? null;
                $movie->setRuntime($runtime);

                // Map Genres
                if (isset($movieData['genre_ids'])) {
                    foreach ($movieData['genre_ids'] as $genreId) {
                        if (isset($genreMap[$genreId])) {
                            $genreName = $genreMap[$genreId];
                            $genre = $this->entityManager->getRepository(Genre::class)->findOneBy(['genreName' => $genreName]);
                            if (!$genre) {
                                $genre = new Genre();
                                $genre->setGenreName($genreName);
                                $this->entityManager->persist($genre);
                            }
                            $movie->addGenre($genre);
                        }
                    }
                }

                // Map Platforms
                foreach ($platformsNames as $platformName) {
                    $platform = $this->entityManager->getRepository(Platform::class)->findOneBy(['platformName' => $platformName]);
                    if (!$platform) {
                        $platform = new Platform();
                        $platform->setPlatformName($platformName);
                        $this->entityManager->persist($platform);
                    }
                    $movie->addPlatform($platform);
                }

                // Fetch Credits
                $credits = $this->tmdbService->getCredits($tmdbId);
                
                // Director
                $director = null;
                foreach ($credits['crew'] as $crewMember) {
                    if ($crewMember['job'] === 'Director') {
                        $director = $crewMember['name'];
                        break;
                    }
                }
                $movie->setDirector($director);

                // Actors (Top 5)
                $actorIdsSeen = [];
                foreach (array_slice($credits['cast'], 0, 5) as $index => $actorData) {
                    $actorName = $actorData['name'];
                    if (in_array($actorName, $actorIdsSeen)) continue;
                    $actorIdsSeen[] = $actorName;
                    
                    $casting = $this->entityManager->getRepository(Casting::class)->findOneBy(['name' => $actorName]);
                    if (!$casting) {
                        $casting = new Casting();
                        $casting->setName($actorName);
                        $casting->setProfilePath($actorData['profile_path'] ? 'https://image.tmdb.org/t/p/w200' . $actorData['profile_path'] : null);
                        $this->entityManager->persist($casting);
                    }

                    $movieCasting = new MovieCasting();
                    $movieCasting->setMovie($movie);
                    $movieCasting->setCasting($casting);
                    $movieCasting->setCharacterName($actorData['character'] ?? 'Actor');
                    $movieCasting->setCastOrder($index);
                    $this->entityManager->persist($movieCasting);
                    $movie->addMovieCasting($movieCasting);
                }

                $movie->setTrailerKey($frTrailer ?? $enTrailer);

                $this->entityManager->persist($movie);
                $importedCount++;
            }

            // Flush every page
            $this->entityManager->flush();
            $this->entityManager->clear(); // Libérer la mémoire pour éviter Fatal error: Allowed memory size exhausted
            $io->progressAdvance();
        }

        $io->newLine();
        $io->success("Successfully imported/updated $importedCount movies!");

        return Command::SUCCESS;
    }
}
