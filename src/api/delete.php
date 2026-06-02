<?php
/**
 * POST api/delete.php — Suppression d'une slide par id.
 * Corps attendu (JSON) : { "id": "..." }
 */

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/store.php';

api_require_admin();
header('Content-Type: application/json');

$body = json_decode(file_get_contents('php://input'), true);
$id   = is_array($body) ? ($body['id'] ?? '') : '';

if ($id === '') {
    echo json_encode(['ok' => false, 'error' => 'missing_id']);
    exit;
}

$ok = slides_remove($id);
echo json_encode(['ok' => $ok]);
