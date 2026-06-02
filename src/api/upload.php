<?php
/**
 * POST api/upload.php — Réception d'un ou plusieurs fichiers.
 * Valide le type réel (pas l'extension), stocke les images, convertit les PDF
 * en images (une slide par page), puis enregistre les slides.
 * Réponse JSON : { ok, added: [...slides], errors: [...] }
 */

require_once __DIR__ . '/../lib/auth.php';
require_once __DIR__ . '/../lib/store.php';
require_once __DIR__ . '/../lib/pdf.php';

api_require_admin();
header('Content-Type: application/json');

$added  = [];
$errors = [];

if (empty($_FILES['files'])) {
    echo json_encode(['ok' => false, 'error' => 'no_files']);
    exit;
}

// Normalise $_FILES['files'] (champ multiple) en liste de fichiers individuels.
$files = $_FILES['files'];
$count = is_array($files['name']) ? count($files['name']) : 0;

$finfo = new finfo(FILEINFO_MIME_TYPE);

// Extensions sûres par type MIME image
$imageExt = [
    'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif',
    'image/webp' => 'webp', 'image/avif' => 'avif', 'image/bmp' => 'bmp',
    'image/svg+xml' => 'svg',
];

for ($i = 0; $i < $count; $i++) {
    $name = $files['name'][$i];
    $tmp  = $files['tmp_name'][$i];
    $err  = $files['error'][$i];
    $size = $files['size'][$i];

    if ($err !== UPLOAD_ERR_OK) {
        $errors[] = ['file' => $name, 'error' => 'upload_error_' . $err];
        continue;
    }
    if ($size > MAX_FILE_SIZE) {
        $errors[] = ['file' => $name, 'error' => 'too_large'];
        continue;
    }
    if (!is_uploaded_file($tmp)) {
        $errors[] = ['file' => $name, 'error' => 'invalid'];
        continue;
    }

    $mime = $finfo->file($tmp);

    // --- Cas image ---------------------------------------------------------
    if (isset($imageExt[$mime])) {
        $stored = slides_gen_id() . '.' . $imageExt[$mime];
        if (!move_uploaded_file($tmp, UPLOADS_DIR . '/' . $stored)) {
            $errors[] = ['file' => $name, 'error' => 'move_failed'];
            continue;
        }
        $added[] = slides_add($stored, $name);
        continue;
    }

    // --- Cas PDF -----------------------------------------------------------
    if (in_array($mime, ALLOWED_PDF_MIME, true)) {
        // On stocke le PDF temporairement pour le convertir
        $pdfTmp = UPLOADS_DIR . '/' . slides_gen_id() . '.pdf';
        if (!move_uploaded_file($tmp, $pdfTmp)) {
            $errors[] = ['file' => $name, 'error' => 'move_failed'];
            continue;
        }
        $pages = pdf_page_count($pdfTmp);
        if ($pages === 0) {
            @unlink($pdfTmp);
            $errors[] = ['file' => $name, 'error' => 'pdf_unreadable'];
            continue;
        }
        if ($pages > MAX_PDF_PAGES) {
            @unlink($pdfTmp);
            $errors[] = ['file' => $name, 'error' => 'too_many_pages'];
            continue;
        }
        $base   = pathinfo($pdfTmp, PATHINFO_FILENAME); // l'id généré
        $images = pdf_to_images($pdfTmp, $base);
        @unlink($pdfTmp); // le PDF source n'a plus d'utilité une fois converti
        if (count($images) === 0) {
            $errors[] = ['file' => $name, 'error' => 'pdf_convert_failed'];
            continue;
        }
        $p = 1;
        foreach ($images as $img) {
            $label = $name . ' — p.' . $p;
            $added[] = slides_add($img, $label);
            $p++;
        }
        continue;
    }

    // --- Type refusé -------------------------------------------------------
    $errors[] = ['file' => $name, 'error' => 'unsupported_type', 'mime' => $mime];
}

echo json_encode([
    'ok'     => count($errors) === 0,
    'added'  => $added,
    'errors' => $errors,
]);
