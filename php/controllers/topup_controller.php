<?php
// topup.php — เติมเงิน (หน้าผู้ใช้)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../config/database.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$userId = (int)$_SESSION['user_id'];

// Balance
$st = $pdo->prepare("SELECT credit_balance FROM users WHERE user_id=? LIMIT 1");
$st->execute([$userId]);
$bal = (float)($st->fetchColumn() ?? 0);
$st->closeCursor();

// User info for Navbar
$currentUserId = $userId;
$userDisplayName = '';
$userAvatarImage = '';
$userAvatarText = '🙂';
$isVerified = false;

$stmtNav = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmtNav->execute([$currentUserId]);
$u = $stmtNav->fetch();
if ($u) {
    // Determine Verified status (Mock property for UI if not in DB)
    $isVerified = true; 
    
    $userAvatarImage = !empty($u['img']) ? '../uploads/avatars/'.basename($u['img']) : '';
    $fn = trim((string)($u['fname'] ?? ''));
    $ln = trim((string)($u['lname'] ?? ''));
    if ($fn !== '' || $ln !== '') {
        $userDisplayName = trim($fn . ' ' . $ln);
    } else {
        $userDisplayName = (string)($u['username'] ?? ($_SESSION['username'] ?? ''));
    }
    $parts = preg_split('/\s+/', $userDisplayName, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts) {
        $userAvatarText = mb_substr($parts[0], 0, 1, 'UTF-8') . (isset($parts[1]) ? mb_substr($parts[1], 0, 1, 'UTF-8') : '');
    }
}
$stmtNav->closeCursor();

// History
$hist = $pdo->prepare("SELECT topup_id, amount, method, status, created_at, approved_at, reference_no FROM credit_topups WHERE user_id=? ORDER BY topup_id DESC LIMIT 5");
$hist->execute([$userId]);
$historyRows = $hist->fetchAll();
$hist->closeCursor();
