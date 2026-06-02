<?php
/**
 * POST api/save_order.php — Enregistre le nouvel ordre des slides.
 * Corps attendu (JSON) : { "ids": ["id1", "id2", ...] }
 */

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/store.php';

api_require_admin();
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$ids  = is_array($body) ? ($body['ids'] ?? null) : null;

if (!is_array($ids)) {
    echo json_encode(['ok' => false, 'error' => 'missing_ids']);
    exit;
}

$ok = slides_reorder($ids);
echo json_encode(['ok' => $ok]);
