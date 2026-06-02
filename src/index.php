<?php
/**
 * index.php — Le diaporama public.
 * Charge la liste ordonnée des slides, l'injecte au JS, et affiche un
 * diaporama plein écran : fondu enchaîné, autoplay, contrôles, navigation
 * clavier et plein écran.
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

<?php if (count($slides) === 0): ?>
    <main class="stage stage--empty">
        <p class="empty">Aucune slide à afficher.</p>
    </main>
<?php else: ?>
    <main class="stage" id="stage">
        <!-- Les <img> sont injectées par le JS pour gérer fondu + préchargement -->
        <div class="layers" id="layers"></div>

        <!-- Barre de progression de la slide courante -->
        <div class="progress" id="progress"><span class="progress__bar" id="bar"></span></div>

        <!-- Contrôles (auto-masqués après inactivité) -->
        <div class="controls" id="controls">
            <button class="ctrl" id="prev" aria-label="Précédent" title="Précédent (←)">‹</button>
            <button class="ctrl ctrl--play" id="play" aria-label="Lecture / Pause" title="Lecture / Pause (Espace)">❚❚</button>
            <button class="ctrl" id="next" aria-label="Suivant" title="Suivant (→)">›</button>
            <span class="counter" id="counter"></span>
            <button class="ctrl ctrl--fs" id="fs" aria-label="Plein écran" title="Plein écran (F)">⤢</button>
        </div>
    </main>
<?php endif; ?>

    <script>
        window.SLIDES = <?= json_encode($slides, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
        window.SLIDESHOW_CONFIG = {
            interval: <?= (int) SLIDE_INTERVAL_MS ?>,
            fade:     <?= (int) SLIDE_FADE_MS ?>,
            uploads:  "uploads"
        };
    </script>
    <script src="assets/js/slideshow.js"></script>
</body>
</html>
