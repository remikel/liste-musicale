#!/bin/bash

# Script pour nettoyer les secrets de l'historique Git

echo "🔒 Nettoyage des secrets de l'historique Git..."

# Créer un fichier avec les patterns à remplacer
cat > /tmp/secrets_patterns.txt << 'EOF'
YOUR_EXPOSED_CLIENT_ID==>your_spotify_client_id
YOUR_EXPOSED_CLIENT_SECRET==>your_spotify_client_secret
EOF

# Option 1 : Utiliser git filter-repo (recommandé)
if command -v git-filter-repo &> /dev/null; then
    echo "Utilisation de git-filter-repo..."
    git filter-repo --replace-text /tmp/secrets_patterns.txt --force

elif command -v bfg &> /dev/null; then
    echo "Utilisation de BFG Repo-Cleaner..."
    bfg --replace-text /tmp/secrets_patterns.txt
    git reflog expire --expire=now --all
    git gc --prune=now --aggressive

else
    echo "⚠️  git-filter-repo ou BFG Repo-Cleaner n'est pas installé"
    echo ""
    echo "Option 1 - Installer git-filter-repo (recommandé):"
    echo "  pip install git-filter-repo"
    echo "  Puis relancez ce script"
    echo ""
    echo "Option 2 - Télécharger BFG:"
    echo "  https://rtyley.github.io/bfg-repo-cleaner/"
    echo ""
    echo "Option 3 - Utiliser filter-branch (déprécié mais fonctionne):"
    echo "  Exécutez manuellement les commandes dans CLEAN_HISTORY.md"
fi

rm /tmp/secrets_patterns.txt

echo ""
echo "✅ Une fois nettoyé, forcez le push :"
echo "   git push --force --all"
echo "   git push --force --tags"
