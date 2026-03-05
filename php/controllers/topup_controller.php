<?php
// topup.php — เติมเงิน (หน้าผู้ใช้)
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../config/database.php";
if (!isLoggedIn()) {
    redirect($baseUrl . "/login");
}

$userId = (int)$_SESSION['user_id'];

// Balance
$st = $pdo->prepare("SELECT credit_balance FROM users WHERE user_id=? LIMIT 1");
$st->execute([$userId]);
$bal = (float)($st->fetchColumn() ?? 0);
$st->closeCursor();

// User info for Navbar
$userDisplayName = '';
$userAvatarImage = '';
$userAvatarText = '🙂';

$stmtNav = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
$stmtNav->execute([$userId]);
$u = $stmtNav->fetch();
if ($u) {
    $userAvatarImage = !empty($u['img']) ? $baseUrl . '/uploads/avatars/'.basename($u['img']) : '';
    $fn = trim((string)($u['fname'] ?? ''));
    $ln = trim((string)($u['lname'] ?? ''));
    $userDisplayName = ($fn !== '' || $ln !== '') ? trim($fn . ' ' . $ln) : (string)($u['username'] ?? '');
    $userAvatarText = mb_substr($userDisplayName, 0, 1) ?: 'U';
}
$stmtNav->closeCursor();

// History
$hist = $pdo->prepare("SELECT topup_id, amount, method, status, created_at, approved_at, reference_no FROM credit_topups WHERE user_id=? ORDER BY topup_id DESC LIMIT 5");
$hist->execute([$userId]);
$historyRows = $hist->fetchAll();
$hist->closeCursor();
