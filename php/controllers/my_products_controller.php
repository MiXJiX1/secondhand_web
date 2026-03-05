<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isLoggedIn()) {
    redirect($baseUrl . '/login');
}
$user_id = (int)$_SESSION['user_id'];

// CSRF
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); }
$csrf = $_SESSION['csrf_token'];

// Fetch User Info for Header
$currentUserId = $user_id;

$userDisplayName = '';
$userAvatarImage = '';
$userAvatarText = '🙂';
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$currentUserId]);
    $u = $stmt->fetch();
    if ($u) {
        $userAvatarImage = !empty($u['img']) ? $baseUrl . '/uploads/avatars/'.basename($u['img']) : '';
        $fn = trim((string)($u['fname'] ?? ''));
        $ln = trim((string)($u['lname'] ?? ''));
        if ($fn !== '' || $ln !== '') {
            $userDisplayName = trim($fn . ' ' . $ln);
        } else {
            $userDisplayName = (string)($u['username'] ?? ($_SESSION['username'] ?? ''));
        }
        $userAvatarText = mb_substr($userDisplayName, 0, 1) ?: 'U';
    }
} catch (PDOException $e) {
    // Fail silently for UI headers
}

// Fetch Products
try {
    $sql = "SELECT * FROM products WHERE user_id = ? ORDER BY (status='sold') ASC, product_id DESC";
    $st = $pdo->prepare($sql);
    $st->execute([$user_id]);
    $rows = $st->fetchAll();
} catch (PDOException $e) {
    throw new Exception("Database error while fetching products: " . $e->getMessage());
}
