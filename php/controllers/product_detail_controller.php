<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($product_id <= 0) { die("ไม่ระบุรหัสสินค้า"); }

// Fetch Product with Seller Info
$sql = "SELECT p.*, u.username AS seller_username, u.fname, u.lname, u.img AS profile_img
        FROM products p
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE p.product_id = ?";
$st = $pdo->prepare($sql);
$st->execute([$product_id]);
$product = $st->fetch();

if (!$product) { die("ไม่พบสินค้านี้"); }

// Map Lat/Lng Handling from JSON column 'location'
$lat = null;
$lng = null;
$locRaw = $product['location'] ?? '';
if ($locRaw !== '' && $locRaw[0] === '{') {
    $locObj = json_decode($locRaw, true);
    if ($locObj && isset($locObj['lat']) && isset($locObj['lng'])) {
        $lat = $locObj['lat'];
        $lng = $locObj['lng'];
    }
}
// If not JSON, we might have lat/lng directly if they existed as columns, but DESCRIBE didn't show them.
// So we rely on the JSON blob.

// Image Handling
if (!function_exists('getAllImages')) {
function getAllImages($s) {
    if (!$s) return ['assets/no-image.png'];
    $s = trim($s);
    if ($s !== '' && $s[0] === '[') {
        $arr = json_decode($s, true);
        if (is_array($arr) && !empty($arr)) return array_map(fn($v) => 'uploads/'.basename($v), $arr);
    }
    $parts = preg_split('/[|,;]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts) return array_map(fn($v) => 'uploads/'.basename(trim($v)), $parts);
    return ['uploads/'.basename($s)];
}
}

$images = getAllImages($product['product_image']);
$currentUserId = $_SESSION['user_id'] ?? 0;
$isOwner = ($currentUserId > 0 && (int)$product['user_id'] === (int)$currentUserId);

// Favorite Status
$isFavorited = false;
if ($currentUserId > 0) {
    $stFav = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $stFav->execute([$currentUserId, $product_id]);
    $isFavorited = !!$stFav->fetch();
}

$baseUrl = $baseUrl ?? '';
