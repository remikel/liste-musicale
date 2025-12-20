# Configuration OAuth2 Spotify

## Pourquoi vous ne pouvez pas importer/exporter ?

L'application utilise actuellement **Client Credentials Flow** qui permet uniquement de :
- ✅ Rechercher des titres sur Spotify

Pour importer/exporter des playlists, il faut un **token d'accès utilisateur** via **Authorization Code Flow**.

## Étapes de configuration

### 1. Configurer l'application Spotify

1. Allez sur https://developer.spotify.com/dashboard
2. Cliquez sur votre application existante (ou créez-en une nouvelle)
3. Cliquez sur "Edit Settings"
4. Dans **Redirect URIs**, ajoutez :
   ```
   http://localhost:8000/spotify/auth/callback
   ```
5. Cliquez sur "Save"

### 2. Nouveau contrôleur OAuth ajouté

J'ai créé `src/Controller/SpotifyAuthController.php` avec les routes suivantes :

- `GET /spotify/auth/login` - Redirige vers Spotify pour l'autorisation
- `GET /spotify/auth/callback` - Callback après autorisation
- `GET /spotify/auth/logout` - Déconnexion
- `GET /spotify/auth/refresh` - Rafraîchit le token
- `GET /spotify/auth/token` - Récupère le token actuel

### 3. Comment utiliser

#### Dans votre interface utilisateur

**Bouton de connexion Spotify :**
```html
<a href="/spotify/auth/login?return_url={{ current_url }}" class="btn btn-success">
    🎵 Se connecter à Spotify
</a>
```

**Vérifier si l'utilisateur est connecté :**
```javascript
fetch('/spotify/auth/token')
    .then(res => res.json())
    .then(data => {
        if (data.authenticated) {
            console.log('Token:', data.access_token);
            // Afficher les boutons import/export
            showSpotifyFeatures(data.access_token);
        } else {
            // Afficher le bouton de connexion
            showLoginButton();
        }
    });
```

**Importer une playlist :**
```javascript
// 1. Récupérer le token
fetch('/spotify/auth/token')
    .then(res => res.json())
    .then(tokenData => {
        if (!tokenData.authenticated) {
            window.location.href = '/spotify/auth/login?return_url=' + window.location.pathname;
            return;
        }

        const accessToken = tokenData.access_token;

        // 2. Récupérer les playlists
        return fetch('/export/spotify/playlists', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ access_token: accessToken })
        });
    })
    .then(res => res.json())
    .then(data => {
        // 3. Afficher les playlists
        displayPlaylists(data.playlists);
    });

// 4. Quand l'utilisateur sélectionne une playlist
function importPlaylist(playlistId) {
    fetch('/spotify/auth/token')
        .then(res => res.json())
        .then(tokenData => {
            return fetch('/export/spotify/import/ABC12', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    access_token: tokenData.access_token,
                    playlist_id: playlistId
                })
            });
        })
        .then(res => res.json())
        .then(data => {
            alert(`${data.tracks_imported} titres importés !`);
            location.reload();
        });
}
```

**Exporter vers Spotify :**
```javascript
fetch('/spotify/auth/token')
    .then(res => res.json())
    .then(tokenData => {
        if (!tokenData.authenticated) {
            window.location.href = '/spotify/auth/login?return_url=' + window.location.pathname;
            return;
        }

        return fetch('/export/spotify/ABC12', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({ access_token: tokenData.access_token })
        });
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('Playlist créée !');
            window.open(data.playlist_url, '_blank');
        }
    });
```

### 4. Exemple d'interface utilisateur

Ajoutez ceci dans votre template Twig :

```twig
{# templates/session/playlist.html.twig #}

<div id="spotify-auth-section">
    <div id="spotify-login" style="display: none;">
        <div class="alert alert-info">
            <strong>🎵 Connectez-vous à Spotify</strong> pour importer vos playlists ou exporter cette session.
        </div>
        <a href="/spotify/auth/login?return_url={{ path('app_session_playlist', {code: session.code}) }}"
           class="btn btn-success">
            Se connecter à Spotify
        </a>
    </div>

    <div id="spotify-features" style="display: none;">
        <div class="alert alert-success">
            ✅ Connecté à Spotify
            <a href="/spotify/auth/logout" class="btn btn-sm btn-link">Se déconnecter</a>
        </div>

        <div class="mb-3">
            <button id="btn-import-spotify" class="btn btn-primary">
                📥 Importer une playlist Spotify
            </button>

            <button id="btn-export-spotify" class="btn btn-success">
                📤 Exporter vers Spotify
            </button>
        </div>
    </div>
</div>

<script>
// Vérifie l'état de connexion au chargement
fetch('/spotify/auth/token')
    .then(res => res.json())
    .then(data => {
        if (data.authenticated) {
            document.getElementById('spotify-features').style.display = 'block';
            initSpotifyFeatures(data.access_token);
        } else {
            document.getElementById('spotify-login').style.display = 'block';
        }
    });

function initSpotifyFeatures(accessToken) {
    // Import
    document.getElementById('btn-import-spotify').onclick = () => {
        showPlaylistPicker(accessToken);
    };

    // Export
    document.getElementById('btn-export-spotify').onclick = () => {
        exportToSpotify(accessToken);
    };
}

function showPlaylistPicker(accessToken) {
    fetch('/export/spotify/playlists', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ access_token: accessToken })
    })
    .then(res => res.json())
    .then(data => {
        if (!data.success) {
            alert('Erreur: ' + data.error);
            return;
        }

        // Créer un modal avec la liste des playlists
        let html = '<select id="playlist-select" class="form-control">';
        data.playlists.forEach(p => {
            html += `<option value="${p.id}">${p.name} (${p.tracks_total} titres)</option>`;
        });
        html += '</select>';
        html += '<button onclick="importSelectedPlaylist(\'' + accessToken + '\')" class="btn btn-primary mt-2">Importer</button>';

        // Afficher dans un modal ou div
        document.getElementById('playlist-modal-content').innerHTML = html;
        // Ouvrir le modal...
    });
}

function importSelectedPlaylist(accessToken) {
    const playlistId = document.getElementById('playlist-select').value;
    const code = '{{ session.code }}';

    fetch('/export/spotify/import/' + code, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            access_token: accessToken,
            playlist_id: playlistId
        })
    })
    .then(res => res.json())
    .then(data => {
        alert(`${data.tracks_imported} titres importés sur ${data.tracks_total} !`);
        location.reload();
    });
}

function exportToSpotify(accessToken) {
    const code = '{{ session.code }}';

    if (!confirm('Créer une playlist sur votre compte Spotify ?')) {
        return;
    }

    fetch('/export/spotify/' + code, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ access_token: accessToken })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`Playlist créée avec ${data.tracks_added} titres !`);
            window.open(data.playlist_url, '_blank');
        } else {
            alert('Erreur: ' + data.error);
        }
    });
}
</script>
```

## Résumé

**Avant** (ne fonctionnait pas pour import/export) :
```
Client ID + Client Secret → Client Credentials Token → ❌ Pas d'accès aux playlists utilisateur
```

**Maintenant** (fonctionne) :
```
1. Utilisateur clique "Se connecter à Spotify"
2. Redirigé vers Spotify pour autoriser
3. Token utilisateur stocké en session
4. Import/Export disponibles ✅
```

## Test rapide

1. Redémarrez le serveur
2. Allez sur http://localhost:8000/spotify/auth/login
3. Autorisez l'application sur Spotify
4. Vous serez redirigé avec un message "Connecté à Spotify avec succès !"
5. Le token est maintenant stocké en session
6. Vous pouvez utiliser `/export/spotify/playlists` et `/export/spotify/import/{code}`
