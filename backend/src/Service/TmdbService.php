<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(TMDB_API_KEY)%')] private string $apiKey
    ) {
    }

    public function getGenres(): array
    {
        $response = $this->client->request('GET', self::BASE_URL . '/genre/movie/list', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => 'fr-FR', // Fetch in French as requested "regrouper les films" context implies French
            ]
        ]);

        $data = $response->toArray();
        $genres = [];
        foreach ($data['genres'] as $genre) {
            $genres[$genre['id']] = $genre['name'];
        }

        return $genres;
    }

    public function getPopularMovies(int $page = 1): array
    {
        $response = $this->client->request('GET', self::BASE_URL . '/movie/popular', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => 'fr-FR',
                'page' => $page
            ]
        ]);

        return $response->toArray()['results'] ?? [];
    }

    public function discoverStreamingMovies(int $page = 1): array
    {
        // 7 dernières années
        $startDate = (new \DateTime('-7 years'))->format('Y-m-d');
        $endDate = (new \DateTime())->format('Y-m-d');

        $response = $this->client->request('GET', self::BASE_URL . '/discover/movie', [
            'query' => [
                'api_key' => $this->apiKey,
                'language' => 'fr-FR',
                'page' => $page,
                'sort_by' => 'popularity.desc',
                'primary_release_date.gte' => $startDate,
                'primary_release_date.lte' => $endDate,
                'watch_region' => 'FR',
                'with_watch_monetization_types' => 'flatrate|rent|buy|ads|free', // Seulement dispos en streaming/VOD
            ]
        ]);

        return $response->toArray()['results'] ?? [];
    }

    public function getMovieDetails(int $movieId): array
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL . '/movie/' . $movieId, [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                ]
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getCredits(int $movieId): array
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL . '/movie/' . $movieId . '/credits', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                ]
            ]);
            return $response->toArray();
        } catch (\Exception $e) {
            return ['cast' => [], 'crew' => []];
        }
    }
    
    public function getKeywords(int $movieId): array
    {
        try {
             $response = $this->client->request('GET', self::BASE_URL . '/movie/' . $movieId . '/keywords', [
                'query' => [
                    'api_key' => $this->apiKey,
                ]
            ]);
            return $response->toArray()['keywords'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
    public function getWatchProviders(int $movieId): array
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL . '/movie/' . $movieId . '/watch/providers', [
                'query' => [
                    'api_key' => $this->apiKey,
                ]
            ]);
            return $response->toArray()['results'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function getVideos(int $movieId): array
    {
        try {
            $response = $this->client->request('GET', self::BASE_URL . '/movie/' . $movieId . '/videos', [
                'query' => [
                    'api_key' => $this->apiKey,
                    'language' => 'fr-FR',
                    'include_video_language' => 'fr,en,null'
                ]
            ]);
            return $response->toArray()['results'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }
}
