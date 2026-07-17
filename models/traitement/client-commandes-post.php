<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

function clientOrderResponse($success, $message, $extra = [], $status = 200) {
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['is_logged_in']) || ($_SESSION['user_type'] ?? '') !== 'acheteur') {
    clientOrderResponse(false, 'Connectez-vous avec un compte acheteur pour commander.', ['login_required' => true], 401);
}

require_once __DIR__ . '/../../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) clientOrderResponse(false, 'Données invalides.', [], 400);

$adresse = trim($data['adresse_livraison'] ?? '');
$instructions = trim($data['instructions'] ?? '');
$mode = $data['mode_paiement'] ?? '';
$items = $data['items'] ?? [];
if ($adresse === '') clientOrderResponse(false, "L'adresse de livraison est obligatoire.", [], 422);
if (!in_array($mode, ['especes', 'carte', 'mobile_money', 'virement', 'autre'], true)) clientOrderResponse(false, 'Mode de paiement invalide.', [], 422);
if (!is_array($items) || !$items || count($items) > 50) clientOrderResponse(false, 'Votre panier est vide ou invalide.', [], 422);

try {
    $pdo->beginTransaction();
    $products = [];
    foreach ($items as $item) {
        $id = (int) ($item['id'] ?? 0);
        $quantity = (float) ($item['quantite'] ?? 0);
        if ($id <= 0 || $quantity <= 0) throw new DomainException('Un article du panier est invalide.');
        if (isset($products[$id])) $products[$id] += $quantity;
        else $products[$id] = $quantity;
    }

    $select = $pdo->prepare('SELECT id, nom, prix_unitaire, quantite_stock FROM produits WHERE id = :id AND supprime = 0 AND est_disponible = 1 FOR UPDATE');
    $verified = []; $subtotal = 0.0;
    foreach ($products as $id => $quantity) {
        $select->execute([':id' => $id]);
        $product = $select->fetch();
        if (!$product) throw new DomainException('Un produit du panier n’est plus disponible.');
        if ((float) $product->quantite_stock < $quantity) throw new DomainException('Stock insuffisant pour « ' . $product->nom . ' ».');
        $price = (float) $product->prix_unitaire;
        $subtotal += $price * $quantity;
        $verified[] = ['id' => $id, 'quantity' => $quantity, 'price' => $price];
    }

    $deliveryFee = 500.0;
    $total = $subtotal + $deliveryFee;
    $number = 'CMD-' . date('Ymd-His') . '-' . strtoupper(bin2hex(random_bytes(2)));
    $stmt = $pdo->prepare('INSERT INTO commandes (acheteur_id, numero_commande, date_commande, montant_total, supprime) VALUES (:buyer, :number, NOW(), :total, 0)');
    $stmt->execute([':buyer' => (int) $_SESSION['user_id'], ':number' => $number, ':total' => $total]);
    $orderId = (int) $pdo->lastInsertId();

    $line = $pdo->prepare('INSERT INTO ligne_commandes (commande_id, produit_id, quantite, prix_unitaire, supprime) VALUES (:order_id, :product_id, :quantity, :price, 0)');
    $stock = $pdo->prepare('UPDATE produits SET quantite_stock = quantite_stock - :q1, est_disponible = IF(quantite_stock - :q2 <= 0, 0, est_disponible) WHERE id = :id');
    foreach ($verified as $item) {
        $line->execute([':order_id' => $orderId, ':product_id' => $item['id'], ':quantity' => $item['quantity'], ':price' => $item['price']]);
        $stock->execute([':id' => $item['id'], ':q1' => $item['quantity'], ':q2' => $item['quantity']]);
    }

    $stmt = $pdo->prepare('INSERT INTO details_livraison (id_commande, adresse_livraison, instructions_specifiques, supprime) VALUES (:order_id, :address, :instructions, 0)');
    $stmt->execute([':order_id' => $orderId, ':address' => $adresse, ':instructions' => $instructions ?: null]);
    $stmt = $pdo->prepare('INSERT INTO paiements (commande_id, reference_paiement, montant, mode_paiement, supprime) VALUES (:order_id, :reference, :amount, :mode, 0)');
    $stmt->execute([':order_id' => $orderId, ':reference' => 'PAY-' . $number, ':amount' => $total, ':mode' => $mode]);

    $pdo->commit();
    clientOrderResponse(true, 'Commande #' . $number . ' enregistrée avec succès.', ['commande_id' => $orderId, 'numero_commande' => $number, 'total' => $total]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Commande client: ' . $e->getMessage());
    clientOrderResponse(false, $e instanceof DomainException ? $e->getMessage() : "Impossible d'enregistrer la commande.", [], 422);
}
