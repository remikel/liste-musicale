<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Session;
use App\Entity\Track;
use App\Service\ExportService;
use App\Service\SpotifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/export')]
class ExportController extends AbstractController
{
    #[Route('/spotify/{code}', name: 'app_export_spotify', methods: ['POST'])]
    public function spotify(
        string $code,
        Request $request,
        EntityManagerInterface $em,
        ExportService $exportService
    ): JsonResponse {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            return new JsonResponse(['error' => 'Token d\'accès requis.'], 400);
        }

        $result = $exportService->exportToSpotify($session, $accessToken);
        return new JsonResponse($result);
    }

    #[Route('/youtube/{code}', name: 'app_export_youtube', methods: ['POST'])]
    public function youtube(
        string $code,
        Request $request,
        EntityManagerInterface $em,
        ExportService $exportService
    ): JsonResponse {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            return new JsonResponse(['error' => 'Token d\'accès requis.'], 400);
        }

        $result = $exportService->exportToYouTubeMusic($session, $accessToken);
        return new JsonResponse($result);
    }

    #[Route('/qobuz/{code}', name: 'app_export_qobuz', methods: ['POST'])]
    public function qobuz(
        string $code,
        Request $request,
        EntityManagerInterface $em,
        ExportService $exportService
    ): JsonResponse {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable.'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            return new JsonResponse(['error' => 'Token d\'accès requis.'], 400);
        }

        $result = $exportService->exportToQobuz($session, $accessToken);
        return new JsonResponse($result);
    }

    #[Route('/spotify/playlists', name: 'app_get_spotify_playlists', methods: ['POST'])]
    public function getSpotifyPlaylists(
        Request $request,
        SpotifyService $spotifyService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $accessToken = $data['access_token'] ?? null;

        if (!$accessToken) {
            return new JsonResponse(['error' => 'Token d\'accès requis.'], 400);
        }

        try {
            $playlists = $spotifyService->getUserPlaylists($accessToken);
            return new JsonResponse(['success' => true, 'playlists' => $playlists]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    #[Route('/spotify/import/{code}', name: 'app_import_spotify', methods: ['POST'])]
    public function importFromSpotify(
        string $code,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage,
        SpotifyService $spotifyService
    ): JsonResponse {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable.'], 404);
        }

        $participantId = $sessionStorage->get('participant_id_' . $session->getId());
        $participant = null;

        if ($participantId) {
            $participant = $em->getRepository(Participant::class)->find($participantId);
        }

        if (!$participant || $participant->getSession() !== $session) {
            return new JsonResponse(['error' => 'Non autorisé.'], 403);
        }

        if ($participant->isValidated()) {
            return new JsonResponse(['error' => 'Playlist déjà validée.'], 400);
        }

        $data = json_decode($request->getContent(), true);
        $accessToken = $data['access_token'] ?? null;
        $playlistId = $data['playlist_id'] ?? null;

        if (!$accessToken || !$playlistId) {
            return new JsonResponse(['error' => 'Token d\'accès et ID de playlist requis.'], 400);
        }

        try {
            $tracks = $spotifyService->getPlaylistTracks($playlistId, $accessToken);

            $maxTracks = $session->getMaxTracksPerParticipant();
            $currentTrackCount = $participant->getTracks()->count();
            $addedCount = 0;

            foreach ($tracks as $trackData) {
                if ($maxTracks && ($currentTrackCount + $addedCount) >= $maxTracks) {
                    break;
                }

                $track = new Track();
                $track->setTitle($trackData['title']);
                $track->setArtist($trackData['artist']);
                $track->setAlbum($trackData['album']);
                $track->setDuration($trackData['duration']);
                $track->setSpotifyTrackId($trackData['id']);
                $track->setSpotifyUri($trackData['uri']);
                $track->setCoverUrl($trackData['cover']);
                $track->setSession($session);
                $track->setParticipant($participant);

                $em->persist($track);
                $addedCount++;
            }

            $em->flush();

            return new JsonResponse([
                'success' => true,
                'tracks_imported' => $addedCount,
                'tracks_total' => count($tracks)
            ]);

        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }
}
