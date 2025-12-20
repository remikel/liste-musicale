<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[Route('/spotify/auth')]
class SpotifyAuthController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $httpClient,
        private string $clientId,
        private string $clientSecret
    ) {
    }

    /**
     * Redirige l'utilisateur vers Spotify pour autoriser l'application
     */
    #[Route('/login', name: 'app_spotify_login')]
    public function login(Request $request, SessionInterface $session): RedirectResponse
    {
        // Sauvegarde l'URL de retour
        $returnUrl = $request->query->get('return_url', '/');
        $session->set('spotify_return_url', $returnUrl);

        // Génère un state pour la sécurité
        $state = bin2hex(random_bytes(16));
        $session->set('spotify_oauth_state', $state);

        // URL de callback (à configurer dans Spotify Dashboard)
        $redirectUri = $request->getSchemeAndHttpHost() . '/spotify/auth/callback';

        // Scopes nécessaires
        $scopes = [
            'playlist-read-private',
            'playlist-read-collaborative',
            'playlist-modify-private',
            'playlist-modify-public'
        ];

        // Construit l'URL d'autorisation Spotify
        $authUrl = 'https://accounts.spotify.com/authorize?' . http_build_query([
            'client_id' => $this->clientId,
            'response_type' => 'code',
            'redirect_uri' => $redirectUri,
            'scope' => implode(' ', $scopes),
            'state' => $state,
            'show_dialog' => false
        ]);

        return new RedirectResponse($authUrl);
    }

    /**
     * Callback appelé par Spotify après autorisation
     */
    #[Route('/callback', name: 'app_spotify_callback')]
    public function callback(Request $request, SessionInterface $session): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');
        $error = $request->query->get('error');

        // Vérifie le state pour la sécurité
        if ($state !== $session->get('spotify_oauth_state')) {
            $this->addFlash('error', 'Erreur de sécurité OAuth. Veuillez réessayer.');
            return $this->redirect($session->get('spotify_return_url', '/'));
        }

        // Vérifie les erreurs
        if ($error) {
            $this->addFlash('error', 'Autorisation refusée par Spotify.');
            return $this->redirect($session->get('spotify_return_url', '/'));
        }

        if (!$code) {
            $this->addFlash('error', 'Code d\'autorisation manquant.');
            return $this->redirect($session->get('spotify_return_url', '/'));
        }

        try {
            // Échange le code contre un access token
            $redirectUri = $request->getSchemeAndHttpHost() . '/spotify/auth/callback';

            $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
                ],
                'body' => [
                    'grant_type' => 'authorization_code',
                    'code' => $code,
                    'redirect_uri' => $redirectUri
                ]
            ]);

            $data = $response->toArray();

            // Stocke le token en session
            $session->set('spotify_access_token', $data['access_token']);
            $session->set('spotify_refresh_token', $data['refresh_token'] ?? null);
            $session->set('spotify_token_expires_at', time() + ($data['expires_in'] ?? 3600));

            $this->addFlash('success', 'Connecté à Spotify avec succès !');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la connexion à Spotify: ' . $e->getMessage());
        }

        return $this->redirect($session->get('spotify_return_url', '/'));
    }

    /**
     * Déconnexion Spotify
     */
    #[Route('/logout', name: 'app_spotify_logout')]
    public function logout(SessionInterface $session): RedirectResponse
    {
        $session->remove('spotify_access_token');
        $session->remove('spotify_refresh_token');
        $session->remove('spotify_token_expires_at');

        $this->addFlash('success', 'Déconnecté de Spotify.');
        return $this->redirectToRoute('app_home');
    }

    /**
     * Rafraîchit le token d'accès
     */
    #[Route('/refresh', name: 'app_spotify_refresh')]
    public function refresh(SessionInterface $session): Response
    {
        $refreshToken = $session->get('spotify_refresh_token');

        if (!$refreshToken) {
            return $this->json(['error' => 'Aucun refresh token disponible'], 400);
        }

        try {
            $response = $this->httpClient->request('POST', 'https://accounts.spotify.com/api/token', [
                'headers' => [
                    'Content-Type' => 'application/x-www-form-urlencoded',
                    'Authorization' => 'Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
                ],
                'body' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken
                ]
            ]);

            $data = $response->toArray();

            $session->set('spotify_access_token', $data['access_token']);
            $session->set('spotify_token_expires_at', time() + ($data['expires_in'] ?? 3600));

            return $this->json(['success' => true, 'access_token' => $data['access_token']]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Récupère le token actuel (pour utilisation en AJAX)
     */
    #[Route('/token', name: 'app_spotify_get_token', methods: ['GET'])]
    public function getToken(SessionInterface $session): Response
    {
        $accessToken = $session->get('spotify_access_token');
        $expiresAt = $session->get('spotify_token_expires_at');

        if (!$accessToken) {
            return $this->json(['authenticated' => false], 401);
        }

        // Vérifie si le token est expiré
        if ($expiresAt && time() >= $expiresAt) {
            return $this->json(['authenticated' => false, 'expired' => true], 401);
        }

        return $this->json([
            'authenticated' => true,
            'access_token' => $accessToken,
            'expires_at' => $expiresAt
        ]);
    }
}
