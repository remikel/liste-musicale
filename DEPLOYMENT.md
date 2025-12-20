# 🚀 Guide de Déploiement

## Configuration GitHub Actions

Ce projet utilise GitHub Actions pour déployer automatiquement l'application sur votre serveur de production.

## 📋 Prérequis

1. Un serveur avec :
   - PHP 8.2+
   - MySQL/MariaDB 10.6+
   - Composer
   - Accès SSH

2. Un repository GitHub : `git@github.com:remikel/liste-musicale.git`

## 🔐 Secrets GitHub à configurer

Allez dans **Settings > Secrets and variables > Actions > New repository secret** et ajoutez :

### 1. APP_SECRET
```
Générez une clé aléatoire :
php -r "echo bin2hex(random_bytes(32));"
```
Copiez le résultat et ajoutez-le comme secret.

### 2. DATABASE_URL
```
mysql://USERNAME:PASSWORD@HOST:3306/DATABASE_NAME?serverVersion=mariadb-10.6.21&charset=utf8mb4
```
Exemple :
```
mysql://playlist_user:MySecurePassword123@db.example.com:3306/playlist_prod?serverVersion=mariadb-10.6.21&charset=utf8mb4
```

### 3. SPOTIFY_CLIENT_ID
```
Votre Client ID Spotify (depuis le .env)
```

### 4. SPOTIFY_CLIENT_SECRET
```
Votre Client Secret Spotify (depuis le .env)
```

### 5. SSH_PRIVATE_KEY
```
Votre clé privée SSH pour se connecter au serveur
```

Pour générer une paire de clés SSH :
```bash
ssh-keygen -t ed25519 -C "github-actions-deploy"
```
- Ajoutez la clé **publique** (`~/.ssh/id_ed25519.pub`) dans `~/.ssh/authorized_keys` sur votre serveur
- Ajoutez la clé **privée** (`~/.ssh/id_ed25519`) comme secret GitHub

### 6. REMOTE_HOST
```
Adresse IP ou domaine de votre serveur
Exemple: 192.168.1.100 ou server.example.com
```

### 7. REMOTE_USER
```
Nom d'utilisateur SSH
Exemple: ubuntu ou root ou votre_username
```

### 8. REMOTE_TARGET
```
Chemin absolu vers le dossier de déploiement
Exemple: /var/www/playlist-app
```

---

## 📝 Liste complète des secrets

| Secret Name | Description | Exemple |
|------------|-------------|---------|
| `APP_SECRET` | Clé secrète Symfony | `a1b2c3d4e5f6...` |
| `DATABASE_URL` | URL de connexion MySQL | `mysql://user:pass@host:3306/db?serverVersion=...` |
| `SPOTIFY_CLIENT_ID` | Client ID Spotify | `REDACTED_CLIENT_ID` |
| `SPOTIFY_CLIENT_SECRET` | Client Secret Spotify | `REDACTED_CLIENT_SECRET` |
| `SSH_PRIVATE_KEY` | Clé SSH privée | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `REMOTE_HOST` | Adresse du serveur | `192.168.1.100` |
| `REMOTE_USER` | User SSH | `ubuntu` |
| `REMOTE_TARGET` | Dossier de destination | `/var/www/playlist-app` |

---

## 🎯 Configuration du serveur de production

### 1. Préparer le serveur

```bash
# Se connecter au serveur
ssh user@your-server.com

# Créer le dossier de destination
sudo mkdir -p /var/www/playlist-app
sudo chown $USER:$USER /var/www/playlist-app

# Installer les dépendances
sudo apt update
sudo apt install -y php8.2 php8.2-cli php8.2-fpm php8.2-mysql php8.2-xml php8.2-mbstring php8.2-curl php8.2-zip composer
```

### 2. Configurer la base de données

```bash
# Se connecter à MySQL
mysql -u root -p

# Créer la base de données et l'utilisateur
CREATE DATABASE playlist_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'playlist_user'@'localhost' IDENTIFIED BY 'VotreMotDePasse';
GRANT ALL PRIVILEGES ON playlist_prod.* TO 'playlist_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3. Configurer Nginx (optionnel)

```nginx
server {
    listen 80;
    server_name votre-domaine.com;
    root /var/www/playlist-app/public;

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

    error_log /var/log/nginx/playlist_error.log;
    access_log /var/log/nginx/playlist_access.log;
}
```

### 4. Configurer Spotify Redirect URI

Dans le Spotify Dashboard, ajoutez l'URL de production :
```
https://votre-domaine.com/spotify/auth/callback
```

---

## 🚀 Déploiement

### Automatique (via GitHub Actions)

Le déploiement se fait automatiquement quand vous poussez sur la branche `main` :

```bash
git add .
git commit -m "Deploy to production"
git push origin main
```

GitHub Actions va :
1. ✅ Installer les dépendances
2. ✅ Créer le fichier .env avec les secrets
3. ✅ Optimiser le cache Symfony
4. ✅ Déployer sur le serveur via SSH
5. ✅ Exécuter les migrations de base de données

### Manuel (via SSH)

Si vous préférez déployer manuellement :

```bash
# Sur votre machine locale
git push origin main

# Sur le serveur
cd /var/www/playlist-app
git pull origin main
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
php bin/console doctrine:migrations:migrate --no-interaction
```

---

## 🔍 Vérification du déploiement

Après le déploiement, vérifiez que tout fonctionne :

```bash
# Sur le serveur
cd /var/www/playlist-app

# Vérifier les permissions
ls -la var/

# Tester la connexion à la base de données
php bin/console doctrine:query:sql "SELECT 1"

# Vérifier les routes
php bin/console debug:router | grep spotify
```

---

## 🐛 Dépannage

### Erreur : Permission denied

```bash
sudo chown -R www-data:www-data /var/www/playlist-app/var
sudo chmod -R 775 /var/www/playlist-app/var
```

### Erreur : Database connection failed

Vérifiez le secret `DATABASE_URL` dans GitHub.

### Erreur : SSH connection failed

Vérifiez que :
- La clé publique SSH est bien dans `~/.ssh/authorized_keys` sur le serveur
- La clé privée complète est dans le secret `SSH_PRIVATE_KEY`
- L'utilisateur a les droits d'accès au dossier

### Erreur : Composer dependencies

```bash
# Sur le serveur
cd /var/www/playlist-app
rm -rf vendor/
composer install --no-dev --optimize-autoloader
```

---

## 📊 Monitoring

### Logs Nginx
```bash
tail -f /var/log/nginx/playlist_error.log
```

### Logs Symfony
```bash
tail -f /var/www/playlist-app/var/log/prod.log
```

### Status du déploiement

Allez sur GitHub > Actions pour voir l'historique des déploiements.

---

## 🔒 Sécurité

### Checklist de sécurité

- [x] `.env` est dans `.gitignore`
- [x] Les secrets sont dans GitHub Secrets (pas dans le code)
- [x] `APP_ENV=prod` en production
- [x] Base de données avec utilisateur dédié (pas root)
- [x] HTTPS activé (recommandé avec Let's Encrypt)
- [x] Firewall configuré sur le serveur
- [x] Clés SSH sécurisées (pas de mot de passe)

### Activer HTTPS avec Let's Encrypt

```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d votre-domaine.com
```

---

## 📦 Rollback en cas de problème

Si le déploiement cause des problèmes :

```bash
# Sur le serveur
cd /var/www/playlist-app
git log --oneline -5  # Voir les derniers commits
git reset --hard COMMIT_HASH  # Revenir à un commit précédent
composer install --no-dev --optimize-autoloader
php bin/console cache:clear --env=prod
```

---

## ✅ Checklist avant le premier déploiement

- [ ] Tous les secrets GitHub sont configurés (8 secrets)
- [ ] Le serveur est accessible via SSH
- [ ] La base de données est créée
- [ ] Nginx/Apache est configuré
- [ ] L'utilisateur SSH a les permissions sur `/var/www/playlist-app`
- [ ] Le Redirect URI Spotify est configuré pour la production
- [ ] `.env` est dans `.gitignore`
- [ ] Le repository GitHub est configuré

Une fois tout prêt, faites :
```bash
git add .
git commit -m "Initial production deployment"
git push origin main
```

🎉 **Votre application sera déployée automatiquement !**
