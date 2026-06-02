<?php
/**
 * POST api/fixed_upload.php — Définit (ou remplace) l'image fixe du volet droit.
 * Champ attendu : un seul fichier image dans `file`.
 * Réponse JSON : { ok, fixed: {...} } ou { ok:false, error }
 */

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/settings.php';

api_require_admin();
header('Content-Type: application/json');

if (empty($_FILES['file']) || !is_array($_FILES['file'])) {
    echo json_encode(['ok' => false, 'error' => 'no_file']);
    exit;
}

$f = $_FILES['file'];

if ($f['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'upload_error_' . $f['error']]);
    exit;
}
if ($f['size'] > MAX_FILE_SIZE) {
    echo json_encode(['ok' => false, 'error' => 'too_large']);
    exit;
}
if (!is_uploaded_file($f['tmp_name'])) {
    echo json_encode(['ok' => false, 'error' => 'invalid']);
    exit;
}

$imageExt = [
    'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
    'image/webp' => 'webp', 'image/avif' => 'avif', 'image/bmp' => 'bmp',
    'image/svg+xml' => 'svg',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($f['tmp_name']);

if (!isset($imageExt[$mime])) {
    // Le volet fixe n'accepte que des images (pas de PDF).
    echo json_encode(['ok' => false, 'error' => 'unsupported_type', 'mime' => $mime]);
    exit;
}

$stored = slides_gen_id() . '.' . $imageExt[$mime];
if (!move_uploaded_file($f['tmp_name'], UPLOADS_DIR . '/' . $stored)) {
    echo json_encode(['ok' => false, 'error' => 'move_failed']);
    exit;
}

$fixed = fixed_set($stored, $f['name']);
echo json_encode(['ok' => true, 'fixed' => $fixed]);
