<?php

/**
 * Déconnexion - Marché Numérique de Butembo
 * Gère la déconnexion des utilisateurs
 */

// Démarrer la session si elle n'est pas déjà démarrée
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Fonction de déconnexion
 */
function logoutUser()
{
    // 1. Supprimer toutes les variables de session
    $_SESSION = array();

    // 2. Supprimer le cookie de session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }

    // 3. Supprimer les cookies de session persistante "Se souvenir de moi"
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_email', '', time() - 3600, '/');

    // 4. Supprimer les cookies de session (si existants)
    setcookie('session_id', '', time() - 3600, '/');

    // 5. Détruire la session
    session_destroy();

    return true;
}

// Déterminer la redirection
$redirect_url = '../connexion.php';
$message = 'deconnecte';

// Récupérer l'URL de redirection si spécifiée
if (isset($_GET['redirect']) && !empty($_GET['redirect'])) {
    // Nettoyer l'URL pour éviter les attaques
    $redirect_url = filter_var($_GET['redirect'], FILTER_SANITIZE_URL);
    // Vérifier que c'est une URL relative
    if (strpos($redirect_url, 'http') === 0) {
        $redirect_url = '../connexion.php';
    }
}

// Récupérer un message personnalisé
if (isset($_GET['message']) && !empty($_GET['message'])) {
    $message = filter_var($_GET['message'], FILTER_SANITIZE_STRING);
}

// Exécuter la déconnexion
$logout_success = logoutUser();

// Si la déconnexion a réussi, rediriger
if ($logout_success) {
    // Ajouter le message de succès à l'URL
    $redirect_url .= '?message=' . $message;

    // Si une redirection spécifique est demandée
    if (isset($_GET['redirect_to']) && !empty($_GET['redirect_to'])) {
        $redirect_to = filter_var($_GET['redirect_to'], FILTER_SANITIZE_URL);
        if (strpos($redirect_to, 'http') !== 0) {
            $redirect_url = $redirect_to . '?message=' . $message;
        }
    }

    // Rediriger
    header('Location: ' . $redirect_url);
    exit;
} else {
    // Si la déconnexion a échoué (cas rare)
    error_log('Erreur lors de la déconnexion');
    header('Location: ../connexion.php?message=erreur_deconnexion');
    exit;
}
