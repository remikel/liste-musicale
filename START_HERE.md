# 🚀 COMMENCEZ ICI !

## Repository GitHub

```
git@github.com:remikel/liste-musicale.git
```

---

## ⚡ Push rapide vers GitHub (3 commandes)

```bash
# 1. Ajouter le remote (une seule fois)
git remote add origin git@github.com:remikel/liste-musicale.git

# 2. Commiter tout
git add . && git commit -m "Initial commit"

# 3. Pousser
git push -u origin main
```

**C'est fait !** 🎉

---

## 🔐 Fichiers sensibles (SÉCURITÉ)

### ✅ Ce qui est protégé

- ✅ `.env` est dans `.gitignore` → NE SERA PAS poussé
- ✅ Vos secrets Spotify sont en sécurité
- ✅ Vos mots de passe BDD ne seront pas sur GitHub

### ✅ Ce qui sera poussé

- ✅ `.env.example` (avec des exemples, pas vos vraies valeurs)
- ✅ Le code de l'application
- ✅ La documentation
- ✅ Les workflows GitHub Actions

---

## 📋 Après le push : Configurer les secrets

Allez sur : https://github.com/remikel/liste-musicale/settings/secrets/actions

**8 secrets à créer** (copiez-collez depuis votre `.env`) :

| Secret | Où le trouver |
|--------|--------------|
| `SPOTIFY_CLIENT_ID` | Dans votre `.env` |
| `SPOTIFY_CLIENT_SECRET` | Dans votre `.env` |
| `APP_SECRET` | Générez avec `php -r "echo bin2hex(random_bytes(32));"` |
| `DATABASE_URL` | Format : `mysql://user:pass@host:3306/db?serverVersion=...` |
| `SSH_PRIVATE_KEY` | Si vous déployez sur un serveur |
| `REMOTE_HOST` | IP/domaine du serveur |
| `REMOTE_USER` | Username SSH |
| `REMOTE_TARGET` | Ex: `/var/www/playlist-app` |

---

## 🎯 Redirect URI Spotify

N'oubliez pas d'ajouter dans Spotify Dashboard :

**Local** :
```
http://localhost:8000/spotify/auth/callback
```

**Production** (si vous déployez) :
```
https://votre-domaine.com/spotify/auth/callback
```

---

## 📚 Documentation complète

Besoin de plus de détails ? Consultez :

| Fichier | Description |
|---------|-------------|
| [GIT_COMMANDS.txt](GIT_COMMANDS.txt) | **Toutes les commandes Git** |
| [SETUP_COMPLET.md](SETUP_COMPLET.md) | **Guide complet de A à Z** |
| [DEPLOYMENT.md](DEPLOYMENT.md) | Déploiement automatique |
| [GITHUB_PUSH.md](GITHUB_PUSH.md) | Guide détaillé GitHub |
| [README.md](README.md) | Vue d'ensemble du projet |

---

## ✅ Checklist rapide

Avant de pousser :
- [ ] `.env` n'apparaît PAS dans `git status`
- [ ] `.env.example` existe
- [ ] Vos secrets ne sont pas hardcodés dans le code

Après le push :
- [ ] Code visible sur GitHub
- [ ] `.env` n'est PAS visible sur GitHub
- [ ] Secrets configurés dans GitHub Actions

---

## 🆘 Problème ?

### .env apparaît dans git status ?

```bash
git rm --cached .env
git commit -m "Remove .env"
```

### Erreur SSH ?

```bash
ssh -T git@github.com
# Si erreur, configurez votre clé SSH sur GitHub
```

### Besoin d'aide ?

Consultez [SETUP_COMPLET.md](SETUP_COMPLET.md) pour le guide pas à pas.

---

## 🎉 C'est tout !

Votre application est maintenant sur GitHub avec :
- ✅ Code sécurisé (.env ignoré)
- ✅ CI/CD automatique
- ✅ Documentation complète
- ✅ Prête à être déployée

**Bon développement !** 🚀
