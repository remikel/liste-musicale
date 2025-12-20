<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpotifyService
{
    private const API_BASE_URL = 'https://api.spotify.com/v1';
    private const AUTH_URL = 'https://accounts.spotify.com/api/token';

    private ?string $accessToken = null;
    private ?\DateTimeImmutable $tokenExpiry = null;

    public function __construct(
        private HttpClientInterface $httpClient,
        private string $clientId,
        private string $clientSecret
    ) {
    }

    /**
     * Obtient un token d'accès via Client Credentials Flow
     */
    private function getAccessToken(): string
    {
        // Vérifie si le token est encore valide
        if ($this->accessToken && $this->tokenExpiry && $this->tokenExpiry > new \DateTimeImmutable()) {
            return $this->accessToken;
        }

        try {
            $response = $this->httpClient->request('POST', self::AUTH_URL, [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
                ],
                'body' => [
                    'grant_type' => 'client_credentials'
                ]
            ]);

            $data = $response->toArray();
            $this->accessToken = $data['access_token'];

            // Le token expire généralement après 3600 secondes, on prend une marge de 60 secondes
            $expiresIn = $data['expires_in'] ?? 3600;
            $this->tokenExpiry = (new \DateTimeImmutable())->modify('+' . ($expiresIn - 60) . ' seconds');

            return $this->accessToken;

        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible d\'obtenir le token Spotify: ' . $e->getMessage());
        }
    }

    /**
     * Recherche des titres sur Spotify
     */
    public function searchTracks(string $query): array
    {
        if (empty($query)) {
            return [];
        }

        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/search', [
                'query' => [
                    'q' => $query,
                    'type' => 'track',
                    'limit' => 20
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $data = $response->toArray();

            if (!isset($data['tracks']['items'])) {
                return [];
            }

            return array_map(function ($track) {
                return [
                    'id' => $track['id'] ?? null,
                    'title' => $track['name'] ?? '',
                    'artist' => $track['artists'][0]['name'] ?? '',
                    'album' => $track['album']['name'] ?? '',
                    'duration' => isset($track['duration_ms']) ? (int)($track['duration_ms'] / 1000) : 0,
                    'cover' => $track['album']['images'][1]['url'] ?? ($track['album']['images'][0]['url'] ?? null),
                    'preview' => $track['preview_url'] ?? null,
                    'uri' => $track['uri'] ?? null,
                ];
            }, $data['tracks']['items']);

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Récupère les informations d'un titre Spotify
     */
    public function getTrack(string $trackId): ?array
    {
        try {
            $token = $this->getAccessToken();

            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/tracks/' . $trackId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token
                ]
            ]);

            $track = $response->toArray();

            return [
                'id' => $track['id'] ?? null,
                'title' => $track['name'] ?? '',
                'artist' => $track['artists'][0]['name'] ?? '',
                'album' => $track['album']['name'] ?? '',
                'duration' => isset($track['duration_ms']) ? (int)($track['duration_ms'] / 1000) : 0,
                'cover' => $track['album']['images'][1]['url'] ?? ($track['album']['images'][0]['url'] ?? null),
                'uri' => $track['uri'] ?? null,
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Récupère les playlists de l'utilisateur (nécessite un token utilisateur)
     */
    public function getUserPlaylists(string $userAccessToken): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_BASE_URL . '/me/playlists', [
                'query' => [
                    'limit' => 50
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $userAccessToken
                ]
            ]);

            $data = $response->toArray();

            if (!isset($data['items'])) {
                return [];
            }

            return array_map(function ($playlist) {
                return [
                    'id' => $playlist['id'],
                    'name' => $playlist['name'],
                    'description' => $playlist['description'] ?? '',
                    'tracks_total' => $playlist['tracks']['total'] ?? 0,
                    'image' => $playlist['images'][0]['url'] ?? null,
                    'url' => $playlist['external_urls']['spotify'] ?? null,
                ];
            }, $data['items']);

        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible de récupérer les playlists: ' . $e->getMessage());
        }
    }

    /**
     * Récupère les titres d'une playlist Spotify
     */
    public function getPlaylistTracks(string $playlistId, string $userAccessToken): array
    {
        try {
            $allTracks = [];
            $offset = 0;
            $limit = 100;

            do {
                $response = $this->httpClient->request('GET', self::API_BASE_URL . '/playlists/' . $playlistId . '/tracks', [
                    'query' => [
                        'limit' => $limit,
                        'offset' => $offset,
                        'fields' => 'items(track(id,name,artists,album,duration_ms,uri)),next'
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $userAccessToken
                    ]
                ]);

                $data = $response->toArray();

                if (isset($data['items'])) {
                    foreach ($data['items'] as $item) {
                        if (isset($item['track']) && $item['track']) {
                            $track = $item['track'];
                            $allTracks[] = [
                                'id' => $track['id'] ?? null,
                                'title' => $track['name'] ?? '',
                                'artist' => $track['artists'][0]['name'] ?? '',
                                'album' => $track['album']['name'] ?? '',
                                'duration' => isset($track['duration_ms']) ? (int)($track['duration_ms'] / 1000) : 0,
                                'cover' => $track['album']['images'][1]['url'] ?? ($track['album']['images'][0]['url'] ?? null),
                                'uri' => $track['uri'] ?? null,
                            ];
                        }
                    }
                }

                $offset += $limit;
                $hasMore = isset($data['next']) && $data['next'] !== null;

            } while ($hasMore);

            return $allTracks;

        } catch (\Exception $e) {
            throw new \RuntimeException('Impossible de récupérer les titres de la playlist: ' . $e->getMessage());
        }
    }

    /**
     * Crée une playlist et y ajoute des titres (nécessite un token utilisateur)
     */
    public function createPlaylist(string $userAccessToken, string $name, array $trackUris, string $description = ''): array
    {
        try {
            // Récupère l'ID de l'utilisateur
            $userResponse = $this->httpClient->request('GET', self::API_BASE_URL . '/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $userAccessToken
                ]
            ]);
            $userData = $userResponse->toArray();
            $userId = $userData['id'];

            // Crée la playlist
            $playlistResponse = $this->httpClient->request('POST', self::API_BASE_URL . '/users/' . $userId . '/playlists', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $userAccessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'name' => $name,
                    'description' => $description ?: 'Playlist créée depuis l\'app collaborative',
                    'public' => false
                ]
            ]);
            $playlistData = $playlistResponse->toArray();
            $playlistId = $playlistData['id'];

            // Ajoute les titres par lots de 100 (limite Spotify)
            $chunks = array_chunk($trackUris, 100);
            foreach ($chunks as $chunk) {
                $this->httpClient->request('POST', self::API_BASE_URL . '/playlists/' . $playlistId . '/tracks', [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $userAccessToken,
                        'Content-Type' => 'application/json'
                    ],
                    'json' => [
                        'uris' => $chunk
                    ]
                ]);
            }

            return [
                'success' => true,
                'playlist_id' => $playlistId,
                'playlist_url' => $playlistData['external_urls']['spotify'] ?? null,
                'tracks_added' => count($trackUris)
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
