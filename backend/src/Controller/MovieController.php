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
        $cacheFile = sys_get_temp_dir() . '/cinemate_movies_list_v2.json';
        $cacheTtl = 3600; // 1 hour (3600 seconds)
        $jsonContent = null;

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
            $jsonContent = file_get_contents($cacheFile);
        }

        if (!$jsonContent) {
            // Using optimized repository method to avoid N+1 queries
            $movies = $movieRepository->findMoviesWithDetails(200);

            if (empty($movies)) {
                return $this->json([]);
            }

            $data = [];
            foreach ($movies as $movie) {
                $genres = [];
                foreach ($movie->getGenres() as $g) {
                    $genres[] = $g->getGenreName();
                }

                $castNames = [];
                foreach ($movie->getMovieCastings() as $mc) {
                    $castNames[] = $mc->getCasting()->getName();
                }

                $data[] = [
                    'id' => (string)$movie->getId(),
                    'tmdbId' => $movie->getTmdbId(),
                    'title' => $movie->getTitle(),
                    'year' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y') : null,
                    'releaseDate' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y-m-d') : null,
                    'rating' => (float)$movie->getRating(),
                    'imageUrl' => $movie->getPoster() ? 'https://image.tmdb.org/t/p/w500' . $movie->getPoster() : null,
                    'genres' => $genres,
                    'category' => $genres[0] ?? 'Unknown',
                    'duration' => $movie->getRuntime(),
                    'director' => $movie->getDirector(),
                    'castNames' => $castNames,
                ];
            }

            $jsonContent = json_encode($data);
            file_put_contents($cacheFile, $jsonContent);
        }

        $etag = '"' . md5($jsonContent) . '"';

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
        $movie = null;
        if (is_numeric($id)) {
            $movie = $movieRepository->find((int)$id);
            
             if (!$movie) {
                 $movie = $movieRepository->findOneBy(['tmdbId' => (int)$id]);
            }
        }
        
        if (!$movie) {
            return $this->json(['message' => 'Movie not found'], 404);
        }

        return $this->json($this->serializeMovie($movie, true));
    }

    private function serializeMovie(Movie $movie, bool $full = true): array
    {
        $genres = [];
        foreach ($movie->getGenres() as $g) {
            $genres[] = $g->getGenreName();
        }

        $platforms = [];
        foreach ($movie->getPlatforms() as $p) {
            $platforms[] = $p->getPlatformName();
        }

        $cast = [];
        foreach ($movie->getMovieCastings() as $mc) {
            $profilePath = $mc->getCasting()->getProfilePath();
            $cast[] = [
                'name' => $mc->getCasting()->getName(),
                'imageUrl' => $profilePath ? 'https://image.tmdb.org/t/p/w200' . $profilePath : null,
                'role' => $mc->getCharacterName(),
                'order' => $mc->getCastOrder()
            ];
        }

        $data = [
            'id' => (string)$movie->getId(),
            'tmdbId' => $movie->getTmdbId(),
            'title' => $movie->getTitle(),
            'year' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y') : null,
            'releaseDate' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y-m-d') : null,
            'rating' => $movie->getRating(),
            'imageUrl' => $movie->getPoster() ? 'https://image.tmdb.org/t/p/w500' . $movie->getPoster() : null,
            'genres' => $genres,
            'category' => $genres[0] ?? 'Unknown',
            'duration' => $movie->getRuntime(),
        ];

        if ($full) {
            $data['description'] = $movie->getDescription();
            $data['backdropUrl'] = $movie->getBackdrop() ? 'https://image.tmdb.org/t/p/original' . $movie->getBackdrop() : null;
            $data['director'] = $movie->getDirector();
            $data['cast'] = $cast;
            $data['availableOn'] = $platforms;
            $data['trailerKey'] = $movie->getTrailerKey();
        }

        return $data;
    }

    #[Route('/recommendations/for-you', name: 'api_movies_recommendations', methods: ['GET'])]
    public function getRecommendations(EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if (!$user) {
            return $this->json(['message' => 'Non authentifié'], 401);
        }

        $favoriteGenres = [];
        foreach ($user->getFavoriteGenres() as $g) {
            $favoriteGenres[] = $g->getGenreName();
        }

        $platforms = [];
        foreach ($user->getPlatforms() as $p) {
            $platforms[] = $p->getPlatformName();
        }

        $watchlistIds = [];
        foreach ($user->getWatchlists() as $w) {
            $watchlistIds[] = (string)$w->getMovie()->getId();
        }

        $repository = $entityManager->getRepository(Movie::class);
        $watchlistMovies = [];
        if (!empty($watchlistIds)) {
            $watchlistMovies = $repository->findBy(['id' => $watchlistIds]);
        }
        
        $implicitGenresCount = [];
        foreach ($watchlistMovies as $wm) {
            foreach ($wm->getGenres() as $genre) {
                $genreName = $genre->getGenreName();
                $implicitGenresCount[$genreName] = ($implicitGenresCount[$genreName] ?? 0) + 1;
            }
        }
        
        arsort($implicitGenresCount);
        $implicitGenres = array_slice(array_keys($implicitGenresCount), 0, 3);

        $allMovies = $repository->findAll();
        
        $scoredMovies = [];
        foreach ($allMovies as $movie) {
            if (in_array((string)$movie->getId(), $watchlistIds)) {
                continue; 
            }

            $score = 0;
            $movieGenres = [];
            foreach ($movie->getGenres() as $g) {
                $movieGenres[] = $g->getGenreName();
            }
            
            $moviePlatforms = [];
            foreach ($movie->getPlatforms() as $p) {
                $moviePlatforms[] = $p->getPlatformName();
            }

            foreach ($favoriteGenres as $fg) {
                if (in_array($fg, $movieGenres)) {
                    $score += 3;
                }
            }

            foreach ($implicitGenres as $ig) {
                if (in_array($ig, $movieGenres)) {
                    $score += 2;
                }
            }

            $platformMatch = false;
            foreach ($platforms as $up) {
                foreach ($moviePlatforms as $mp) {
                    if (stripos($mp, $up) !== false) {
                        $platformMatch = true;
                        break 2;
                    }
                }
            }
            if ($platformMatch) {
                $score += 5;
            }

            if ($movie->getRating()) {
                $score += ($movie->getRating() / 10);
            }

            if ($score >= 2) {
                // On ajoute un poids aléatoire entre 0.0 et 1.5 pour mélanger
                // les films qui ont une pertinence similaire à chaque rechargement.
                $randomWeight = mt_rand(0, 150) / 100;
                
                $scoredMovies[] = [
                    'movie' => $movie,
                    'score' => $score + $randomWeight
                ];
            }
        }

        usort($scoredMovies, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        $topMovies = array_slice($scoredMovies, 0, 100);

        $data = [];
        foreach ($topMovies as $item) {
            /** @var Movie $movie */
            $movie = $item['movie'];
            
            $castNames = [];
            foreach ($movie->getMovieCastings() as $mc) {
                $castNames[] = $mc->getCasting()->getName();
            }
            
            $genres = [];
            foreach ($movie->getGenres() as $g) {
                $genres[] = $g->getGenreName();
            }

            $data[] = [
                'id' => (string)$movie->getId(),
                'tmdbId' => $movie->getTmdbId(),
                'title' => $movie->getTitle(),
                'year' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y') : null,
                'releaseDate' => $movie->getReleaseDate() ? $movie->getReleaseDate()->format('Y-m-d') : null,
                'rating' => (float)$movie->getRating(),
                'imageUrl' => $movie->getPoster() ? 'https://image.tmdb.org/t/p/w500' . $movie->getPoster() : null,
                'genres' => $genres,
                'category' => $genres[0] ?? 'Unknown',
                'duration' => $movie->getRuntime(),
                'director' => $movie->getDirector(),
                'castNames' => $castNames,
                'score' => $item['score']
            ];
        }

        return $this->json($data);
    }
}
