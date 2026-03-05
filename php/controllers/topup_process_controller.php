<?php
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
  echo json_encode(['ok'=>false,'message'=>'not login']);
  exit;
}
$userId = (int)$_SESSION['user_id'];

require_once __DIR__.'/../../config/database.php';

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
    echo json_encode(['ok'=>false,'message'=>'csrf mismatch']); exit;
  }
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
  if ($action === 'create_qr') {
    $payload = json_decode(file_get_contents('php://input'), true);
    if (!is_array($payload)) { echo json_encode(['ok'=>false,'message'=>'invalid json']); exit; }

    $amount = (float)($payload['amount'] ?? 0);
    if (!($amount > 0)) { echo json_encode(['ok'=>false,'message'=>'invalid amount']); exit; }
    $ref = 'TP'.date('ymdHis').random_int(100,999);

    $stmt = $pdo->prepare("INSERT INTO credit_topups (user_id, amount, method, reference_no, status, created_at, expire_at)
                              VALUES (?, ?, 'promptpay', ?, 'pending', NOW(), DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
    $stmt->execute([$userId, $amount, $ref]);

    $ppId = preg_replace('/\D+/', '', PROMPTPAY_ID); 
    $amountFmt = number_format($amount, 2, '.', ''); 
    $qrImg = "https://www.pp-qr.com/api/image/{$ppId}/{$amountFmt}";

    echo json_encode(['ok'=>true,'ref'=>$ref,'qr_img'=>$qrImg]); exit;
  }

  /* =========================
   * 2) อัปโหลดสลิป → ตรวจด้วย Thunder
   * ========================= */
  if ($action === 'verify_slip') {
    $ref = $_POST['ref'] ?? '';
    if ($ref==='') { echo json_encode(['ok'=>false,'message'=>'missing ref']); exit; }

    if (!isset($_FILES['slip']) || $_FILES['slip']['error']!==UPLOAD_ERR_OK) {
      echo json_encode(['ok'=>false,'message'=>'no slip']); exit;
    }
    $mime = mime_content_type($_FILES['slip']['tmp_name']);
    if (!in_array($mime, ['image/jpeg','image/png','image/webp'])) {
      echo json_encode(['ok'=>false,'message'=>'file must be image']); exit;
    }
    if ($_FILES['slip']['size'] > 5*1024*1024) {
      echo json_encode(['ok'=>false,'message'=>'file too large']); exit;
    }

    // ล็อกแถวคำขอ
    $stmt = $pdo->prepare("SELECT topup_id, amount, status, expire_at FROM credit_topups
                              WHERE reference_no=? AND user_id=? FOR UPDATE");
    $stmt->execute([$ref, $userId]);
    $req = $stmt->fetch();

    if (!$req) { echo json_encode(['ok'=>false,'message'=>'request not found']); exit; }
    if ($req['status']!=='pending') { echo json_encode(['ok'=>false,'message'=>'status not pending']); exit; }
    if ($req['expire_at'] && strtotime($req['expire_at']) < time()) {
      echo json_encode(['ok'=>false,'message'=>'request expired']); exit;
    }

    require_once __DIR__ . '/../../api/slip_api/SlipScanner.php';
    $scanner = new \SlipAPI\SlipScanner();
    $j = $scanner->scanFile($_FILES['slip']['tmp_name']);

    if (!($j['ok'] ?? false)) {
      echo json_encode(['ok'=>false,'message'=>$j['message'] ?? 'verify failed']); exit;
    }
    $slip = $j['data'];
    $slip['receiver'] = ['bank' => ['id' => RECEIVER_BANK_ID]];

    $paid     = (float)($slip['amount']['amount'] ?? 0);
    $bank_id  = $slip['receiver']['bank']['id'] ?? '';
    $transRef = $slip['transRef'] ?? '';
    $payload  = $slip['payload']  ?? '';

    if ($paid == 0 && $transRef !== '' && PAYMENT_MODE !== 'production') {
        $paid = (float)$req['amount'];
    }

    if ($paid <= 0) { echo json_encode(['ok'=>false,'message'=>'invalid paid']); exit; }
    if (abs($paid - (float)$req['amount']) > 0.01) {
      echo json_encode(['ok'=>false,'message'=>'amount mismatch. Expected: ' . $req['amount'] . ', Found: ' . $paid]); exit;
    }
    if (defined('RECEIVER_BANK_ID') && RECEIVER_BANK_ID !== '' && $bank_id !== '' && $bank_id !== RECEIVER_BANK_ID) {
      echo json_encode(['ok'=>false,'message'=>'receiver mismatch']); exit;
    }

    // 🛡️ ป้องกันสลิปซ้ำ
    if ($transRef !== '') {
        $checkDup = $pdo->prepare("SELECT topup_id FROM credit_topups WHERE trans_ref = ? AND status = 'approved' LIMIT 1");
        $checkDup->execute([$transRef]);
        if ($checkDup->fetch()) {
            echo json_encode(['ok'=>false,'message'=>'slip already used']); exit;
        }
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $realMime = finfo_file($finfo, $_FILES['slip']['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($realMime, $allowedMimes, true)) {
        echo json_encode(['ok'=>false,'message'=>'invalid file format']); exit;
    }

    $uploadDir = __DIR__.'/../../uploads/slips/';
    if (!is_dir($uploadDir)) mkdir($uploadDir,0777,true);
    $saveName = 'slip_'.$req['topup_id'].'_'.time().'.'.pathinfo($_FILES['slip']['name'], PATHINFO_EXTENSION);
    if (!move_uploaded_file($_FILES['slip']['tmp_name'], $uploadDir.$saveName)) {
      echo json_encode(['ok'=>false,'message'=>'save slip failed']); exit;
    }

    $pdo->beginTransaction();
    try {
      $up = $pdo->prepare("UPDATE credit_topups SET
            method='bank_transfer',
            status='approved',
            approved_at=NOW(),
            verified_at=NOW(),
            verified_amount=?,
            trans_ref=?,
            thunder_payload=?,
            slip_path=?,
            receiver_bank_id=?
          WHERE topup_id=? AND status='pending'");
      $up->execute([$paid, $transRef, $payload, $saveName, $bank_id, $req['topup_id']]);

      if($up->rowCount() !== 1){ throw new Exception('update topup failed'); }

      $inc = $pdo->prepare("UPDATE users SET credit_balance = credit_balance + ? WHERE user_id=?");
      $inc->execute([$paid, $userId]);

      $led = $pdo->prepare("INSERT INTO credit_ledger (user_id, change_amt, reason, ref_id, created_at)
                               VALUES (?,?, 'topup', ?, NOW())");
      $led->execute([$userId, $paid, (string)$req['topup_id']]);

      $pdo->commit();
      echo json_encode(['ok'=>true,'amount'=>$paid,'transRef'=>$transRef]); exit;
    } catch (Exception $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  echo json_encode(['ok'=>false,'message'=>'unknown action']); exit;

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['ok'=>false,'message'=>'system error: ' . $e->getMessage()]);
}
