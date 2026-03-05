<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

require_once __DIR__ . "/../../config/database.php";

$userId = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);

if (!is_array($in)) {
    echo json_encode(['ok' => false, 'error' => 'invalid json body']);
    exit;
}

$orderId = isset($in['order_id']) ? (int)$in['order_id'] : 0;
$productId = isset($in['product_id']) ? (int)$in['product_id'] : 0;
$score = isset($in['score']) ? (int)$in['score'] : 0;
$comment = isset($in['comment']) ? trim($in['comment']) : '';

if ($orderId <= 0 || $productId <= 0 || $score < 1 || $score > 5) {
    echo json_encode(['ok' => false, 'error' => 'bad params']);
    exit;
}

try {
    // 1) Get Order and Verify Ownership
    $st = $pdo->prepare("
        SELECT o.id, o.status, o.request_id, p.user_id AS seller_id
        FROM orders o
        JOIN products p ON p.product_id = o.product_id
        WHERE o.id = ? AND o.user_id = ?
        LIMIT 1
    ");
    $st->execute([$orderId, $userId]);
    $o = $st->fetch();

    if (!$o) {
        throw new Exception('ไม่พบคำสั่งซื้อ หรือคุณไม่ใช่เจ้าของ');
    }
    if (!in_array($o['status'], ['released', 'completed'], true)) {
        throw new Exception('คำสั่งซื้อนี้ยังไม่เสร็จสมบูรณ์');
    }

    $sellerId = (int)$o['seller_id'];

    if ($sellerId === $userId) {
        throw new Exception('ไม่สามารถให้คะแนนตัวเองได้');
    }

    // 2) Check if already rated (though DB constraints also enforce this)
    $ch = $pdo->prepare("SELECT rating_id FROM user_ratings WHERE rater_id = ? AND order_id = ? LIMIT 1");
    $ch->execute([$userId, $orderId]);
    if ($ch->fetch()) {
        throw new Exception('คุณให้คะแนนคำสั่งซื้อนี้ไปแล้ว');
    }

    // 3) Insert Rating
    $ins = $pdo->prepare("INSERT INTO user_ratings (rater_id, rated_user_id, order_id, product_id, score, comment, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $ins->execute([$userId, $sellerId, $orderId, $productId, $score, $comment]);

    // Log this activity
    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, (SELECT username FROM users WHERE user_id=? LIMIT 1), 'rate_seller', ?)";
    $logStmt = $pdo->prepare($logSql);
    $desc = "ให้คะแนนผู้ขาย {$score} ดาว สำหรับคำสั่งซื้อ #{$orderId}";
    $logStmt->execute([$userId, $userId, $desc]);

    // 4) Notify in Chat
    $starsStr = str_repeat('⭐', $score);
    $msgText = "คะแนนผู้ขาย: {$score}/5 {$starsStr}\n" . ($comment ? "รีวิว: \"{$comment}\"" : "ผู้ซื้อไม่ได้เขียนคำอธิบายเพิ่มเติม");
    
    $reqId = $o['request_id'];
    if ($reqId) {
        $insMsg = $pdo->prepare("INSERT INTO messages (request_id, product_id, sender_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $insMsg->execute([$reqId, $productId, $userId, $msgText]);
    }

    echo json_encode(['ok' => true]);

} catch (PDOException $e) {
    // Catch unique constraint violation (duplicate entry)
    if ($e->getCode() == 23000) {
        echo json_encode(['ok' => false, 'error' => 'คุณให้คะแนนรายการนี้ไปแล้ว']);
    } else {
        echo json_encode(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} catch (Throwable $e) {
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
