<?php
/**
 * POST api/fixed_clear.php — Retire l'image fixe du volet droit.
 */

// Les erreurs PHP vont aux logs, jamais dans la réponse JSON.
ini_set('display_errors', '0');

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/settings.php';

api_require_admin();
header('Content-Type: application/json');

$ok = fixed_clear();
echo json_encode(['ok' => $ok]);