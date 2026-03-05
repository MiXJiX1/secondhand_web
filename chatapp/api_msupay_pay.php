<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'error' => 'unauthorized']);
    exit;
}

require_once __DIR__ . "/../config/database.php";

$userId = (int)$_SESSION['user_id'];
$raw = file_get_contents('php://input');
$in  = json_decode($raw, true);

if (!is_array($in)) {
    echo json_encode(['ok' => false, 'error' => 'invalid json body']);
    exit;
}

$requestId = isset($in['request_id']) ? trim($in['request_id']) : '';
$productId = isset($in['product_id']) ? (int)$in['product_id'] : 0;
$password  = (string)($in['password'] ?? '');

if ($requestId === '' || $productId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'bad params']);
    exit;
}
if ($password === '') {
    echo json_encode(['ok' => false, 'error' => 'กรุณากรอกรหัสผ่าน']);
    exit;
}

try {
    // 1) Verify Password
    $st = $pdo->prepare("SELECT password FROM users WHERE user_id=?");
    $st->execute([$userId]);
    $u = $st->fetch();
    if (!$u) {
        echo json_encode(['ok' => false, 'error' => 'user not found']);
        exit;
    }

    $hash = $u['password'];
    if (!password_verify($password, $hash) && !hash_equals($hash, $password)) {
        echo json_encode(['ok' => false, 'error' => 'รหัสผ่านไม่ถูกต้อง']);
        exit;
    }

    // 2) Get Product & Seller Info
    $st = $pdo->prepare("SELECT product_name, product_price, user_id FROM products WHERE product_id=?");
    $st->execute([$productId]);
    $p = $st->fetch();
    if (!$p) {
        echo json_encode(['ok' => false, 'error' => 'product not found']);
        exit;
    }
    $amount = (float)$p['product_price'];
    $sellerId = (int)$p['user_id'];

    if ($amount <= 0) {
        echo json_encode(['ok' => false, 'error' => 'invalid product price']);
        exit;
    }

    // 3) Start Transaction
    $pdo->beginTransaction();

    // 4) Get/Create Escrow User
    $q = $pdo->prepare("SELECT user_id FROM users WHERE username='escrow' LIMIT 1");
    $q->execute();
    $escrowId = $q->fetchColumn();
    if (!$escrowId) {
        $ins = $pdo->prepare("INSERT INTO users (username, password, role, credit_balance, fname, lname, email, img, status) VALUES ('escrow', '', 'admin', 0, 'Escrow', 'Wallet', 'escrow@example.com', '', 'active')");
        $ins->execute();
        $escrowId = (int)$pdo->lastInsertId();
    } else {
        $escrowId = (int)$escrowId;
    }

    // 5) Check & Deduct Buyer Balance
    $st = $pdo->prepare("SELECT credit_balance FROM users WHERE user_id=? FOR UPDATE");
    $st->execute([$userId]);
    $buyer = $st->fetch();
    if (!$buyer || (float)$buyer['credit_balance'] < $amount) {
        throw new Exception('ยอดเงินของคุณไม่เพียงพอ กรุณาเติมเงิน');
    }

    $upd = $pdo->prepare("UPDATE users SET credit_balance = credit_balance - ? WHERE user_id=?");
    $upd->execute([$amount, $userId]);

    // 6) Add to Escrow Balance
    $upd = $pdo->prepare("UPDATE users SET credit_balance = credit_balance + ? WHERE user_id=?");
    $upd->execute([$amount, $escrowId]);

    // 7) Create Order
    $orderNo = 'MSU'.date('YmdHis').bin2hex(random_bytes(3));
    $ins = $pdo->prepare("INSERT INTO orders (order_no, user_id, product_id, request_id, amount, status, paid_at, created_at) VALUES (?,?,?,?,?, 'paid', NOW(), NOW())");
    $ins->execute([$orderNo, $userId, $productId, $requestId, $amount]);

    // 8) Logs
    $led = $pdo->prepare("INSERT INTO credit_ledger (user_id, change_amt, reason, ref_id, created_at) VALUES (?, ?, 'purchase', ?, NOW())");
    $led->execute([$userId, -$amount, $orderNo]);

    $led = $pdo->prepare("INSERT INTO credit_ledger (user_id, change_amt, reason, ref_id, created_at) VALUES (?, ?, 'escrow_hold', ?, NOW())");
    $led->execute([$escrowId, $amount, $orderNo]);

    // 9) Notify in Chat
    $msgText = "✅ นำจ่ายเงินเข้าสู่ระบบ Escrow สำเร็จแล้ว\nจำนวนเงิน: ฿" . number_format($amount, 2) . "\nผู้ขายสามารถจัดส่งสินค้าได้เลย (เงินจะโอนให้เมื่อผู้ซื้อกดรับสินค้า)";
    $insMsg = $pdo->prepare("INSERT INTO messages (request_id, product_id, sender_id, message, created_at) VALUES (?, ?, ?, ?, NOW())");
    $insMsg->execute([$requestId, $productId, $userId, $msgText]);

    $pdo->commit();
    echo json_encode(['ok' => true, 'order_no' => $orderNo]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
