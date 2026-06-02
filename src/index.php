<?php
/**
 * index.php — Le diaporama public.
 * Charge la liste ordonnée des slides, l'injecte au JS, et affiche un
 * diaporama plein écran : fondu enchaîné, autoplay, contrôles, navigation
 * clavier, plein écran, et rafraîchissement à chaud (au bouclage).
 */

require_once __DIR__ . '/lib/store.php';

$slides = slides_all();
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

<!-- La scène est toujours rendue : le JS gère l'état vide / peuplé, ce qui
     permet au diaporama de démarrer tout seul dès qu'une slide apparaît. -->
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

<script>
    window.SLIDES = <?= json_encode($slides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
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