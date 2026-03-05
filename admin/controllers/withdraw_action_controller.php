require_once __DIR__ . "/../../config/database.php";

/* ---------- Auth & CSRF ---------- */
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: " . ($baseUrl ?? '') . "/login"); exit();
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  throw new Exception('CSRF invalid', 400);
}
$adminId = (int)($_SESSION['user_id'] ?? 0);

/* ---------- Thunder Config (ตัวอย่าง) ---------- */
$THUNDER_TOKEN      = '518dca4c-9ddc-4ee7-a77d-2b972ae9392b';
$THUNDER_VERIFY_URL = 'https://api.thunder.example/v1/transactions/verify';

/* ---------- Thunder helper ---------- */
if(!function_exists('verifyWithThunder')){
function verifyWithThunder(string $verifyUrl, string $token, string $transRef): array {
  $ch = curl_init($verifyUrl);
  $body = http_build_query(['trans_ref'=>$transRef]);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $body,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
      'Authorization: Bearer '.$token,
      'Content-Type: application/x-www-form-urlencoded'
    ],
    CURLOPT_TIMEOUT => 15
  ]);
  $resp = curl_exec($ch);
  $err  = curl_error($ch);
  curl_close($ch);

  if ($resp === false) {
    return ['ok'=>false,'payload'=>json_encode(['error'=>$err], JSON_UNESCAPED_UNICODE),'verified_at'=>null];
  }
  $ok=false; $verifiedAt=null;
  try {
    $j = json_decode($resp, true, 512, JSON_THROW_ON_ERROR);
    if (!empty($j['verified']) || (isset($j['status']) && in_array($j['status'], ['success','ok'], true))) {
      $ok = true; $verifiedAt = date('Y-m-d H:i:s');
    }
  } catch (Throwable $e) { /* ignore parse error; treat as not verified */ }
  return ['ok'=>$ok,'payload'=>$resp,'verified_at'=>$verifiedAt];
}
}

/* ---------- Inputs ---------- */
$action     = $_POST['action'] ?? '';
$withdrawId = (int)($_POST['withdraw_id'] ?? 0);
if ($withdrawId <= 0 || !in_array($action, ['approve','reject','mark_paid'], true)) {
  header("Location: " . ($baseUrl ?? '') . "/admin/payments?type=withdraw"); exit();
}

/* ---------- DB ---------- */
// DB is now already included at the top
  $pdo->exec("SET NAMES utf8mb4");

  $pdo->beginTransaction();

  // Lock แถวรายการถอน
  $q = $pdo->prepare("SELECT * FROM credit_withdrawals WHERE withdraw_id = ? FOR UPDATE");
  $q->execute([$withdrawId]);
  $w = $q->fetch();
  if (!$w) {
    $pdo->rollBack();
    header("Location: " . ($baseUrl ?? '') . "/admin/payments?type=withdraw"); exit();
  }

  $status = $w['status'];
  $now    = date('Y-m-d H:i:s');

  // ช็อตคัทส่งกลับ
  $done = function(string $flag) use ($pdo, $baseUrl) {
    if ($pdo->inTransaction()) $pdo->commit();
    header("Location: " . ($baseUrl ?? '') . "/admin/payments?type=withdraw&$flag=1"); exit();
  };

  /* ---------- 1) อนุมัติ ---------- */
  if ($action === 'approve') {
    // อนุมัติได้เฉพาะ requested/pending เท่านั้น
    if (!in_array($status, ['requested','pending'], true)) {
      // ถ้าถูก approve/paid/rejected ไปแล้ว ให้ถือว่า OK แล้ว
      $done('ok');
    }
    $u = $pdo->prepare("UPDATE credit_withdrawals SET status='approved', processed_at=?, admin_id=? WHERE withdraw_id=?");
    $u->execute([$now, $adminId, $withdrawId]);

    $done('ok');
  }

  /* ---------- 2) ปฏิเสธ + คืนเครดิต + เก็บเหตุผล ---------- */
  if ($action === 'reject') {
    // ถ้าเคย reject/paid แล้ว ให้ถือว่า OK
    if (in_array($status, ['rejected','paid'], true)) { $done('ok'); }

    $reason = trim($_POST['reason'] ?? '');
    if ($reason === '') $reason = 'ไม่ระบุเหตุผล';

    // คืนเครดิต (กรณีหักไว้ตอนผู้ใช้ยื่นถอน)
    // ใช้ ref เดิมถ้ามี เพื่อกันซ้ำ; เงียบกรณี unique key collision
    $ref = $w['ref_txn'] ?: ('WD'.strtoupper(bin2hex(random_bytes(6))));
    try {
      // บัญชีแยกประเภท (ถ้ามี)
      $pdo->prepare("INSERT INTO credit_ledger (user_id, change_amt, reason, ref_id)
                     VALUES (?, ?, 'withdraw_refund', ?)")
          ->execute([(int)$w['user_id'], (float)$w['amount'], $ref]);
    } catch (PDOException $e) {
      if ($e->getCode() !== '23000') throw $e; // 23000 = duplicate key
    }

    // (ไม่ปรับ users.credit_balance ที่นี่ หากระบบคุณหักตั้งแต่ยื่นคำขอแล้ว + จะมี worker คืนตอน reject)
    // ถ้าต้องการคืนยอดทันที ให้ปลดคอมเมนต์บรรทัดล่าง:
    // $pdo->prepare("UPDATE users SET credit_balance = credit_balance + ? WHERE user_id = ?")
    //     ->execute([(float)$w['amount'], (int)$w['user_id']]);

    $u = $pdo->prepare("UPDATE credit_withdrawals
                        SET status='rejected', processed_at=?, reject_reason=?, admin_id=?
                        WHERE withdraw_id=?");
    $u->execute([$now, $reason, $adminId, $withdrawId]);

    $done('ok');
  }

  /* ---------- 3) โอนแล้ว + อัปสลิป + Verify Thunder ---------- */
  if ($action === 'mark_paid') {
    // ต้องอยู่สถานะ approved (หรืออย่างน้อยยังไม่ถูกปิด) เพื่อป้องกันซ้ำซ้อน
    if (!in_array($status, ['approved','requested','pending'], true)) {
      // ถ้าเป็น paid/rejected แล้ว ไม่ทำซ้ำ
      $done('ok');
    }

    $transRef = trim($_POST['trans_ref'] ?? '');
    if ($transRef === '') { throw new Exception('กรุณาระบุ Transaction Ref', 400); }

    if (!isset($_FILES['slip']) || $_FILES['slip']['error'] !== UPLOAD_ERR_OK) {
      throw new Exception('กรุณาอัปโหลดไฟล์สลิป', 400);
    }

    // ตรวจชนิดไฟล์ + ขนาด
    $f      = $_FILES['slip'];
    $size   = (int)$f['size'];
    $maxMb  = 8 * 1024 * 1024; // จำกัด 8MB
    if ($size <= 0 || $size > $maxMb) {
      throw new Exception('ไฟล์ใหญ่เกินกำหนด (≤ 8MB)', 400);
    }

    $finfoMime = function_exists('finfo_open')
      ? (static function($tmp){
          $finfo = finfo_open(FILEINFO_MIME_TYPE);
          if (!$finfo) return false;
          $mime = finfo_file($finfo, $tmp);
          finfo_close($finfo);
          return $mime;
        })($f['tmp_name'])
      : @mime_content_type($f['tmp_name']);

    $allowMap = ['image/jpeg'=>'jpg', 'image/png'=>'png', 'application/pdf'=>'pdf'];
    if (!$finfoMime || !isset($allowMap[$finfoMime])) {
      throw new Exception('ชนิดไฟล์ไม่ถูกต้อง (อนุญาต: JPG, PNG, PDF)', 400);
    }

    // โฟลเดอร์ปลายทาง (ใช้โฟลเดอร์ slips เดียวกันกับ Top-up เพื่อความง่ายในการดึงข้อมูล)
    $uploadDir = __DIR__ . '/../../uploads/slips/';
    if (!is_dir($uploadDir)) {
      mkdir($uploadDir, 0777, true);
    }

    $ext  = $allowMap[$finfoMime];
    $name = 'wd_slip_' . $withdrawId . '_' . time() . '.' . $ext;
    $dest = $uploadDir . $name;

    if (!move_uploaded_file($f['tmp_name'], $dest)) {
      throw new Exception('อัปโหลดสลิปไม่สำเร็จ', 500);
    }
    @chmod($dest, 0644);

    // Bypass Thunder Verify (แอดมินตรวจเช็คด้วยตัวเอง)
    $verifiedAt = $now; 
    $payload    = 'manual_admin_upload';

    // อัปเดตสถานะ -> paid
    $u = $pdo->prepare("
      UPDATE credit_withdrawals
      SET status='paid',
          processed_at=?,
          admin_id=?,
          slip_path=?,
          trans_ref=?,
          thunder_payload=?,
          verified_at=?
      WHERE withdraw_id=?
    ");
    $u->execute([
      $now, $adminId, $name, $transRef, $payload, $verifiedAt, $withdrawId
    ]);

    $done('ok');
  }

  // action ไม่แมตช์
  if ($pdo->inTransaction()) $pdo->rollBack();
  header("Location: " . ($baseUrl ?? '') . "/admin/payments?type=withdraw"); exit();

  if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
  if (!headers_sent()) {
    header("Location: " . ($baseUrl ?? '') . "/admin/payments?type=withdraw&err=1");
  } else {
    echo "Error: " . htmlspecialchars($e->getMessage());
  }
  exit();
}
