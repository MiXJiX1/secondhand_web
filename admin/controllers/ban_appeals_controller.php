<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../php/login.php"); exit();
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf'];

/* ===== Actions ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) { http_response_code(403); die('CSRF invalid'); }

  $appeal_id = (int)($_POST['appeal_id'] ?? 0);
  $action    = $_POST['action'] ?? '';
  $note      = trim($_POST['note'] ?? '');

  // หา appeal + user
  $st = $conn->prepare("
    SELECT a.appeal_id, a.user_id, a.status, u.status AS user_status
    FROM ban_appeals a
    JOIN users u ON u.user_id = a.user_id
    WHERE a.appeal_id=? LIMIT 1
  ");
  $st->bind_param('i', $appeal_id);
  $st->execute();
  $ap = $st->get_result()->fetch_assoc();
  $st->close();

  if ($ap) {
    if ($action === 'approve') {
      $conn->begin_transaction();
      try{
        $st = $conn->prepare("UPDATE users SET status='active', banned_at=NULL, ban_reason=NULL WHERE user_id=?");
        $st->bind_param('i', $ap['user_id']); $st->execute(); $st->close();

        $st = $conn->prepare("
          UPDATE ban_appeals
             SET status='approved', reviewed_at=NOW(), reviewed_by=?, decision_note=?
           WHERE appeal_id=?");
        $adminId = (int)$_SESSION['user_id'];
        $st->bind_param('isi', $adminId, $note, $appeal_id);
        $st->execute(); $st->close();

        $conn->commit();
      }catch(Throwable $e){
        $conn->rollback(); die('เกิดข้อผิดพลาด: '.$e->getMessage());
      }
    } elseif ($action === 'reject') {
      $st = $conn->prepare("
        UPDATE ban_appeals
           SET status='rejected', reviewed_at=NOW(), reviewed_by=?, decision_note=?
         WHERE appeal_id=?");
      $adminId = (int)$_SESSION['user_id'];
      $st->bind_param('isi', $adminId, $note, $appeal_id);
      $st->execute(); $st->close();
    }
  }
  header('Location: ban_appeals.php'); exit;
}

/* ===== Data ===== */
$rs = $conn->query("
  SELECT a.*, u.username, u.email
    FROM ban_appeals a
    LEFT JOIN users u ON u.user_id=a.user_id
   ORDER BY FIELD(a.status,'pending','approved','rejected'), a.created_at DESC
");
$rows = $rs->fetch_all(MYSQLI_ASSOC);
$total = count($rows);
