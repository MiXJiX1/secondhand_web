<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../php/login.php"); exit();
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf'])) {
  $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* ===== DB ===== */
require_once __DIR__ . "/../../config/database.php";

$flash = null;

// Handle Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("BANK VERIFY POST RECEIVED: action=" . ($_POST['action'] ?? 'null') . ", user_id=" . ($_POST['user_id'] ?? 'null'));
    
    if (!isset($_POST['csrf']) || $_POST['csrf'] !== $_SESSION['csrf']) {
        $flash = ['type'=>'error', 'msg'=>'CSRF Token Mismatch!'];
        error_log("BANK VERIFY: CSRF Mismatch");
    } else {
        $action = $_POST['action'] ?? '';
        $target_user_id = (int)($_POST['user_id'] ?? 0);

        if ($target_user_id > 0) {
            try {
                if ($action === 'approve') {
                    $stmt = $pdo->prepare("UPDATE users SET bank_verified = 1 WHERE user_id = ?");
                    $stmt->execute([$target_user_id]);
                    error_log("BANK VERIFY: Approved user_id " . $target_user_id);
                    
                    // Logging
                    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) SELECT ?, username, 'system', ? FROM users WHERE user_id=? LIMIT 1";
                    $pdo->prepare($logSql)->execute([$_SESSION['user_id'], "อนุมัติบัญชีธนาคารสำหรับ user_id: $target_user_id", $_SESSION['user_id']]);
                    
                    $flash = ['type'=>'success', 'msg'=>'อนุมัติบัญชีธนาคารเรียบร้อยแล้ว'];
                } elseif ($action === 'reject') {
                    // Clear bank details to let user re-enter
                    $stmt = $pdo->prepare("UPDATE users SET bank_name=NULL, bank_account=NULL, bank_account_name=NULL, bank_verified=0 WHERE user_id = ?");
                    $stmt->execute([$target_user_id]);
                    error_log("BANK VERIFY: Rejected user_id " . $target_user_id);
                    
                    // Logging
                    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) SELECT ?, username, 'system', ? FROM users WHERE user_id=? LIMIT 1";
                    $pdo->prepare($logSql)->execute([$_SESSION['user_id'], "ปฏิเสธ/ล้างข้อมูลบัญชีธนาคารสำหรับ user_id: $target_user_id", $_SESSION['user_id']]);
                    
                    $flash = ['type'=>'success', 'msg'=>'ปฏิเสธและล้างข้อมูลบัญชีธนาคารเรียบร้อยแล้ว'];
                }
            } catch (PDOException $e) {
                error_log("BANK VERIFY EXCEPTION: " . $e->getMessage());
                $flash = ['type'=>'error', 'msg'=>'เกิดข้อผิดพลาดฐานข้อมูล: ' . $e->getMessage()];
            }
        } else {
            error_log("BANK VERIFY: Invalid target_user_id = " . $target_user_id);
        }
    }
}

// Fetch Pending and Verified Bank Accounts
try {
  $stmt = $pdo->query("
    SELECT 
      user_id, username, fname, lname, email, img,
      bank_name, bank_account, bank_account_name, bank_verified
    FROM users
    WHERE bank_account IS NOT NULL AND bank_account != ''
    ORDER BY bank_verified ASC, user_id DESC
  ");
  $bankRecords = $stmt->fetchAll();

  $pendingCount = 0;
  foreach ($bankRecords as $r) {
      if (!$r['bank_verified']) $pendingCount++;
  }
} catch (PDOException $e) {
  die("Database error: ".$e->getMessage());
}

if(!function_exists('h')){ function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
