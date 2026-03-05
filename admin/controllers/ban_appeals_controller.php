<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../php/login.php"); exit();
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

/* ===== Actions ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
      throw new Exception('CSRF invalid', 403);
  }

  $appeal_id = (int)($_POST['appeal_id'] ?? 0);
  $action    = $_POST['action'] ?? '';
  $note      = trim($_POST['note'] ?? '');

  // หา appeal + user
  $st = $pdo->prepare("
    SELECT a.appeal_id, a.user_id, a.status, u.status AS user_status
    FROM ban_appeals a
    JOIN users u ON u.user_id = a.user_id
    WHERE a.appeal_id=? LIMIT 1
  ");
  $st->execute([$appeal_id]);
  $ap = $st->fetch();

  if ($ap) {
    if ($action === 'approve') {
      $pdo->beginTransaction();
      try {
        $st = $pdo->prepare("UPDATE users SET status='active', banned_at=NULL, ban_reason=NULL WHERE user_id=?");
        $st->execute([$ap['user_id']]);

        $st = $pdo->prepare("
          UPDATE ban_appeals
             SET status='approved', reviewed_at=NOW(), reviewed_by=?, decision_note=?
           WHERE appeal_id=?");
        $adminId = (int)$_SESSION['user_id'];
        $st->execute([$adminId, $note, $appeal_id]);

        $pdo->commit();
      } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
      }
    } elseif ($action === 'reject') {
      $st = $pdo->prepare("
        UPDATE ban_appeals
           SET status='rejected', reviewed_at=NOW(), reviewed_by=?, decision_note=?
         WHERE appeal_id=?");
      $adminId = (int)$_SESSION['user_id'];
      $st->execute([$adminId, $note, $appeal_id]);
    }
  }
  header('Location: ban_appeals.php'); exit;
}

/* ===== Data ===== */
$rows = $pdo->query("
  SELECT a.*, u.username, u.email
    FROM ban_appeals a
    LEFT JOIN users u ON u.user_id=a.user_id
   ORDER BY FIELD(a.status,'pending','approved','rejected'), a.created_at DESC
")->fetchAll();
$total = count($rows);
