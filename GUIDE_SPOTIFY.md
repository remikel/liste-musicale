# 🎵 Guide Spotify - Import/Export de playlists

## Pourquoi ça ne fonctionne pas actuellement ?

Votre application a **2 systèmes d'authentification Spotify** :

### 1. ✅ Client Credentials (DÉJÀ CONFIGURÉ)
```
Votre app → Spotify API
```
**Permet** : Rechercher des titres
**Ne permet PAS** : Accéder aux playlists utilisateur

### 2. ❌ OAuth Utilisateur (MANQUANT - À CONFIGURER)
```
Utilisateur → Autorise votre app → Token → Import/Export
```
**Permet** : Tout ! (lire/créer/modifier des playlists)

---

## 🔧 Configuration en 3 étapes

### Étape 1 : Configurer l'URL de callback sur Spotify

1. Allez sur https://developer.spotify.com/dashboard
2. Cliquez sur votre application
3. Cliquez sur **"Edit Settings"**
4. Dans **"Redirect URIs"**, ajoutez :
   ```
   http://localhost:8000/spotify/auth/callback
   ```
5. Cliquez sur **"Save"**

**Capture d'écran de ce que vous devriez voir :**
```
┌─────────────────────────────────────────┐
│ Redirect URIs                           │
├─────────────────────────────────────────┤
│ http://localhost:8000/spotify/auth/callback │ [×]
│                                         │
│ [+ Add another]                         │
│                                   [Save]│
└─────────────────────────────────────────┘
```

---

### Étape 2 : Tester la connexion

Ouvrez votre navigateur et allez sur :
```
http://localhost:8000/spotify/auth/login
```

**Ce qui va se passer :**
1. Vous serez redirigé vers Spotify
2. Spotify vous demandera d'autoriser l'application
3. Vous serez redirigé vers votre app
4. Vous verrez : "Connecté à Spotify avec succès !"

---

### Étape 3 : Utiliser l'import/export

Une fois connecté, le token est stocké en session.

#### Test rapide en console du navigateur :

```javascript
// 1. Vérifier que vous êtes connecté
fetch('/spotify/auth/token')
  .then(r => r.json())
  .then(d => console.log(d));
// Résultat attendu : {authenticated: true, access_token: "BQ...", expires_at: ...}

// 2. Lister vos playlists
fetch('/export/spotify/playlists', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({access_token: 'COLLER_LE_TOKEN_ICI'})
})
  .then(r => r.json())
  .then(d => console.log(d.playlists));

// 3. Importer une playlist (remplacer ABC12 par votre code de session)
fetch('/export/spotify/import/ABC12', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({
    access_token: 'COLLER_LE_TOKEN_ICI',
    playlist_id: 'COLLER_ID_PLAYLIST_ICI'
  })
})
  .then(r => r.json())
  .then(d => console.log(d));
```

---

## 📊 Flux complet

```
┌──────────────┐
│ Utilisateur  │
└──────┬───────┘
       │ 1. Clique sur "Se connecter à Spotify"
       ↓
┌──────────────────────────┐
│ /spotify/auth/login      │
└──────┬───────────────────┘
       │ 2. Redirige vers Spotify
       ↓
┌──────────────────────────┐
│ Spotify - Page d'auth    │
│ "Autoriser cette app?"   │
└──────┬───────────────────┘
       │ 3. Utilisateur accepte
       ↓
┌──────────────────────────┐
│ /spotify/auth/callback   │
│ Échange code → token     │
│ Stocke en session        │
└──────┬───────────────────┘
       │ 4. Token disponible !
       ↓
┌──────────────────────────────────────────┐
│ Import/Export maintenant disponibles    │
│ - /export/spotify/playlists              │
│ - /export/spotify/import/{code}          │
│ - /export/spotify/{code}                 │
└──────────────────────────────────────────┘
```

---

## 🧪 Test de vérification

Exécutez ceci pour vérifier que tout est en place :

```bash
# Vérifier que les routes existent
php bin/console debug:router | grep spotify

# Vous devriez voir :
# ✓ app_spotify_login
# ✓ app_spotify_callback
# ✓ app_spotify_logout
# ✓ app_spotify_get_token
# ✓ app_get_spotify_playlists
# ✓ app_import_spotify
# ✓ app_export_spotify
```

---

## ❓ FAQ

### Q: J'ai une erreur "redirect_uri_mismatch"
**R:** L'URL de callback dans le dashboard Spotify ne correspond pas exactement. Vérifiez qu'il n'y a pas d'espace ou de différence (http vs https, localhost vs 127.0.0.1).

### Q: Le token expire ?
**R:** Oui, après 1 heure. Utilisez `/spotify/auth/refresh` pour le rafraîchir automatiquement.

### Q: Dois-je me reconnecter à chaque fois ?
**R:** Non, tant que votre session PHP est active. Le token est stocké en session.

### Q: Puis-je utiliser l'import/export sans connexion ?
**R:** Non, c'est impossible. Spotify exige que l'utilisateur autorise explicitement l'accès à ses playlists.

---

## 📝 Résumé

**Avant** :
- ❌ Import de playlist : Ne fonctionne pas
- ❌ Export vers Spotify : Ne fonctionne pas
- ✅ Recherche de titres : Fonctionne

**Après configuration** :
- ✅ Import de playlist : Fonctionne avec OAuth
- ✅ Export vers Spotify : Fonctionne avec OAuth
- ✅ Recherche de titres : Fonctionne toujours

**Action requise** :
1. Ajouter `http://localhost:8000/spotify/auth/callback` dans Spotify Dashboard
2. Tester en allant sur `/spotify/auth/login`
3. Intégrer les boutons dans votre interface (voir [OAUTH_SETUP.md](OAUTH_SETUP.md))

C'est tout ! 🎉
