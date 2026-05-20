<?php

namespace App\Command;

use App\Entity\Movie;
use App\Service\TmdbService;
use App\Service\OmdbService;
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
        private TmdbService $tmdbService,
        private OmdbService $omdbService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('pages', 'p', InputOption::VALUE_OPTIONAL, 'Number of pages to import', 5)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pages = (int) $input->getOption('pages');

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

        for ($page = 1; $page <= $pages; $page++) {
            $io->text("Processing page $page...");
            
            try {
                // Utilise la nouvelle méthode ciblée (7 dernières années, streaming/VOD en France)
                $moviesData = $this->tmdbService->discoverStreamingMovies($page);
            } catch (\Exception $e) {
                $io->warning("Failed to fetch page $page: " . $e->getMessage());
                continue;
            }

            foreach ($moviesData as $movieData) {
                $tmdbId = $movieData['id'];
                $title = $movieData['title'];
                $originalTitle = $movieData['original_title'] ?? $title;

                // Check if exists
                $movie = $repository->findOneBy(['tmdbId' => $tmdbId]);
                if (!$movie) {
                    $movie = new Movie();
                    $movie->setTmdbId($tmdbId);
                }

                $movie->setTitle($title);
                $movie->setDescription($movieData['overview'] ?? null);
                
                if (!empty($movieData['release_date'])) {
                    $movie->setReleaseDate(new \DateTime($movieData['release_date']));
                }

                $movie->setPoster($movieData['poster_path'] ?? null);
                $movie->setBackdrop($movieData['backdrop_path'] ?? null);
                $movie->setRating($movieData['vote_average'] ?? null);

                // Fetch Movie Details (for Runtime)
                $movieDetails = $this->tmdbService->getMovieDetails($tmdbId);
                $runtime = $movieDetails['runtime'] ?? null;

                // Current runtime in DB (to check if it's the 120 placeholder)
                $currentRuntime = $movie->getRuntime();

                // OMDB Fallback if TMDB runtime is missing, 0, OR still 120 (our old placeholder)
                if (!$runtime || $runtime === 0 || $currentRuntime === 120) {
                    $year = $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y') : null;
                    
                    // Use Original Title for better matching with OMDB (English database)
                    $runtime = $this->omdbService->getRuntimeByTitle($originalTitle, $year);
                    
                    if ($runtime) {
                        $io->text(" [OMDB] Found runtime for $originalTitle ($title): $runtime min");
                    } else if ($title !== $originalTitle) {
                        // Try with French title as last resort
                        $runtime = $this->omdbService->getRuntimeByTitle($title, $year);
                        if ($runtime) {
                             $io->text(" [OMDB] Found runtime for $title (FR): $runtime min");
                        }
                    }
                }

                $movie->setRuntime($runtime);

                // Map Genres
                $movieGenres = [];
                if (isset($movieData['genre_ids'])) {
                    foreach ($movieData['genre_ids'] as $genreId) {
                        if (isset($genreMap[$genreId])) {
                            $movieGenres[] = $genreMap[$genreId];
                        }
                    }
                }
                $movie->setGenres($movieGenres);

                // Fetch Credits (Director & Actors)
                // Note: This makes N requests. In a real large import, we'd queue this.
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
                $cast = [];
                foreach (array_slice($credits['cast'], 0, 5) as $actor) {
                     $cast[] = [
                        'name' => $actor['name'],
                        'role' => $actor['character'] ?? 'Actor',
                        'imageUrl' => $actor['profile_path'] ? 'https://image.tmdb.org/t/p/w200' . $actor['profile_path'] : null
                     ];
                }
                $movie->setCast($cast);

                // Fetch Watch Providers (JustWatch)
                $providers = $this->tmdbService->getWatchProviders($tmdbId);
                $platforms = [];
                $frData = $providers['FR'] ?? [];
                
                // 1. Streaming subscriptions (flatrate)
                if (isset($frData['flatrate'])) {
                    foreach ($frData['flatrate'] as $provider) {
                        $platforms[] = $provider['provider_name'];
                    }
                }
                
                // 2. Free with ads
                if (isset($frData['ads'])) {
                    foreach ($frData['ads'] as $provider) {
                        $name = $provider['provider_name'];
                        if (!in_array($name, $platforms)) {
                            $platforms[] = $name . ' (gratuit)';
                        }
                    }
                }
                
                // 3. If no streaming, check rent/buy (VOD)
                if (empty($platforms)) {
                    $hasRentOrBuy = !empty($frData['rent']) || !empty($frData['buy']);
                    if ($hasRentOrBuy) {
                        $platforms[] = 'VOD (payant)';
                    }
                }
                
                // 4. Verification finale des plateformes et de la date de sortie
                $isReleased = $movie->getReleaseDate() && $movie->getReleaseDate() <= new \DateTime();
                
                // On rejette le film s'il n'est pas encore sorti OU s'il n'a aucune plateforme VOD/Streaming
                if (!$isReleased || empty($platforms)) {
                    // Si le film existait déjà, on le supprime
                    if ($movie->getId()) {
                        $this->entityManager->remove($movie);
                    }
                    continue; // On passe au film suivant sans l'enregistrer
                }
                
                $movie->setPlatforms($platforms);

                // Fetch Trailer (Priority: FR Trailer > EN Trailer > Any Trailer > FR Teaser > EN Teaser > Any Teaser > Any video)
                $videos = $this->tmdbService->getVideos($tmdbId);
                $frTrailer = null;
                $enTrailer = null;
                $anyTrailer = null;
                $frTeaser = null;
                $enTeaser = null;
                $anyTeaser = null;
                $anyVideo = null;
                
                foreach ($videos as $video) {
                    if ($video['site'] !== 'YouTube') continue;
                    
                    $lang = $video['iso_639_1'] ?? '';
                    $type = $video['type'] ?? '';
                    
                    if ($type === 'Trailer') {
                        if ($lang === 'fr' && !$frTrailer) $frTrailer = $video['key'];
                        elseif ($lang === 'en' && !$enTrailer) $enTrailer = $video['key'];
                        elseif (!$anyTrailer) $anyTrailer = $video['key'];
                    } elseif ($type === 'Teaser') {
                        if ($lang === 'fr' && !$frTeaser) $frTeaser = $video['key'];
                        elseif ($lang === 'en' && !$enTeaser) $enTeaser = $video['key'];
                        elseif (!$anyTeaser) $anyTeaser = $video['key'];
                    } elseif (!$anyVideo) {
                        $anyVideo = $video['key'];
                    }
                }
                
                $movie->setTrailerKey($frTrailer ?? $enTrailer ?? $anyTrailer ?? $frTeaser ?? $enTeaser ?? $anyTeaser ?? $anyVideo);

                $this->entityManager->persist($movie);
                $importedCount++;
            }

            // Flush every page
            $this->entityManager->flush();
            $io->progressAdvance();
        }

        $io->newLine();
        $io->success("Successfully imported/updated $importedCount movies!");

        return Command::SUCCESS;
    }
}
