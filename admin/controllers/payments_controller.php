<?php
session_start();

/* ====== ตรวจสิทธิ์แอดมิน ====== */
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

/* ====== DB: ใช้ config กลาง ====== */
require_once __DIR__ . "/../../config/database.php";

/* ====== CSRF ====== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

/* ====== กำหนดแท็บ ====== */
$tab = (isset($_GET['type']) && in_array($_GET['type'], ['topup','withdraw'], true)) ? $_GET['type'] : 'topup';

/* ====== Query ====== */
if ($tab === 'topup') {
  $stmt = $pdo->query("
    SELECT t.topup_id, t.user_id, t.amount, t.method, t.reference_no, t.slip_path,
           t.status, t.created_at, t.approved_at,
           u.username, u.credit_balance
    FROM credit_topups t
    LEFT JOIN users u ON u.user_id = t.user_id
    ORDER BY (t.status='pending') DESC, t.topup_id DESC
  ");
  $rows = $stmt->fetchAll();
} else {
  $stmt = $pdo->query("
    SELECT w.withdraw_id, w.user_id, w.amount, w.bank_name, w.bank_account, w.account_name,
           w.status, w.ref_txn, w.created_at, w.processed_at, w.slip_path, w.trans_ref, w.verified_at,
           w.reject_reason,
           u.username, u.credit_balance
    FROM credit_withdrawals w
    LEFT JOIN users u ON u.user_id = w.user_id
    ORDER BY (w.status IN ('requested','pending')) DESC, w.withdraw_id DESC
  ");
  $rows = $stmt->fetchAll();
}

/* ====== สรุปสถานะ ====== */
$sum = []; $totalAmt = 0.0;
foreach ($rows as $r) {
  $st = $r['status'] ?? '-';
  $sum[$st] = ($sum[$st] ?? 0) + 1;
  $totalAmt += (float)($r['amount'] ?? 0);
}

/* ====== helper ====== */
if(!function_exists('h')){ function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if(!function_exists('slip_href')){
function slip_href($p){
  if (!$p) return '';
  $base = basename((string)$p);
  return "../uploads/slips/" . rawurlencode($base);
}
}
