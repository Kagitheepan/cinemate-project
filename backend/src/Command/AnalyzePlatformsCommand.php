<?php

namespace App\Command;

use App\Entity\Movie;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:analyze-platforms',
    description: 'Analyze TMDB watch provider data for all movies to understand coverage',
)]
class AnalyzePlatformsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TmdbService $tmdbService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $repository = $this->entityManager->getRepository(Movie::class);
        $movies = $repository->findAll();

        $io->title('Analyzing ' . count($movies) . ' movies for platform coverage...');
        
        $noPlatform = [];       // Currently empty in DB
        $hasOnlyFlatrate = 0;
        $hasRent = 0;
        $hasBuy = 0;
        $hasAds = 0;
        $inTheaters = 0;
        $tmdbEmpty = [];        // TMDB returns nothing at all for FR
        $couldBeFixed = [];     // Has rent/buy/ads but we missed it
        
        $io->progressStart(count($movies));
        
        foreach ($movies as $movie) {
            $currentPlatforms = $movie->getPlatforms();
            $tmdbId = $movie->getTmdbId();
            $releaseDate = $movie->getReleaseDate();
            
            // Check if movie is currently in theaters (released < 3 months ago)
            $isInTheaters = false;
            if ($releaseDate) {
                $daysSinceRelease = (new \DateTime())->diff($releaseDate)->days;
                $isInTheaters = $daysSinceRelease <= 90 && $releaseDate <= new \DateTime();
            }
            
            if ($isInTheaters) {
                $inTheaters++;
            }
            
            if (count($currentPlatforms) === 0) {
                // This movie has no platform in our DB, let's check TMDB
                $providers = $this->tmdbService->getWatchProviders($tmdbId);
                $frData = $providers['FR'] ?? [];
                
                $info = [
                    'title' => $movie->getTitle(),
                    'tmdbId' => $tmdbId,
                    'releaseDate' => $releaseDate ? $releaseDate->format('Y-m-d') : 'N/A',
                    'inTheaters' => $isInTheaters,
                    'flatrate' => [],
                    'rent' => [],
                    'buy' => [],
                    'ads' => [],
                ];
                
                if (isset($frData['flatrate'])) {
                    foreach ($frData['flatrate'] as $p) $info['flatrate'][] = $p['provider_name'];
                    $hasOnlyFlatrate++;
                }
                if (isset($frData['rent'])) {
                    foreach ($frData['rent'] as $p) $info['rent'][] = $p['provider_name'];
                    $hasRent++;
                }
                if (isset($frData['buy'])) {
                    foreach ($frData['buy'] as $p) $info['buy'][] = $p['provider_name'];
                    $hasBuy++;
                }
                if (isset($frData['ads'])) {
                    foreach ($frData['ads'] as $p) $info['ads'][] = $p['provider_name'];
                    $hasAds++;
                }
                
                if (empty($info['flatrate']) && empty($info['rent']) && empty($info['buy']) && empty($info['ads'])) {
                    $tmdbEmpty[] = $info;
                } else {
                    $couldBeFixed[] = $info;
                }
                
                $noPlatform[] = $info;
            }
            
            $io->progressAdvance();
        }
        
        $io->progressFinish();
        
        $io->section('RESULTS');
        $io->text("Total movies: " . count($movies));
        $io->text("Movies with platforms in DB: " . (count($movies) - count($noPlatform)));
        $io->text("Movies WITHOUT platforms in DB: " . count($noPlatform));
        $io->text("---");
        $io->text("Currently in theaters (< 90 days): " . $inTheaters);
        $io->text("---");
        $io->text("Among movies without platforms:");
        $io->text("  - Have flatrate on TMDB (BUG - should have been imported): " . $hasOnlyFlatrate);
        $io->text("  - Have rent on TMDB: " . $hasRent);
        $io->text("  - Have buy on TMDB: " . $hasBuy);
        $io->text("  - Have ads (free) on TMDB: " . $hasAds);
        $io->text("  - TMDB returns nothing for FR: " . count($tmdbEmpty));
        $io->text("  - Could be fixed (have rent/buy/ads): " . count($couldBeFixed));
        
        if (!empty($couldBeFixed)) {
            $io->section('FIXABLE MOVIES (have rent/buy/ads data on TMDB)');
            foreach ($couldBeFixed as $m) {
                $io->text(sprintf(
                    "  [%s] %s | flatrate: [%s] | rent: [%s] | buy: [%s] | ads: [%s] | theaters: %s",
                    $m['releaseDate'],
                    $m['title'],
                    implode(', ', $m['flatrate']),
                    implode(', ', $m['rent']),
                    implode(', ', $m['buy']),
                    implode(', ', $m['ads']),
                    $m['inTheaters'] ? 'YES' : 'no',
                ));
            }
        }
        
        if (!empty($tmdbEmpty)) {
            $io->section('MOVIES WITH NO DATA AT ALL ON TMDB FR');
            foreach ($tmdbEmpty as $m) {
                $io->text(sprintf("  [%s] %s (theaters: %s)", $m['releaseDate'], $m['title'], $m['inTheaters'] ? 'YES' : 'no'));
            }
        }

        return Command::SUCCESS;
    }
}
