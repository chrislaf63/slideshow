<?php
/**
 * GET api/state.php — État courant du diaporama (lecture publique).
 * Renvoie à la fois les slides et l'image fixe, pour le rafraîchissement à chaud.
 *   { "slides": [...], "fixed": {...}|null }
 */

require_once __DIR__ . '/../lib/settings.php'; // inclut aussi store.php

header('Content-Type: application/json');
header('Cache-Control: no-store');

echo json_encode([
    'slides' => slides_all(),
    'fixed'  => fixed_get(),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
