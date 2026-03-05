<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

require_once __DIR__ . '/../config/database.php';

$userId = (int)$_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;

if ($productId <= 0) {
    echo json_encode(['success' => false, 'error' => 'invalid_product']);
    exit;
}

try {
    // Check if the product exists
    $stProd = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ?");
    $stProd->execute([$productId]);
    if (!$stProd->fetch()) {
        echo json_encode(['success' => false, 'error' => 'product_not_found']);
        exit;
    }

    // Check if already favorited
    $stCheck = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
    $stCheck->execute([$userId, $productId]);
    $fav = $stCheck->fetch();

    if ($fav) {
        // Unfavorite
        $stDel = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
        $stDel->execute([$fav['id']]);
        echo json_encode(['success' => true, 'is_favorited' => false]);
    } else {
        // Favorite
        $stIns = $pdo->prepare("INSERT INTO favorites (user_id, product_id, created_at) VALUES (?, ?, NOW())");
        $stIns->execute([$userId, $productId]);
        echo json_encode(['success' => true, 'is_favorited' => true]);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'database_error', 'details' => $e->getMessage()]);
}
