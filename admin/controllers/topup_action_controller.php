<?php
session_start();

/* ── Auth & CSRF ───────────────────────────────────────────── */
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit();
}
if (!isset($_POST['csrf']) || !hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'])) {
  http_response_code(403); die('Invalid CSRF');
}

require_once __DIR__ . "/../../config/database.php";
$pdo->exec("SET NAMES utf8mb4");

/* ── Inputs ────────────────────────────────────────────────── */
$topupId = isset($_POST['topup_id']) ? (int)$_POST['topup_id'] : 0;
$action  = $_POST['action'] ?? '';

if ($topupId <= 0 || !in_array($action, ['approve','reject'], true)) {
  http_response_code(400); die('Invalid request');
}

try {
  $pdo->beginTransaction();

  // Lock แถวที่กำลังพิจารณา
  $st = $pdo->prepare("SELECT user_id, amount, status FROM credit_topups WHERE topup_id = ? FOR UPDATE");
  $st->execute([$topupId]);
  $row = $st->fetch();

  if (!$row) { throw new Exception('Topup not found'); }
  if ($row['status'] !== 'pending') { throw new Exception('Already processed'); }

  $userId = (int)$row['user_id'];
  $amount = (float)$row['amount'];
  if ($amount <= 0) { throw new Exception('Invalid amount'); }

  if ($action === 'approve') {
    // เพิ่มเครดิตให้ผู้ใช้
    $u = $pdo->prepare("UPDATE users SET credit_balance = credit_balance + ? WHERE user_id = ?");
    $u->execute([$amount, $userId]);

    // ลงสมุดบัญชี
    $lg = $pdo->prepare("INSERT INTO credit_ledger (user_id, change_amt, reason, ref_id) VALUES (?, ?, 'topup_approved', ?)");
    $lg->execute([$userId, $amount, (string)$topupId]);

    // อัปเดตสถานะ topup
    $t = $pdo->prepare("UPDATE credit_topups SET status='approved', approved_at=NOW(), admin_id=? WHERE topup_id=?");
    $t->execute([(int)($_SESSION['user_id'] ?? 0), $topupId]);

  } else { // reject
    $t = $pdo->prepare("UPDATE credit_topups SET status='rejected', approved_at=NOW(), admin_id=? WHERE topup_id=?");
    $t->execute([(int)($_SESSION['user_id'] ?? 0), $topupId]);
  }

  $pdo->commit();

  // กลับหน้า overview (คงอยู่ในแท็บ topup)
  header("Location: payments.php?type=topup");
  exit();

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo "Action error: " . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
}
