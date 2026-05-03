<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class OmdbService
{
    private const BASE_URL = 'http://www.omdbapi.com/';

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(OMDB_API_KEY)%')] private string $apiKey
    ) {
    }

    /**
     * Fetch movie runtime from OMDB by title and optionally year.
     * Returns runtime in minutes as integer, or null if not found.
     */
    public function getRuntimeByTitle(string $title, ?string $year = null): ?int
    {
        try {
            $query = [
                'apikey' => $this->apiKey,
                't' => $title,
            ];

            if ($year) {
                $query['y'] = $year;
            }

            $response = $this->client->request('GET', self::BASE_URL, [
                'query' => $query
            ]);

            $data = $response->toArray();

            if (isset($data['Runtime']) && $data['Runtime'] !== 'N/A') {
                // Extract number from "148 min"
                preg_match('/\d+/', $data['Runtime'], $matches);
                return isset($matches[0]) ? (int) $matches[0] : null;
            }

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
