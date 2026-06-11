#!/bin/sh
# Copie les fichiers de config locaux (gitignored) vers la copie MAMP htdocs.
# Usage : ./scripts/sync-mamp-config.sh

set -e
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
MAMP="/Applications/MAMP/htdocs/Projet-ABS"

if [ ! -d "$MAMP" ]; then
  echo "Dossier MAMP introuvable : $MAMP"
  echo "Adaptez MAMP= dans ce script ou copiez manuellement :"
  echo "  cp app/Config/bdd.exemple.php app/Config/bdd.php"
  exit 1
fi

for f in bdd.php mapbox.php; do
  if [ -f "$ROOT/app/Config/$f" ]; then
    cp "$ROOT/app/Config/$f" "$MAMP/app/Config/$f"
    echo "OK : $f"
  elif [ -f "$ROOT/app/Config/${f%.php}.exemple.php" ]; then
    cp "$ROOT/app/Config/${f%.php}.exemple.php" "$MAMP/app/Config/$f"
    echo "OK : $f (depuis exemple)"
  fi
done

echo "Config synchronisée vers $MAMP/app/Config/"
