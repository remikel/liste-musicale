# Playlist Collaborative - Application Symfony

Application web collaborative pour créer et partager des playlists musicales avec vos amis.

## Fonctionnalités

- **Création de sessions** : Créez une session avec un code unique de 5 caractères
- **Limite de titres** : Définissez une limite de titres par participant (optionnel)
- **Recherche de musique** : Recherche de titres via l'API Spotify
- **Ajout de titres** : Chaque participant peut ajouter ses titres préférés
- **Import de playlist Spotify** : Importez directement vos playlists Spotify existantes
- **Playlist collaborative** : Visualisation en temps réel de tous les titres ajoutés
- **Export** : Exportez la playlist vers Spotify, YouTube Music ou Qobuz

## Installation

L'application est déjà installée et configurée. Le serveur tourne sur `http://localhost:8000`

### Base de données

La base de données MySQL est déjà configurée et les tables sont créées.

Connexion :
- Host: 127.0.0.1:3306
- Database: playlist
- Username: admin
- Password: 07297364

## Utilisation

### 1. Accéder à l'application

Ouvrez votre navigateur et allez sur : `http://localhost:8000`

### 2. Créer une session

1. Sur la page d'accueil, remplissez le formulaire "Créer une session"
2. Indiquez le nom de la session
3. Optionnel : définissez un nombre maximum de titres par participant
4. Cliquez sur "Créer la session"
5. Vous serez redirigé vers la page de la session avec le code à partager

### 3. Rejoindre une session

1. Sur la page d'accueil, entrez le code de 5 caractères dans "Rejoindre une session"
2. Cliquez sur "Rejoindre"
3. Entrez votre nom
4. Vous accédez à la page de la playlist

### 4. Ajouter des titres

1. Utilisez la barre de recherche pour trouver des titres (recherche via Spotify)
2. Cliquez sur un titre pour l'ajouter à votre liste
3. Vous pouvez supprimer des titres avant de valider
4. Une fois satisfait, cliquez sur "Valider ma sélection"

### 4.bis Importer une playlist Spotify

1. Cliquez sur le bouton "Importer depuis Spotify"
2. Authentifiez-vous avec votre compte Spotify (nécessite OAuth)
3. Sélectionnez la playlist que vous souhaitez importer
4. Les titres seront automatiquement ajoutés à votre sélection (dans la limite configurée)

### 5. Consulter la playlist générale

La section "Playlist générale de la session" affiche tous les titres ajoutés par tous les participants, avec le nom de la personne qui a ajouté chaque titre.

Cette liste se met à jour automatiquement toutes les 5 secondes.

## Intégration Spotify

L'application utilise l'API Spotify pour la recherche de titres, l'import et l'export de playlists.

### Configuration

Les identifiants Spotify sont déjà configurés dans le fichier `.env` :
```
SPOTIFY_CLIENT_ID=votre_client_id
SPOTIFY_CLIENT_SECRET=votre_client_secret
```

### Fonctionnalités disponibles

#### 1. Recherche de titres (automatique)
La recherche utilise automatiquement l'API Spotify via Client Credentials Flow (pas besoin d'authentification utilisateur).

#### 2. Import de playlist Spotify
Pour importer une playlist Spotify, l'utilisateur doit s'authentifier via OAuth2 :

**Endpoint** : `POST /export/spotify/playlists`
```javascript
// 1. Récupérer les playlists de l'utilisateur
fetch('/export/spotify/playlists', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        access_token: 'user_access_token_spotify'
    })
})
.then(response => response.json())
.then(data => {
    console.log('Playlists:', data.playlists);
});

// 2. Importer une playlist
fetch('/export/spotify/import/ABC12', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        access_token: 'user_access_token_spotify',
        playlist_id: 'id_de_la_playlist'
    })
})
.then(response => response.json())
.then(data => {
    console.log('Titres importés:', data.tracks_imported);
});
```

#### 3. Export vers Spotify
**Endpoint** : `POST /export/spotify/{code}`
```javascript
fetch('/export/spotify/ABC12', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        access_token: 'user_access_token_spotify'
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('Playlist créée:', data.playlist_url);
    }
});
```

### Obtenir un access token utilisateur Spotify

Pour l'import et l'export, vous devez implémenter l'OAuth2 Authorization Code Flow :

1. Créez une application sur https://developer.spotify.com/dashboard
2. Configurez les Redirect URIs
3. Demandez les scopes : `playlist-read-private playlist-modify-private playlist-modify-public`
4. Échangez le code d'autorisation contre un access token

## Export vers d'autres plateformes

### YouTube Music
1. Créez un projet sur https://console.cloud.google.com/
2. Activez l'API YouTube Data v3
3. Obtenez un access token OAuth2
4. Utilisez l'endpoint : `POST /export/youtube/{code}` avec `{"access_token": "votre_token"}`

### Qobuz
L'intégration Qobuz nécessite de contacter Qobuz pour obtenir un accès développeur à leur API.

## Architecture technique

### Entités

- **Session** : Représente une session collaborative avec un code unique
- **Participant** : Représente un utilisateur dans une session
- **Track** : Représente un titre de musique ajouté par un participant

### Services

- **SpotifyService** : Service pour l'authentification Spotify, la recherche de titres, l'import et l'export de playlists
- **ExportService** : Service pour exporter les playlists vers Spotify, YouTube Music et Qobuz
- **DeezerService** : (Obsolète, remplacé par SpotifyService)

### Routes principales

#### Session Management
- `GET /` : Page d'accueil
- `POST /create-session` : Créer une nouvelle session
- `POST /join-session` : Rejoindre une session existante
- `GET /session/{code}` : Rejoindre une session avec un code
- `POST /session/{code}/enter` : Entrer dans une session avec son nom
- `GET /session/{code}/playlist` : Page de la playlist

#### Track Management
- `GET /session/{code}/search?q=query` : Rechercher des titres via Spotify
- `POST /session/{code}/add-track` : Ajouter un titre
- `DELETE /session/{code}/remove-track/{id}` : Supprimer un titre
- `POST /session/{code}/validate` : Valider sa sélection
- `GET /session/{code}/my-tracks` : Obtenir ses titres
- `GET /session/{code}/all-tracks` : Obtenir tous les titres de la session

#### Spotify Integration
- `POST /export/spotify/playlists` : Récupérer les playlists de l'utilisateur Spotify
- `POST /export/spotify/import/{code}` : Importer une playlist Spotify dans la session
- `POST /export/spotify/{code}` : Exporter la session vers Spotify
- `POST /export/youtube/{code}` : Exporter vers YouTube Music
- `POST /export/qobuz/{code}` : Exporter vers Qobuz

## Technologies utilisées

- **Symfony 7.2** : Framework PHP
- **Doctrine ORM** : Gestion de la base de données
- **Twig** : Moteur de templates
- **Bootstrap 5** : Framework CSS
- **API Spotify** : Recherche de musique, import/export de playlists
- **MySQL/MariaDB** : Base de données

## Démarrage du serveur

Le serveur est déjà lancé. Si vous devez le redémarrer :

```bash
cd playlist-app
php -S localhost:8000 -t public/
```

Ou avec Symfony CLI :

```bash
cd playlist-app
symfony serve
```

## Notes importantes

1. Le code de session est généré automatiquement et est unique
2. Une fois qu'un participant a validé sa sélection, il ne peut plus modifier ses titres
3. La recherche utilise l'API Spotify avec Client Credentials (configuré dans .env)
4. L'import et l'export nécessitent une authentification OAuth utilisateur Spotify
5. La playlist générale se rafraîchit automatiquement toutes les 5 secondes
6. Les titres importés depuis Spotify conservent leur ID et URI Spotify pour un export optimisé

## Support

Pour toute question ou problème, consultez la documentation Symfony : https://symfony.com/doc
