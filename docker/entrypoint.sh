#!/bin/sh
set -e

# Les volumes montés depuis l'hôte n'ont pas forcément le bon propriétaire.
# Apache tourne en www-data : on lui donne la main sur les données
# persistantes pour éviter les erreurs d'écriture silencieuses.
chown -R www-data:www-data /var/www/html/uploads
chown www-data:www-data /var/www/html/slides.json 2>/dev/null || true

# Démarre la commande passée (par défaut apache2-foreground)
exec "$@"
