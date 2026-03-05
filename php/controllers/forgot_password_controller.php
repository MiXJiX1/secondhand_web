<?php
/** forgot_password.php — ฟังก์ชันลืมรหัสผ่าน (ขอรีเซ็ต + ตั้งรหัสใหม่ด้วยโทเคน) */

/* ===== DB (ใช้ config กลาง) ===== */
require_once __DIR__ . "/../../config/database.php";

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
$CSRF = $_SESSION['csrf_token'];

/* ===== Ensure table password_resets ===== */
$pdo->exec("
CREATE TABLE IF NOT EXISTS password_resets (
  reset_id   INT AUTO_INCREMENT PRIMARY KEY,
  user_id    INT NOT NULL,
  token      VARCHAR(128) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  expires_at DATETIME NOT NULL,
  used_at    DATETIME DEFAULT NULL,
  INDEX(user_id), INDEX(expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Logic continues using $baseUrl from helper.php

/* ===== Logic ===== */
// Modes: 'request' (enter email), 'sent' (email sent mockup), 'reset' (enter new password)
$mode = 'request';
if (isset($_GET['token'])) {
    $mode = 'reset';
} elseif (isset($_GET['sent'])) {
    $mode = 'sent';
}

$infoMsg = $errMsg = '';
$debugLink = ''; // เผื่อแสดงลิงก์ในกรณีไม่ได้ส่งอีเมล
$sentEmail = $_SESSION['last_reset_email'] ?? 'your email';

/* ---------- Handle POST: request reset ---------- */
if ($mode === 'request' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
      throw new Exception('CSRF invalid', 400);
  }
  $email = trim($_POST['email'] ?? '');
  
  if ($email === '') {
    $errMsg = 'กรุณากรอกอีเมล';
  } else {
    // ค้นหาผู้ใช้ด้วย email เท่านั้น
    $stmt = $pdo->prepare("SELECT user_id, username, email FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $_SESSION['last_reset_email'] = $email; // สำหรับโชว์ในหน้า Sent

    if ($user) {
      $token = bin2hex(random_bytes(32)); // 64 ตัวอักษร hex
      $expires = (new DateTime('+30 minutes'))->format('Y-m-d H:i:s');

      $ins = $pdo->prepare("INSERT INTO password_resets(user_id, token, expires_at) VALUES(?,?,?)");
      $ins->execute([$user['user_id'], $token, $expires]);

      $link = $baseUrl . "/forgot_password?token=" . urlencode($token);

      // ส่งอีเมล (หากเซิร์ฟเวอร์ตั้งค่าเมลไว้)
      $to      = $user['email'];
      $subject = "รีเซ็ตรหัสผ่าน - Secondhand Web";
      $message = "สวัสดี {$user['username']}\n\n"
               . "มีการร้องขอรีเซ็ตรหัสผ่านบัญชีของคุณ หากเป็นคุณเอง โปรดกดลิงก์ด้านล่างภายใน 30 นาที:\n"
               . "$link\n\n"
               . "หากไม่ใช่คุณ โปรดเพิกเฉยอีเมลฉบับนี้\n";
      $headers = "Content-Type: text/plain; charset=UTF-8\r\n";

      if (!@mail($to, $subject, $message, $headers)) {
        $_SESSION['debug_link'] = $link; // โหมดทดสอบ/ไม่มีเมล
      }
    }
    
    redirect($baseUrl . "/forgot_password?sent=1");
  }
}

/* ---------- Handle POST: perform reset ---------- */
if ($mode === 'reset' && $_SERVER['REQUEST_METHOD']==='POST') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
      throw new Exception('CSRF invalid', 400);
  }
  $token = $_GET['token'] ?? '';
  $pass1 = (string)($_POST['password'] ?? '');
  $pass2 = (string)($_POST['password2'] ?? '');
  if ($pass1 === '' || $pass2 === '' || $pass1 !== $pass2) {
    $errMsg = 'กรุณากรอกรหัสผ่านให้ครบและตรงกัน';
  } elseif (strlen($pass1) < 6) {
    $errMsg = 'รหัสผ่านต้องมีอย่างน้อย 6 ตัวอักษร';
  } else {
    // ตรวจโทเคน
    $stmt = $pdo->prepare("SELECT pr.reset_id, pr.user_id, pr.expires_at, pr.used_at, u.username 
                            FROM password_resets pr 
                            JOIN users u ON u.user_id=pr.user_id
                            WHERE pr.token=? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();

    $now = new DateTime();
    $valid = $row && empty($row['used_at']) && (new DateTime($row['expires_at']) >= $now);

    if (!$valid) {
      $errMsg = 'ลิงก์รีเซ็ตหมดอายุหรือใช้งานไปแล้ว';
    } else {
      // อัปเดตรหัสผ่าน
      $hash = password_hash($pass1, PASSWORD_DEFAULT);
      $u = $pdo->prepare("UPDATE users SET password=? WHERE user_id=?");
      $u->execute([$hash, $row['user_id']]);

      // มาร์กโทเคนว่าใช้แล้ว
      $m = $pdo->prepare("UPDATE password_resets SET used_at=NOW() WHERE reset_id=?");
      $m->execute([$row['reset_id']]);

      // สำเร็จ -> ส่งไปหน้า login
      $_SESSION['flash_ok'] = 'ตั้งรหัสผ่านใหม่เรียบร้อยแล้ว เข้าสู่ระบบด้วยรหัสใหม่ได้เลย';
      redirect($baseUrl . "/login");
    }
  }
}

/* ---------- ตรวจสอบโทเคน (หน้า reset) ---------- */
$tokenInvalid = false;
if ($mode === 'reset' && $_SERVER['REQUEST_METHOD']!=='POST') {
  $token = $_GET['token'] ?? '';
  if ($token === '') {
    $tokenInvalid = true;
  } else {
    $stmt = $pdo->prepare("SELECT expires_at, used_at FROM password_resets WHERE token=? LIMIT 1");
    $stmt->execute([$token]);
    $row = $stmt->fetch();
    $tokenInvalid = !$row || !empty($row['used_at']) || (new DateTime($row['expires_at']) < new DateTime());
  }
}
