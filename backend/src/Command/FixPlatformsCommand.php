<?php

namespace App\Command;

use App\Entity\Movie;
use App\Service\TmdbService;
use App\Service\StreamingAvailabilityService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-platforms',
    description: 'Fix missing platforms using TMDB + Streaming Availability API fallback',
)]
class FixPlatformsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TmdbService $tmdbService,
        private StreamingAvailabilityService $streamingService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $conn = $this->entityManager->getConnection();
        $rows = $conn->executeQuery("SELECT id FROM movie WHERE JSON_LENGTH(platforms) = 0 OR platforms = '[]'")->fetchAllAssociative();
        $movieIds = array_column($rows, 'id');
        
        $repository = $this->entityManager->getRepository(Movie::class);
        $movies = [];
        foreach ($movieIds as $id) {
            $movie = $repository->find($id);
            if ($movie) $movies[] = $movie;
        }

        $io->title('Fixing ' . count($movies) . ' movies with empty platforms...');
        
        $io->progressStart(count($movies));
        
        $fixedTmdb = 0;
        $fixedStreaming = 0;
        
        foreach ($movies as $movie) {
            $tmdbId = $movie->getTmdbId();
            $platforms = [];
            
            // === STEP 1: Try TMDB/JustWatch (free, unlimited) — multiple regions ===
            $providers = $this->tmdbService->getWatchProviders($tmdbId);
            $regionsToTry = ['FR', 'US', 'GB', 'DE', 'ES', 'IT', 'CA', 'BE', 'CH'];
            
            foreach ($regionsToTry as $region) {
                $regionData = $providers[$region] ?? [];
                
                if (isset($regionData['flatrate'])) {
                    foreach ($regionData['flatrate'] as $provider) {
                        $name = $provider['provider_name'];
                        if (!in_array($name, $platforms)) {
                            $platforms[] = $name;
                        }
                    }
                }
                if (isset($regionData['ads'])) {
                    foreach ($regionData['ads'] as $provider) {
                        $name = $provider['provider_name'];
                        if (!in_array($name, $platforms) && !in_array($name . ' (gratuit)', $platforms)) {
                            $platforms[] = $name . ' (gratuit)';
                        }
                    }
                }
                
                // If we found streaming platforms, stop checking other regions
                if (!empty($platforms)) break;
                
                // Check rent/buy only if nothing found yet
                if (!empty($regionData['rent']) || !empty($regionData['buy'])) {
                    $platforms[] = 'VOD (payant)';
                    break;
                }
            }
            
            if (!empty($platforms)) {
                $fixedTmdb++;
            }
            
            // === STEP 2: If TMDB returned nothing, try Streaming Availability API (fallback) ===
            if (empty($platforms)) {
                $streamingPlatforms = $this->streamingService->getStreamingByTmdbId($tmdbId);
                if (!empty($streamingPlatforms)) {
                    $platforms = $streamingPlatforms;
                    $fixedStreaming++;
                }
            }
            
            // === STEP 3: Intelligent guess from Production Companies (Streaming Exclusives) ===
            if (empty($platforms)) {
                $details = $this->tmdbService->getMovieDetails($tmdbId);
                if (!empty($details['production_companies'])) {
                    foreach ($details['production_companies'] as $company) {
                        $name = strtolower($company['name']);
                        if (str_contains($name, 'marvel') || str_contains($name, 'disney') || str_contains($name, 'pixar') || str_contains($name, 'lucasfilm') || str_contains($name, '20th century')) {
                            $platforms[] = 'Disney+';
                            break;
                        } elseif (str_contains($name, 'netflix')) {
                            $platforms[] = 'Netflix';
                            break;
                        } elseif (str_contains($name, 'amazon')) {
                            $platforms[] = 'Amazon Prime Video';
                            break;
                        } elseif (str_contains($name, 'apple')) {
                            $platforms[] = 'Apple TV+';
                            break;
                        }
                    }
                }
            }

            if (!empty($platforms)) {
                $movie->setPlatforms($platforms);
                $this->entityManager->persist($movie);
            }
            
            if (($fixedTmdb + $fixedStreaming) > 0 && ($fixedTmdb + $fixedStreaming) % 20 === 0) {
                $this->entityManager->flush();
            }
            
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();
        
        $total = $fixedTmdb + $fixedStreaming;
        $io->section('Results');
        $io->text("Fixed via TMDB/JustWatch: $fixedTmdb");
        $io->text("Fixed via Streaming Availability API: $fixedStreaming");
        $io->success("Total: $total movies fixed!");

        return Command::SUCCESS;
    }
}
