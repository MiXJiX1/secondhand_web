<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

$product_id = (int)($_GET['product_id'] ?? $_GET['id'] ?? 0);
if ($product_id <= 0) { throw new Exception("ไม่ระบุรหัสสินค้า", 400); }

// Fetch Product with Seller Info
$sql = "SELECT p.*, u.username AS seller_username, u.fname, u.lname, u.img AS profile_img
        FROM products p
        LEFT JOIN users u ON p.user_id = u.user_id
        WHERE p.product_id = ?";
$st = $pdo->prepare($sql);
$st->execute([([$product_id])[0]]); // Single param execute
$product = $st->fetch();

if (!$product) { throw new Exception("ไม่พบสินค้านี้", 404); }

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

$images = allImagesFromField($product['product_image']);
$currentUserId = (int)($_SESSION['user_id'] ?? 0);
$isOwner = (isLoggedIn() && (int)$product['user_id'] === $currentUserId);

// Favorite Status
$isFavorited = false;
if (isLoggedIn()) {
    $stFav = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $stFav->execute([$currentUserId, $product_id]);
    $isFavorited = !!$stFav->fetch();
}

$baseUrl = $baseUrl ?? '';
