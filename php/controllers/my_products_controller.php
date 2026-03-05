<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

// CSRF
if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(24)); }
$csrf = $_SESSION['csrf_token'];

// Fetch User Info for Header
$currentUserId = $user_id;

$userDisplayName = '';
$userAvatarImage = '';
$userAvatarText = '🙂';
if ($stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1")) {
    $stmt->bind_param('i', $currentUserId);
    if ($stmt->execute()) {
        $u = $stmt->get_result()->fetch_assoc();
        if ($u) {
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
    }
    $stmt->close();
}

// Fetch Products
$sql = "SELECT * FROM products WHERE user_id = ? ORDER BY (status='sold') ASC, product_id DESC";
$st = $conn->prepare($sql);
$st->bind_param('i', $user_id);
$st->execute();
$rows = $st->get_result()->fetch_all(MYSQLI_ASSOC);

if (!function_exists('firstImageFromField')) {
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
