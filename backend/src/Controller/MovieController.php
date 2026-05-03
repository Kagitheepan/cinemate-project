<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
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
    public function list(MovieRepository $movieRepository, Request $request): JsonResponse
    {
        // Server-side file cache to avoid re-querying DB on every request
        $cacheFile = sys_get_temp_dir() . '/cinemate_movies_list.json';
        $cacheTtl = 3600; // 1 hour (3600 seconds)
        $jsonContent = null;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
            $jsonContent = file_get_contents($cacheFile);
        }

        if (!$jsonContent) {
            // Use raw DBAL query for maximum performance, bypassing Doctrine ORM overhead
            $conn = $this->entityManager->getConnection();
            $sql = 'SELECT id, tmdb_id AS tmdbId, title, release_date AS releaseDate, rating, poster, genres, runtime FROM movie ORDER BY release_date DESC LIMIT 200';
            $stmt = $conn->prepare($sql);
            $resultSet = $stmt->executeQuery();
            $rows = $resultSet->fetchAllAssociative();

            $data = [];
            foreach ($rows as $row) {
                // Genres is stored as JSON string in DB, need to decode it
                $genres = is_string($row['genres']) ? json_decode($row['genres'], true) : $row['genres'];
                
                $data[] = [
                    'id' => (string)$row['id'],
                    'tmdbId' => $row['tmdbId'],
                    'title' => $row['title'],
                    'year' => $row['releaseDate'] ? substr($row['releaseDate'], 0, 4) : null,
                    'releaseDate' => $row['releaseDate'] ? substr($row['releaseDate'], 0, 10) : null,
                    'rating' => (float)$row['rating'],
                    'imageUrl' => $row['poster'] ? 'https://image.tmdb.org/t/p/w500' . $row['poster'] : null,
                    'genres' => $genres,
                    'category' => $genres[0] ?? 'Unknown',
                    'duration' => $row['runtime'],
                ];
            }

            $jsonContent = json_encode($data);
            file_put_contents($cacheFile, $jsonContent);
        }

        // Generate ETag from content hash for conditional requests
        $etag = '"' . md5($jsonContent) . '"';

        // Check If-None-Match header for conditional GET
        $ifNoneMatch = $request->headers->get('If-None-Match');
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return new JsonResponse(null, 304, [
                'ETag' => $etag,
                'Cache-Control' => 'public, max-age=60, stale-while-revalidate=300',
            ]);
        }

        $response = JsonResponse::fromJsonString($jsonContent);
        $response->headers->set('Cache-Control', 'public, max-age=60, stale-while-revalidate=300');
        $response->headers->set('ETag', $etag);

        return $response;
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
