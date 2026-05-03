<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/movies')]
class MovieController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'api_movies_list', methods: ['GET'])]
    public function list(MovieRepository $movieRepository): JsonResponse
    {
        // Fetch up to 200 movies
        $movies = $movieRepository->findBy([], ['releaseDate' => 'DESC'], 200);

        $data = [];
        foreach ($movies as $movie) {
            // Light version for list (no cast, no platforms, no heavy description)
            $data[] = $this->serializeMovie($movie, false);
        }

        return $this->json($data);
    }
    
    #[Route('/{id}', name: 'api_movies_show', methods: ['GET'])]
    public function show(string $id, MovieRepository $movieRepository): JsonResponse
    {
        // Try by ID (if numeric)
        $movie = null;
        if (is_numeric($id)) {
            $movie = $movieRepository->find((int)$id);
            
            // Also try by TMDB ID via same method if not found
             if (!$movie) {
                 $movie = $movieRepository->findOneBy(['tmdbId' => (int)$id]);
            }
        }
        
        if (!$movie) {
            return $this->json(['message' => 'Movie not found'], 404);
        }

        // Full version for detail view
        return $this->json($this->serializeMovie($movie, true));
    }

    private function serializeMovie(Movie $movie, bool $full = true): array
    {
        $data = [
            'id' => (string)$movie->getId(), // DB ID
            'tmdbId' => $movie->getTmdbId(),
            'title' => $movie->getTitle(),
            'year' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y') : null,
            'releaseDate' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y-m-d') : null,
            'rating' => $movie->getRating(),
            'imageUrl' => $movie->getPoster() ? 'https://image.tmdb.org/t/p/w500' . $movie->getPoster() : null,
            'genres' => $movie->getGenres(),
            'category' => $movie->getGenres()[0] ?? 'Unknown', // Frontend expects single category?
            'duration' => $movie->getRuntime(), // Real duration from DB, or null
        ];

        if ($full) {
            $data['description'] = $movie->getDescription();
            $data['backdropUrl'] = $movie->getBackdrop() ? 'https://image.tmdb.org/t/p/original' . $movie->getBackdrop() : null;
            $data['director'] = $movie->getDirector();
            $data['cast'] = $movie->getCast();
            $data['availableOn'] = $movie->getPlatforms();
        }

        return $data;
    }
}
