<?php
$status = $_GET['status'] ?? 'failed';
$orderNo= $_GET['order_no'] ?? '';
$msg    = $_GET['msg'] ?? '';
?>
<!doctype html><meta charset="utf-8">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/chatapp-payment_result.css">
<div class="card">
  <h2><?= $status==='paid' ? '<span class="ok">ชำระเงินสำเร็จ</span>' : '<span class="no">ชำระเงินไม่สำเร็จ</span>' ?></h2>
  <p>Order Number: <b><?= htmlspecialchars($orderNo) ?></b></p>
  <?php if($msg && $status!=='paid'): ?><p><?= htmlspecialchars($msg) ?></p><?php endif; ?>
  <p><a href="../topup.php">กลับหน้าเติมเครดิต/ยอดคงเหลือ</a></p>
</div>
