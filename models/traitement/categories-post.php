<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['is_logged_in']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accès non autorisé']); exit;
}
require_once __DIR__ . '/../../config/database.php';
$data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$action = $data['action'] ?? ''; $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
$nom = trim($data['nom'] ?? ''); $description = trim($data['description'] ?? '') ?: null;
if (in_array($action, ['create', 'update'], true) && mb_strlen($nom) < 2) {
    echo json_encode(['success' => false, 'message' => 'Le nom doit contenir au moins 2 caractères.'], JSON_UNESCAPED_UNICODE); exit;
}
if ($action === 'create') {
    $result = executeInsert('INSERT INTO categories (nom, description, supprime) VALUES (:nom, :description, 0)', [':nom' => $nom, ':description' => $description]); $message = 'Catégorie créée avec succès.';
} elseif ($action === 'update' && $id) {
    $result = (bool) executeQuery('UPDATE categories SET nom = :nom, description = :description WHERE id = :id AND supprime = 0', [':nom' => $nom, ':description' => $description, ':id' => $id]); $message = 'Catégorie modifiée avec succès.';
} elseif ($action === 'delete' && $id) {
    $count = fetchOne('SELECT COUNT(*) AS total FROM produits WHERE categorie_id = :id AND supprime = 0', [':id' => $id]);
    if (($count->total ?? 0) > 0) { echo json_encode(['success' => false, 'message' => 'Cette catégorie contient encore des produits.'], JSON_UNESCAPED_UNICODE); exit; }
    $result = (bool) executeQuery('UPDATE categories SET supprime = 1 WHERE id = :id', [':id' => $id]); $message = 'Catégorie supprimée avec succès.';
} else { $result = false; $message = 'Action ou données invalides.'; }
echo json_encode(['success' => (bool) $result, 'message' => $result ? $message : 'Une erreur est survenue.'], JSON_UNESCAPED_UNICODE);
