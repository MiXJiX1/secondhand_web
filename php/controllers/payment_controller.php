<?php
session_start();

require_once __DIR__ . "/../../config/database.php";

if (empty($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

/* ---------- DB (PDO) ---------- */
// PDO is provided by database.php ($pdo)

/* ---------- Helpers ---------- */
if(!function_exists('h')){ function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

/** ดึงไฟล์รูปแรกจากฟิลด์ที่อาจเป็น JSON/คั่นด้วย | , ; หรือเป็นชื่อไฟล์เดี่ยว */
if(!function_exists('firstImageFromField')){
function firstImageFromField(?string $s): ?string {
    if (!$s) return null;
    $s = trim($s);
    if ($s !== '' && $s[0] === '[') {
        $arr = json_decode($s, true);
        if (is_array($arr) && !empty($arr)) return basename((string)$arr[0]);
    }
    $parts = preg_split('/[|,;]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts && isset($parts[0])) return basename(trim($parts[0]));
    return basename($s);
}
}

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
if ($firstImg) {
    // ถ้าชื่อไฟล์เริ่มด้วย assets/ ให้ใช้ตรง ๆ ไม่ต้องผ่าน uploads
    $imgSrc = (strpos($firstImg, 'assets/') === 0) ? $firstImg : 'uploads/' . $firstImg;
} else {
    $imgSrc = 'assets/no-image.png';
}

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_order'])) {
    $_SESSION['csrf_order'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_order'];
