# Test de l'import de playlist Spotify

## ✅ Modifications effectuées

1. **Bouton "Importer une playlist Spotify"** ajouté au-dessus du champ de recherche
2. **Gestion automatique de l'authentification** - Si pas connecté, propose de se connecter
3. **Modal de sélection** - Affiche toutes vos playlists Spotify avec images et nombre de titres
4. **Export Spotify amélioré** - Le bouton "Exporter vers Spotify" gère maintenant l'OAuth

## 🧪 Comment tester

### Étape 1 : Configuration Spotify Dashboard

1. Allez sur https://developer.spotify.com/dashboard
2. Ouvrez votre application
3. Cliquez sur "Edit Settings"
4. Dans **Redirect URIs**, ajoutez :
   ```
   http://localhost:8000/spotify/auth/callback
   ```
5. Sauvegardez

### Étape 2 : Créer une session

1. Allez sur http://localhost:8000
2. Créez une nouvelle session (ou rejoignez-en une)
3. Entrez votre nom

### Étape 3 : Tester l'import

**Scénario 1 : Utilisateur pas connecté**

1. Cliquez sur "Importer une playlist Spotify"
2. Vous verrez : "Vous devez vous connecter à Spotify pour importer une playlist. Voulez-vous vous connecter maintenant ?"
3. Cliquez sur "OK"
4. Vous serez redirigé vers Spotify
5. Autorisez l'application
6. Vous revenez sur votre session

**Scénario 2 : Utilisateur connecté**

1. Cliquez sur "Importer une playlist Spotify"
2. Un modal s'ouvre avec toutes vos playlists Spotify
3. Cliquez sur une playlist
4. Confirmez l'import
5. Les titres sont ajoutés à votre sélection !

### Étape 4 : Tester l'export

1. Ajoutez quelques titres à la session
2. Cliquez sur "Exporter vers Spotify"
3. Si pas connecté, même processus que l'import
4. Si connecté, confirmez la création
5. Une nouvelle playlist est créée sur votre compte Spotify !

## 📋 Ce qui se passe en coulisses

### Import
```
1. Clic sur "Importer playlist"
   ↓
2. Vérification : /spotify/auth/token
   ↓
3a. Si non connecté → Redirection vers /spotify/auth/login
3b. Si connecté → Récupération des playlists via /export/spotify/playlists
   ↓
4. Affichage dans le modal
   ↓
5. Clic sur une playlist → /export/spotify/import/{code}
   ↓
6. Titres ajoutés à la base de données avec spotify_track_id et spotify_uri
```

### Export
```
1. Clic sur "Exporter vers Spotify"
   ↓
2. Vérification : /spotify/auth/token
   ↓
3a. Si non connecté → Redirection vers /spotify/auth/login
3b. Si connecté → Création de playlist via /export/spotify/{code}
   ↓
4. Playlist créée sur Spotify
   ↓
5. Ouverture de la playlist dans un nouvel onglet
```

## 🎯 Comportements clés

### Si pas validé
- ✅ Bouton "Importer playlist" actif
- ✅ Peut ajouter des titres depuis Spotify
- ✅ Peut rechercher manuellement

### Si déjà validé
- ❌ Bouton "Importer playlist" affiche une alerte
- ❌ Ne peut plus modifier sa sélection
- ✅ Peut toujours exporter vers Spotify

### Limite de titres
- Si la session a une limite (ex: 10 titres max)
- Et que vous importez une playlist de 50 titres
- Seuls les 10 premiers seront importés
- Message : "10 titres importés sur 50 !"

## 🔍 Vérification en console

Ouvrez la console du navigateur (F12) et testez :

```javascript
// Vérifier l'état de connexion
fetch('/spotify/auth/token')
  .then(r => r.json())
  .then(d => console.log(d));
// Résultat : {authenticated: true, access_token: "...", ...}

// Lister les playlists (remplacer TOKEN)
fetch('/export/spotify/playlists', {
  method: 'POST',
  headers: {'Content-Type': 'application/json'},
  body: JSON.stringify({access_token: 'TOKEN'})
})
  .then(r => r.json())
  .then(d => console.log(d.playlists));
```

## 📸 Captures d'écran attendues

### 1. Bouton d'import visible
```
┌─────────────────────────────────────────┐
│ 🔍 Rechercher des titres                │
├─────────────────────────────────────────┤
│ [🎵 Importer une playlist Spotify]      │
│                                         │
│ [Rechercher un titre...]                │
└─────────────────────────────────────────┘
```

### 2. Modal de sélection
```
┌───────────────────────────────────────────┐
│ 🎵 Sélectionner une playlist Spotify  [×] │
├───────────────────────────────────────────┤
│ [Image] My Playlist 1          📥         │
│         50 titres                         │
│                                           │
│ [Image] Chill Vibes            📥         │
│         30 titres                         │
│                                           │
│ [Image] Party Mix              📥         │
│         100 titres                        │
└───────────────────────────────────────────┘
```

### 3. Import en cours
```
┌─────────────────────────────────────────┐
│ [⌛ Import en cours...]                  │
└─────────────────────────────────────────┘
```

### 4. Import réussi
```
┌─────────────────────────────────────────┐
│ ✅ 15 titres importés sur 50 !          │
│                           [OK]          │
└─────────────────────────────────────────┘
```

## 🐛 Dépannage

### Erreur : "redirect_uri_mismatch"
➡️ Vérifiez que `http://localhost:8000/spotify/auth/callback` est bien dans le dashboard Spotify

### Erreur : "Token d'accès requis"
➡️ Reconnectez-vous : `/spotify/auth/logout` puis réessayez

### Modal ne s'ouvre pas
➡️ Vérifiez la console (F12) pour voir les erreurs
➡️ Assurez-vous que Bootstrap JS est chargé

### Aucune playlist affichée
➡️ Créez au moins une playlist sur votre compte Spotify
➡️ Vérifiez que les scopes sont corrects (playlist-read-private)

## 🎉 Succès !

Si tout fonctionne, vous devriez pouvoir :
- ✅ Cliquer sur "Importer playlist"
- ✅ Se connecter à Spotify si nécessaire
- ✅ Voir vos playlists dans un modal
- ✅ Importer une playlist entière en 2 clics
- ✅ Exporter la session vers Spotify
- ✅ Voir la nouvelle playlist sur votre compte

**L'intégration Spotify est complète !** 🎵🚀
