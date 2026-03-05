<?php
// api/ban_appeal.php
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/database.php';

try {
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
    $st = $conn->prepare("SELECT user_id, status FROM users WHERE username=? LIMIT 1");
    $st->bind_param('s', $username);
    $st->execute();
    $u = $st->get_result()->fetch_assoc();
    $st->close();

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

    // ✅ กันส่งซ้ำโดยไม่ใช้ fetch_column()
    $st = $conn->prepare("SELECT appeal_id FROM ban_appeals WHERE user_id=? AND status='pending' LIMIT 1");
    $st->bind_param('i', $u['user_id']);
    $st->execute();
    $st->bind_result($appealId);
    $hasPending = $st->fetch();   // true ถ้ามีแถวคืนมา
    $st->close();

    if ($hasPending) {
        echo json_encode(['ok'=>true, 'message'=>'ส่งคำร้องไว้แล้ว กำลังรอผู้ดูแลตรวจสอบ']);
        exit;
    }

    // บันทึกคำร้องใหม่
    $st = $conn->prepare("INSERT INTO ban_appeals (user_id, username, message) VALUES (?,?,?)");
    $st->bind_param('iss', $u['user_id'], $username, $message);
    $st->execute();

    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'appeal', 'ส่งคำร้องขอปลดแบนจากระบบ')";
    $logStmt = $conn->prepare($logSql);
    $logStmt->bind_param('is', $u['user_id'], $username);
    $logStmt->execute();

    $st->close();

    echo json_encode(['ok'=>true, 'message'=>'ส่งคำร้องเรียบร้อย แอดมินจะตรวจสอบให้เร็วที่สุด']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'message'=>'เกิดข้อผิดพลาด: '.$e->getMessage()]);
}
