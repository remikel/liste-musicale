# 📤 Guide : Pousser vers GitHub

## 🎯 Repository GitHub

```
git@github.com:remikel/liste-musicale.git
```

## 🚀 Commandes à exécuter

### 1. Initialiser Git (si pas déjà fait)

```bash
cd c:\Users\Remi\playlist-app
git init
```

### 2. Vérifier que .env est bien ignoré

```bash
# Vérifier le .gitignore
cat .gitignore | grep .env

# Résultat attendu : devrait contenir /.env
```

### 3. Ajouter le remote GitHub

```bash
git remote add origin git@github.com:remikel/liste-musicale.git

# Vérifier
git remote -v
```

### 4. Créer la branche main et faire le premier commit

```bash
# Ajouter tous les fichiers (sauf .env qui est ignoré)
git add .

# Vérifier ce qui sera commité
git status

# IMPORTANT: Vérifiez que .env n'apparaît PAS dans la liste
# Si .env apparaît, il faut le retirer :
# git rm --cached .env

# Faire le commit
git commit -m "Initial commit - Playlist Collaborative App

- Intégration complète Spotify (recherche, import, export)
- Authentification OAuth2 pour Spotify
- Import de playlists Spotify
- Export vers Spotify
- Système de sessions collaboratives
- GitHub Actions pour CI/CD
- Documentation complète"

# Renommer la branche en main
git branch -M main
```

### 5. Pousser vers GitHub

```bash
# Premier push
git push -u origin main

# Ensuite, pour les push suivants
git push
```

---

## ⚠️ IMPORTANT : Vérifications avant le push

### Checklist de sécurité

Avant de faire `git push`, vérifiez :

```bash
# 1. Vérifier que .env n'est PAS tracké
git status | grep .env
# Résultat : ne devrait rien afficher

# 2. Vérifier le .gitignore
cat .gitignore | grep -E "\.env$"
# Résultat : devrait afficher /.env

# 3. Lister les fichiers qui seront poussés
git ls-files | grep .env
# Résultat : ne devrait afficher que .env.example

# 4. Vérifier qu'il n'y a pas de secrets dans le code
grep -r "SPOTIFY_CLIENT_SECRET" --exclude-dir=vendor --exclude-dir=.git --exclude="*.md" .
# Résultat : ne devrait trouver que dans .env et .env.example
```

### Si .env est déjà tracké par erreur

```bash
# Retirer .env du tracking Git
git rm --cached .env

# Vérifier
git status

# Re-commit
git commit -m "Remove .env from version control"
git push
```

---

## 🔐 Configuration des secrets GitHub (APRÈS le push)

Une fois le code poussé, allez sur GitHub configurer les secrets :

1. Allez sur https://github.com/remikel/liste-musicale
2. Cliquez sur **Settings** > **Secrets and variables** > **Actions**
3. Cliquez sur **New repository secret**
4. Ajoutez chaque secret :

| Secret | Valeur à mettre |
|--------|----------------|
| `APP_SECRET` | Générez avec `php -r "echo bin2hex(random_bytes(32));"` |
| `DATABASE_URL` | `mysql://user:password@host:3306/database?serverVersion=mariadb-10.6.21` |
| `SPOTIFY_CLIENT_ID` | Votre Client ID depuis le .env |
| `SPOTIFY_CLIENT_SECRET` | Votre Client Secret depuis le .env |
| `SSH_PRIVATE_KEY` | Votre clé SSH privée |
| `REMOTE_HOST` | IP ou domaine du serveur |
| `REMOTE_USER` | Nom d'utilisateur SSH |
| `REMOTE_TARGET` | `/var/www/playlist-app` |

---

## 📊 Vérification post-push

Après avoir poussé :

1. **Vérifier sur GitHub** : https://github.com/remikel/liste-musicale
   - Le code est bien là
   - `.env` n'apparaît PAS dans les fichiers
   - Seulement `.env.example` est visible

2. **Vérifier les Actions**
   - Allez sur l'onglet **Actions**
   - Le workflow "Tests" devrait se lancer automatiquement
   - Attendez qu'il soit vert ✅

3. **Badges**
   - Les badges dans README.md devraient être actifs
   - [![Deploy](https://github.com/remikel/liste-musicale/actions/workflows/deploy.yml/badge.svg)](...)

---

## 🔄 Workflow de développement

### Développement local

```bash
# Créer une branche
git checkout -b feature/nouvelle-fonctionnalite

# Faire des modifications
# ... coder ...

# Commiter
git add .
git commit -m "Add nouvelle fonctionnalité"

# Pousser la branche
git push -u origin feature/nouvelle-fonctionnalite
```

### Pull Request

1. Sur GitHub, créez une Pull Request
2. Les tests s'exécutent automatiquement
3. Une fois approuvée, mergez vers `main`
4. Le déploiement se lance automatiquement

### Déploiement direct

```bash
# Depuis main
git checkout main
git pull
git merge feature/nouvelle-fonctionnalite
git push origin main
# → Déploiement automatique !
```

---

## 🐛 Dépannage

### Erreur : Permission denied (publickey)

```bash
# Vérifier que votre clé SSH est configurée
ssh -T git@github.com

# Si erreur, configurer la clé
ssh-keygen -t ed25519 -C "votre@email.com"
cat ~/.ssh/id_ed25519.pub
# Copiez la clé et ajoutez-la sur GitHub > Settings > SSH Keys
```

### Erreur : .env apparaît dans git status

```bash
# Retirer du tracking
git rm --cached .env

# Vérifier le .gitignore
echo "/.env" >> .gitignore

# Commit
git add .gitignore
git commit -m "Ensure .env is ignored"
```

### Erreur : Remote already exists

```bash
# Supprimer et recréer
git remote remove origin
git remote add origin git@github.com:remikel/liste-musicale.git
```

---

## ✅ Checklist finale

Avant de pousser pour la première fois :

- [ ] `.env` est dans `.gitignore`
- [ ] `git status` ne montre PAS `.env`
- [ ] `.env.example` existe avec des valeurs d'exemple
- [ ] Pas de secrets hardcodés dans le code
- [ ] README.md est à jour
- [ ] Les workflows GitHub Actions sont présents
- [ ] Le remote est configuré : `git remote -v`

Une fois tout vérifié :

```bash
git push -u origin main
```

🎉 **Votre code est maintenant sur GitHub !**

Prochaine étape : Configurer les secrets pour le déploiement automatique (voir [DEPLOYMENT.md](DEPLOYMENT.md))
