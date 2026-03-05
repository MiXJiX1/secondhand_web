<?php
/* exchange.php — แลกเปลี่ยนสินค้า (แจ้งเตือน + ปุ่มแชท + ยืนยันรับของทั้งสองฝั่ง) */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . "/../../config/database.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

// PDO is provided by database.php

/* ===== USER ===== */
$userId   = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 0;
$username = $_SESSION['username'] ?? null;

/* ===== ตารางแจ้งเตือน / ตารางยืนยันรับของ ===== */
$pdo->exec("
  CREATE TABLE IF NOT EXISTS exchange_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    item_id INT NOT NULL,
    offer_id INT NOT NULL,
    type ENUM('offer_created') NOT NULL DEFAULT 'offer_created',
    message VARCHAR(255) NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX(user_id), INDEX(item_id), INDEX(offer_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
  CREATE TABLE IF NOT EXISTS exchange_receipts (
    item_id INT NOT NULL,
    offer_id INT NOT NULL,
    seller_id INT NOT NULL,
    buyer_id INT NOT NULL,
    seller_confirm TINYINT(1) NOT NULL DEFAULT 0,
    buyer_confirm TINYINT(1) NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY(item_id, offer_id),
    INDEX(seller_id), INDEX(buyer_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
try {
  $cols  = $pdo->query("SHOW COLUMNS FROM exchange_receipts")->fetchAll(PDO::FETCH_ASSOC);
  $have  = array_column($cols, 'Field');
  $alter = [];

  if (!in_array('seller_id',      $have)) $alter[] = "ADD COLUMN seller_id INT NOT NULL AFTER offer_id";
  if (!in_array('buyer_id',       $have)) $alter[] = "ADD COLUMN buyer_id INT NOT NULL AFTER seller_id";
  if (!in_array('seller_confirm', $have)) $alter[] = "ADD COLUMN seller_confirm TINYINT(1) NOT NULL DEFAULT 0";
  if (!in_array('buyer_confirm',  $have)) $alter[] = "ADD COLUMN buyer_confirm TINYINT(1) NOT NULL DEFAULT 0";
  if (!in_array('updated_at',     $have)) $alter[] = "ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP";
  if (!in_array('closed_at',      $have)) $alter[] = "ADD COLUMN closed_at TIMESTAMP NULL DEFAULT NULL";

  if ($alter) {
    $pdo->exec("ALTER TABLE exchange_receipts " . implode(", ", $alter));
  }

  // เพิ่มดัชนีถ้ายังไม่มี
  $idx    = $pdo->query("SHOW INDEX FROM exchange_receipts")->fetchAll(PDO::FETCH_ASSOC);
  $idxSet = array_column($idx, 'Key_name');
  if (!in_array('seller_id', $idxSet)) $pdo->exec("CREATE INDEX seller_id ON exchange_receipts (seller_id)");
  if (!in_array('buyer_id',  $idxSet)) $pdo->exec("CREATE INDEX buyer_id  ON exchange_receipts (buyer_id)");
} catch (Throwable $__) {
  // ปล่อยผ่านเพื่อไม่ให้หน้าแตก หากสิทธิ์ ALTER ไม่มี
}

/* ===== ตารางข้อเสนอ ===== */
$pdo->exec("
  CREATE TABLE IF NOT EXISTS exchange_offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL,
    offer_user_id INT NOT NULL,
    offer_item_id INT NULL DEFAULT NULL,
    offer_text TEXT NOT NULL,
    cash_adjustment DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('pending','accepted','declined') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    responded_at TIMESTAMP NULL DEFAULT NULL,
    INDEX(item_id), INDEX(offer_user_id)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
try {
  $cols  = $pdo->query("SHOW COLUMNS FROM exchange_offers")->fetchAll(PDO::FETCH_ASSOC);
  $have  = array_column($cols, 'Field');
  $alter = [];

  if (!in_array('offer_item_id', $have))   $alter[] = "ADD COLUMN offer_item_id INT NULL DEFAULT NULL AFTER offer_user_id";
  if (!in_array('cash_adjustment', $have)) $alter[] = "ADD COLUMN cash_adjustment DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER offer_text";

  if ($alter) {
    $pdo->exec("ALTER TABLE exchange_offers " . implode(", ", $alter));
  }
} catch (Throwable $__) {
  // Ignore permission errors
}

/* ---------- AJAX แจ้งเตือน ---------- */
if (isset($_GET['ajax'])) {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('X-Content-Type-Options: nosniff');

  $ajax = $_GET['ajax'];
  if (!$userId) {
    echo json_encode($ajax === 'notify_count' ? ['count'=>0] : ['items'=>[]], JSON_UNESCAPED_UNICODE);
    exit;
  }

  if ($ajax === 'notify_count') {
    $st = $pdo->prepare("SELECT COUNT(*) FROM exchange_notifications WHERE user_id=? AND is_read=0");
    $st->execute([$userId]);
    echo json_encode(['count'=>(int)$st->fetchColumn()], JSON_UNESCAPED_UNICODE);
    exit;
  } elseif ($ajax === 'notify_list') {
    $st = $pdo->prepare("
      SELECT notification_id,item_id,offer_id,message,is_read,created_at
      FROM exchange_notifications
      WHERE user_id=?
      ORDER BY is_read ASC, notification_id DESC
      LIMIT 30
    ");
    $st->execute([$userId]);
    echo json_encode(['items'=>$st->fetchAll()], JSON_UNESCAPED_UNICODE);
    exit;
  } elseif ($ajax === 'notify_mark' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $st = $pdo->prepare("UPDATE exchange_notifications SET is_read=1 WHERE user_id=? AND is_read=0");
    $st->execute([$userId]);
    echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
    exit;
  } else {
    http_response_code(400);
    echo json_encode(['ok'=>false,'error'=>'unknown ajax'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf_ex'])) $_SESSION['csrf_ex'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_ex'];

/* ===== CATEGORIES ===== */
$CATS = ['electronics','fashion','furniture','vehicle','gameandtoys','household','sport','music','others'];

/* ===== Helper: รูปแรก ===== */
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

/* ===== FILTER/LIST (New Tabs Logic) ===== */
$tab = $_GET['tab'] ?? 'all'; // all, incoming, outgoing, completed

// Query all exchanges regardless of tab, we will filter in PHP for simplicity 
// given the complex nature of incoming vs outgoing.
$sql = "SELECT 
          e.item_id, e.user_id AS seller_id, e.title, e.want_text, e.status AS item_status, e.images AS item_images, e.description,
          u.username AS seller_username, u.fname AS seller_fname, u.lname AS seller_lname, u.img AS seller_avatar,
          o.offer_id, o.offer_user_id AS buyer_id, o.offer_text, o.offer_item_id, o.cash_adjustment, o.status AS offer_status, o.created_at AS offer_date, o.responded_at AS offer_responded,
          ub.username AS buyer_username, ub.fname AS buyer_fname, ub.lname AS buyer_lname, ub.img AS buyer_avatar,
          ei.title AS offer_item_title, ei.images AS offer_item_images
        FROM exchange_items e
        JOIN users u ON u.user_id = e.user_id
        LEFT JOIN exchange_offers o ON o.item_id = e.item_id
        LEFT JOIN users ub ON ub.user_id = o.offer_user_id
        LEFT JOIN exchange_items ei ON ei.item_id = o.offer_item_id
        ORDER BY e.item_id DESC, o.created_at DESC";

$st = $pdo->prepare($sql);
$st->execute();
$raw_trades = $st->fetchAll();

$trades = [
    'all'       => [],
    'incoming'  => [],
    'outgoing'  => [],
    'completed' => []
];

// Helper to check if an item is already added to avoid duplicates in the "All" tab view for items with no offers
$seen_available_items = [];

foreach ($raw_trades as $row) {
    if (!$row['offer_id']) {
        // This is an item with no offers yet.
        if ($row['item_status'] === 'available' && !isset($seen_available_items[$row['item_id']])) {
            $seen_available_items[$row['item_id']] = true;
            $trade = $row;
            $trade['display_status'] = 'AVAILABLE';
            $trade['partner_name'] = trim($row['seller_fname'] . ' ' . $row['seller_lname']) ?: $row['seller_username'] ?: "Unknown User";
            $trade['my_image'] = null; // No offering yet
            $trade['their_image'] = firstImageFromField($row['item_images']) ?: null;
            $trade['cash_adjustment'] = 0;
            $trade['offer_date'] = $row['offer_date'] ?? date('Y-m-d H:i:s'); // Fallback to now if no offer date
            $trades['all'][] = $trade;
        }
        continue;
    }

    $is_mine = ($row['seller_id'] == $userId); // I am the item owner (seller) -> someone offered me (Incoming)
    $is_my_offer = ($row['buyer_id'] == $userId); // I am the offerer (buyer) -> I offered someone (Outgoing)

    $trade = $row;
    $trade['partner_id'] = $is_mine ? $row['buyer_id'] : $row['seller_id'];
    
    $partner_fname = $is_mine ? $row['buyer_fname'] : $row['seller_fname'];
    $partner_lname = $is_mine ? $row['buyer_lname'] : $row['seller_lname'];
    $partner_username = $is_mine ? $row['buyer_username'] : $row['seller_username'];
    $trade['partner_name'] = trim($partner_fname . ' ' . $partner_lname) ?: $partner_username ?: "Unknown User";
    
    // Determine the images to show
    $my_item_images = $is_mine ? $row['item_images'] : $row['offer_item_images'];
    $their_item_images = $is_mine ? $row['offer_item_images'] : $row['item_images'];
    
    if (!$is_mine && !$is_my_offer) {
        $my_item_images = $row['offer_item_images'];
        $their_item_images = $row['item_images'];
        $trade['partner_name'] = trim($row['seller_fname'] . ' ' . $row['seller_lname']) ?: $row['seller_username'] ?: "Unknown User";
    }
    
    $trade['my_image'] = firstImageFromField($my_item_images) ?: null;
    $trade['their_image'] = firstImageFromField($their_item_images) ?: null;
    
    $trade_status = 'PENDING';
    if ($row['item_status'] === 'swapped' && $row['offer_status'] === 'accepted') {
        $trade_status = 'COMPLETED';
    } elseif ($row['offer_status'] === 'accepted') {
        $trade_status = 'ACCEPTED';
    } elseif ($row['offer_status'] === 'declined' || $row['item_status'] === 'cancelled' || $row['item_status'] === 'swapped') {
        $trade_status = 'CANCELLED';
    }

    $trade['display_status'] = $trade_status;
    
    if ($trade_status !== 'CANCELLED') {
        $trades['all'][] = $trade;
    }

    if ($trade_status === 'COMPLETED') {
        if ($is_mine || $is_my_offer) $trades['completed'][] = $trade;
    } elseif ($is_mine && $trade_status !== 'CANCELLED') {
        $trades['incoming'][] = $trade;
    } elseif ($is_my_offer && $trade_status !== 'CANCELLED') {
        $trades['outgoing'][] = $trade;
    }
}

if (!isset($trades[$tab])) $tab = 'all';
$active_trades = $trades[$tab];

/* ===== ACTIONS ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $userId > 0) {
  if (!isset($_POST['csrf']) || $_POST['csrf'] !== $csrf) {
    die('การตรวจสอบ CSRF ล้มเหลว กรุณารีเฟรชหน้าแล้วลองใหม่');
  }

  $action = $_POST['action'] ?? '';

  /* A) สร้างโพสต์ใหม่ */
  if ($action === 'create') {
    $title        = trim($_POST['title'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $want_text    = trim($_POST['want_text'] ?? '');
    $condition_text = trim($_POST['condition_text'] ?? '');
    $location     = trim($_POST['location'] ?? '');
    $description  = trim($_POST['description'] ?? '');

    if (empty($title) || empty($category) || empty($want_text)) {
      $err = 'กรุณากรอกข้อมูลให้ครบถ้วน';
    } else {
      $image_paths = [];
      if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        foreach ($_FILES['images']['name'] as $key => $name) {
          if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['images']['tmp_name'][$key];
            $ext = pathinfo($name, PATHINFO_EXTENSION);
            
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $realMime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            
            $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (in_array($realMime, $allowed, true)) {
                $new_name = uniqid('img_') . '.' . $ext;
                $upload_dir = __DIR__ . '/../../uploads/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
                if (move_uploaded_file($tmp_name, $upload_dir . $new_name)) {
                  $image_paths[] = $new_name;
                }
            }
          }
        }
      }
      $images_json = json_encode($image_paths);

      $st = $pdo->prepare("INSERT INTO exchange_items (user_id, title, category, want_text, condition_text, location, description, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
      $st->execute([$userId, $title, $category, $want_text, $condition_text, $location, $description, $images_json]);
      header("Location: exchange.php?ok=1"); exit;
    }
  }

  /* B) เสนอแลก */
  if ($action === 'offer') {
    $item_id   = (int)($_POST['item_id'] ?? 0);
    $offer_text = trim($_POST['offer_text'] ?? '');

    $q = $pdo->prepare("SELECT user_id FROM exchange_items WHERE item_id=? LIMIT 1");
    $q->execute([$item_id]);
    $item = $q->fetch();

    if (!$item) { $err = 'ไม่พบรายการที่ต้องการเสนอแลก'; }
    elseif ((int)$item['user_id'] === $userId) { $err = 'คุณไม่สามารถเสนอแลกสินค้าของตัวเองได้'; }
    elseif (empty($offer_text)) { $err = 'กรุณากรอกข้อเสนอ'; }
    else {
      $st = $pdo->prepare("INSERT INTO exchange_offers (item_id, offer_user_id, offer_text) VALUES (?, ?, ?)");
      $st->execute([$item_id, $userId, $offer_text]);

      /* แจ้งเตือนเจ้าของโพสต์ */
      $offerId = (int)$pdo->lastInsertId();
      $display = $username ?: ("ผู้ใช้ #".$userId);
      if (function_exists('mb_strimwidth')) {
        $snippet = mb_strimwidth($offer_text, 0, 80, '…', 'UTF-8');
      } else {
        $snippet = (strlen($offer_text)>80)?(substr($offer_text,0,77).'...'):$offer_text;
      }
      $msg = $display.' ส่งคำขอแลก: '.$snippet;
      $nt = $pdo->prepare("INSERT INTO exchange_notifications (user_id,item_id,offer_id,type,message) VALUES (?,?,?,?,?)");
      $nt->execute([(int)$item['user_id'], $item_id, $offerId, 'offer_created', $msg]);

      header("Location: exchange.php?offer_ok=1#item-$item_id"); exit;
    }
  }

  /* C) อัปเดตสถานะโพสต์ / สร้างห้องแชทเมื่อยอมรับ */
  if ($action === 'update_status' && $userId > 0) {
    $item_id  = (int)($_POST['item_id'] ?? 0);
    $status   = trim($_POST['status'] ?? '');
    $offer_id = isset($_POST['offer_id']) ? (int)$_POST['offer_id'] : null;

    $q = $pdo->prepare("SELECT item_id,user_id FROM exchange_items WHERE item_id=? LIMIT 1");
    $q->execute([$item_id]);
    $it = $q->fetch();
    if (!$it || (int)$it['user_id'] !== $userId) { $err='คุณไม่มีสิทธิ์จัดการรายการนี้'; }
    else {
      if ($status==='swapped') {
        $pdo->prepare("UPDATE exchange_items SET status='swapped' WHERE item_id=?")->execute([$item_id]);
        if ($offer_id) {
          /* อัปเดตข้อเสนอ */
          $pdo->prepare("UPDATE exchange_offers SET status='accepted', responded_at=NOW() WHERE offer_id=?")->execute([$offer_id]);
          $pdo->prepare("UPDATE exchange_offers SET status='declined', responded_at=NOW() WHERE item_id=? AND offer_id<>? AND status='pending'")
              ->execute([$item_id,$offer_id]);

          /* สร้างห้องแชท + แถวใน exchange_receipts */
          $of = $pdo->prepare("
  SELECT o.offer_user_id, i.user_id AS seller_id
  FROM exchange_offers o
  JOIN exchange_items  i ON i.item_id = o.item_id
  WHERE o.offer_id=? LIMIT 1
");
          $of->execute([$offer_id]);
          if ($row = $of->fetch()) {
            $buyerId  = (int)$row['offer_user_id']; // คนที่ขอแลก
            $sellerId = (int)$row['seller_id'];     // เจ้าของโพสต์
            $reqId    = "EXC-{$item_id}-{$offer_id}";

            $pdo->exec("
              CREATE TABLE IF NOT EXISTS chat_requests (
                request_id VARCHAR(64) PRIMARY KEY,
                buyer_id INT NOT NULL,
                seller_id INT NOT NULL,
                product_id INT NOT NULL DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            $ins = $pdo->prepare("INSERT IGNORE INTO chat_requests (request_id,buyer_id,seller_id,product_id) VALUES (?,?,?,0)");
            $ins->execute([$reqId, $buyerId, $sellerId]);

            $pdo->exec("
              CREATE TABLE IF NOT EXISTS messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                request_id VARCHAR(64) NOT NULL,
                product_id INT NOT NULL DEFAULT 0,
                sender_id INT NOT NULL,
                receiver_id INT NULL,
                message MEDIUMTEXT,
                is_read TINYINT(1) NULL,
                sent_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX(request_id)
              ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
            ");
            $sys = $pdo->prepare("INSERT INTO messages (request_id,product_id,sender_id,message) VALUES (?,?,?,?)");
            $sys->execute([$reqId, 0, $sellerId, "[SYS] ผู้ขายยอมรับข้อเสนอแล้ว เริ่มแชทตกลงรายละเอียดได้เลย"]);

            /* แถวสถานะรับของ */
            $pdo->prepare("INSERT IGNORE INTO exchange_receipts (item_id,offer_id,seller_id,buyer_id) VALUES (?,?,?,?)")
                ->execute([$item_id,$offer_id,$sellerId,$buyerId]);
          }
        }
      } elseif (in_array($status,['cancelled','pending','available'],true)) {
        $pdo->prepare("UPDATE exchange_items SET status=? WHERE item_id=?")->execute([$status,$item_id]);
      }
      header("Location: exchange.php?upd=1#item-$item_id"); exit;
    }
  }

  /* D) ยืนยันว่า “ฉันได้รับสินค้าแล้ว” (ทั้งสองฝั่ง) */
  if ($action === 'confirm_received' && $userId > 0) {
    $item_id  = (int)($_POST['item_id'] ?? 0);
    $offer_id = (int)($_POST['offer_id'] ?? 0);

    $of = $pdo->prepare("
  SELECT o.offer_user_id, i.user_id AS seller_id
  FROM exchange_offers o
  JOIN exchange_items  i ON i.item_id = o.item_id
  WHERE o.offer_id=? AND o.item_id=? LIMIT 1
");
    $of->execute([$offer_id,$item_id]);
    if ($ox = $of->fetch()) {
      $sellerId = (int)$ox['seller_id'];
      $buyerId  = (int)$ox['offer_user_id'];

      $pdo->prepare("INSERT IGNORE INTO exchange_receipts (item_id,offer_id,seller_id,buyer_id) VALUES (?,?,?,?)")
          ->execute([$item_id,$offer_id,$sellerId,$buyerId]);

      if ($userId === $sellerId) {
        $pdo->prepare("UPDATE exchange_receipts SET seller_confirm=1 WHERE item_id=? AND offer_id=?")->execute([$item_id,$offer_id]);
      } elseif ($userId === $buyerId) {
        $pdo->prepare("UPDATE exchange_receipts SET buyer_confirm=1 WHERE item_id=? AND offer_id=?")->execute([$item_id,$offer_id]);
      }

      /* ถ้าครบทั้งสองฝั่งแล้ว — ปิดโพสต์ให้เรียบร้อย */
      $st = $pdo->prepare("SELECT seller_confirm,buyer_confirm FROM exchange_receipts WHERE item_id=? AND offer_id=?");
      $st->execute([$item_id,$offer_id]);
      $rc = $st->fetch();
      if ($rc && (int)$rc['seller_confirm']===1 && (int)$rc['buyer_confirm']===1) {
        $pdo->prepare("UPDATE exchange_receipts SET closed_at=NOW() WHERE item_id=? AND offer_id=?")->execute([$item_id,$offer_id]);
        $pdo->prepare("UPDATE exchange_items SET status='swapped' WHERE item_id=?")->execute([$item_id]);

        $reqId = "EXC-{$item_id}-{$offer_id}";
        $msgOk = "[SYS] ทั้งสองฝ่ายยืนยันได้รับของแล้ว โพสต์ปิดการแลกเรียบร้อย ✅";
        $pdo->prepare("INSERT INTO messages (request_id,product_id,sender_id,message) VALUES (?,?,?,?)")
            ->execute([$reqId,0,$sellerId,$msgOk]);
      }
    }
    header("Location: exchange.php?upd=1#item-$item_id"); exit;
  }
}
