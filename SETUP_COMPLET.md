# 🎯 Setup Complet - De A à Z

Guide complet pour mettre en place et déployer l'application.

## 📋 Table des matières

1. [Configuration locale](#1-configuration-locale)
2. [Push vers GitHub](#2-push-vers-github)
3. [Configuration GitHub Secrets](#3-configuration-github-secrets)
4. [Configuration du serveur](#4-configuration-du-serveur)
5. [Déploiement](#5-déploiement)
6. [Test de l'application](#6-test-de-lapplication)

---

## 1. Configuration locale

### ✅ Vérifier que tout est prêt

```bash
cd c:\Users\Remi\playlist-app

# Vérifier que .env existe (mais ne sera pas commité)
ls .env

# Vérifier que .env.example existe
ls .env.example

# Vérifier le .gitignore
cat .gitignore | grep "/.env"
# Doit afficher : /.env
```

### ✅ Tester en local

```bash
# Lancer le serveur
php -S localhost:8000 -t public/

# Dans un navigateur, tester :
# http://localhost:8000
# http://localhost:8000/spotify/auth/login
```

---

## 2. Push vers GitHub

### Étape 2.1 : Initialiser Git

```bash
# Si pas déjà fait
git init
git branch -M main
```

### Étape 2.2 : Vérifier les fichiers

```bash
# IMPORTANT : Vérifier que .env n'est PAS dans la liste
git status

# Si .env apparaît, le retirer
git rm --cached .env
```

### Étape 2.3 : Premier commit

```bash
git add .
git commit -m "Initial commit - Playlist Collaborative App"
```

### Étape 2.4 : Ajouter le remote

```bash
git remote add origin git@github.com:remikel/liste-musicale.git
git remote -v
```

### Étape 2.5 : Push

```bash
git push -u origin main
```

**Résultat attendu** : Code poussé sur https://github.com/remikel/liste-musicale

---

## 3. Configuration GitHub Secrets

Allez sur : https://github.com/remikel/liste-musicale/settings/secrets/actions

### Secret 1 : APP_SECRET

```bash
# Générer une clé aléatoire
php -r "echo bin2hex(random_bytes(32));"
```

Copiez le résultat (64 caractères) et créez le secret `APP_SECRET`.

### Secret 2 : DATABASE_URL

Format :
```
mysql://USERNAME:PASSWORD@HOST:3306/DATABASE?serverVersion=mariadb-10.6.21&charset=utf8mb4
```

Exemple production :
```
mysql://playlist_user:SecurePass123!@db.production.com:3306/playlist_prod?serverVersion=mariadb-10.6.21&charset=utf8mb4
```

### Secret 3 : SPOTIFY_CLIENT_ID

Copiez depuis votre `.env` local (ne pas partager cette valeur)

### Secret 4 : SPOTIFY_CLIENT_SECRET

Copiez depuis votre `.env` local (ne pas partager cette valeur)

### Secrets 5-8 : SSH (pour le déploiement)

#### 5. SSH_PRIVATE_KEY

```bash
# Générer une paire de clés
ssh-keygen -t ed25519 -C "github-actions-deploy"
# Sauvegarder dans : ~/.ssh/github_actions

# Afficher la clé privée
cat ~/.ssh/github_actions
# Copiez TOUT (y compris BEGIN et END)
```

Créez le secret `SSH_PRIVATE_KEY` avec le contenu complet.

#### 6. REMOTE_HOST

Adresse IP ou domaine de votre serveur :
```
192.168.1.100
```
ou
```
server.example.com
```

#### 7. REMOTE_USER

Nom d'utilisateur SSH :
```
ubuntu
```

#### 8. REMOTE_TARGET

Chemin de déploiement :
```
/var/www/playlist-app
```

---

## 4. Configuration du serveur

### Étape 4.1 : Se connecter au serveur

```bash
ssh votre_user@votre_serveur
```

### Étape 4.2 : Installer PHP et dépendances

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-intl composer \
    nginx mysql-server git
```

### Étape 4.3 : Créer le dossier de déploiement

```bash
sudo mkdir -p /var/www/playlist-app
sudo chown $USER:$USER /var/www/playlist-app
```

### Étape 4.4 : Configurer la clé SSH

```bash
# Créer le dossier .ssh si nécessaire
mkdir -p ~/.ssh
chmod 700 ~/.ssh

# Ajouter la clé publique
# (celle générée avec ssh-keygen, le fichier .pub)
cat >> ~/.ssh/authorized_keys << 'EOF'
ssh-ed25519 AAAAC3... github-actions-deploy
EOF

chmod 600 ~/.ssh/authorized_keys
```

### Étape 4.5 : Configurer MySQL

```bash
sudo mysql

# Dans MySQL
CREATE DATABASE playlist_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'playlist_user'@'localhost' IDENTIFIED BY 'VotreMotDePasseSecurise';
GRANT ALL PRIVILEGES ON playlist_prod.* TO 'playlist_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Étape 4.6 : Configurer Nginx

```bash
sudo nano /etc/nginx/sites-available/playlist
```

Contenu :
```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/playlist-app/public;
    index index.php;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }

    location ~ \.php$ {
        return 404;
    }
}
```

Activer le site :
```bash
sudo ln -s /etc/nginx/sites-available/playlist /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

### Étape 4.7 : Configurer Spotify

Sur https://developer.spotify.com/dashboard :
- Ajoutez dans **Redirect URIs** :
  ```
  https://votre-domaine.com/spotify/auth/callback
  ```

---

## 5. Déploiement

### Option 1 : Déploiement automatique (recommandé)

Le déploiement se fait automatiquement à chaque push sur `main`.

```bash
# Sur votre machine locale
git add .
git commit -m "Update feature X"
git push origin main
```

Allez sur GitHub > Actions pour voir le déploiement en cours.

### Option 2 : Déploiement manuel

```bash
# Sur le serveur
cd /var/www/playlist-app
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction
```

---

## 6. Test de l'application

### Test 1 : Page d'accueil

```
https://votre-domaine.com
```

Devrait afficher la page d'accueil.

### Test 2 : Créer une session

1. Cliquez sur "Créer une session"
2. Remplissez le formulaire
3. Notez le code généré

### Test 3 : Authentification Spotify

```
https://votre-domaine.com/spotify/auth/login
```

1. Devrait rediriger vers Spotify
2. Autorisez l'application
3. Retour sur votre site avec message de succès

### Test 4 : Import de playlist

1. Dans une session, cliquez sur "Importer une playlist Spotify"
2. Connectez-vous si nécessaire
3. Sélectionnez une playlist
4. Vérifiez que les titres sont importés

### Test 5 : Export vers Spotify

1. Ajoutez des titres
2. Cliquez sur "Exporter vers Spotify"
3. Vérifiez qu'une playlist est créée sur votre compte

---

## 📊 Récapitulatif des URLs

| Environnement | URL | Usage |
|--------------|-----|-------|
| Local | http://localhost:8000 | Développement |
| Production | https://votre-domaine.com | Application live |
| GitHub | https://github.com/remikel/liste-musicale | Code source |
| GitHub Actions | https://github.com/remikel/liste-musicale/actions | CI/CD |
| Spotify Dashboard | https://developer.spotify.com/dashboard | Configuration API |

---

## ✅ Checklist complète

### Avant le push
- [ ] `.env` dans `.gitignore`
- [ ] `.env.example` créé
- [ ] Code testé en local
- [ ] Pas de secrets hardcodés

### Configuration GitHub
- [ ] Repository créé
- [ ] Code poussé
- [ ] 8 secrets configurés
- [ ] Workflows présents

### Configuration serveur
- [ ] PHP 8.2 installé
- [ ] MySQL configuré
- [ ] Nginx configuré
- [ ] Clé SSH ajoutée
- [ ] Dossier créé avec bonnes permissions

### Configuration Spotify
- [ ] Redirect URI local : `http://localhost:8000/spotify/auth/callback`
- [ ] Redirect URI prod : `https://votre-domaine.com/spotify/auth/callback`

### Tests
- [ ] Page d'accueil fonctionne
- [ ] Création de session fonctionne
- [ ] Authentification Spotify fonctionne
- [ ] Import de playlist fonctionne
- [ ] Export vers Spotify fonctionne

---

## 🎉 Félicitations !

Si toutes les étapes sont validées, votre application est :
- ✅ Déployée sur GitHub
- ✅ Configurée pour CI/CD
- ✅ En production sur votre serveur
- ✅ Intégrée avec Spotify
- ✅ Prête à être utilisée !

---

## 📚 Documentation complète

- [README.md](README.md) - Vue d'ensemble du projet
- [README_APP.md](README_APP.md) - Guide utilisateur
- [DEPLOYMENT.md](DEPLOYMENT.md) - Guide de déploiement détaillé
- [GITHUB_PUSH.md](GITHUB_PUSH.md) - Guide GitHub
- [OAUTH_SETUP.md](OAUTH_SETUP.md) - Configuration OAuth
- [SPOTIFY_API.md](SPOTIFY_API.md) - Documentation API
- [TEST_IMPORT.md](TEST_IMPORT.md) - Guide de test

---

## 🆘 Besoin d'aide ?

1. Vérifiez les logs :
   ```bash
   # Logs Nginx
   sudo tail -f /var/log/nginx/error.log

   # Logs Symfony
   tail -f /var/www/playlist-app/var/log/prod.log
   ```

2. Vérifiez GitHub Actions :
   - Allez sur Actions
   - Cliquez sur le workflow qui a échoué
   - Lisez les logs d'erreur

3. Testez la connexion SSH :
   ```bash
   ssh votre_user@votre_serveur "echo 'SSH works!'"
   ```

4. Vérifiez la base de données :
   ```bash
   php bin/console doctrine:query:sql "SELECT 1"
   ```
