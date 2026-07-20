<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json; charset=utf-8');

function chatResponse(bool $success, string $message = '', array $data = [], int $status = 200): void {
    http_response_code($status);
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

if (empty($_SESSION['is_logged_in']) || empty($_SESSION['user_id'])) chatResponse(false, 'Votre session a expiré.', [], 401);
$role = $_SESSION['user_type'] ?? '';
if (!in_array($role, ['acheteur', 'agriculteur'], true)) chatResponse(false, 'La messagerie est réservée aux acheteurs et agriculteurs.', [], 403);

require_once __DIR__ . '/../../config/database.php';
$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) chatResponse(false, 'Requête invalide.', [], 400);
$action = $data['action'] ?? '';
$userId = (int) $_SESSION['user_id'];
$contactRole = $role === 'acheteur' ? 'agriculteur' : 'acheteur';

function validContact(PDO $pdo, int $contactId, string $contactRole) {
    $stmt = $pdo->prepare('SELECT id, nom, prenom FROM utilisateurs WHERE id = :id AND type_utilisateur = :role AND statut = "actif" AND supprime = 0');
    $stmt->execute([':id' => $contactId, ':role' => $contactRole]);
    return $stmt->fetch();
}

function findChat(PDO $pdo, int $userId, int $contactId, string $role): ?int {
    $sql = $role === 'acheteur'
        ? 'SELECT id FROM chats WHERE acheteur_id = :me AND agriculteur_id = :contact AND supprime = 0 LIMIT 1'
        : 'SELECT id FROM chats WHERE agriculteur_id = :me AND acheteur_id = :contact AND supprime = 0 LIMIT 1';
    $stmt = $pdo->prepare($sql); $stmt->execute([':me' => $userId, ':contact' => $contactId]);
    $id = $stmt->fetchColumn(); return $id ? (int) $id : null;
}

try {
    if ($action === 'list') {
        $join = $role === 'acheteur' ? 'c.agriculteur_id = u.id AND c.acheteur_id = :me' : 'c.acheteur_id = u.id AND c.agriculteur_id = :me';
        $stmt = $pdo->prepare("SELECT u.id AS user_id, u.nom, u.prenom, c.id AS chat_id,
            (SELECT m.contenu FROM messages m WHERE m.chat_id = c.id AND m.supprime = 0 ORDER BY m.id DESC LIMIT 1) AS dernier_message,
            (SELECT COUNT(*) FROM messages m WHERE m.chat_id = c.id AND m.expediteur_id <> :me2 AND m.est_lu = 0 AND m.supprime = 0) AS non_lus
            FROM utilisateurs u LEFT JOIN chats c ON $join AND c.supprime = 0
            WHERE u.type_utilisateur = :contact_role AND u.statut = 'actif' AND u.supprime = 0
            ORDER BY (c.id IS NULL), c.id DESC, u.prenom, u.nom");
        $stmt->execute([':me' => $userId, ':me2' => $userId, ':contact_role' => $contactRole]);
        $contacts = array_map(function ($item) use ($contactRole) {
            return ['user_id'=>(int)$item->user_id,'nom'=>$item->nom,'prenom'=>$item->prenom,'chat_id'=>$item->chat_id?(int)$item->chat_id:null,'dernier_message'=>$item->dernier_message,'non_lus'=>(int)$item->non_lus,'role_label'=>$contactRole === 'agriculteur' ? 'Agriculteur' : 'Acheteur'];
        }, $stmt->fetchAll());
        chatResponse(true, '', ['contacts' => $contacts]);
    }

    $contactId = (int) ($data['contact_id'] ?? 0);
    if ($contactId <= 0 || !validContact($pdo, $contactId, $contactRole)) chatResponse(false, 'Contact introuvable.', [], 404);
    $chatId = findChat($pdo, $userId, $contactId, $role);

    if ($action === 'messages') {
        $items = [];
        if ($chatId) {
            $pdo->prepare('UPDATE messages SET est_lu = 1 WHERE chat_id = :chat AND expediteur_id <> :me AND est_lu = 0')->execute([':chat'=>$chatId, ':me'=>$userId]);
            $stmt = $pdo->prepare("SELECT expediteur_id, contenu, DATE_FORMAT(date_envoi, '%d/%m %H:%i') AS heure FROM messages WHERE chat_id = :chat AND supprime = 0 ORDER BY id ASC LIMIT 300");
            $stmt->execute([':chat' => $chatId]);
            $items = array_map(fn($m) => ['contenu'=>$m->contenu,'heure'=>$m->heure,'est_moi'=>(int)$m->expediteur_id===$userId], $stmt->fetchAll());
        }
        chatResponse(true, '', ['chat_id' => $chatId, 'messages' => $items]);
    }

    if ($action === 'send') {
        $content = trim((string) ($data['contenu'] ?? ''));
        if ($content === '' || mb_strlen($content) > 2000) chatResponse(false, 'Le message doit contenir entre 1 et 2 000 caractères.', [], 422);
        $pdo->beginTransaction();
        if (!$chatId) {
            $farmerId = $role === 'agriculteur' ? $userId : $contactId;
            $buyerId = $role === 'acheteur' ? $userId : $contactId;
            $stmt = $pdo->prepare('INSERT INTO chats (agriculteur_id, acheteur_id) VALUES (:farmer, :buyer)');
            $stmt->execute([':farmer'=>$farmerId, ':buyer'=>$buyerId]); $chatId = (int) $pdo->lastInsertId();
        }
        $stmt = $pdo->prepare('INSERT INTO messages (chat_id, expediteur_id, contenu) VALUES (:chat, :sender, :content)');
        $stmt->execute([':chat'=>$chatId, ':sender'=>$userId, ':content'=>$content]);
        $pdo->commit();
        chatResponse(true, 'Message envoyé.', ['chat_id' => $chatId]);
    }
    chatResponse(false, 'Action inconnue.', [], 400);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('Chat: ' . $e->getMessage());
    chatResponse(false, 'Impossible de charger la messagerie.', [], 500);
}
