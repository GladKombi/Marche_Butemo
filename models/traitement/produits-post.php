<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');
if (empty($_SESSION['is_logged_in']) || !in_array($_SESSION['user_type'] ?? '', ['admin', 'agriculteur'], true)) {
    http_response_code(403); echo json_encode(['success' => false, 'message' => 'Accès non autorisé']); exit;
}
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/ProduitTraitement.php';

function productResponse($success, $message, $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE); exit;
}
function productImages($value) {
    if (!$value) return [];
    $decoded = json_decode($value, true);
    return array_values(array_filter(is_array($decoded) ? $decoded : [$value], 'is_string'));
}
function uploadProductImages($files) {
    if (!isset($files['name']) || !is_array($files['name'])) return [];
    $indexes = [];
    foreach ($files['name'] as $i => $name) if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) $indexes[] = $i;
    if (count($indexes) > 3) productResponse(false, 'Vous pouvez téléverser au maximum 3 images.');
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $directory = __DIR__ . '/../../assets/uploads/produits';
    if (!is_dir($directory) && !mkdir($directory, 0775, true)) productResponse(false, "Impossible de créer le dossier d'images.");
    $saved = []; $finfo = new finfo(FILEINFO_MIME_TYPE);
    foreach ($indexes as $i) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) productResponse(false, "Échec du téléversement d'une image.");
        if (($files['size'][$i] ?? 0) > 5 * 1024 * 1024) productResponse(false, 'Chaque image doit peser au maximum 5 Mo.');
        $mime = $finfo->file($files['tmp_name'][$i]);
        if (!isset($allowed[$mime])) productResponse(false, 'Formats autorisés : JPG, PNG et WEBP.');
        $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($files['tmp_name'][$i], $directory . DIRECTORY_SEPARATOR . $filename)) productResponse(false, "Impossible d'enregistrer une image.");
        $saved[] = 'assets/uploads/produits/' . $filename;
    }
    return $saved;
}

$json = json_decode(file_get_contents('php://input'), true);
$data = is_array($json) ? $json : $_POST;
$action = $data['action'] ?? '';
if ($action === 'delete') {
    $id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
    if (!$id) productResponse(false, 'Produit invalide.');
    if (($_SESSION['user_type'] ?? '') === 'agriculteur') {
        $owned = fetchOne('SELECT p.id FROM produits p JOIN agriculteurs a ON a.id = p.agriculteur_id WHERE p.id = :produit AND a.utilisateur_id = :utilisateur AND p.supprime = 0', [':produit' => $id, ':utilisateur' => $_SESSION['user_id']]);
        if (!$owned) productResponse(false, 'Vous ne pouvez supprimer que vos propres produits.');
    }
    $ok = ProduitTraitement::delete($id); productResponse($ok, $ok ? 'Produit supprimé avec succès.' : 'Erreur lors de la suppression.');
}
if (!in_array($action, ['create', 'update'], true)) productResponse(false, 'Action non reconnue.');
$requiredFields = [
    'nom' => 'Nom du produit',
    'agriculteur_id' => 'Agriculteur',
    'categorie_id' => 'Catégorie',
    'prix_unitaire' => 'Prix unitaire',
    'unite_mesure' => 'Unité de mesure',
    'quantite_stock' => 'Quantité en stock',
];
foreach ($requiredFields as $field => $label) {
    if (!isset($data[$field]) || trim((string) $data[$field]) === '') {
        productResponse(false, 'Le champ « ' . $label . ' » est obligatoire.');
    }
}
if (mb_strlen(trim($data['nom'])) < 2) productResponse(false, 'Le nom du produit doit contenir au moins 2 caractères.');
if (!in_array($data['unite_mesure'], ['kg', 'g', 'tonne', 'piece', 'douzaine', 'litre', 'sac', 'autre'], true)) productResponse(false, 'Unité invalide.');
if (!is_numeric($data['prix_unitaire']) || (float) $data['prix_unitaire'] < 0) productResponse(false, 'Prix invalide.');
if (!is_numeric($data['quantite_stock'] ?? 0) || (float) ($data['quantite_stock'] ?? 0) < 0) productResponse(false, 'Stock invalide.');
$farmerSelection = (int) $data['agriculteur_id'];
if (($_SESSION['user_type'] ?? '') === 'agriculteur') {
    $farmer = fetchOne('SELECT id FROM agriculteurs WHERE utilisateur_id = :id AND supprime = 0', [':id' => $_SESSION['user_id']]);
    if (!$farmer) {
        $farmerId = executeInsert('INSERT INTO agriculteurs (utilisateur_id, supprime) VALUES (:id, 0)', [':id' => $_SESSION['user_id']]);
        $farmer = $farmerId ? (object) ['id' => $farmerId] : null;
    }
} elseif ($farmerSelection > 0) {
    $farmer = fetchOne('SELECT id FROM agriculteurs WHERE id = :id AND supprime = 0', [':id' => $farmerSelection]);
} else {
    $farmerUserId = abs($farmerSelection);
    $farmerUser = fetchOne("SELECT id FROM utilisateurs WHERE id = :id
        AND type_utilisateur = 'agriculteur' AND supprime = 0", [':id' => $farmerUserId]);
    $farmer = $farmerUser
        ? fetchOne('SELECT id FROM agriculteurs WHERE utilisateur_id = :id AND supprime = 0', [':id' => $farmerUserId])
        : null;
    if ($farmerUser && !$farmer) {
        $farmerId = executeInsert('INSERT INTO agriculteurs (utilisateur_id, supprime) VALUES (:id, 0)', [':id' => $farmerUserId]);
        $farmer = $farmerId ? (object) ['id' => $farmerId] : null;
    }
}
$category = fetchOne('SELECT id FROM categories WHERE id = :id AND supprime = 0', [':id' => (int) $data['categorie_id']]);
if (!$farmer || !$category) productResponse(false, 'Agriculteur ou catégorie invalide.');

$images = uploadProductImages($_FILES['images'] ?? []);
if ($action === 'update' && !$images) {
    $current = fetchOne('SELECT images FROM produits WHERE id = :id AND supprime = 0', [':id' => $data['id'] ?? 0]);
    $images = productImages($current->images ?? null);
}
$product = [
    'agriculteur_id' => (int) $farmer->id, 'categorie_id' => (int) $data['categorie_id'],
    'nom' => trim($data['nom']), 'description' => trim($data['description'] ?? '') ?: null,
    'prix_unitaire' => (float) $data['prix_unitaire'], 'unite_mesure' => $data['unite_mesure'],
    'quantite_stock' => (float) ($data['quantite_stock'] ?? 0),
    'images' => $images ? json_encode(array_slice($images, 0, 3), JSON_UNESCAPED_SLASHES) : null,
    'est_bio' => !empty($data['est_bio']) ? 1 : 0, 'origine' => trim($data['origine'] ?? '') ?: null,
    'est_disponible' => !empty($data['est_disponible']) ? 1 : 0
];
if ($action === 'create') {
    $id = ProduitTraitement::create($product); productResponse((bool) $id, $id ? 'Produit créé avec succès.' : 'Erreur lors de la création.', ['id' => $id]);
}
$id = filter_var($data['id'] ?? null, FILTER_VALIDATE_INT);
if (!$id) productResponse(false, 'Produit invalide.');
if (($_SESSION['user_type'] ?? '') === 'agriculteur') {
    $owned = fetchOne('SELECT id FROM produits WHERE id = :id AND agriculteur_id = :agriculteur AND supprime = 0', [':id' => $id, ':agriculteur' => $farmer->id]);
    if (!$owned) productResponse(false, 'Vous ne pouvez modifier que vos propres produits.');
}
$ok = ProduitTraitement::update($id, $product); productResponse($ok, $ok ? 'Produit modifié avec succès.' : 'Erreur lors de la modification.');
