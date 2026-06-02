<?php
/**
 * Conversion PDF -> images (une PNG par page) via poppler-utils.
 */

require_once __DIR__ . '/../config.php';

/** Nombre de pages d'un PDF (via pdfinfo). 0 si indéterminable. */
function pdf_page_count(string $pdf_path): int
{
    $cmd = 'pdfinfo ' . escapeshellarg($pdf_path) . ' 2>/dev/null';
    $out = shell_exec($cmd);
    if ($out !== null && preg_match('/^Pages:\s+(\d+)/m', $out, $m)) {
        return (int) $m[1];
    }
    return 0;
}

/**
 * Convertit un PDF en images PNG dans UPLOADS_DIR.
 * $basename : préfixe des fichiers générés (sans extension).
 * Retourne la liste ordonnée des noms de fichiers produits, ou [] en cas d'échec.
 */
function pdf_to_images(string $pdf_path, string $basename): array
{
    $prefix = UPLOADS_DIR . '/' . $basename;

    $cmd = 'pdftoppm -png -r ' . (int) PDF_DPI . ' '
        . escapeshellarg($pdf_path) . ' '
        . escapeshellarg($prefix) . ' 2>/dev/null';
    exec($cmd, $output, $code);
    if ($code !== 0) {
        return [];
    }

    // pdftoppm produit basename-1.png, basename-02.png... (padding variable)
    $files = glob($prefix . '-*.png');
    if ($files === false || count($files) === 0) {
        return [];
    }
    natsort($files);
    return array_map('basename', array_values($files));
}

/**
 * Convertit uniquement la PREMIÈRE page d'un PDF en image PNG (pour l'image fixe).
 * Retourne le nom du fichier généré, ou null en cas d'échec.
 */
function pdf_first_page(string $pdf_path, string $basename): ?string
{
    $prefix = UPLOADS_DIR . '/' . $basename;
    $cmd = 'pdftoppm -png -f 1 -l 1 -r ' . (int) PDF_DPI . ' '
        . escapeshellarg($pdf_path) . ' ' . escapeshellarg($prefix) . ' 2>/dev/null';
    exec($cmd, $output, $code);
    if ($code !== 0) {
        return null;
    }
    $files = glob($prefix . '-*.png');
    if ($files === false || count($files) === 0) {
        return null;
    }
    natsort($files);
    return basename(reset($files));
}