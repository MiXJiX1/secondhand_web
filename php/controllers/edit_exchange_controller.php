<?php
session_start();
if (!isLoggedIn()) {
    redirect($baseUrl . "/login");
}
$user_id = (int)$_SESSION['user_id'];

// โหลดค่าคงที่ DB / Token ต่างๆ
require_once __DIR__ . "/../../config/database.php";

// Image helpers are already in database.php

$CATS = ['ทั่วไป','อิเล็กทรอนิกส์','เสื้อผ้า','หนังสือ','ของสะสม','บริการ'];

// ---------- โหลดสินค้า ----------
$itemId = (int)($_GET['id'] ?? 0);
$isCreate = ($itemId <= 0);

$prod = [
    'title' => '',
    'category' => 'ทั่วไป',
    'want_text' => '',
    'condition_text' => '',
    'location' => '',
    'description' => '',
    'images' => '[]',
    'status' => 'available'
];

if (!$isCreate) {
    $stmt = $pdo->prepare("SELECT * FROM exchange_items WHERE item_id=? AND user_id=? LIMIT 1");
    $stmt->execute([$itemId, $user_id]);
    $res = $stmt->fetch();
    if (!$res) {
        throw new Exception("ไม่พบสินค้าหรือไม่มีสิทธิ์แก้ไข", 404);
    }
    $prod = $res;
}

$successMsg = $errorMsg = "";

// ---------- CSRF ----------
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrfToken = $_SESSION['csrf_token'];

// ---------- SAVE FORM ----------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['title'])) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $errorMsg = "CSRF token ไม่ถูกต้อง";
    } else {
        $title     = trim((string)$_POST['title']);
        $cat       = trim((string)$_POST['category']);
        $want      = trim((string)$_POST['want_text']);
        $cond_text = trim((string)$_POST['condition_text']);
        $loc       = trim((string)$_POST['location']);
        $desc      = trim((string)$_POST['description']);
        $status    = $_POST['status'] ?? 'available';

        if (empty($title) || empty($cat) || empty($want)) {
            $errorMsg = "กรุณากรอกข้อมูลให้ครบถ้วน (ชื่อสินค้า, หมวดหมู่, สิ่งที่ต้องการแลก)";
        } else {
            $uploadDir = rtrim(__DIR__ . '/../../uploads', '/\\') . DIRECTORY_SEPARATOR;
            if (!is_dir($uploadDir)) @mkdir($uploadDir, 0777, true);

            $allowed = ['jpg','jpeg','png','webp','gif'];
            $oldImages = allImagesFromField($prod['images']);
            $finalImages = $oldImages;

            // ลบรูปที่เลือก
            if (isset($_POST['remove_images']) && is_array($_POST['remove_images'])) {
                foreach ($_POST['remove_images'] as $rimg) {
                    $rimg = basename($rimg);
                    if (($key = array_search($rimg, $finalImages)) !== false) {
                        unset($finalImages[$key]);
                        // optional: @unlink($uploadDir . $rimg);
                    }
                }
                $finalImages = array_values($finalImages);
            }

            // อัปโหลดรูปใหม่
            if (!empty($_FILES['images']['name'][0])) {
                foreach ($_FILES['images']['name'] as $i => $fname) {
                    if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                    $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                    if (!in_array($ext, $allowed)) continue;
                    
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $realMime = finfo_file($finfo, $_FILES['images']['tmp_name'][$i]);
                    finfo_close($finfo);
                    
                    $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                    if (!in_array($realMime, $allowedMime, true)) continue;

                    $newName = 'ex_' . uniqid() . '_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $newName)) {
                        $finalImages[] = $newName;
                    }
                }
            }

            $imagesJson = json_encode($finalImages, JSON_UNESCAPED_SLASHES);

            if ($isCreate) {
                $st = $pdo->prepare("INSERT INTO exchange_items (user_id, title, category, want_text, condition_text, location, description, images, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $st->execute([$user_id, $title, $cat, $want, $cond_text, $loc, $desc, $imagesJson, $status]);
                redirect($baseUrl . "/exchange?ok=1");
            } else {
                $st = $pdo->prepare("UPDATE exchange_items SET title=?, category=?, want_text=?, condition_text=?, location=?, description=?, images=?, status=?, updated_at=NOW() WHERE item_id=? AND user_id=?");
                $st->execute([$title, $cat, $want, $cond_text, $loc, $desc, $imagesJson, $status, $itemId, $user_id]);
                $successMsg = "บันทึกข้อมูลเรียบร้อยแล้ว";
                // รีโหลดข้อมูลใหม่
                $stmt = $pdo->prepare("SELECT * FROM exchange_items WHERE item_id=? AND user_id=? LIMIT 1");
                $stmt->execute([$itemId, $user_id]);
                $prod = $stmt->fetch();
            }
        }
    }
}
