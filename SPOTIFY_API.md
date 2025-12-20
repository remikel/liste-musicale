# API Spotify - Documentation

## Vue d'ensemble

L'application utilise maintenant l'API Spotify pour :
- ✅ **Recherche de titres** (via Client Credentials - automatique)
- ✅ **Import de playlists Spotify** (nécessite OAuth utilisateur)
- ✅ **Export vers Spotify** (nécessite OAuth utilisateur)

## Configuration

Les identifiants sont dans le fichier `.env` :
```env
SPOTIFY_CLIENT_ID=your_spotify_client_id
SPOTIFY_CLIENT_SECRET=your_spotify_client_secret
```

## Nouveaux Endpoints

### 1. Récupérer les playlists d'un utilisateur Spotify

**Endpoint** : `POST /export/spotify/playlists`

**Body** :
```json
{
  "access_token": "BQDxT8..."
}
```

**Réponse** :
```json
{
  "success": true,
  "playlists": [
    {
      "id": "37i9dQZF1DXcBWIGoYBM5M",
      "name": "Today's Top Hits",
      "description": "Ed Sheeran is on top of the Hottest 50!",
      "tracks_total": 50,
      "image": "https://i.scdn.co/image/...",
      "url": "https://open.spotify.com/playlist/37i9dQZF1DXcBWIGoYBM5M"
    }
  ]
}
```

---

### 2. Importer une playlist Spotify dans une session

**Endpoint** : `POST /export/spotify/import/{code}`

**Paramètres** :
- `{code}` : Le code de la session (ex: ABC12)

**Body** :
```json
{
  "access_token": "BQDxT8...",
  "playlist_id": "37i9dQZF1DXcBWIGoYBM5M"
}
```

**Réponse** :
```json
{
  "success": true,
  "tracks_imported": 15,
  "tracks_total": 50
}
```

**Notes** :
- Respecte la limite de titres par participant configurée dans la session
- Les titres sont ajoutés avec leur `spotify_track_id` et `spotify_uri` pour un export optimisé
- Le participant doit être connecté et ne pas avoir validé sa sélection

---

### 3. Recherche de titres (MODIFIÉ)

**Endpoint** : `GET /session/{code}/search?q={query}`

**Changement** : Utilise maintenant Spotify au lieu de Deezer

**Réponse** :
```json
[
  {
    "id": "3n3Ppam7vgaVa1iaRUc9Lp",
    "title": "Mr. Brightside",
    "artist": "The Killers",
    "album": "Hot Fuss",
    "duration": 222,
    "cover": "https://i.scdn.co/image/...",
    "preview": "https://p.scdn.co/mp3-preview/...",
    "uri": "spotify:track:3n3Ppam7vgaVa1iaRUc9Lp"
  }
]
```

---

### 4. Export vers Spotify (AMÉLIORÉ)

**Endpoint** : `POST /export/spotify/{code}`

**Body** :
```json
{
  "access_token": "BQDxT8..."
}
```

**Améliorations** :
- Si les titres ont déjà un `spotify_uri`, ils sont utilisés directement (pas de recherche)
- Sinon, recherche automatique du titre sur Spotify
- Création d'une playlist privée au nom de la session

**Réponse** :
```json
{
  "success": true,
  "playlist_url": "https://open.spotify.com/playlist/...",
  "tracks_added": 42
}
```

---

## OAuth2 Flow pour l'utilisateur

Pour obtenir un `access_token` utilisateur, vous devez implémenter l'OAuth2 Authorization Code Flow.

### Étapes :

1. **Créer une application Spotify**
   - Allez sur https://developer.spotify.com/dashboard
   - Créez une nouvelle application
   - Notez le `Client ID` et `Client Secret`
   - Configurez les Redirect URIs (ex: `http://localhost:8000/spotify/callback`)

2. **Demander l'autorisation**
   ```
   GET https://accounts.spotify.com/authorize?
     client_id=VOTRE_CLIENT_ID&
     response_type=code&
     redirect_uri=http://localhost:8000/spotify/callback&
     scope=playlist-read-private playlist-modify-private playlist-modify-public
   ```

3. **Échanger le code contre un token**
   ```bash
   curl -X POST https://accounts.spotify.com/api/token \
     -H "Content-Type: application/x-www-form-urlencoded" \
     -d "grant_type=authorization_code" \
     -d "code=CODE_RECU" \
     -d "redirect_uri=http://localhost:8000/spotify/callback" \
     -d "client_id=VOTRE_CLIENT_ID" \
     -d "client_secret=VOTRE_CLIENT_SECRET"
   ```

4. **Utiliser le token**
   Le token obtenu peut être utilisé dans les endpoints `/export/spotify/playlists` et `/export/spotify/import/{code}`

### Scopes nécessaires :
- `playlist-read-private` : Lire les playlists privées
- `playlist-modify-private` : Modifier les playlists privées
- `playlist-modify-public` : Modifier les playlists publiques

---

## Modifications de la base de données

Nouvelles colonnes dans la table `track` :
```sql
spotify_track_id VARCHAR(255) NULL
spotify_uri VARCHAR(255) NULL
```

Ces colonnes stockent :
- `spotify_track_id` : L'ID Spotify du titre (ex: `3n3Ppam7vgaVa1iaRUc9Lp`)
- `spotify_uri` : L'URI Spotify du titre (ex: `spotify:track:3n3Ppam7vgaVa1iaRUc9Lp`)

---

## Exemple d'utilisation complet

### 1. L'utilisateur se connecte à Spotify
```javascript
// Rediriger vers Spotify pour l'authentification
window.location.href = 'https://accounts.spotify.com/authorize?' +
  'client_id=REDACTED_CLIENT_ID&' +
  'response_type=code&' +
  'redirect_uri=http://localhost:8000/spotify/callback&' +
  'scope=playlist-read-private playlist-modify-private playlist-modify-public';
```

### 2. Récupérer et afficher les playlists
```javascript
fetch('/export/spotify/playlists', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({ access_token: userAccessToken })
})
.then(res => res.json())
.then(data => {
  // Afficher la liste des playlists
  data.playlists.forEach(playlist => {
    console.log(playlist.name, playlist.tracks_total);
  });
});
```

### 3. Importer une playlist dans la session
```javascript
fetch('/export/spotify/import/ABC12', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    access_token: userAccessToken,
    playlist_id: selectedPlaylistId
  })
})
.then(res => res.json())
.then(data => {
  console.log(`${data.tracks_imported} titres importés sur ${data.tracks_total}`);
});
```

### 4. Exporter la session vers Spotify
```javascript
fetch('/export/spotify/ABC12', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({ access_token: userAccessToken })
})
.then(res => res.json())
.then(data => {
  if (data.success) {
    console.log('Playlist créée:', data.playlist_url);
    window.open(data.playlist_url, '_blank');
  }
});
```

---

## Architecture des Services

### SpotifyService

**Emplacement** : `src/Service/SpotifyService.php`

**Méthodes** :
- `searchTracks(string $query): array` - Recherche de titres (Client Credentials)
- `getTrack(string $trackId): ?array` - Récupère un titre spécifique
- `getUserPlaylists(string $userAccessToken): array` - Liste les playlists de l'utilisateur
- `getPlaylistTracks(string $playlistId, string $userAccessToken): array` - Récupère les titres d'une playlist
- `createPlaylist(string $userAccessToken, string $name, array $trackUris, string $description = ''): array` - Crée une playlist

**Authentification** :
- Gère automatiquement le token Client Credentials pour la recherche
- Cache le token jusqu'à expiration
- Les tokens utilisateur sont fournis par l'appelant

### ExportService

**Emplacement** : `src/Service/ExportService.php`

**Modifications** :
- Utilise maintenant `SpotifyService` au lieu de faire les appels API directement
- Optimisation : utilise les URIs Spotify déjà stockés quand disponibles

---

## Notes de migration

### Ancien système (Deezer)
- ❌ `DeezerService` est maintenant obsolète
- ❌ Le champ `deezer_track_id` est conservé mais non utilisé
- ❌ Les anciens titres n'ont pas de `spotify_track_id`

### Nouveau système (Spotify)
- ✅ Recherche plus précise
- ✅ Preview audio disponible
- ✅ Import de playlists
- ✅ Export optimisé (pas de recherche si URI déjà connu)

### Compatibilité ascendante
Les titres ajoutés avec l'ancien système Deezer :
- Seront recherchés sur Spotify lors de l'export
- Peuvent être mélangés avec des titres Spotify dans une même session
