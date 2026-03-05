<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) {
    echo json_encode(["status" => "error", "message" => "Not logged in"]);
    exit();
}

$me = (int)$_SESSION['user_id'];
$requestId = isset($_POST['request_id']) ? trim($_POST['request_id']) : '';
$message = isset($_POST['message']) ? trim($_POST['message']) : '';

if ($requestId === '' || $message === '') {
    echo json_encode(["status" => "error", "message" => "Missing parameters"]);
    exit();
}

try {
    // Ensure the user is a participant in this chat request.
    $stmt = $pdo->prepare("SELECT product_id, seller_id, buyer_id FROM chat_requests WHERE request_id = ? LIMIT 1");
    $stmt->execute([$requestId]);
    $req = $stmt->fetch();

    if (!$req) {
        echo json_encode(["status" => "error", "message" => "Chat not found"]);
        exit();
    }

    if ($me !== (int)$req['seller_id'] && $me !== (int)$req['buyer_id']) {
        echo json_encode(["status" => "error", "message" => "Unauthorized"]);
        exit();
    }

    $receiverId = ($me === (int)$req['seller_id']) ? (int)$req['buyer_id'] : (int)$req['seller_id'];
    $productId = (int)$req['product_id'];

    // Check if this is an exchange chat
    $isExchange = preg_match('/^EXC-\d+-\d+$/', $requestId);

    if ($isExchange) {
        // Insert into exchange_messages
        $ins = $pdo->prepare("INSERT INTO exchange_messages (request_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
        $ins->execute([$requestId, $me, $message]);
    } else {
        // Insert into messages
        $ins = $pdo->prepare("INSERT INTO messages (request_id, product_id, sender_id, receiver_id, message, is_read, created_at, sent_at) VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())");
        $ins->execute([$requestId, $productId, $me, $receiverId, $message]);
    }

    echo json_encode(["status" => "ok"]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
