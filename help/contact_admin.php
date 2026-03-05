<?php
// php/contact_admin.php — ติดต่อผู้ดูแลระบบ
declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . "/../config/database.php";
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

/* ===== สร้างตารางถ้ายังไม่มี ===== */
$pdo->exec("
CREATE TABLE IF NOT EXISTS support_tickets(
  ticket_id     INT AUTO_INCREMENT PRIMARY KEY,
  ref_code      VARCHAR(32) NOT NULL,
  user_id       INT NOT NULL,
  category      ENUM('account','payment','bug','abuse','other') NOT NULL DEFAULT 'other',
  subject       VARCHAR(255) NOT NULL,
  message       TEXT NOT NULL,
  status        ENUM('open','in_progress','resolved','closed') NOT NULL DEFAULT 'open',
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP NULL DEFAULT NULL,
  INDEX(user_id), INDEX(status), UNIQUE(ref_code)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pdo->exec("
CREATE TABLE IF NOT EXISTS support_attachments(
  id        INT AUTO_INCREMENT PRIMARY KEY,
  ticket_id INT NOT NULL,
  file_name VARCHAR(255) NOT NULL,
  file_path VARCHAR(255) NOT NULL,
  mime_type VARCHAR(100) NOT NULL,
  size_bytes INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX(ticket_id),
  CONSTRAINT fk_sup_att FOREIGN KEY (ticket_id) REFERENCES support_tickets(ticket_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

/* ===== CSRF ===== */
if (empty($_SESSION['csrf_support'])) $_SESSION['csrf_support'] = bin2hex(random_bytes(24));
$CSRF = $_SESSION['csrf_support'];

$flash = null;

/* ===== POST: สร้างทิกเก็ต ===== */
if ($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='create' ) {
  try{
    if (!hash_equals($CSRF, $_POST['csrf'] ?? '')) throw new RuntimeException('CSRF ไม่ถูกต้อง');

    $category = $_POST['category'] ?? 'other';
    if (!in_array($category, ['account','payment','bug','abuse','other'], true)) $category = 'other';

    $subject = trim((string)($_POST['subject'] ?? ''));
    $message = trim((string)($_POST['message'] ?? ''));
    if ($subject === '' || $message === '') throw new RuntimeException('กรุณากรอกหัวข้อและรายละเอียดให้ครบ');

    // อัปโหลดไฟล์แนบ (ไม่บังคับ)
    $uploadDirFs  = realpath(__DIR__ . '/..') . '/uploads/support/';
    $uploadDirUrl = '../uploads/support/';
    if (!is_dir($uploadDirFs)) @mkdir($uploadDirFs, 0755, true);

    $pdo->beginTransaction();

    $ref = 'SUP-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
    $ins = $pdo->prepare("
      INSERT INTO support_tickets(ref_code,user_id,category,subject,message)
      VALUES (?,?,?,?,?)
    ");
    $ins->execute([$ref, $userId, $category, $subject, $message]);
    $ticketId = (int)$pdo->lastInsertId();

    if (!empty($_FILES['files']) && is_array($_FILES['files']['name'])) {
      $allow = ['image/jpeg','image/png','image/webp','image/gif','application/pdf'];
      for ($i=0; $i<count($_FILES['files']['name']); $i++){
        if (($_FILES['files']['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) continue;
        if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_OK) throw new RuntimeException('อัปโหลดไฟล์แนบล้มเหลว');

        $tmp  = $_FILES['files']['tmp_name'][$i];
        $name = basename((string)$_FILES['files']['name'][$i]);
        $type = (string)($_FILES['files']['type'][$i] ?? 'application/octet-stream');
        $size = (int)($_FILES['files']['size'][$i] ?? 0);
        if ($size > 5*1024*1024) throw new RuntimeException('ไฟล์แนบเกิน 5MB');
        if (!in_array($type, $allow, true)) throw new RuntimeException('ชนิดไฟล์ไม่รองรับ');

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $new = 'att_' . bin2hex(random_bytes(8)) . ($ext?'.'.$ext:'');
        if (!move_uploaded_file($tmp, $uploadDirFs.$new)) throw new RuntimeException('บันทึกไฟล์แนบไม่สำเร็จ');
        @chmod($uploadDirFs.$new, 0644);

        $pdo->prepare("
          INSERT INTO support_attachments(ticket_id,file_name,file_path,mime_type,size_bytes)
          VALUES (?,?,?,?,?)
        ")->execute([$ticketId, $name, $uploadDirUrl.$new, $type, $size]);
      }
    }

    $pdo->commit();
    $flash = ['ok'=>true, 'msg'=>"ส่งคำขอเรียบร้อย! หมายเลขอ้างอิง $ref"];
  } catch(Throwable $e){
    if ($pdo->inTransaction()) $pdo->rollBack();
    $flash = ['ok'=>false,'msg'=>$e->getMessage()];
  }
}

/* ===== ดึงรายการทิกเก็ตของฉัน (ล่าสุด 20) ===== */
$my = $pdo->prepare("
  SELECT ticket_id, ref_code, category, subject, status, created_at, updated_at
  FROM support_tickets WHERE user_id=? ORDER BY ticket_id DESC LIMIT 20
");
$my->execute([$userId]);
$items = $my->fetchAll();

/* ===== Helper ===== */
if(!function_exists('h')){ function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ติดต่อผู้ดูแล</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/help-contact_admin.css">
</head>
<body>
  <div class="topbar">
    <a class="back" href="../index.php">← กลับหน้าแรก</a>
    <div class="title">ติดต่อผู้ดูแลระบบ</div>
  </div>

  <div class="wrap">
    <!-- ฟอร์มส่งเรื่อง -->
    <div class="card">
      <h2>ส่งคำขอความช่วยเหลือ</h2>
      <?php if($flash): ?>
        <div class="card" style="background:#f9fbe7;border-color:#e6ee9c;margin-top:8px">
          <?= h($flash['msg']) ?>
        </div>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="create">
        <input type="hidden" name="csrf" value="<?= h($CSRF) ?>">

        <label>ประเภทปัญหา</label>
        <select name="category" required>
          <option value="account">บัญชีผู้ใช้ / การเข้าสู่ระบบ</option>
          <option value="payment">การชำระเงิน / เครดิต</option>
          <option value="bug">ปัญหาการใช้งาน (บั๊ก)</option>
          <option value="abuse">รายงานการทุจริต/การใช้งานไม่เหมาะสม</option>
          <option value="other" selected>อื่น ๆ</option>
        </select>

        <label>หัวข้อ</label>
        <input type="text" name="subject" maxlength="255" placeholder="เช่น เติมเครดิตแล้วไม่ขึ้น" required>

        <label>รายละเอียด</label>
        <textarea name="message" placeholder="อธิบายปัญหา/สิ่งที่เกิดขึ้น พร้อมข้อมูลประกอบ เช่น เลขคำสั่งซื้อ เวลาที่เกิดเหตุ ฯลฯ" required></textarea>
        <small class="muted">เคล็ดลับ: แนบรูปหน้าจอ/สลิป จะช่วยให้ตรวจสอบเร็วขึ้น</small>

        <div class="att">
          <label>ไฟล์แนบ (ไม่บังคับ)</label>
          <input type="file" name="files[]" accept=".jpg,.jpeg,.png,.webp,.gif,.pdf" multiple>
          <small class="muted">รองรับ JPG/PNG/WebP/GIF/PDF รวมไฟล์ละไม่เกิน 5MB</small>
        </div>

        <div style="margin-top:12px;display:flex;gap:10px;flex-wrap:wrap">
          <button class="btn btn-brand" type="submit">ส่งคำขอ</button>
          <a class="btn btn-ghost" href="../php/feedback.php">ไปหน้า “ให้คะแนน/รายงานผู้ใช้”</a>
        </div>
      </form>
    </div>

    <!-- ประวัติคำขอของฉัน -->
    <div class="card">
      <h2>ประวัติคำขอล่าสุด</h2>
      <?php if(!$items): ?>
        <div class="muted">ยังไม่มีคำขอ</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="table">
            <thead>
              <tr><th>อ้างอิง</th><th>หัวข้อ</th><th>ประเภท</th><th>สถานะ</th><th>ยื่นเมื่อ</th></tr>
            </thead>
            <tbody>
              <?php foreach($items as $it): ?>
                <tr>
                  <td><code><?= h($it['ref_code']) ?></code></td>
                  <td><?= h($it['subject']) ?></td>
                  <td><?= h($it['category']==='account'?'บัญชี':($it['category']==='payment'?'การเงิน':($it['category']==='bug'?'บั๊ก':($it['category']==='abuse'?'ทุจริต':'อื่น ๆ')))) ?></td>
                  <td>
                    <?php 
                      $st_map = ['open'=>'เปิดเรื่อง','in_progress'=>'กำลังดำเนินการ','resolved'=>'แก้ไขแล้ว','closed'=>'ปิดเรื่อง'];
                      $st_label = $st_map[$it['status']] ?? $it['status'];
                    ?>
                    <span class="badge <?= h($it['status']) ?>"><?= h($st_label) ?></span>
                  </td>
                  <td><?= h($it['created_at']) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
      <small class="muted">ผู้ดูแลจะติดต่อกลับผ่านอีเมล/ข้อความในระบบ หากต้องการแนบข้อมูลเพิ่ม ให้ส่งคำขอใหม่ระบุอ้างอิงเดิม</small>
    </div>
  </div>
</body>
</html>
