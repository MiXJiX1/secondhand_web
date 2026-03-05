<?php
/* admin_user_ratings.php — รายงาน/จัดการเรตติ้งจากตาราง user_ratings */
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);
session_start();

/* ===== ตรวจสิทธิ์แอดมิน ===== */
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); die('Forbidden'); }

/* ===== DB: ใช้ config กลาง ===== */
require_once __DIR__ . "/../../config/database.php";

/* ===== CSRF ===== */
if (empty($_SESSION['csrf_admin'])) $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_admin'];

/* ===== รับพารามิเตอร์กรอง/เพจ ===== */
$q        = trim($_GET['q'] ?? '');            // ค้นหาชื่อผู้ใช้/สินค้า/คอมเมนต์
$score    = trim($_GET['score'] ?? '');        // =1..5 หรือว่าง
$ratedId  = (int)($_GET['rated_id'] ?? 0);     // กรองผู้ถูกให้คะแนน
$raterId  = (int)($_GET['rater_id'] ?? 0);     // กรองผู้ให้คะแนน
$d1       = trim($_GET['d1'] ?? '');           // YYYY-MM-DD
$d2       = trim($_GET['d2'] ?? '');           // YYYY-MM-DD
$page     = max(1, (int)($_GET['page'] ?? 1));
$per      = min(100, max(10, (int)($_GET['per'] ?? 20)));
$off      = ($page-1)*$per;

/* ===== helper ===== */
if(!function_exists('safeQuery')){
function safeQuery(PDO $pdo, string $sql, array $params = []): array {
  try { $st=$pdo->prepare($sql); $st->execute($params); return $st->fetchAll(); }
  catch(Throwable $e){ return []; }
}
}
if(!function_exists('h')){ function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if(!function_exists('stars')){
function stars($n){
  $n = max(0,min(5,(int)$n));
  $full = str_repeat('★',$n); $empty = str_repeat('☆',5-$n);
  return "<span class='stars' aria-label='{$n} / 5'>{$full}{$empty}</span>";
}
}

/* ===== Build WHERE ===== */
$where = ["1=1"]; $P = [];
if ($q !== '') {
  $where[] = "(ur.comment LIKE ? OR pr.product_name LIKE ? OR ru.username LIKE ? OR rru.username LIKE ?)";
  array_push($P, "%$q%","%$q%","%$q%","%$q%");
}
if ($score !== '' && ctype_digit($score) && (int)$score>=1 && (int)$score<=5) {
  $where[] = "ur.score = ?"; $P[] = (int)$score;
}
if ($ratedId > 0) { $where[] = "ur.rated_user_id = ?"; $P[] = $ratedId; }
if ($raterId > 0) { $where[] = "ur.rater_id = ?";      $P[] = $raterId; }
if ($d1 !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$d1)) { $where[] = "ur.created_at >= ?"; $P[] = $d1." 00:00:00"; }
if ($d2 !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$d2)) { $where[] = "ur.created_at <= ?"; $P[] = $d2." 23:59:59"; }
$W = implode(' AND ', $where);

/* ===== จำนวนรวมเพื่อเพจจิเนชัน ===== */
$sqlCount = "SELECT COUNT(*) 
             FROM user_ratings ur
             LEFT JOIN users ru  ON ru.user_id  = ur.rater_id
             LEFT JOIN users rru ON rru.user_id = ur.rated_user_id
             LEFT JOIN products pr ON pr.product_id = ur.product_id
             WHERE $W";
$st = $pdo->prepare($sqlCount); $st->execute($P); $total = (int)$st->fetchColumn();
$pages = max(1, (int)ceil($total/$per));

/* ===== ดึงรายการหลัก ===== */
/* หมายเหตุ: cast $per/$off ให้เป็น int ก่อนฝัง ป้องกัน SQL injection */
$per_i = (int)$per; $off_i = (int)$off;
$sql = "SELECT ur.*, 
               ru.username  AS rater_username,
               rru.username AS rated_username,
               pr.product_name
        FROM user_ratings ur
        LEFT JOIN users ru  ON ru.user_id  = ur.rater_id
        LEFT JOIN users rru ON rru.user_id = ur.rated_user_id
        LEFT JOIN products pr ON pr.product_id = ur.product_id
        WHERE $W
        ORDER BY ur.rating_id DESC
        LIMIT $per_i OFFSET $off_i";
$st = $pdo->prepare($sql); $st->execute($P); $rows = $st->fetchAll();

/* ===== สรุปค่าเฉลี่ย & distribution ===== */
$avgRow = $pdo->query("SELECT ROUND(AVG(score),2) AS avg_score, COUNT(*) AS cnt FROM user_ratings")->fetch() ?: ['avg_score'=>0,'cnt'=>0];
$dist = $pdo->query("SELECT score, COUNT(*) c FROM user_ratings GROUP BY score")->fetchAll();
$distMap = [1=>0,2=>0,3=>0,4=>0,5=>0]; foreach($dist as $d){ $distMap[(int)$d['score']] = (int)$d['c']; }

/* ===== รายชื่อผู้ใช้สำหรับตัวกรอง ===== */
$usersList = $pdo->query("SELECT user_id, username FROM users ORDER BY user_id DESC LIMIT 200")->fetchAll();
