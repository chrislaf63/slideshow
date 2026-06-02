FROM php:8.3-apache

# poppler-utils fournit pdftoppm (conversion PDF -> images, une par page)
RUN apt-get update && apt-get install -y --no-install-recommends \
        poppler-utils \
    && rm -rf /var/lib/apt/lists/*

# Limites d'upload PHP (ajustables : on accepte de gros fichiers/PDF)
RUN { \
        echo "upload_max_filesize = 64M"; \
        echo "post_max_size = 80M"; \
        echo "memory_limit = 256M"; \
    } > /usr/local/etc/php/conf.d/uploads.ini

# Config Apache : interdit l'exécution de tout script dans uploads/.
# Placé dans l'image (et non dans un .htaccess du volume) pour que la règle
# survive même si le volume monté masque le contenu du dossier.
COPY docker/uploads-no-exec.conf /etc/apache2/conf-available/uploads-no-exec.conf
RUN a2enconf uploads-no-exec

# Le code de l'application (immuable : reconstruit avec l'image)
COPY src/ /var/www/html/

# Entrypoint : corrige les permissions des volumes montés avant de lancer Apache
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
