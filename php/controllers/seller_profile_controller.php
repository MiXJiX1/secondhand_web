<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

$seller_id = (int)($_GET['id'] ?? 0);

if ($seller_id <= 0) {
    redirect($baseUrl . "/");
}

// Fetch Seller Data
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

if (!$seller) {
    http_response_code(404);
    echo "ไม่พบผู้ใช้รายนี้ในระบบ";
    exit;
}

$avatarPath = !empty($seller['img']) ? $baseUrl . '/uploads/avatars/'.basename($seller['img']) : $baseUrl . '/assets/no-avatar.png';
$fullName = trim(($seller['fname']??'') . ' ' . ($seller['lname']??'')) ?: h((string)$seller['username']);
$joinDateTh = date('M Y', strtotime($seller['created_at']));
$location = "Thailand"; 

// Fetch Stats (Active Listings, Items Sold)
$stStats = $pdo->prepare("SELECT COUNT(CASE WHEN status='active' THEN 1 END) as active_count, COUNT(CASE WHEN status='sold' THEN 1 END) as sold_count FROM products WHERE user_id = ?");
$stStats->execute([$seller_id]);
$stats = $stStats->fetch();
$activeCount = $stats['active_count'] ?? 0;
$soldCount = $stats['sold_count'] ?? 0;

// Fetch Average Rating
$stRating = $pdo->prepare("SELECT AVG(score) as avg_score, COUNT(*) as count FROM user_ratings WHERE rated_user_id = ?");
$stRating->execute([$seller_id]);
$ratingData = $stRating->fetch();
$avgRating = $ratingData['avg_score'] ? number_format((float)$ratingData['avg_score'], 1) : '0.0';
$ratingCount = $ratingData['count'] ?? 0;

// Fetch Seller's Active Products
$stProds = $pdo->prepare("SELECT * FROM products WHERE user_id = ? AND status='active' ORDER BY created_at DESC");
$stProds->execute([$seller_id]);
$products = $stProds->fetchAll();

// For Navbar
$userDisplayName = '';
$userAvatarImage = '';
$userAvatarText = '🙂';
if (isLoggedIn()) {
    $currentUserId = (int)$_SESSION['user_id'];
    $stmtNav = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
    $stmtNav->execute([$currentUserId]);
    $u = $stmtNav->fetch();
    if ($u) {
        $userAvatarImage = !empty($u['img']) ? $baseUrl . '/uploads/avatars/'.basename($u['img']) : '';
        $fn = trim((string)($u['fname'] ?? ''));
        $ln = trim((string)($u['lname'] ?? ''));
        $userDisplayName = ($fn !== '' || $ln !== '') ? trim($fn . ' ' . $ln) : (string)($u['username'] ?? '');
        $userAvatarText = mb_substr($userDisplayName, 0, 1) ?: 'U';
    }
}
