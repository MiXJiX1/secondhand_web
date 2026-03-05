<?php
require_once __DIR__ . '/../../config/database.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) { 
    echo json_encode([]); 
    exit(); 
}

try {
    $requestId = isset($_GET['request_id']) ? trim($_GET['request_id']) : '';
    $productId = isset($_GET['product_id']) ? (int)($_GET['product_id'] ?? 0) : 0;

    if ($requestId === '') { echo json_encode([]); exit(); }

    // ห้องแลกเปลี่ยน? EXC-<item_id>-<offer_id>
    $isExchange = preg_match('/^EXC-(\d+)-(\d+)$/', $requestId);

    if ($isExchange) {
        $sql  = "SELECT id, sender_id, message, created_at
                 FROM exchange_messages
                 WHERE request_id = ?
                 ORDER BY created_at ASC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requestId]);
    } else {
        if ($productId <= 0) { echo json_encode([]); exit(); }
        $sql  = "SELECT id, sender_id, message,
                        COALESCE(sent_at, created_at) AS created_at
                 FROM messages
                 WHERE request_id = ? AND product_id = ?
                 ORDER BY created_at ASC, id ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$requestId, $productId]);
    }

    $out = [];
    while ($row = $stmt->fetch()) {
        $out[] = [
            "id"        => (int)$row["id"],
            "sender_id" => (int)$row["sender_id"],
            "fullname"  => "",
            "message"   => (string)($row["message"] ?? ""),
            "sent_at"   => (string)($row["created_at"] ?? "")
        ];
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    echo json_encode([]);
}
