# 🚨 URGENCE : Clés API exposées - Action immédiate

## ✅ Ce qui a été fait

1. ✅ Clés supprimées de tous les fichiers de documentation
2. ✅ Commit créé avec les fichiers nettoyés
3. ✅ `.env` n'est plus tracké par Git

## 🔴 CE QUE VOUS DEVEZ FAIRE MAINTENANT

### Étape 1 : RÉGÉNÉRER les clés Spotify (OBLIGATOIRE)

⚠️ **Les anciennes clés sont compromises** car elles sont dans l'historique Git !

```
1. Allez sur https://developer.spotify.com/dashboard
2. Ouvrez votre application
3. Cliquez sur "Settings"
4. Cliquez sur "Reset Client Secret" ou supprimez et recréez l'app
5. Notez les NOUVELLES clés
6. Mettez à jour votre .env avec les nouvelles clés
```

### Étape 2 : Nettoyer l'historique Git

Vous avez 2 options :

#### Option A : Recommencer de zéro (PLUS SIMPLE)

```bash
# 1. Sauvegarder votre .env
cp .env .env.backup

# 2. Supprimer le repository sur GitHub
# Allez sur https://github.com/remikel/liste-musicale/settings
# Scrollez en bas et cliquez "Delete this repository"

# 3. Supprimer .git local
rm -rf .git

# 4. Recommencer
git init
git branch -M main
git add .
git commit -m "Initial commit (secrets removed)"

# 5. Recréer le repo sur GitHub et push
git remote add origin git@github.com:remikel/liste-musicale.git
git push -u origin main
```

#### Option B : Nettoyer l'historique (AVANCÉ)

Suivez le guide détaillé : [CLEAN_HISTORY.md](CLEAN_HISTORY.md)

### Étape 3 : Push les changements

```bash
# Si vous avez choisi l'Option A, c'est déjà fait

# Si vous gardez l'historique, push le commit de nettoyage
git push origin master --force
```

## 📋 Checklist de sécurité

- [ ] Nouvelles clés Spotify générées
- [ ] Anciennes clés révoquées
- [ ] `.env` mis à jour avec nouvelles clés
- [ ] Historique Git nettoyé (option A ou B)
- [ ] Push effectué
- [ ] Vérification sur GitHub : pas de secrets visibles
- [ ] Si secrets GitHub configurés : mis à jour avec nouvelles clés

## ⏱️ FAITES-LE MAINTENANT

Ne remettez pas à plus tard ! Les clés exposées peuvent être utilisées par n'importe qui.

**Temps estimé : 10 minutes**

---

## 🔒 Pour éviter ça à l'avenir

1. ✅ `.env` est maintenant dans `.gitignore`
2. ✅ Utilisez toujours `.env.example` pour les exemples
3. ✅ Ne committez JAMAIS de secrets
4. ✅ Vérifiez avant chaque push : `git status` ne doit pas montrer `.env`
