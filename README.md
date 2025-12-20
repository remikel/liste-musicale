# 🎵 Playlist Collaborative

Application web collaborative pour créer et partager des playlists musicales avec vos amis via Spotify.

[![Deploy](https://github.com/remikel/liste-musicale/actions/workflows/deploy.yml/badge.svg)](https://github.com/remikel/liste-musicale/actions/workflows/deploy.yml)
[![Tests](https://github.com/remikel/liste-musicale/actions/workflows/tests.yml/badge.svg)](https://github.com/remikel/liste-musicale/actions/workflows/tests.yml)

## ✨ Fonctionnalités

- 🎯 **Création de sessions** - Créez une session avec un code unique de 5 caractères
- 🔢 **Limite de titres** - Définissez une limite de titres par participant (optionnel)
- 🔍 **Recherche Spotify** - Recherche de titres en temps réel via l'API Spotify
- 📥 **Import de playlists** - Importez directement vos playlists Spotify existantes
- ➕ **Ajout collaboratif** - Chaque participant peut ajouter ses titres préférés
- 👥 **Playlist collaborative** - Visualisation en temps réel de tous les titres ajoutés
- 📤 **Export Spotify** - Exportez la playlist collaborative vers votre compte Spotify
- 🔒 **Authentification OAuth** - Connexion sécurisée avec Spotify

## 🚀 Démarrage rapide

### Prérequis

- PHP 8.2+
- Composer
- MySQL/MariaDB 10.6+
- Compte Spotify Developer

### Installation

```bash
# Cloner le repository
git clone git@github.com:remikel/liste-musicale.git
cd liste-musicale

# Installer les dépendances
composer install

# Copier le fichier d'environnement
cp .env.example .env

# Configurer la base de données et Spotify dans .env
# Puis créer la base de données
php bin/console doctrine:database:create
php bin/console doctrine:schema:update --force

# Lancer le serveur
php -S localhost:8000 -t public/
# ou avec Symfony CLI
symfony serve
```

### Configuration Spotify

1. Créez une application sur https://developer.spotify.com/dashboard
2. Notez votre `Client ID` et `Client Secret`
3. Ajoutez dans **Redirect URIs** :
   ```
   http://localhost:8000/spotify/auth/callback
   ```
4. Mettez à jour votre `.env` :
   ```env
   SPOTIFY_CLIENT_ID=votre_client_id
   SPOTIFY_CLIENT_SECRET=votre_client_secret
   ```

## 📖 Documentation

- [Guide d'utilisation complet](README_APP.md)
- [Configuration OAuth Spotify](OAUTH_SETUP.md)
- [Guide de déploiement](DEPLOYMENT.md)
- [Documentation API Spotify](SPOTIFY_API.md)
- [Guide de test de l'import](TEST_IMPORT.md)

## 🏗️ Architecture

### Technologies

- **Backend** : Symfony 7.2, PHP 8.2
- **Base de données** : MySQL/MariaDB avec Doctrine ORM
- **Frontend** : Bootstrap 5, JavaScript vanilla
- **API** : Spotify Web API
- **Déploiement** : GitHub Actions

### Structure

```
src/
├── Controller/
│   ├── HomeController.php
│   ├── SessionController.php
│   ├── ExportController.php
│   └── SpotifyAuthController.php
├── Entity/
│   ├── Session.php
│   ├── Participant.php
│   └── Track.php
├── Service/
│   ├── SpotifyService.php
│   └── ExportService.php
└── Repository/
    ├── SessionRepository.php
    ├── ParticipantRepository.php
    └── TrackRepository.php
```

## 🔐 Sécurité

- Le fichier `.env` n'est **jamais** commité (dans `.gitignore`)
- Les secrets sont gérés via GitHub Secrets en production
- Authentification OAuth2 pour l'accès aux playlists Spotify
- Validation des sessions et des participants
- Protection CSRF via Symfony

## 🚀 Déploiement

Le déploiement se fait automatiquement via GitHub Actions sur push vers `main`.

### Configuration requise

1. Configurez les secrets GitHub (voir [DEPLOYMENT.md](DEPLOYMENT.md))
2. Préparez votre serveur de production
3. Push vers `main` :

```bash
git add .
git commit -m "Deploy to production"
git push origin main
```

Voir le guide complet : [DEPLOYMENT.md](DEPLOYMENT.md)

## 🧪 Tests

```bash
# Lancer les tests
vendor/bin/phpunit

# Les tests s'exécutent automatiquement sur GitHub Actions
```

## 📝 Utilisation

### 1. Créer une session

```
Accueil → Créer une session → Définir le nom et la limite
```

### 2. Partager le code

```
Partagez le code à 5 caractères avec vos amis
```

### 3. Ajouter des titres

```
Recherche manuelle OU Import de playlist Spotify
```

### 4. Valider et exporter

```
Valider sa sélection → Exporter vers Spotify
```

## 🤝 Contribution

Les contributions sont les bienvenues !

1. Fork le projet
2. Créez votre branche (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 Licence

Ce projet est sous licence MIT.

## 👤 Auteur

**Remi**
- GitHub: [@remikel](https://github.com/remikel)
- Repository: [liste-musicale](https://github.com/remikel/liste-musicale)

## 🙏 Remerciements

- [Symfony](https://symfony.com/) pour le framework PHP
- [Spotify](https://developer.spotify.com/) pour l'API Web
- [Bootstrap](https://getbootstrap.com/) pour le design

---

Made with ❤️ using Symfony and Spotify API
