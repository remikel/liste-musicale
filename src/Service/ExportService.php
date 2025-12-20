<?php

namespace App\Service;

use App\Entity\Session;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ExportService
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private SpotifyService $spotifyService
    ) {
    }

    public function exportToSpotify(Session $session, string $accessToken): array
    {
        $tracks = $session->getTracks();
        $trackUris = [];

        foreach ($tracks as $track) {
            // Utilise l'URI Spotify si déjà stocké, sinon recherche le titre
            if ($track->getSpotifyUri()) {
                $trackUris[] = $track->getSpotifyUri();
            } else {
                $searchQuery = $track->getArtist() . ' ' . $track->getTitle();
                $searchResults = $this->spotifyService->searchTracks($searchQuery);

                if (!empty($searchResults) && isset($searchResults[0]['uri'])) {
                    $trackUris[] = $searchResults[0]['uri'];
                }
            }
        }

        if (empty($trackUris)) {
            return ['success' => false, 'error' => 'Aucun titre trouvé sur Spotify'];
        }

        // Utilise SpotifyService pour créer la playlist
        return $this->spotifyService->createPlaylist(
            $accessToken,
            $session->getName(),
            $trackUris
        );
    }

    public function exportToYouTubeMusic(Session $session, string $accessToken): array
    {
        $tracks = $session->getTracks();
        $videoIds = [];

        foreach ($tracks as $track) {
            $searchQuery = urlencode($track->getArtist() . ' ' . $track->getTitle());

            try {
                $response = $this->httpClient->request('GET', 'https://www.googleapis.com/youtube/v3/search', [
                    'query' => [
                        'part' => 'snippet',
                        'q' => $searchQuery,
                        'type' => 'video',
                        'videoCategoryId' => '10',
                        'maxResults' => 1
                    ],
                    'headers' => [
                        'Authorization' => 'Bearer ' . $accessToken
                    ]
                ]);

                $data = $response->toArray();
                if (isset($data['items'][0]['id']['videoId'])) {
                    $videoIds[] = $data['items'][0]['id']['videoId'];
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (empty($videoIds)) {
            return ['success' => false, 'error' => 'Aucun titre trouvé sur YouTube Music'];
        }

        try {
            $playlistResponse = $this->httpClient->request('POST', 'https://www.googleapis.com/youtube/v3/playlists', [
                'query' => [
                    'part' => 'snippet,status'
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json'
                ],
                'json' => [
                    'snippet' => [
                        'title' => $session->getName(),
                        'description' => 'Playlist créée depuis l\'app collaborative'
                    ],
                    'status' => [
                        'privacyStatus' => 'private'
                    ]
                ]
            ]);
            $playlistData = $playlistResponse->toArray();
            $playlistId = $playlistData['id'];

            foreach ($videoIds as $videoId) {
                try {
                    $this->httpClient->request('POST', 'https://www.googleapis.com/youtube/v3/playlistItems', [
                        'query' => [
                            'part' => 'snippet'
                        ],
                        'headers' => [
                            'Authorization' => 'Bearer ' . $accessToken,
                            'Content-Type' => 'application/json'
                        ],
                        'json' => [
                            'snippet' => [
                                'playlistId' => $playlistId,
                                'resourceId' => [
                                    'kind' => 'youtube#video',
                                    'videoId' => $videoId
                                ]
                            ]
                        ]
                    ]);
                } catch (\Exception $e) {
                    continue;
                }
            }

            return [
                'success' => true,
                'playlist_url' => 'https://music.youtube.com/playlist?list=' . $playlistId,
                'tracks_added' => count($videoIds)
            ];

        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function exportToQobuz(Session $session, string $accessToken): array
    {
        return [
            'success' => false,
            'error' => 'L\'export vers Qobuz nécessite une implémentation spécifique avec leur API. Contactez le support Qobuz pour obtenir les détails de l\'API.'
        ];
    }
}
