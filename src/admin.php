<?php
/**
 * admin.php — Le back-office.
 * Gère la connexion / déconnexion et affiche, selon l'état :
 *   - un écran de connexion (mot de passe) ;
 *   - l'interface : upload + liste triable (glisser-déposer) + suppression.
 */

require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/store.php';
require_once __DIR__ . '/lib/settings.php';

// --- Déconnexion -----------------------------------------------------------
if (isset($_GET['logout'])) {
    admin_logout();
    header('Location: admin.php');
    exit;
}

// --- Tentative de connexion ------------------------------------------------
$login_error = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if (admin_login($_POST['password'])) {
        header('Location: admin.php');
        exit;
    }
    $login_error = true;
}

$authed = is_authed();
$slides = $authed ? slides_all() : [];
$fixed  = $authed ? fixed_get() : null;

/** Échappement HTML court. */
function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Administration — Diaporama</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=IBM+Plex+Mono:wght@400;500&family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="<?= $authed ? 'is-admin' : 'is-login' ?>">

<?php if (!$authed): ?>
    <!-- ========================= ÉCRAN DE CONNEXION ========================= -->
    <main class="login">
        <form class="login__card" method="post" autocomplete="off">
            <p class="login__kicker">Diaporama</p>
            <h1 class="login__title">Administration</h1>
            <?php if ($login_error): ?>
                <p class="login__error">Mot de passe incorrect.</p>
            <?php endif; ?>
            <label class="field">
                <span class="field__label">Mot de passe</span>
                <input class="field__input" type="password" name="password"
                       autofocus required>
            </label>
            <button class="btn btn--primary" type="submit">Entrer</button>
        </form>
    </main>

<?php else: ?>
    <!-- ============================ INTERFACE ============================== -->
    <header class="topbar">
        <div class="topbar__brand">
            <span class="topbar__kicker">Diaporama</span>
            <h1 class="topbar__title">Administration</h1>
        </div>
        <div class="topbar__actions">
            <span class="count" id="count"><?= count($slides) ?></span>
            <a class="btn btn--ghost" href="index.php" target="_blank" rel="noopener">Voir le diaporama ↗</a>
            <a class="btn btn--ghost" href="admin.php?logout=1">Déconnexion</a>
        </div>
    </header>

    <main class="wrap">
        <!-- Zone d'upload -->
        <section class="dropzone" id="dropzone" aria-label="Ajouter des fichiers">
            <input type="file" id="fileInput" multiple hidden
                   accept="image/*,application/pdf">
            <div class="dropzone__inner">
                <span class="dropzone__icon">＋</span>
                <p class="dropzone__text">
                    Glissez vos fichiers ici, ou
                    <button type="button" class="linklike" id="browseBtn">parcourez</button>
                </p>
                <p class="dropzone__hint">Images et PDF · les PDF deviennent une slide par page</p>
            </div>
            <div class="dropzone__progress" id="progress" hidden></div>
        </section>

        <!-- Image fixe (volet droit du diaporama) -->
        <section class="fixed" aria-label="Image fixe">
            <div class="fixed__head">
                <h2 class="section-title">Image fixe — volet droit</h2>
                <p class="hint">Affichée en permanence à droite du diaporama. Laisser vide pour un diaporama plein écran.</p>
            </div>
            <input type="file" id="fixedInput" hidden accept="image/*">
            <div class="fixed__body" id="fixedBody" data-has="<?= $fixed ? '1' : '0' ?>">
                <div class="fixed__preview">
                    <img id="fixedPreview"
                         src="<?= $fixed ? h('uploads/' . $fixed['file']) : '' ?>"
                         alt="<?= $fixed ? h($fixed['name'] ?? '') : '' ?>"
                            <?= $fixed ? '' : 'hidden' ?>>
                    <span class="fixed__placeholder" <?= $fixed ? 'hidden' : '' ?>>Aucune image fixe</span>
                </div>
                <div class="fixed__actions">
                    <span class="fixed__name" id="fixedName"><?= $fixed ? h($fixed['name'] ?? '') : '' ?></span>
                    <div class="fixed__buttons">
                        <button type="button" class="btn btn--ghost" id="fixedPick">
                            <span id="fixedPickLabel"><?= $fixed ? 'Remplacer' : 'Choisir une image' ?></span>
                        </button>
                        <button type="button" class="btn btn--ghost" id="fixedRemove" <?= $fixed ? '' : 'hidden' ?>>Retirer</button>
                    </div>
                </div>
            </div>
        </section>

        <!-- Liste triable -->
        <p class="hint hint--reorder">Glissez les vignettes pour définir l'ordre d'apparition.</p>
        <ul class="grid" id="grid">
            <?php foreach ($slides as $s): ?>
                <li class="card" data-id="<?= h($s['id']) ?>">
                    <div class="card__thumb">
                        <img src="<?= h('uploads/' . $s['file']) ?>"
                             alt="<?= h($s['name'] ?? '') ?>" loading="lazy">
                        <span class="card__handle" title="Glisser pour réordonner">⠿</span>
                    </div>
                    <div class="card__foot">
                        <span class="card__name" title="<?= h($s['name'] ?? '') ?>"><?= h($s['name'] ?? '') ?></span>
                        <button class="card__del" data-id="<?= h($s['id']) ?>"
                                title="Supprimer" aria-label="Supprimer">✕</button>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="empty" id="empty" <?= count($slides) ? 'hidden' : '' ?>>
            Aucune slide pour l'instant. Ajoutez vos premiers fichiers ci-dessus.
        </div>
    </main>

    <div class="toast" id="toast" hidden></div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Sortable/1.15.6/Sortable.min.js"></script>
    <script src="assets/js/admin.js"></script>
<?php endif; ?>

</body>
</html>