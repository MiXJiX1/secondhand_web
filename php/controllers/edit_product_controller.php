<?php
session_start();
if (!isLoggedIn()) {
    redirect($baseUrl . '/login');
}
$user_id = (int)$_SESSION['user_id'];

// โหลดค่าคงที่ DB / Token ต่างๆ
require_once __DIR__ . "/../../config/database.php";

$categories = [
    'electronics'=>'อุปกรณ์อิเล็กทรอนิกส์','fashion'=>'แฟชั่น','furniture'=>'เฟอร์นิเจอร์',
    'vehicle'=>'ยานพาหนะ','gameandtoys'=>'เกมและของเล่น','household'=>'ของใช้ในครัวเรือน',
    'sport'=>'อุปกรณ์กีฬา','music'=>'เครื่องดนตรี','others'=>'อื่นๆ',
];

// ---------- โหลดสินค้า ----------
$productId = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM products WHERE product_id=? AND user_id=? LIMIT 1");
$stmt->execute([$productId, $user_id]);
$prod = $stmt->fetch();
if (!$prod) {
    throw new Exception("ไม่พบสินค้าหรือไม่มีสิทธิ์แก้ไข", 404);
}

$successMsg = $errorMsg = "";

// ---------- CSRF ----------
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrfToken = $_SESSION['csrf_token'];

// ---------- SAVE FORM ----------
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['product_name'])) {
    if (!hash_equals($csrfToken, $_POST['csrf_token'] ?? '')) {
        $errorMsg = "CSRF token ไม่ถูกต้อง";
    } else {
        // รับค่าแบบปลอดภัย
        $name   = trim((string)$_POST['product_name']);
        $price  = (float)($_POST['product_price'] ?? 0);
        $cat    = trim((string)($_POST['category'] ?? ''));
        $desc   = trim((string)($_POST['description'] ?? ''));
        $status = in_array(($_POST['status'] ?? ''), ['active','sold','hidden'], true) ? $_POST['status'] : 'active';

        // กันค่าหมวดหมู่ไม่ตรง whitelist
        if ($cat !== '' && !array_key_exists($cat, $categories)) {
            $cat = 'others';
        }

        // เตรียมโฟลเดอร์อัปโหลด
        $uploadDir = rtrim(__DIR__ . '/../../uploads', '/\\') . DIRECTORY_SEPARATOR;
        
        $allowed = ['jpg','jpeg','png','webp','gif'];
        $oldImages = allImagesFromField($prod['product_image']);
        $newImages = [];

        // อัปโหลดรูปใหม่ (multiple)
        if (empty($errorMsg) && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['name'] as $i => $fname) {
                if (!isset($_FILES['images']['tmp_name'][$i])) continue;
                if (!is_uploaded_file($_FILES['images']['tmp_name'][$i])) continue;

                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed, true)) continue;

                if (!empty($_FILES['images']['size'][$i]) && $_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
                    continue;
                }
                
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $realMime = finfo_file($finfo, $_FILES['images']['tmp_name'][$i]);
                finfo_close($finfo);
                
                $allowedMime = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                if (!in_array($realMime, $allowedMime, true)) continue;

                $new = 'p_'.$productId.'_'.bin2hex(random_bytes(4)).'.'.$ext;
                if (@move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir.$new)) {
                    $newImages[] = $new;
                }
            }
        }

        // ถ้าติ๊กแทนที่รูปเดิม → ลบรูปเก่าออก
        if (empty($errorMsg)) {
            if (!empty($_POST['replace_images'])) {
                foreach ($oldImages as $old) {
                    $p = $uploadDir.basename($old);
                    if ($old && is_file($p)) @unlink($p);
                }
                $finalImages = $newImages;
            } else {
                $finalImages = array_values(array_unique(array_merge(array_map('basename', $oldImages), $newImages)));
            }

            $imgField = (count($finalImages) > 1)
                ? json_encode($finalImages, JSON_UNESCAPED_SLASHES)
                : ($finalImages[0] ?? '');
        }

        $loc = trim($_POST['location_text'] ?? '');
        $locName = trim($_POST['location_area'] ?? '');

        // อัปเดตฐานข้อมูล
        if (empty($errorMsg)) {
            $sql = "UPDATE products
                    SET product_name=?, product_price=?, category=?, description=?, product_image=?, location=?, location_name=?, status=?
                    WHERE product_id=? AND user_id=?";
            $upd = $pdo->prepare($sql);
            try {
                $ok = $upd->execute([$name,$price,$cat,$desc,$imgField,$loc,$locName,$status,$productId,$user_id]);
            } catch (Throwable $e) {
                $ok = false;
            }

            if ($ok) {
                if ($status !== $prod['status']) {
                    $statusName = $status === 'sold' ? 'ปิดการขาย' : ($status === 'hidden' ? 'ซ่อน' : 'เปิดขาย');
                    $logDesc = "เปลี่ยนสถานะสินค้า \"{$name}\" เป็น {$statusName}";
                    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, (SELECT username FROM users WHERE user_id=? LIMIT 1), 'toggle_product', ?)";
                    $pdo->prepare($logSql)->execute([$user_id, $user_id, $logDesc]);
                }

                $successMsg = "บันทึกสำเร็จ";
                // sync ตัวแปร $prod
                $prod['product_name']  = $name;
                $prod['product_price'] = $price;
                $prod['category']      = $cat;
                $prod['description']   = $desc;
                $prod['product_image'] = $imgField;
                $prod['location_name'] = $locName;
                $prod['status']        = $status;
                
                redirect($baseUrl . "/product/" . $productId);
            } else {
                $errorMsg = "บันทึกไม่สำเร็จ";
            }
        }
    }
}

// ---------- สร้าง URL อ้างถึง uploads ----------
$scriptDir   = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); // /mix/secondhand_web/php
$projectUrl  = rtrim(dirname($scriptDir), '/');                    // /mix/secondhand_web
$uploadsUrl  = $projectUrl . '/uploads/';                          // /mix/secondhand_web/uploads/
$assetsUrl   = $projectUrl . '/';                                  // /mix/secondhand_web/
