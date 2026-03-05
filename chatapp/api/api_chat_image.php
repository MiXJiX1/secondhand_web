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

if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    echo json_encode(['ok' => false, 'error' => 'csrf invalid']);
    exit;
}

$requestId = $_POST['request_id'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);

if ($requestId === '' || !isset($_FILES['image'])) {
    echo json_encode(['ok' => false, 'error' => 'missing parameters']);
    exit;
}

$file = $_FILES['image'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'Upload error code: ' . $file['error']]);
    exit;
}

// Validate file type
$allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);

if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['ok' => false, 'error' => 'Invalid file type. Only JPG, PNG, WEBP are allowed.']);
    exit;
}

// Generate unique filename
$ext = pathinfo($file['name'], PATHINFO_EXTENSION);
if (!$ext) {
    $ext = ($mime === 'image/jpeg') ? 'jpg' : (($mime === 'image/png') ? 'png' : 'webp');
}
$filename = 'chat_' . uniqid() . '_' . time() . '.' . $ext;
$targetDir = __DIR__ . '/../../uploads/chat/';

if (!is_dir($targetDir)) {
    mkdir($targetDir, 0777, true);
}

$targetPath = $targetDir . $filename;

if (move_uploaded_file($file['tmp_name'], $targetPath)) {
    // Insert into DB
    $messageText = "IMAGE:" . $filename;
    
    // Check if exchange chat
    $isExchange = preg_match('/^EXC-\d+-\d+$/', $requestId);

    try {
        if ($isExchange) {
            $stmt = $conn->prepare("INSERT INTO exchange_messages (request_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("sis", $requestId, $userId, $messageText);
        } else {
            // Need buyer/seller/product info
            $q = $conn->prepare("SELECT seller_id, buyer_id, product_id FROM chat_requests WHERE request_id = ? LIMIT 1");
            $q->bind_param("s", $requestId);
            $q->execute();
            $req = $q->get_result()->fetch_assoc();
            
            if (!$req) {
                 echo json_encode(['ok' => false, 'error' => 'Chat request not found']);
                 exit;
            }

            $sellerId = (int)$req['seller_id'];
            $buyerId = (int)$req['buyer_id'];
            $pId = (int)$req['product_id'];
            $receiverId = ($userId === $sellerId) ? $buyerId : $sellerId;

            $stmt = $conn->prepare("INSERT INTO messages (request_id, product_id, sender_id, receiver_id, message, is_read, created_at, sent_at) VALUES (?, ?, ?, ?, ?, 0, NOW(), NOW())");
            $stmt->bind_param("siiis", $requestId, $pId, $userId, $receiverId, $messageText);
        }

        if ($stmt->execute()) {
            echo json_encode(['ok' => true, 'filename' => $filename]);
        } else {
            echo json_encode(['ok' => false, 'error' => 'Database error: ' . $stmt->error]);
        }
    } catch (Throwable $e) {
        echo json_encode(['ok' => false, 'error' => 'Server error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['ok' => false, 'error' => 'Failed to move uploaded file']);
}
