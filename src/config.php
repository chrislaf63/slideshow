<?php
/**
 * Configuration partagée de l'application.
 * Inclus par toutes les pages et tous les scripts d'API.
 */

// --- Chemins ---------------------------------------------------------------
define('BASE_DIR',     __DIR__);                       // racine web (/var/www/html)
// Données HORS de la racine web : robuste (montage de dossier) et non exposées.
define('DATA_DIR',     dirname(BASE_DIR) . '/data');   // /var/www/data
define('UPLOADS_DIR',  DATA_DIR . '/uploads');
define('SLIDES_FILE',  DATA_DIR . '/slides.json');

// URL publique des fichiers (Apache mappe /uploads -> DATA_DIR/uploads via Alias)
define('UPLOADS_URL',  'uploads');

// --- Sécurité --------------------------------------------------------------
// Mot de passe du back-office, fourni par variable d'environnement Docker.
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'change-me');

// --- Limites d'upload ------------------------------------------------------
define('MAX_FILE_SIZE', 64 * 1024 * 1024); // 64 Mo
define('MAX_PDF_PAGES', 50);               // garde-fou anti-abus

// Types réellement acceptés (validés par le contenu, pas par l'extension)
define('ALLOWED_IMAGE_MIME', [
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif',
    'image/bmp', 'image/svg+xml',
]);
define('ALLOWED_PDF_MIME', ['application/pdf']);

// --- Conversion PDF --------------------------------------------------------
define('PDF_DPI', 150); // résolution de rendu PDF -> PNG

// --- Diaporama -------------------------------------------------------------
define('SLIDE_INTERVAL_MS', 5000); // durée d'affichage de chaque slide (ms)
define('SLIDE_FADE_MS', 800);      // durée du fondu entre deux slides (ms)
define('SLIDE_POLL_MS', 10000);    // fréquence de vérification des changements (ms)