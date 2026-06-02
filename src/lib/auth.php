<?php
/**
 * Protection du back-office par mot de passe (session PHP).
 */

require_once __DIR__ . '/../config.php';

/** Démarre la session une seule fois, avec un cookie durci. */
function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Strict',
        // 'secure' => true,  // à activer derrière HTTPS
    ]);
    session_start();
}

/** L'administrateur est-il authentifié ? */
function is_authed(): bool
{
    session_boot();
    return !empty($_SESSION['authed']);
}

/** Tente une connexion. Comparaison en temps constant. */
function admin_login(string $password): bool
{
    session_boot();
    if (hash_equals(ADMIN_PASSWORD, $password)) {
        session_regenerate_id(true);
        $_SESSION['authed'] = true;
        return true;
    }
    return false;
}

/** Déconnecte l'administrateur. */
function admin_logout(): void
{
    session_boot();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Garde pour les endpoints d'API : répond 401 JSON et arrête si non authentifié.
 * (Les API sont appelées en AJAX : on renvoie une erreur, pas une redirection.)
 */
function api_require_admin(): void
{
    if (!is_authed()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'unauthorized']);
        exit;
    }
}
