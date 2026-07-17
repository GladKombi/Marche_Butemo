<?php

/**
 * Traitement des commandes - CRUD
 * Marché Numérique de Butembo
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['is_logged_in']) || $_SESSION['is_logged_in'] !== true || !in_array($_SESSION['user_type'] ?? '', ['admin', 'agriculteur'], true)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Accès non autorisé']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$action = $data['action'] ?? '';
header('Content-Type: application/json');

switch ($action) {
    case 'assign_delivery':
        $commandeId = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        $livreurId = filter_var($data['livreur_id'] ?? null, FILTER_VALIDATE_INT);
        if (!$commandeId || !$livreurId) {
            echo json_encode(['success' => false, 'message' => 'Commande ou livreur invalide.'], JSON_UNESCAPED_UNICODE); exit;
        }
        if (($_SESSION['user_type'] ?? '') === 'admin') {
            $owned = fetchOne('SELECT id FROM commandes WHERE id = :commande AND supprime = 0 AND date_annulation IS NULL', [':commande' => $commandeId]);
        } else {
            $owned = fetchOne("SELECT c.id FROM commandes c
                JOIN ligne_commandes lc ON lc.commande_id = c.id AND lc.supprime = 0
                JOIN produits p ON p.id = lc.produit_id AND p.supprime = 0
                JOIN agriculteurs a ON a.id = p.agriculteur_id AND a.supprime = 0
                WHERE c.id = :commande AND c.supprime = 0 AND c.date_annulation IS NULL
                  AND a.utilisateur_id = :utilisateur LIMIT 1",
                [':commande' => $commandeId, ':utilisateur' => $_SESSION['user_id']]);
        }
        $driver = fetchOne("SELECT id FROM utilisateurs WHERE id = :id AND type_utilisateur = 'livreur' AND statut = 'actif' AND supprime = 0", [':id' => $livreurId]);
        $assigned = fetchOne("SELECT id FROM livraisons WHERE commande_id = :id AND supprime = 0 AND statut_livraison IN ('en_cours','terminee') LIMIT 1", [':id' => $commandeId]);
        if (!$owned) { echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas assigner cette commande.'], JSON_UNESCAPED_UNICODE); exit; }
        if (!$driver) { echo json_encode(['success' => false, 'message' => 'Livreur invalide ou inactif.'], JSON_UNESCAPED_UNICODE); exit; }
        if ($assigned) { echo json_encode(['success' => false, 'message' => 'Cette commande est déjà assignée.'], JSON_UNESCAPED_UNICODE); exit; }
        $tracking = 'LIV-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $result = executeInsert("INSERT INTO livraisons (commande_id, livreur_id, date_assignation, statut_livraison, code_suivi, supprime)
            VALUES (:commande, :livreur, NOW(), 'en_cours', :code, 0)",
            [':commande' => $commandeId, ':livreur' => $livreurId, ':code' => $tracking]);
        echo json_encode(['success' => (bool) $result, 'message' => $result ? 'Commande assignée au livreur avec succès.' : 'Erreur lors de l’assignation.'], JSON_UNESCAPED_UNICODE);
        break;

    case 'details':
        $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Commande invalide'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        if (($_SESSION['user_type'] ?? '') === 'agriculteur') {
            $owned = fetchOne("SELECT lc.id FROM ligne_commandes lc JOIN produits p ON p.id = lc.produit_id
                JOIN agriculteurs a ON a.id = p.agriculteur_id
                WHERE lc.commande_id = :commande AND a.utilisateur_id = :utilisateur AND lc.supprime = 0 LIMIT 1",
                [':commande' => $id, ':utilisateur' => $_SESSION['user_id']]);
            if (!$owned) { echo json_encode(['success' => false, 'message' => 'Accès non autorisé'], JSON_UNESCAPED_UNICODE); exit; }
        }

        $commande = fetchOne("SELECT c.*, u.nom AS acheteur_nom, u.prenom AS acheteur_prenom,
                u.email AS acheteur_email, u.telephone AS acheteur_telephone,
                d.adresse_livraison, d.instructions_specifiques,
                p.mode_paiement, p.date_paiement,
                l.statut_livraison
            FROM commandes c
            JOIN utilisateurs u ON u.id = c.acheteur_id AND u.supprime = 0
            LEFT JOIN details_livraison d ON d.id_commande = c.id AND d.supprime = 0
            LEFT JOIN paiements p ON p.commande_id = c.id AND p.supprime = 0
            LEFT JOIN livraisons l ON l.commande_id = c.id AND l.supprime = 0
            WHERE c.id = :id AND c.supprime = 0
            ORDER BY d.id DESC, p.id DESC, l.id DESC LIMIT 1", [':id' => $id]);

        if (!$commande) {
            echo json_encode(['success' => false, 'message' => 'Commande introuvable'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $lineOwnershipSql = ($_SESSION['user_type'] ?? '') === 'agriculteur'
            ? ' JOIN agriculteurs a ON a.id = p.agriculteur_id AND a.utilisateur_id = :utilisateur '
            : '';
        $lineParams = [':id' => $id];
        if (($_SESSION['user_type'] ?? '') === 'agriculteur') $lineParams[':utilisateur'] = $_SESSION['user_id'];
        $lignes = fetchAll("SELECT lc.produit_id, lc.quantite, lc.prix_unitaire,
                p.nom AS produit_nom, p.unite_mesure, p.images
            FROM ligne_commandes lc
            JOIN produits p ON p.id = lc.produit_id
            {$lineOwnershipSql}
            WHERE lc.commande_id = :id AND lc.supprime = 0
            ORDER BY lc.id", $lineParams);

        $commande->statut_commande = $commande->date_annulation
            ? 'annulee'
            : (($commande->statut_livraison ?? null) === 'terminee'
                ? 'livree'
                : (($commande->statut_livraison ?? null) === 'en_cours' ? 'en_livraison' : 'en_attente'));
        $commande->statut_paiement = $commande->date_paiement ? 'paye' : 'en_attente';
        echo json_encode(['success' => true, 'commande' => $commande, 'lignes' => $lignes], JSON_UNESCAPED_UNICODE);
        break;

    case 'delete':
        if (($_SESSION['user_type'] ?? '') !== 'admin') {
            echo json_encode(['success' => false, 'message' => 'Accès non autorisé'], JSON_UNESCAPED_UNICODE); exit;
        }
        if (empty($data['id'])) {
            echo json_encode(['success' => false, 'message' => 'ID commande requis']);
            exit;
        }

        $sql = "UPDATE commandes SET supprime = 1 WHERE id = :id";
        $result = executeQuery($sql, [':id' => $data['id']]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Commande supprimée avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la suppression']);
        }
        break;

    case 'update_status':
        if (empty($data['id']) || empty($data['statut'])) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            exit;
        }

        $sql = "UPDATE commandes SET statut_commande = :statut WHERE id = :id AND supprime = 0";
        $result = executeQuery($sql, [
            ':id' => $data['id'],
            ':statut' => $data['statut']
        ]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur lors de la mise à jour']);
        }
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        break;
}
