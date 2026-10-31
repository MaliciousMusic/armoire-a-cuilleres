#!/usr/bin/env sh
# Met à jour la démo en ligne (branche gh-pages du dépôt GitHub), à partir du serveur PHP local lancé sur le port 8080.
#   sh tools/deploy-demo.sh
set -e
cd "$(dirname "$0")/.."
URL="https://maliciousmusic.github.io/armoire-a-cuilleres"
REMOTE="$(git remote get-url origin)"
node tools/build.mjs
rm -f app/data/cache/*.html
node tools/export-demo.mjs demo-dist "$URL"
cd demo-dist
rm -rf .git
git init -q -b gh-pages
git add -A
git -c user.name="$(git -C .. config user.name)" -c user.email="$(git -C .. config user.email)" commit -q -m "Démo du $(date +%Y-%m-%d)"
git push -q -f "$REMOTE" gh-pages
echo "Démo publiée : $URL/fr/"
