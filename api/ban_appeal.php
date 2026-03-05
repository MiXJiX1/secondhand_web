<?php
// api/ban_appeal.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok'=>false, 'message'=>'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$message  = trim($_POST['message']  ?? '');

if ($username === '') {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'message'=>'กรอกชื่อผู้ใช้ก่อน']);
    exit;
}

// หา user
$st = $pdo->prepare("SELECT user_id, status FROM users WHERE username=? LIMIT 1");
$st->execute([$username]);
$u = $st->fetch();

if (!$u) {
    http_response_code(404);
    echo json_encode(['ok'=>false, 'message'=>'ไม่พบบัญชีนี้ในระบบ']);
    exit;
}

if (strtolower((string)$u['status']) !== 'banned') {
    http_response_code(400);
    echo json_encode(['ok'=>false, 'message'=>'ส่งคำร้องได้เฉพาะบัญชีที่ถูกแบน']);
    exit;
}

// ✅ กันส่งซ้ำ
$st = $pdo->prepare("SELECT appeal_id FROM ban_appeals WHERE user_id=? AND status='pending' LIMIT 1");
$st->execute([$u['user_id']]);
$hasPending = $st->fetch();

if ($hasPending) {
    echo json_encode(['ok'=>true, 'message'=>'ส่งคำร้องไว้แล้ว กำลังรอผู้ดูแลตรวจสอบ']);
    exit;
}

// บันทึกคำร้องใหม่
$pdo->beginTransaction();
try {
    $st = $pdo->prepare("INSERT INTO ban_appeals (user_id, username, message) VALUES (?,?,?)");
    $st->execute([$u['user_id'], $username, $message]);

    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'appeal', 'ส่งคำร้องขอปลดแบนจากระบบ')";
    $logStmt = $pdo->prepare($logSql);
    $logStmt->execute([$u['user_id'], $username]);

    $pdo->commit();
    echo json_encode(['ok'=>true, 'message'=>'ส่งคำร้องเรียบร้อย แอดมินจะตรวจสอบให้เร็วที่สุด']);
} catch (Exception $e) {
    $pdo->rollBack();
    throw $e;
}
