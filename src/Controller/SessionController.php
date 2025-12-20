<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Session;
use App\Entity\Track;
use App\Service\SpotifyService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/session/{code}')]
class SessionController extends AbstractController
{
    #[Route('', name: 'app_join_session')]
    public function join(
        string $code,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
    ): Response {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            $this->addFlash('error', 'Session introuvable.');
            return $this->redirectToRoute('app_home');
        }

        $participantId = $sessionStorage->get('participant_id_' . $session->getId());
        $participant = null;

        if ($participantId) {
            $participant = $em->getRepository(Participant::class)->find($participantId);
        }

        if (!$participant) {
            return $this->render('session/join.html.twig', [
                'session' => $session,
            ]);
        }

        return $this->redirectToRoute('app_session_playlist', ['code' => $code]);
    }

    #[Route('/enter', name: 'app_session_enter', methods: ['POST'])]
    public function enter(
        string $code,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
    ): Response {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            $this->addFlash('error', 'Session introuvable.');
            return $this->redirectToRoute('app_home');
        }

        $name = $request->request->get('participant_name');

        if (empty($name)) {
            $this->addFlash('error', 'Le nom est requis.');
            return $this->redirectToRoute('app_join_session', ['code' => $code]);
        }

        $participant = new Participant();
        $participant->setName($name);
        $participant->setSession($session);

        $em->persist($participant);
        $em->flush();

        $sessionStorage->set('participant_id_' . $session->getId(), $participant->getId());

        return $this->redirectToRoute('app_session_playlist', ['code' => $code]);
    }

    #[Route('/playlist', name: 'app_session_playlist')]
    public function playlist(
        string $code,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
    ): Response {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            $this->addFlash('error', 'Session introuvable.');
            return $this->redirectToRoute('app_home');
        }

        $participantId = $sessionStorage->get('participant_id_' . $session->getId());
        $participant = null;

        if ($participantId) {
            $participant = $em->getRepository(Participant::class)->find($participantId);
        }

        if (!$participant || $participant->getSession() !== $session) {
            return $this->redirectToRoute('app_join_session', ['code' => $code]);
        }

        return $this->render('session/playlist.html.twig', [
            'session' => $session,
            'participant' => $participant,
        ]);
    }

    #[Route('/search', name: 'app_session_search', methods: ['GET'])]
    public function search(
        string $code,
        Request $request,
        SpotifyService $spotifyService,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
    ): JsonResponse {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable.'], 404);
        }

        $participantId = $sessionStorage->get('participant_id_' . $session->getId());
        if (!$participantId) {
            return new JsonResponse(['error' => 'Non autorisé.'], 403);
        }

        $query = $request->query->get('q', '');
        $results = $spotifyService->searchTracks($query);

        return new JsonResponse($results);
    }

    #[Route('/add-track', name: 'app_session_add_track', methods: ['POST'])]
    public function addTrack(
        string $code,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
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

        $maxTracks = $session->getMaxTracksPerParticipant();
        if ($maxTracks && $participant->getTracks()->count() >= $maxTracks) {
            return new JsonResponse(['error' => 'Limite de titres atteinte.'], 400);
        }

        $data = json_decode($request->getContent(), true);

        $track = new Track();
        $track->setTitle($data['title'] ?? '');
        $track->setArtist($data['artist'] ?? '');
        $track->setAlbum($data['album'] ?? null);
        $track->setDuration($data['duration'] ?? null);
        $track->setSpotifyTrackId($data['id'] ?? null);
        $track->setSpotifyUri($data['uri'] ?? null);
        $track->setCoverUrl($data['cover'] ?? null);
        $track->setSession($session);
        $track->setParticipant($participant);

        $em->persist($track);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'track' => [
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist(),
                'album' => $track->getAlbum(),
                'cover' => $track->getCoverUrl(),
            ]
        ]);
    }

    #[Route('/remove-track/{trackId}', name: 'app_session_remove_track', methods: ['DELETE'])]
    public function removeTrack(
        string $code,
        int $trackId,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
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

        $track = $em->getRepository(Track::class)->find($trackId);

        if (!$track || $track->getParticipant() !== $participant) {
            return new JsonResponse(['error' => 'Titre introuvable.'], 404);
        }

        $em->remove($track);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/validate', name: 'app_session_validate', methods: ['POST'])]
    public function validate(
        string $code,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
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

        $participant->setValidated(true);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/my-tracks', name: 'app_session_my_tracks', methods: ['GET'])]
    public function myTracks(
        string $code,
        EntityManagerInterface $em,
        SessionInterface $sessionStorage
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

        $tracks = [];
        foreach ($participant->getTracks() as $track) {
            $tracks[] = [
                'id' => $track->getId(),
                'title' => $track->getTitle(),
                'artist' => $track->getArtist(),
                'album' => $track->getAlbum(),
                'cover' => $track->getCoverUrl(),
            ];
        }

        return new JsonResponse([
            'tracks' => $tracks,
            'validated' => $participant->isValidated(),
            'max_tracks' => $session->getMaxTracksPerParticipant(),
        ]);
    }

    #[Route('/all-tracks', name: 'app_session_all_tracks', methods: ['GET'])]
    public function allTracks(
        string $code,
        EntityManagerInterface $em
    ): JsonResponse {
        $session = $em->getRepository(Session::class)->findOneBy(['code' => strtoupper($code)]);

        if (!$session) {
            return new JsonResponse(['error' => 'Session introuvable.'], 404);
        }

        $maxTracks = $session->getMaxTracksPerParticipant();
        $participantStats = [];

        foreach ($session->getParticipants() as $participant) {
            $participantStats[] = [
                'name' => $participant->getName(),
                'count' => $participant->getTracks()->count(),
                'max' => $maxTracks,
                'validated' => $participant->isValidated(),
            ];
        }

        return new JsonResponse([
            'participants' => $participantStats,
            'session_name' => $session->getName(),
            'total_tracks' => $session->getTracks()->count(),
        ]);
    }
}
