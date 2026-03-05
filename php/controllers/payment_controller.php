<?php
require_once __DIR__ . "/../../config/database.php";

if (!isLoggedIn()) {
    redirect($baseUrl . "/login");
}

// Product ID handling (helpers are already included in database.php)
$product = null;

/* ---------- รับ product_id & คิวรีสินค้า ---------- */
$product = null;
if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];
    if ($product_id > 0) {
        $st = $pdo->prepare("SELECT product_id, product_name, product_price, product_image, category FROM products WHERE product_id = ? LIMIT 1");
        $st->execute([$product_id]);
        $product = $st->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$product) {
    http_response_code(404);
    echo "ไม่พบสินค้านี้";
    exit;
}

/* ---------- เตรียมรูป ---------- */
$firstImg = firstImageFromField($product['product_image'] ?? '');
$imgSrc = $firstImg ? $baseUrl . '/uploads/' . $firstImg : $baseUrl . '/assets/default.png';

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];
