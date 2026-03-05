<?php
session_start();
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// โหลดค่าคงที่ DB / Token ต่างๆ
require_once __DIR__ . "/../../config/database.php";

// PDO is provided by database.php
if (!isset($pdo)) {
    http_response_code(500);
    exit('Database connection error');
}

if (!function_exists('allImagesFromField')) {
    function allImagesFromField(?string $s): array {
        if (!$s) return [];
        $s = trim($s);
        if ($s !== '' && $s[0] === '[') {
            $arr = json_decode($s, true);
            if (is_array($arr)) {
                return array_values(array_filter(array_map(fn($x)=>basename((string)$x), $arr)));
            }
        }
        $parts = preg_split('/[|,;]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts) return array_values(array_filter(array_map(fn($x)=>basename(trim($x)), $parts)));
        return [basename($s)];
    }
}

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
    http_response_code(404);
    exit("ไม่พบสินค้าหรือไม่มีสิทธิ์แก้ไข");
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

        // เตรียมโฟลเดอร์อัปโหลด (อยู่ข้างบนโฟลเดอร์นี้สองระดับ: ../../uploads)
        $uploadDir = rtrim(__DIR__ . '/../../uploads', '/\\') . DIRECTORY_SEPARATOR;
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }
        if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
            $errorMsg = "ไม่สามารถเขียนโฟลเดอร์ uploads ได้";
        }

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

                // จำกัดขนาดไฟล์ (เช่น 5MB)
                if (!empty($_FILES['images']['size'][$i]) && $_FILES['images']['size'][$i] > 5 * 1024 * 1024) {
                    continue;
                }

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
                    $p = $uploadDir.$old;
                    if ($old && is_file($p)) @unlink($p);
                }
                $finalImages = $newImages;
            } else {
                $finalImages = array_values(array_unique(array_merge($oldImages, $newImages)));
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
                error_log("UPDATE products failed: " . $e->getMessage());
            }

            if ($ok) {
                // Log status change if any
                if ($status !== $prod['status']) {
                    $statusName = $status === 'sold' ? 'ปิดการขาย' : ($status === 'hidden' ? 'ซ่อน' : 'เปิดขาย');
                    $desc = "เปลี่ยนสถานะสินค้า \"{$name}\" เป็น {$statusName}";
                    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, (SELECT username FROM users WHERE user_id=? LIMIT 1), 'toggle_product', ?)";
                    $pdo->prepare($logSql)->execute([$user_id, $user_id, $desc]);
                }

                $successMsg = "บันทึกสำเร็จ";
                // sync ตัวแปร $prod ที่แสดงบนหน้า
                $prod['product_name']  = $name;
                $prod['product_price'] = $price;
                $prod['category']      = $cat;
                $prod['description']   = $desc;
                $prod['product_image'] = $imgField;
                $prod['location_name'] = $locName;
                $prod['status']        = $status;
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
