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
    name: 'app:fix-trailers',
    description: 'Fix missing trailers by querying with broad language constraints',
)]
class FixTrailersCommand extends Command
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
        $movies = $repository->findAll(); // Récupère tous les films pour forcer la mise à jour en FR

        $io->title('Fixing ' . count($movies) . ' movies missing a trailer...');
        
        $io->progressStart(count($movies));
        
        $fixed = 0;
        foreach ($movies as $movie) {
            $tmdbId = $movie->getTmdbId();
            
            $videos = $this->tmdbService->getVideos($tmdbId);
            $trailerKey = null;
            
            // Priority buckets: FR Trailer > EN Trailer > Any Trailer > FR Teaser > EN Teaser > Any Teaser > Any video
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
            
            $trailerKey = $frTrailer ?? $enTrailer ?? $anyTrailer ?? $frTeaser ?? $enTeaser ?? $anyTeaser ?? $anyVideo;
            
            if ($trailerKey) {
                $movie->setTrailerKey($trailerKey);
                $this->entityManager->persist($movie);
                $fixed++;
            }
            
            if ($fixed > 0 && $fixed % 20 === 0) {
                $this->entityManager->flush();
            }
            $io->progressAdvance();
        }

        $this->entityManager->flush();
        $io->progressFinish();
        $io->success("Successfully fixed $fixed trailers!");

        return Command::SUCCESS;
    }
}
