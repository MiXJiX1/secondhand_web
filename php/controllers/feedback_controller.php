<?php
/* feedback.php — ให้คะแนน/รายงานผู้ใช้ (ดึงผู้ขายจากคำสั่งซื้อของเรา) */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

if (!isLoggedIn()) {
  redirect($baseUrl . "/login");
}

/* ===== DB (ใช้ config กลาง) ===== */
require_once __DIR__ . "/../../config/database.php";

// PDO is provided by database.php ($pdo)

$userId = (int)$_SESSION['user_id'];
$tab    = ($_GET['tab'] ?? 'rate') === 'report' ? 'report' : 'rate';

/* ---------- สร้างตารางถ้ายังไม่มี ---------- */
$pdo->exec("
CREATE TABLE IF NOT EXISTS user_ratings(
  rating_id INT AUTO_INCREMENT PRIMARY KEY,
  rater_id  INT NOT NULL,
  rated_user_id INT NOT NULL,
  order_id  INT NULL,
  product_id INT NULL,
  score     TINYINT NOT NULL CHECK (score BETWEEN 1 AND 5),
  comment   TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uniq_once (rater_id, rated_user_id, order_id),
  INDEX(rated_user_id),
  INDEX(rater_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$pdo->exec("
CREATE TABLE IF NOT EXISTS abuse_reports(
  report_id INT AUTO_INCREMENT PRIMARY KEY,
  reporter_id INT NOT NULL,
  reported_user_id INT NOT NULL,
  reason ENUM('fraud','fake','offensive','spam','other') NOT NULL DEFAULT 'other',
  details TEXT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  status ENUM('open','reviewing','done') NOT NULL DEFAULT 'open',
  INDEX(reporter_id), INDEX(reported_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
/* ---------- ตรวจคอลัมน์ใน abuse_reports ---------- */
if (!function_exists('ar_has')) {
function ar_has(PDO $pdo, string $col): bool {
  $s = $pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS
                      WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME='abuse_reports' AND COLUMN_NAME=? LIMIT 1");
  $s->execute([$col]);
  return (bool)$s->fetchColumn();
}
}
$AR_HAS_REPORTED = ar_has($pdo,'reported_user_id');                 // สคีมาแบบเก่า
$AR_HAS_TARGET   = ar_has($pdo,'target_id') && ar_has($pdo,'target_kind'); // สคีมาแบบใหม่


/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

/* ---------- ส่งฟอร์ม ---------- */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {

  /* ให้คะแนน */
  if ($_POST['action'] === 'rate') {
    $rated = (int)($_POST['rated_user_id'] ?? 0);
    $order = (int)($_POST['order_id'] ?? 0);
    $prod  = (int)($_POST['product_id'] ?? 0);
    $score = (int)($_POST['score'] ?? 0);
    $cmt   = trim($_POST['comment'] ?? '');

    if ($rated <= 0 || $order <= 0 || $score < 1 || $score > 5) {
      $msg = 'ข้อมูลไม่ครบ';
    } else {
      // ตรวจว่า order นี้เป็นของเรา และผู้ขายตรงกับ $rated
      $q = $pdo->prepare("
        SELECT o.id, p.user_id AS seller_id
        FROM orders o
        JOIN products p ON p.product_id = o.product_id
        WHERE o.id = ? AND o.user_id = ? AND o.status IN ('paid','released','completed')
        LIMIT 1
      ");
      $q->execute([$order, $userId]);
      $ok = $q->fetch();

      if (!$ok || (int)$ok['seller_id'] !== $rated) {
        $msg = 'ไม่พบคำสั่งซื้อที่ตรงกับผู้ขาย';
      } else {
        try {
          $ins = $pdo->prepare("INSERT INTO user_ratings (rater_id, rated_user_id, order_id, product_id, score, comment) VALUES (?, (?, ?, ?, ?, ?)");
          $ins = $pdo->prepare("INSERT INTO user_ratings (rater_id, rated_user_id, order_id, product_id, score, comment) VALUES (?, ?, ?, ?, ?, ?)");
          $ins->execute([$userId, $rated, $order, $prod, $score, $cmt]);
          $msg = 'ให้คะแนนสำเร็จ ✅';
        } catch (Throwable $e) {
          $msg = 'คุณให้คะแนนรายการนี้ไปแล้ว';
        }
      }
    }
  }

/* รายงานผู้ใช้ */
if ($_POST['action'] === 'report') {
  $reported_manual = trim($_POST['reported_user_id_manual'] ?? '');
  if ($reported_manual !== '' && ctype_digit($reported_manual)) {
    $_POST['reported_user_id'] = $reported_manual;
  }
  $reported = (int)($_POST['reported_user_id'] ?? 0);
  $reason   = $_POST['reason'] ?? 'other';
  $details  = trim($_POST['details'] ?? '');

  if (!$reported || !in_array($reason, ['fraud','fake','offensive','spam','other'], true)) {
    $msg = 'ข้อมูลรายงานไม่ครบ';
  } else {
    if ($AR_HAS_REPORTED) {
      // สคีมาเก่า: มี reported_user_id
      $ins = $pdo->prepare("INSERT INTO abuse_reports (reporter_id, reported_user_id, reason, details)
                            VALUES (?, ?, ?, ?)");
      $ins->execute([$userId, $reported, $reason, $details]);
    } elseif ($AR_HAS_TARGET) {
      // สคีมาใหม่: ใช้ target_kind + target_id (เรารายงาน "user")
      $ins = $pdo->prepare("INSERT INTO abuse_reports (reporter_id, target_kind, target_id, reason, details)
                            VALUES (?, 'user', ?, ?, ?)");
      $ins->execute([$userId, (string)$reported, $reason, $details]);
    } else {
      $msg = 'ตาราง abuse_reports ไม่มีคอลัมน์ที่รองรับ';
    }
    if (!$msg) {
        $msg = 'ส่งรายงานแล้ว ✅';
        $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, (SELECT username FROM users WHERE user_id=? LIMIT 1), 'report', ?)";
        $logStmt = $pdo->prepare($logSql);
        $desc = "ส่งข้อความรายงานปัญหา (เหตุผล: {$reason})";
        $logStmt->execute([$userId, $userId, $desc]);
    }
  }
}
}

/* ---------- ดึง “ผู้ขายที่เราเคยซื้อ” ---------- */
$buyersSellers = [];  // รายการผู้ขาย + ออร์เดอร์ของเรา
$st = $pdo->prepare("
  SELECT 
    o.id AS order_id,
    o.product_id,
    u.user_id AS seller_id,
    CONCAT(COALESCE(u.fname,''),' ',COALESCE(u.lname,'')) AS seller_name,
    p.product_name
  FROM orders o
  JOIN products p ON p.product_id = o.product_id
  JOIN users u ON u.user_id = p.user_id
  WHERE o.user_id = ? AND o.status IN ('paid','released','completed')
  ORDER BY o.id DESC
");
$st->execute([$userId]);
while ($r = $st->fetch()) {
  $key = (int)$r['seller_id'];
  if (!isset($buyersSellers[$key])) {
    $buyersSellers[$key] = [
      'seller_id'   => $key,
      'seller_name' => $r['seller_name'] ?: ('ผู้ใช้ #' . $key),
      'orders'      => []
    ];
  }
  $buyersSellers[$key]['orders'][] = [
    'order_id'     => (int)$r['order_id'],
    'product_id'   => (int)$r['product_id'],
    'product_name' => $r['product_name']
  ];
}

/* ---------- ค่าเฉลี่ยที่ผู้ใช้รายนั้นได้รับ ---------- */
$avgRatingByUser = [];
if ($buyersSellers) {
  $ids = implode(',', array_fill(0, count($buyersSellers), '?'));
  $q = $pdo->prepare("SELECT rated_user_id, AVG(score) AS avg_score, COUNT(*) AS cnt FROM user_ratings WHERE rated_user_id IN ($ids) GROUP BY rated_user_id");
  $q->execute(array_keys($buyersSellers));
  while ($a = $q->fetch()) {
    $avgRatingByUser[(int)$a['rated_user_id']] = [
      'avg' => round((float)$a['avg_score'], 2),
      'cnt' => (int)$a['cnt']
    ];
  }
}

/* ---------- รายงานที่เราเคยส่ง ---------- */
if ($AR_HAS_REPORTED) {
  // สคีมาเก่า
  $st = $pdo->prepare("
    SELECT r.*,
           CONCAT(COALESCE(u.fname,''),' ',COALESCE(u.lname,'')) AS reported_name
    FROM abuse_reports r
    JOIN users u ON u.user_id = r.reported_user_id
    WHERE r.reporter_id = ?
    ORDER BY r.report_id DESC
    LIMIT 50
  ");
  $st->execute([$userId]);
  $myReports = $st->fetchAll();
} else {
  // สคีมาใหม่ (target_kind/target_id) — join เฉพาะเมื่อเป็น user
  $st = $pdo->prepare("
    SELECT r.*,
      CASE
        WHEN r.target_kind='user' THEN CONCAT(COALESCE(u.fname,''),' ',COALESCE(u.lname,''))
        ELSE CONCAT(r.target_kind, ':', r.target_id)
      END AS reported_name
    FROM abuse_reports r
    LEFT JOIN users u
      ON (r.target_kind='user' AND u.user_id = r.target_id)
    WHERE r.reporter_id = ?
    ORDER BY r.report_id DESC
    LIMIT 50
  ");
  $st->execute([$userId]);
  $myReports = $st->fetchAll();
}
$myRatings = $pdo->prepare("
  SELECT a.*, CONCAT(COALESCE(u.fname,''),' ',COALESCE(u.lname,'')) AS rated_name
  FROM user_ratings a
  JOIN users u ON u.user_id = a.rated_user_id
  WHERE a.rater_id = ? ORDER BY a.rating_id DESC LIMIT 50
");
$myRatings->execute([$userId]);
$myRatings = $myRatings->fetchAll();
