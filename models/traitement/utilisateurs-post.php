<?php

/**
 * Traitement des utilisateurs - CRUD
 * Marché Numérique de Butembo
 */

// Démarrer la session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté et est admin
if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || $_SESSION['user_type'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../traitement/UtilisateurTraitement.php';

// Récupérer les données JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$action = $data['action'] ?? '';

header('Content-Type: application/json');

/**
 * Génère une adresse interne à partir du nom et garantit son unicité.
 * Exemple : mukendi@admin-bbomarcher.com, puis mukendi2@... si nécessaire.
 */
function generateAdminEmail($nom)
{
    $nom = trim((string) $nom);
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nom);
    $ascii = $ascii !== false ? $ascii : $nom;
    $base = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '', $ascii));
    $base = $base !== '' ? $base : 'utilisateur';

    $suffix = 1;
    do {
        $email = $base . ($suffix > 1 ? $suffix : '') . '@admin-bbomarcher.com';
        $suffix++;
    } while (UtilisateurTraitement::emailExists($email));

    return $email;
}

switch ($action) {
    case 'create':
        // Validation des champs
        if (empty($data['prenom']) || empty($data['nom']) || empty($data['type_utilisateur']) || empty($data['mot_de_passe'])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
            exit;
        }

        if (mb_strlen($data['mot_de_passe']) < 4) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 4 caractères']);
            exit;
        }

        $email = generateAdminEmail($data['nom']);

        // Créer l'utilisateur
        $userData = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'email' => $email,
            'telephone' => $data['telephone'] ?? null,
            'mot_de_passe' => $data['mot_de_passe'],
            'type_utilisateur' => $data['type_utilisateur'],
            'statut' => $data['statut'] ?? 'actif',
            'adresse' => $data['adresse'] ?? null,
            'est_verifie' => 1
        ];

        $result = UtilisateurTraitement::create($userData);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur créé avec succès. Email : ' . $email, 'id' => $result, 'email' => $email]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la création']);
        }
        break;

    case 'update':
        // Validation
        if (empty($data['id']) || empty($data['prenom']) || empty($data['nom']) || empty($data['type_utilisateur'])) {
            echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
            exit;
        }

        if (!empty($data['mot_de_passe']) && mb_strlen($data['mot_de_passe']) < 4) {
            echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 4 caractères']);
            exit;
        }

        // Mettre à jour
        $userData = [
            'nom' => $data['nom'],
            'prenom' => $data['prenom'],
            'telephone' => $data['telephone'] ?? null,
            'type_utilisateur' => $data['type_utilisateur'],
            'statut' => $data['statut'] ?? 'actif',
            'adresse' => $data['adresse'] ?? null
        ];

        $result = UtilisateurTraitement::update($data['id'], $userData);

        // Mettre à jour le mot de passe si fourni
        if (!empty($data['mot_de_passe'])) {
            UtilisateurTraitement::updatePassword($data['id'], $data['mot_de_passe']);
        }

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur modifié avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la modification']);
        }
        break;

    case 'delete':
        // Validation
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
            exit;
        }

        // Empêcher la suppression de son propre compte
        if ($data['id'] == $_SESSION['user_id']) {
            echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas supprimer votre propre compte']);
            exit;
        }

        $result = UtilisateurTraitement::delete($data['id']);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Utilisateur supprimé avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}
