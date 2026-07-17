<?php

/**
 * Traitement de connexion - Marché Numérique de Butembo
 * Gère l'authentification et la redirection vers le dashboard
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/select/UtilisateurSelect.php';

// Vérifier si l'utilisateur est déjà connecté
if (isset($_SESSION['is_logged_in']) && $_SESSION['is_logged_in'] === true) {
    // Rediriger vers le dashboard dans views
    header('Location: ../views/dashboard.php');
    exit;
}

// Traitement de la déconnexion
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    logout();
    header('Location: ../connexion.php?message=deconnecte');
    exit;
}

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {

    // Récupérer et nettoyer les données
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']) ? true : false;

    $error = '';

    // Validation des champs
    if (empty($email)) {
        $error = 'Veuillez saisir votre adresse email.';
    } elseif (empty($password)) {
        $error = 'Veuillez saisir votre mot de passe.';
    } else {
        // Tentative d'authentification
        $user = UtilisateurSelect::authenticate($email, $password);

        if ($user) {
            // Vérifier si le compte est actif
            if ($user->statut !== 'actif') {
                $error = 'Votre compte est ' . $user->statut . '. Veuillez contacter l\'administrateur.';
            } else {
                // Connexion réussie - Créer la session
                $_SESSION['user_id'] = $user->id;
                $_SESSION['user_nom'] = $user->nom;
                $_SESSION['user_prenom'] = $user->prenom;
                $_SESSION['user_email'] = $user->email;
                $_SESSION['user_type'] = $user->type_utilisateur;
                $_SESSION['user_photo'] = $user->photo_profil ?? null;
                $_SESSION['user_telephone'] = $user->telephone ?? null;
                $_SESSION['user_adresse'] = $user->adresse ?? null;
                $_SESSION['is_logged_in'] = true;
                $_SESSION['login_time'] = time();

                // Gestion du "Se souvenir de moi"
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $_SESSION['remember_token'] = $token;

                    // Stocker dans un cookie (valable 30 jours)
                    setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
                    setcookie('user_email', $email, time() + (86400 * 30), '/', '', false, true);
                }

                // Les acheteurs commandent depuis la page d'accueil.
                header('Location: ' . ($user->type_utilisateur === 'acheteur' ? '../index.php' : '../views/dashboard.php'));
                exit;
            }
        } else {
            $error = 'Email ou mot de passe incorrect. Veuillez réessayer.';
        }
    }

    // En cas d'erreur, stocker le message et revenir à la page de connexion
    if (!empty($error)) {
        $_SESSION['login_error'] = $error;
        $_SESSION['login_email'] = $email;
        header('Location: ../connexion.php');
        exit;
    }
}

// Si on arrive ici sans POST, rediriger vers la page de connexion
header('Location: ../connexion.php');
exit;

// ============================================
// FONCTIONS UTILITAIRES
// ============================================

/**
 * Déconnecte l'utilisateur
 */
function logout()
{
    // Supprimer toutes les variables de session
    $_SESSION = array();

    // Supprimer le cookie de session
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

    // Supprimer les cookies "Se souvenir de moi"
    setcookie('remember_token', '', time() - 3600, '/');
    setcookie('user_email', '', time() - 3600, '/');

    // Détruire la session
    session_destroy();
}
