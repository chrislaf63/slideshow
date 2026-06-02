<?php
/**
 * index.php — Le diaporama public.
 * Affichage en deux volets : diaporama (gauche) + image fixe (droite).
 * Si aucune image fixe n'est définie, le diaporama occupe tout l'écran.
 * Fondu, autoplay, contrôles, clavier, plein écran, rafraîchissement à chaud.
 */

require_once __DIR__ . '/lib/settings.php'; // inclut store.php

$slides = slides_all();
$fixed  = fixed_get();

function hh(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Diaporama</title>
    <link rel="stylesheet" href="assets/css/slideshow.css">
</head>
<body>

    <div class="split <?= $fixed ? 'has-fixed' : '' ?>" id="split">

        <!-- Volet gauche : le diaporama -->
        <section class="pane pane--show" id="paneShow">
            <main class="stage" id="stage">
                <div class="layers" id="layers"></div>
                <div class="progress" id="progress"><span class="progress__bar" id="bar"></span></div>
                <div class="controls" id="controls">
                    <button class="ctrl" id="prev" aria-label="Précédent" title="Précédent (←)">‹</button>
                    <button class="ctrl ctrl--play" id="play" aria-label="Lecture / Pause" title="Lecture / Pause (Espace)">❚❚</button>
                    <button class="ctrl" id="next" aria-label="Suivant" title="Suivant (→)">›</button>
                    <span class="counter" id="counter"></span>
                    <button class="ctrl ctrl--fs" id="fs" aria-label="Plein écran" title="Plein écran (F)">⤢</button>
                </div>
                <p class="empty" id="empty" hidden>Aucune slide à afficher.</p>
            </main>
        </section>

        <!-- Volet droit : l'image fixe (masqué si non définie) -->
        <aside class="pane pane--fixed" id="paneFixed">
            <img id="fixedImg"
                 src="<?= $fixed ? hh('uploads/' . $fixed['file']) : '' ?>"
                 alt="<?= $fixed ? hh($fixed['name'] ?? '') : '' ?>"
                 <?= $fixed ? '' : 'hidden' ?>>
        </aside>

    </div>

    <script>
        window.SLIDES = <?= json_encode($slides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.FIXED  = <?= json_encode($fixed,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.SLIDESHOW_CONFIG = {
            interval: <?= (int) SLIDE_INTERVAL_MS ?>,
            fade:     <?= (int) SLIDE_FADE_MS ?>,
            poll:     <?= (int) SLIDE_POLL_MS ?>,
            uploads:  "uploads"
        };
    </script>
    <script src="assets/js/slideshow.js"></script>
</body>
</html>