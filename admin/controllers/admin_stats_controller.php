<?php
/* ====== DB: ใช้ config กลาง ====== */
require_once __DIR__ . "/../../config/database.php";

/* ====== ตรวจสิทธิ์แอดมิน ====== */
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: " . ($baseUrl ?? '') . "/login");
  exit();
}

/* ====== helper: ดึงข้อมูลแบบปลอดภัย (ตารางอาจไม่มี) ====== */
if(!function_exists('safeQuery')){
function safeQuery(PDO $pdo, string $sql, array $params = []): array {
  try {
    $st = $pdo->prepare($sql);
    $st->execute($params);
    return $st->fetchAll();
  } catch (Throwable $e) {
    return [];
  }
}
}

/* ====== กำหนดช่วงวันที่ (ล่าสุด 30 วัน) ====== */
$end   = new DateTime('today');
$start = (clone $end)->modify('-29 days'); // รวมวันนี้ = 30 จุด
$labels = [];
$cursor = clone $start;
while ($cursor <= $end) {
  $labels[] = $cursor->format('Y-m-d');
  $cursor->modify('+1 day');
}

/* ====== 1) ผู้ใช้ใหม่ต่อวัน (users.created_at) ====== */
$rowsUsers = safeQuery(
  $pdo,
  "SELECT DATE(created_at) d, COUNT(*) c
   FROM users
   WHERE created_at BETWEEN :s AND :e
   GROUP BY DATE(created_at)
   ORDER BY d",
  [':s'=>$start->format('Y-m-d 00:00:00'), ':e'=>$end->format('Y-m-d 23:59:59')]
);
$usersDaily = array_fill_keys($labels, 0);
foreach ($rowsUsers as $r) { $usersDaily[$r['d']] = (int)$r['c']; }

/* ====== 2) ยอดเติม/ถอน ต่อวัน ====== */
$rowsTopup = safeQuery(
  $pdo,
  "SELECT DATE(created_at) d, SUM(amount) s
   FROM credit_topups
   WHERE created_at BETWEEN :s AND :e
     AND status IN ('approved','completed','success','paid')
   GROUP BY DATE(created_at)
   ORDER BY d",
  [':s'=>$start->format('Y-m-d 00:00:00'), ':e'=>$end->format('Y-m-d 23:59:59')]
);

$rowsWithdraw = safeQuery(
  $pdo,
  "SELECT DATE(COALESCE(processed_at, created_at)) d, SUM(amount) s
   FROM credit_withdrawals
   WHERE COALESCE(processed_at, created_at) BETWEEN :s AND :e
     AND status IN ('approved','paid')
   GROUP BY DATE(COALESCE(processed_at, created_at))
   ORDER BY d",
  [':s'=>$start->format('Y-m-d 00:00:00'), ':e'=>$end->format('Y-m-d 23:59:59')]
);

$topupDaily    = array_fill_keys($labels, 0.0);
$withdrawDaily = array_fill_keys($labels, 0.0);
foreach ($rowsTopup as $r)    { $topupDaily[$r['d']]    = (float)$r['s']; }
foreach ($rowsWithdraw as $r) { $withdrawDaily[$r['d']] = (float)$r['s']; }

/* ====== 3) ยอดรวม/ตัวเลขสรุป ====== */
$totUsers    = (int)   (safeQuery($pdo, "SELECT COUNT(*) n FROM users")[0]['n'] ?? 0);
$totProducts = (int)   (safeQuery($pdo, "SELECT COUNT(*) n FROM products")[0]['n'] ?? 0);
$totTopup    = (float) (safeQuery($pdo, "SELECT SUM(amount) n FROM credit_topups WHERE status IN ('approved','completed','success','paid')")[0]['n'] ?? 0);
$totWithdraw = (float) (safeQuery($pdo, "SELECT SUM(amount) n FROM credit_withdrawals WHERE status='paid'")[0]['n'] ?? 0);

/* ====== 4) สัดส่วนสถานะถอน (โดนัท) ====== */
$wdStatusRows = safeQuery($pdo, "SELECT status, COUNT(*) c FROM credit_withdrawals GROUP BY status");
$wdStatusMap = ['requested'=>0,'approved'=>0,'paid'=>0,'rejected'=>0];
foreach ($wdStatusRows as $r) {
  $k = (string)$r['status'];
  if (!isset($wdStatusMap[$k])) $wdStatusMap[$k] = 0;
  $wdStatusMap[$k] += (int)$r['c'];
}
