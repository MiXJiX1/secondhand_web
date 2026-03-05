<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../php/login.php"); exit();
}

/* --- helper: scalar --- */
if(!function_exists('scalar')){
function scalar(mysqli $conn, string $sql, $default = 0) {
  $res = $conn->query($sql);
  if (!$res) return $default;
  $row = $res->fetch_row();
  return $row ? $row[0] : $default;
}
}
/* --- helper: table exists --- */
if(!function_exists('table_exists')){
function table_exists(mysqli $conn, string $name): bool {
  $name = $conn->real_escape_string($name);
  $res  = $conn->query("SHOW TABLES LIKE '{$name}'");
  return $res && $res->num_rows > 0;
}
}

/* --- summary numbers --- */
$totalUsers     = (int)scalar($conn, "SELECT COUNT(*) FROM users", 0);
$totalProducts  = (int)scalar($conn, "SELECT COUNT(*) FROM products", 0);
$totalOrders    = table_exists($conn,'orders') ? (int)scalar($conn, "SELECT COUNT(*) FROM orders", 0) : 0;
$paidOrders     = table_exists($conn,'orders') ? (int)scalar($conn, "SELECT COUNT(*) FROM orders WHERE status='paid'", 0) : 0;
$sumPaid        = table_exists($conn,'orders') ? (float)scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM orders WHERE status='paid'", 0) : 0;
$openReports    = table_exists($conn,'abuse_reports') ? (int)scalar($conn, "SELECT COUNT(*) FROM abuse_reports WHERE status IN ('open','reviewing')", 0) : 0;

/* --- activity feed (safe union) --- */
$parts = [];

/* สมัครสมาชิกใหม่ */
$parts[] = "
SELECT u.username,'user_new' AS type,u.created_at AS ts,'สมัครสมาชิกใหม่' AS action
FROM users u WHERE u.created_at IS NOT NULL
";

/* สินค้าใหม่ */
if (table_exists($conn,'products')) {
  $parts[] = "
  SELECT u.username,'product_add' AS type,p.created_at AS ts,
         CONCAT('เพิ่มสินค้า #',p.product_id,' • ',p.product_name) AS action
  FROM products p JOIN users u ON u.user_id = p.user_id
  WHERE p.created_at IS NOT NULL
  ";
}

/* เติมเงิน */
if (table_exists($conn,'credit_topups')) {
  $parts[] = "
  SELECT u.username,'topup' AS type,t.created_at AS ts,
         CONCAT('เติมเงิน ', FORMAT(t.amount,2), ' บาท (', t.status, ')') AS action
  FROM credit_topups t JOIN users u ON u.user_id = t.user_id
  ";
}

/* ถอนเงิน */
if (table_exists($conn,'credit_withdrawals')) {
  $parts[] = "
  SELECT u.username,'withdraw' AS type,COALESCE(w.processed_at,w.created_at) AS ts,
         CONCAT('ถอนเงิน ', FORMAT(w.amount,2), ' บาท (', w.status, ')') AS action
  FROM credit_withdrawals w JOIN users u ON u.user_id = w.user_id
  ";
}

/* ออเดอร์ */
if (table_exists($conn,'orders')) {
  $parts[] = "
  SELECT u.username,'order' AS type,COALESCE(o.paid_at,o.released_at,o.created_at) AS ts,
         CONCAT('ออเดอร์ #', o.id, ' • ', FORMAT(IFNULL(o.amount,0),2), ' บาท (', o.status, ')') AS action
  FROM orders o JOIN users u ON u.user_id = o.user_id
  ";
}

/* รายงานผู้ใช้ */
if (table_exists($conn,'abuse_reports')) {
  $parts[] = "
  SELECT u.username,'report' AS type,r.created_at AS ts,
         CONCAT('รายงาน/ร้องเรียน #', r.report_id, ' (', r.status, ')') AS action
  FROM abuse_reports r JOIN users u ON u.user_id = r.reporter_id
  ";
}

/* ติดต่อแอดมิน */
if (table_exists($conn,'support_tickets')) {
  $parts[] = "
  SELECT u.username,'ticket' AS type,st.created_at AS ts,
         CONCAT('ติดต่อแอดมิน: ',
                COALESCE(NULLIF(st.subject,''), st.category, 'ไม่ระบุหัวข้อ'),
                CASE WHEN st.status IS NOT NULL THEN CONCAT(' (', st.status, ')') ELSE '' END
         ) AS action
  FROM support_tickets st JOIN users u ON u.user_id = st.user_id
  ";
}

/* คำร้องปลดแบน */
if (table_exists($conn,'ban_appeals')) {
  $parts[] = "
  SELECT u.username,'appeal' AS type,a.created_at AS ts,
         CONCAT('คำร้องปลดแบน #', a.appeal_id, ' (', a.status, ')') AS action
  FROM ban_appeals a JOIN users u ON u.user_id = a.user_id
  ";
}

/* บันทึกกิจกรรมเพิ่มเติม (login, rate_seller) */
if (table_exists($conn, 'activity_logs')) {
  $parts[] = "
  SELECT username, action_type AS type, created_at AS ts, description AS action
  FROM activity_logs
  ";
}

$feedSql = $parts ? "
  SELECT * FROM (".implode("\nUNION ALL\n", $parts).") feed
  WHERE ts IS NOT NULL
  ORDER BY ts DESC
  LIMIT 20
" : "SELECT NULL AS username, NULL AS type, NULL AS ts, NULL AS action LIMIT 0";

// Count pending bank verifications for notification badge
$stBankCount = $pdo->query("SELECT COUNT(*) as count FROM users WHERE bank_account IS NOT NULL AND bank_verified = 0");
$pendingBankCount = (int)($stBankCount->fetch()['count'] ?? 0);

$resFeed = $conn->query($feedSql); // using mysqli query just in case
$resFeedPdo = $pdo->query($feedSql);
$feed = $resFeedPdo->fetchAll();
