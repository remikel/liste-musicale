<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeezerService
{
    private const API_BASE_URL = 'https://api.deezer.com';

    public function __construct(
        private HttpClientInterface $httpClient
    ) {
    }

    public function searchTracks(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/search', [
                'query' => [
                    'q' => $query,
                    'limit' => 20
                ]
            ]);

            $data = $response->toArray();

            if (!isset($data['data'])) {
                return [];
            }

            return array_map(function ($track) {
                return [
                    'id' => $track['id'] ?? null,
                    'title' => $track['title'] ?? '',
                    'artist' => $track['artist']['name'] ?? '',
                    'album' => $track['album']['title'] ?? '',
                    'duration' => $track['duration'] ?? 0,
                    'cover' => $track['album']['cover_medium'] ?? null,
                    'preview' => $track['preview'] ?? null,
                ];
            }, $data['data']);

        } catch (\Exception $e) {
            return [];
        }
    }

    public function getTrack(string $trackId): ?array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/track/' . $trackId);
            $data = $response->toArray();

            return [
                'id' => $data['id'] ?? null,
                'title' => $data['title'] ?? '',
                'artist' => $data['artist']['name'] ?? '',
                'album' => $data['album']['title'] ?? '',
                'duration' => $data['duration'] ?? 0,
                'cover' => $data['album']['cover_medium'] ?? null,
            ];

        } catch (\Exception $e) {
            return null;
        }
    }
}
