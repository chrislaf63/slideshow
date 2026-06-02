<?php
/**
 * Couche de données : lecture/écriture de slides.json.
 * Le fichier contient un tableau JSON ordonné de slides :
 *   [ { "id": "a1b2c3", "file": "xxx.png", "name": "photo.jpg" }, ... ]
 * L'ordre du tableau = l'ordre d'affichage du diaporama.
 */

require_once __DIR__ . '/../config.php';

/** Identifiant court et unique pour une slide. */
function slides_gen_id(): string
{
    return bin2hex(random_bytes(5)); // 10 caractères hex
}

/** Retourne la liste ordonnée des slides. */
function slides_all(): array
{
    if (!is_file(SLIDES_FILE)) {
        return [];
    }
    $raw = file_get_contents(SLIDES_FILE);
    if ($raw === false || trim($raw) === '') {
        return [];
    }
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

/** Écrit la liste sur disque de façon atomique (fichier temporaire + rename). */
function slides_save(array $slides): bool
{
    $json = json_encode(
        array_values($slides),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($json === false) {
        return false;
    }
    $tmp = SLIDES_FILE . '.tmp';
    if (file_put_contents($tmp, $json, LOCK_EX) === false) {
        return false;
    }
    // rename() est atomique sur le même système de fichiers
    return rename($tmp, SLIDES_FILE);
}

/** Ajoute une slide en fin de liste. Retourne la slide créée. */
function slides_add(string $file, string $name = ''): array
{
    $slides = slides_all();
    $slide = [
        'id'   => slides_gen_id(),
        'file' => $file,
        'name' => $name !== '' ? $name : $file,
    ];
    $slides[] = $slide;
    slides_save($slides);
    return $slide;
}

/** Supprime une slide par id, ainsi que son fichier sur disque. */
function slides_remove(string $id): bool
{
    $slides = slides_all();
    $found  = false;
    $kept   = [];
    foreach ($slides as $s) {
        if (($s['id'] ?? null) === $id) {
            $found = true;
            $path  = UPLOADS_DIR . '/' . basename($s['file']);
            if (is_file($path)) {
                @unlink($path);
            }
            continue;
        }
        $kept[] = $s;
    }
    if (!$found) {
        return false;
    }
    return slides_save($kept);
}

/** Réordonne la liste selon un tableau d'ids dans le nouvel ordre. */
function slides_reorder(array $ordered_ids): bool
{
    $slides = slides_all();
    // index id -> slide
    $byId = [];
    foreach ($slides as $s) {
        if (isset($s['id'])) {
            $byId[$s['id']] = $s;
        }
    }
    $reordered = [];
    foreach ($ordered_ids as $id) {
        if (isset($byId[$id])) {
            $reordered[] = $byId[$id];
            unset($byId[$id]);
        }
    }
    // sécurité : on rattache en fin tout id non mentionné (évite une perte)
    foreach ($byId as $s) {
        $reordered[] = $s;
    }
    return slides_save($reordered);
}
