#!/bin/sh
set -e

# Le volume monté (/var/www/data) peut être vide au premier démarrage.
# On garantit la structure et un slides.json valide : plus aucune init manuelle.
mkdir -p /var/www/data/uploads
if [ ! -f /var/www/data/slides.json ]; then
    echo "[]" > /var/www/data/slides.json
fi

# Apache (www-data) doit pouvoir écrire dans les données persistantes.
chown -R www-data:www-data /var/www/data

exec "$@"