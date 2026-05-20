<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class StreamingAvailabilityService
{
    private const BASE_URL = 'https://api.movieofthenight.com/v4';

    // Countries to check in priority order
    private const COUNTRIES = ['fr', 'us', 'gb', 'de', 'es', 'it', 'ca', 'be', 'ch'];

    public function __construct(
        private HttpClientInterface $client,
        #[Autowire('%env(STREAMING_API_KEY)%')] private string $apiKey
    ) {}

    /**
     * Get streaming availability for a movie by TMDB ID.
     * Tries France first, then falls back to other major countries.
     * Returns an array of platform names.
     */
    public function getStreamingByTmdbId(int $tmdbId): array
    {
        if ($this->apiKey === 'YOUR_API_KEY_HERE' || empty($this->apiKey)) {
            return [];
        }

        try {
            // Single API call without country filter => returns ALL countries at once
            $response = $this->client->request('GET', self::BASE_URL . '/shows/movie/' . $tmdbId, [
                'headers' => [
                    'X-API-Key' => $this->apiKey,
                ],
            ]);

            $data = $response->toArray();
            $allStreamingOptions = $data['streamingOptions'] ?? [];

            if (empty($allStreamingOptions)) {
                return [];
            }

            // Try each country in priority order
            foreach (self::COUNTRIES as $country) {
                $options = $allStreamingOptions[$country] ?? [];
                if (empty($options)) continue;

                $platforms = $this->extractPlatforms($options, $country);
                if (!empty($platforms)) {
                    return $platforms;
                }
            }

            // Last resort: try ANY country that has data
            foreach ($allStreamingOptions as $countryCode => $options) {
                if (in_array($countryCode, self::COUNTRIES)) continue; // Already tried
                $platforms = $this->extractPlatforms($options, $countryCode);
                if (!empty($platforms)) {
                    return $platforms;
                }
            }

            return [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Extract platform names from streaming options.
     * If country is not 'fr', we won't append region info to keep it clean.
     */
    private function extractPlatforms(array $options, string $country): array
    {
        $platforms = [];

        foreach ($options as $option) {
            $serviceName = $option['service']['name'] ?? null;
            $type = $option['type'] ?? '';
            
            if (!$serviceName) continue;

            if ($type === 'subscription' || $type === 'free') {
                if (!in_array($serviceName, $platforms)) {
                    $platforms[] = $serviceName;
                }
            } elseif ($type === 'addon') {
                if (!in_array($serviceName, $platforms)) {
                    $platforms[] = $serviceName;
                }
            }
        }

        // If only rent/buy
        if (empty($platforms)) {
            foreach ($options as $option) {
                if (in_array($option['type'] ?? '', ['rent', 'buy'])) {
                    $platforms[] = 'VOD (payant)';
                    break;
                }
            }
        }

        return $platforms;
    }
}
