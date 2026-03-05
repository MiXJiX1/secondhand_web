<?php
// admin/support_tickets.php — จัดการคำขอจากผู้ใช้ (UI สไตล์เดียวกับหน้า Users)
declare(strict_types=1);
session_start();

require_once __DIR__ . '/../../config/database.php';

// ตรวจสิทธิ์แอดมิน
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../php/login.php");
  exit();
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$csrf = $_SESSION['csrf_token'];

/* ===== Action ===== */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
  $ticketId = (int)($_POST['ticket_id'] ?? 0);
  if ($_POST['action'] === 'set_status' && $ticketId > 0) {
    $status = $_POST['status'] ?? 'open';
    if (!in_array($status, ['open','in_progress','resolved','closed'], true)) $status = 'open';
    $q = $pdo->prepare("UPDATE support_tickets SET status=?, updated_at=NOW() WHERE ticket_id=?");
    $q->execute([$status, $ticketId]);
    $msg = "อัปเดตสถานะเรียบร้อย";
  }
}

/* ===== ดึงรายการทั้งหมด ===== */
$st = $pdo->query("
  SELECT t.ticket_id, t.ref_code, t.subject, t.category, t.message, t.status, t.created_at, t.updated_at,
         CONCAT(u.fname,' ',u.lname) AS uname, u.email, u.username, t.user_id
  FROM support_tickets t
  JOIN users u ON u.user_id = t.user_id
  ORDER BY t.ticket_id DESC
  LIMIT 200
");
$tickets = $st->fetchAll();

if(!function_exists('h')){ function h($s){return htmlspecialchars((string)$s,ENT_QUOTES,'UTF-8');} }
$allCount   = count($tickets);
$statuses   = ['open'=>'open', 'in_progress'=>'in_progress', 'resolved'=>'resolved', 'closed'=>'closed'];
$categories = array_values(array_unique(array_filter(array_map(fn($r)=>$r['category'], $tickets))));
sort($categories, SORT_NATURAL);
