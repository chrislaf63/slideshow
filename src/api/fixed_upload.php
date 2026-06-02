<?php
/**
 * POST api/fixed_upload.php — Définit (ou remplace) l'image fixe du volet droit.
 * Champ attendu : un seul fichier dans `file` (image OU PDF).
 * Pour un PDF, seule la PREMIÈRE page est convertie en image.
 * Réponse JSON : { ok, fixed: {...} } ou { ok:false, error }
 */

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/settings.php';
require_once __DIR__ . '/../lib/pdf.php';

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

// --- Cas image -------------------------------------------------------------
if (isset($imageExt[$mime])) {
    $stored = slides_gen_id() . '.' . $imageExt[$mime];
    if (!move_uploaded_file($f['tmp_name'], UPLOADS_DIR . '/' . $stored)) {
        echo json_encode(['ok' => false, 'error' => 'move_failed']);
        exit;
    }
    echo json_encode(['ok' => true, 'fixed' => fixed_set($stored, $f['name'])]);
    exit;
}

// --- Cas PDF (première page seulement) -------------------------------------
if (in_array($mime, ALLOWED_PDF_MIME, true)) {
    $pdfTmp = UPLOADS_DIR . '/' . slides_gen_id() . '.pdf';
    if (!move_uploaded_file($f['tmp_name'], $pdfTmp)) {
        echo json_encode(['ok' => false, 'error' => 'move_failed']);
        exit;
    }
    if (pdf_page_count($pdfTmp) === 0) {
        @unlink($pdfTmp);
        echo json_encode(['ok' => false, 'error' => 'pdf_unreadable']);
        exit;
    }
    $base  = pathinfo($pdfTmp, PATHINFO_FILENAME);
    $image = pdf_first_page($pdfTmp, $base);
    @unlink($pdfTmp); // le PDF source n'a plus d'utilité une fois converti
    if ($image === null) {
        echo json_encode(['ok' => false, 'error' => 'pdf_convert_failed']);
        exit;
    }
    echo json_encode(['ok' => true, 'fixed' => fixed_set($image, $f['name'])]);
    exit;
}

// --- Type refusé -----------------------------------------------------------
echo json_encode(['ok' => false, 'error' => 'unsupported_type', 'mime' => $mime]);