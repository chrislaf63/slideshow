<?php
/**
 * GET api/slides.php — Liste courante des slides (lecture publique).
 * Utilisé par le diaporama pour détecter les changements à chaud.
 * Pas d'authentification : ce sont les mêmes données que le diaporama affiche.
 */

require_once __DIR__ . '/../lib/store.php';

header('Content-Type: application/json');
header('Cache-Control: no-store');

echo json_encode(slides_all(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
