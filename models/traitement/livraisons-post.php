<?php

if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

function deliveryResponse(bool $success, string $message, int $status = 200): void
{
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['is_logged_in']) || ($_SESSION['user_type'] ?? '') !== 'livreur') {
    deliveryResponse(false, 'Accès non autorisé.', 403);
}

require_once __DIR__ . '/../../config/database.php';
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
$action = $data['action'] ?? '';

if (!$id || !in_array($action, ['start', 'complete'], true)) {
    deliveryResponse(false, 'Action ou livraison invalide.', 422);
}

$livraison = fetchOne('SELECT id, statut_livraison, date_depart FROM livraisons
    WHERE id = :id AND livreur_id = :livreur AND supprime = 0 LIMIT 1',
    [':id' => $id, ':livreur' => (int) $_SESSION['user_id']]);

if (!$livraison) deliveryResponse(false, 'Cette livraison ne vous est pas assignée.', 404);
if ($livraison->statut_livraison !== 'en_cours') deliveryResponse(false, 'Cette livraison est déjà clôturée.', 409);

if ($action === 'start') {
    if ($livraison->date_depart) deliveryResponse(false, 'La livraison a déjà été démarrée.', 409);
    $ok = executeQuery('UPDATE livraisons SET date_depart = NOW() WHERE id = :id AND livreur_id = :livreur',
        [':id' => $id, ':livreur' => (int) $_SESSION['user_id']]);
    deliveryResponse((bool) $ok, $ok ? 'Livraison démarrée.' : 'Impossible de démarrer la livraison.', $ok ? 200 : 500);
}

if (!$livraison->date_depart) deliveryResponse(false, 'Démarrez la livraison avant de la terminer.', 409);
$ok = executeQuery("UPDATE livraisons SET statut_livraison = 'terminee', date_livraison = NOW()
    WHERE id = :id AND livreur_id = :livreur", [':id' => $id, ':livreur' => (int) $_SESSION['user_id']]);
deliveryResponse((bool) $ok, $ok ? 'Commande marquée comme livrée.' : 'Impossible de terminer la livraison.', $ok ? 200 : 500);
