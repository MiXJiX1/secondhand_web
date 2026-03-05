<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];
$currentUserId = $user_id;

$errorMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['product_name']);
    $price = (float)$_POST['product_price'];
    $cat = $_POST['category'];
    $cond = $_POST['item_condition'] ?? 'Used';
    $desc = trim($_POST['description']);
    $loc = trim($_POST['location_text']);
    $locName = trim($_POST['location_area'] ?? '');
    
    if($name && $price >= 0 && $cat && $desc) {
        $images = [];
        if(!empty($_FILES['images']['name'][0])) {
            foreach($_FILES['images']['tmp_name'] as $i => $tmp) {
                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $tmp);
                finfo_close($finfo);
                
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (in_array($realMime, $allowed, true)) {
                    $new = 'p_'.bin2hex(random_bytes(8)).'.'.$ext;
                    move_uploaded_file($tmp, __DIR__.'/../../uploads/'.$new);
                    $images[] = $new;
                }
            }
        }
        
        $imgField = json_encode($images);
        $stmt = $conn->prepare("INSERT INTO products (product_name, product_price, product_image, category, item_condition, description, location, location_name, user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
        $stmt->bind_param("sdssssssi", $name, $price, $imgField, $cat, $cond, $desc, $loc, $locName, $user_id);
        
        if($stmt->execute()) {
            $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'product_add', 'ลงขายสินค้าใหม่ในระบบ')";
            $logStmt = $conn->prepare($logSql);
            $userNameFromSession = $_SESSION['username'] ?? "UID_{$user_id}";
            $logStmt->bind_param('is', $user_id, $userNameFromSession);
            $logStmt->execute();

            header("Location: ../index.php");
            exit;
        } else {
            $errorMsg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
        }
    } else {
        $errorMsg = "กรุณากรอกข้อมูลให้ครบถ้วน";
    }
}

// Fetch User details for Header 
$userDisplayName = '';
$userAvatarImage = '';
$userAvatarText = '🙂';
if ($stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1")) {
    $stmt->bind_param('i', $user_id);
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
