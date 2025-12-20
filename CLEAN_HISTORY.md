# 🔒 Nettoyer les secrets de l'historique Git

## ⚠️ URGENT : Clés API exposées

Les clés Spotify ont été exposées dans `SPOTIFY_API.md` et poussées sur GitHub.

### 🔴 Actions IMMÉDIATES

#### 1. RÉGÉNÉRER les clés Spotify (OBLIGATOIRE)

1. Allez sur https://developer.spotify.com/dashboard
2. Ouvrez votre application
3. Cliquez sur **"Settings"**
4. Cliquez sur **"Reset Client Secret"** ou recréez l'application
5. Notez les nouvelles clés
6. Mettez à jour votre `.env` local avec les nouvelles clés

**Les anciennes clés sont maintenant compromises et doivent être révoquées !**

#### 2. Nettoyer l'historique Git

Choisissez une des méthodes ci-dessous :

---

## Méthode 1 : Avec git-filter-repo (RECOMMANDÉ)

### Installation

```bash
# Avec pip
pip install git-filter-repo

# Ou avec scoop (Windows)
scoop install git-filter-repo
```

### Utilisation

```bash
# Créer un fichier avec les remplacements
cat > secrets_replace.txt << 'EOF'
YOUR_EXPOSED_CLIENT_ID==>REDACTED_CLIENT_ID
YOUR_EXPOSED_CLIENT_SECRET==>REDACTED_CLIENT_SECRET
EOF

# Nettoyer l'historique
git filter-repo --replace-text secrets_replace.txt --force

# Vérifier
git log --all --full-history --source --all -- '*SPOTIFY_API.md'

# Forcer le push (écrase l'historique GitHub)
git remote add origin git@github.com:remikel/liste-musicale.git
git push origin --force --all
git push origin --force --tags
```

---

## Méthode 2 : Avec BFG Repo-Cleaner

### Installation

```bash
# Télécharger BFG
# Windows: https://rtyley.github.io/bfg-repo-cleaner/
# Ou avec scoop:
scoop install bfg
```

### Utilisation

```bash
# Créer un fichier avec les secrets
cat > secrets.txt << 'EOF'
YOUR_EXPOSED_CLIENT_ID
YOUR_EXPOSED_CLIENT_SECRET
EOF

# Nettoyer avec BFG
bfg --replace-text secrets.txt

# Nettoyer les refs
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Forcer le push
git push origin --force --all
git push origin --force --tags
```

---

## Méthode 3 : Supprimer et recréer le repository (SIMPLE)

Si c'est un nouveau projet, le plus simple est de repartir de zéro :

```bash
# 1. Sauvegarder les fichiers importants
cp .env .env.backup
cp -r src/ src_backup/

# 2. Supprimer le repository GitHub
# Allez sur https://github.com/remikel/liste-musicale/settings
# Scrollez tout en bas et cliquez "Delete this repository"

# 3. Supprimer le dossier .git local
rm -rf .git

# 4. Recréer un nouveau repository
git init
git branch -M main

# 5. Vérifier que .env est ignoré
cat .gitignore | grep "/.env"

# 6. Recréer le repository sur GitHub
# Allez sur https://github.com/new
# Nom: liste-musicale

# 7. Premier commit (sans les secrets)
git add .
git commit -m "Initial commit (secrets removed)"

# 8. Push
git remote add origin git@github.com:remikel/liste-musicale.git
git push -u origin main
```

---

## Méthode 4 : Sans outils externes (compliqué)

```bash
# Créer un script de remplacement
cat > /tmp/replace-secrets.sed << 'EOF'
s/YOUR_EXPOSED_CLIENT_ID/REDACTED_CLIENT_ID/g
s/YOUR_EXPOSED_CLIENT_SECRET/REDACTED_CLIENT_SECRET/g
EOF

# Nettoyer l'historique
git filter-branch --force --tree-filter '
  if [ -f SPOTIFY_API.md ]; then
    sed -i -f /tmp/replace-secrets.sed SPOTIFY_API.md
  fi
' --tag-name-filter cat -- --all

# Nettoyer
rm -rf .git/refs/original/
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Forcer le push
git push origin --force --all
git push origin --force --tags
```

---

## ✅ Vérifications après nettoyage

```bash
# 1. Vérifier qu'il n'y a plus de secrets dans l'historique
git log --all --full-history --source -S "YOUR_EXPOSED_CLIENT_ID"
git log --all --full-history --source -S "YOUR_EXPOSED_CLIENT_SECRET"
# Résultat attendu : rien

# 2. Vérifier le contenu de SPOTIFY_API.md dans l'historique
git log -p --all -- SPOTIFY_API.md | grep -E "(CLIENT_ID|CLIENT_SECRET)"
# Ne devrait pas afficher les vraies clés

# 3. Vérifier sur GitHub
# Allez sur https://github.com/remikel/liste-musicale/commits/main
# Ouvrez les anciens commits et vérifiez SPOTIFY_API.md
```

---

## 🔐 Mettre à jour les secrets GitHub

Une fois les nouvelles clés générées :

1. Allez sur https://github.com/remikel/liste-musicale/settings/secrets/actions
2. Mettez à jour les secrets :
   - `SPOTIFY_CLIENT_ID` → nouvelle valeur
   - `SPOTIFY_CLIENT_SECRET` → nouvelle valeur

---

## 📋 Checklist de sécurité

- [ ] Nouvelles clés Spotify générées
- [ ] Anciennes clés révoquées/désactivées
- [ ] Historique Git nettoyé localement
- [ ] Historique GitHub nettoyé (force push)
- [ ] Vérifications effectuées (pas de secrets dans l'historique)
- [ ] Secrets GitHub mis à jour
- [ ] `.env` bien dans `.gitignore`
- [ ] Nouveaux commits ne contiennent pas de secrets

---

## 🚨 Pourquoi c'est grave ?

Les clés dans l'historique Git :
- ✅ Ont été supprimées du dernier commit
- ❌ Sont TOUJOURS dans l'historique des commits précédents
- ❌ Sont TOUJOURS visibles sur GitHub
- ❌ Peuvent être utilisées par n'importe qui

**Solution** : Nettoyer l'historique ET régénérer les clés !

---

## 💡 Pour éviter ça à l'avenir

1. Toujours ajouter `.env` au `.gitignore` AVANT le premier commit
2. Utiliser `.env.example` pour les exemples
3. Utiliser des pre-commit hooks pour scanner les secrets
4. Activer GitHub Secret Scanning (gratuit pour les repos publics)

---

## 🆘 Aide

Si vous avez des questions ou problèmes :
1. Vérifiez que `.env` est bien dans `.gitignore`
2. Ne committez JAMAIS de fichiers avec des secrets
3. Utilisez toujours des variables d'environnement
4. En cas de doute, régénérez les clés
