<?php
/**
 * Réglages divers, stockés dans settings.json (hors slides.json).
 * Pour l'instant : l'image fixe affichée dans le volet droit du diaporama.
 *   { "fixed": { "id": "...", "file": "...", "name": "..." } | null }
 */

require_once __DIR__ . '/store.php'; // pour slides_gen_id() + constantes

function settings_path(): string { return DATA_DIR . '/settings.json'; }

/** Lit l'ensemble des réglages (avec valeurs par défaut). */
function settings_all(): array
{
    $defaults = ['fixed' => null];
    $path = settings_path();
    if (!is_file($path)) {
        return $defaults;
    }
    $raw = file_get_contents($path);
    $data = $raw !== false ? json_decode($raw, true) : null;
    return is_array($data) ? array_merge($defaults, $data) : $defaults;
}

/** Écrit les réglages de façon atomique. */
function settings_save(array $s): bool
{
    $json = json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if ($json === false) {
        return false;
    }
    $tmp = settings_path() . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    return rename($tmp, settings_path());
}

/** Retourne l'image fixe courante, ou null. */
function fixed_get(): ?array
{
    $f = settings_all()['fixed'] ?? null;
    return is_array($f) ? $f : null;
}

/** Définit (ou remplace) l'image fixe. Supprime l'ancien fichier si différent. */
function fixed_set(string $file, string $name = ''): array
{
    $s = settings_all();
    $old = $s['fixed'] ?? null;
    if (is_array($old) && !empty($old['file']) && $old['file'] !== $file) {
        $p = UPLOADS_DIR . '/' . basename($old['file']);
        if (is_file($p)) { @unlink($p); }
    }
    $s['fixed'] = [
        'id'   => slides_gen_id(),
        'file' => $file,
        'name' => $name !== '' ? $name : $file,
    ];
    settings_save($s);
    return $s['fixed'];
}

/** Retire l'image fixe (et supprime son fichier). */
function fixed_clear(): bool
{
    $s = settings_all();
    $old = $s['fixed'] ?? null;
    if (is_array($old) && !empty($old['file'])) {
        $p = UPLOADS_DIR . '/' . basename($old['file']);
        if (is_file($p)) { @unlink($p); }
    }
    $s['fixed'] = null;
    return settings_save($s);
}
