<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isLoggedIn()) { redirect($baseUrl . '/login'); }
$user_id = (int)$_SESSION['user_id'];
$currentUserId = $user_id;

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

$errorMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $errorMsg = "CSRF token ไม่ถูกต้อง";
    } else {
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
            
            try {
                $imgField = json_encode($images);
                $stmt = $pdo->prepare("INSERT INTO products (product_name, product_price, product_image, category, item_condition, description, location, location_name, user_id, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())");
                $stmt->execute([$name, $price, $imgField, $cat, $cond, $desc, $loc, $locName, $user_id]);
                
                $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'product_add', 'ลงขายสินค้าใหม่ในระบบ')";
                $logStmt = $pdo->prepare($logSql);
                $userNameFromSession = $_SESSION['username'] ?? "UID_{$user_id}";
                $logStmt->execute([$user_id, $userNameFromSession]);

                redirect($baseUrl . "/");
            } catch (PDOException $e) {
                $errorMsg = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
            }
        } else {
            if (empty($errorMsg)) {
                $errorMsg = "กรุณากรอกข้อมูลให้ครบถ้วน";
            }
        }
    }
}

// Header data (userDisplayName, userAvatarImage, userAvatarText) 
// is now centrally handled in navbar_main.php via global $pdo.
