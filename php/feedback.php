<?php
require_once __DIR__ . '/controllers/feedback_controller.php';
?>

?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ให้คะแนน / รายงานผู้ใช้</title>
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/php-feedback.css">
</head>
<body class="animate-up">

<?php 
$navTitle = 'ให้คะแนน / รายงานผู้ใช้';
$backLink = '../index.php';
$backText = 'กลับหน้าแรก';
$navRight = '<div class="tabs">
    <a class="tab '.($tab==='rate'?'active':'').'" href="feedback.php?tab=rate">ให้คะแนน</a>
    <a class="tab '.($tab==='report'?'active':'').'" href="feedback.php?tab=report">รายงานผู้ใช้</a>
  </div>';
include __DIR__ . '/../includes/navbar_back.php'; 
?>

<div class="container">
  <?php if($msg): ?>
    <div class="panel" style="background:#e7fff1;border:1px solid #86efac;color:#065f46"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <?php if ($tab==='rate'): ?>
    <div class="panel">
      <h3 style="margin-top:0">ผู้ขายที่คุณเคยซื้อ</h3>
      <?php if(!$buyersSellers): ?>
        <div class="small">ยังไม่มีคำสั่งซื้อสำเร็จ จึงยังไม่สามารถให้คะแนนได้</div>
      <?php else: ?>
        <form method="post" style="display:grid;gap:12px">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="action" value="rate">

          <div class="row">
            <div>
              <label>เลือกผู้ขาย</label>
              <select name="rated_user_id" id="rated_user_id" required>
                <option value="">— เลือกผู้ขาย —</option>
                <?php foreach($buyersSellers as $sid=>$info):
                  $avg = $avgRatingByUser[$sid]['avg'] ?? null;
                  $cnt = $avgRatingByUser[$sid]['cnt'] ?? 0;
                  ?>
                  <option value="<?= (int)$sid ?>">
                    <?= htmlspecialchars($info['seller_name']) ?>
                    <?= $cnt? ' (★'.number_format($avg,2).' / '.$cnt.' รีวิว)' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div>
              <label>เลือกรายการสั่งซื้อ</label>
              <select name="order_id" id="order_id" required>
                <option value="">— เลือกจากสินค้า —</option>
                <?php foreach($buyersSellers as $sid=>$info): ?>
                  <?php foreach($info['orders'] as $o): ?>
                    <option value="<?= (int)$o['order_id'] ?>" data-seller="<?= (int)$sid ?>" data-product="<?= (int)$o['product_id'] ?>">
                      #<?= (int)$o['order_id'] ?> — <?= htmlspecialchars($o['product_name']) ?>
                    </option>
                  <?php endforeach; ?>
                <?php endforeach; ?>
              </select>
              <input type="hidden" name="product_id" id="product_id">
              <div class="small">** ระบบจะกรองออร์เดอร์ตามผู้ขายที่เลือกอัตโนมัติ</div>
            </div>
          </div>

          <div>
            <label>ให้คะแนน</label>
            <div class="star" style="display:flex;gap:4px;flex-direction:row-reverse;justify-content:flex-end">
              <input type="radio" id="s5" name="score" value="5" required><label for="s5">★</label>
              <input type="radio" id="s4" name="score" value="4"><label for="s4">★</label>
              <input type="radio" id="s3" name="score" value="3"><label for="s3">★</label>
              <input type="radio" id="s2" name="score" value="2"><label for="s2">★</label>
              <input type="radio" id="s1" name="score" value="1"><label for="s1">★</label>
            </div>
          </div>

          <div>
            <label>ความคิดเห็น (ถ้ามี)</label>
            <textarea name="comment" placeholder="เช่น สินค้าตรงปก ส่งไว บริการดี"></textarea>
          </div>

          <div style="text-align:right">
            <button class="btn">บันทึกคะแนน</button>
          </div>
        </form>
      <?php endif; ?>
    </div>

    <div class="panel">
      <h3 style="margin:0 0 10px 0">คะแนนที่คุณเคยให้ล่าสุด</h3>
      <?php if(!$myRatings): ?>
        <div class="small">ยังไม่มีข้อมูล</div>
      <?php else: ?>
        <div class="list">
          <?php foreach($myRatings as $r): ?>
            <div class="item">
              <div><b><?= htmlspecialchars($r['rated_name'] ?: ('ผู้ใช้ #'.$r['rated_user_id'])) ?></b>
                <span class="badge">★<?= (int)$r['score'] ?></span>
              </div>
              <?php if($r['comment']): ?>
                <div class="small" style="margin-top:6px"><?= nl2br(htmlspecialchars($r['comment'])) ?></div>
              <?php endif; ?>
              <div class="small" style="margin-top:6px">เมื่อ <?= htmlspecialchars($r['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  <?php else: /* report */ ?>

    <div class="panel">
      <h3 style="margin-top:0">รายงานผู้ใช้</h3>
      <form method="post" style="display:grid;gap:12px">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="report">

        <div class="row">
          <div>
            <label>ระบุผู้ใช้ที่ต้องการรายงาน</label>
            <select name="reported_user_id">
              <option value="">— เลือกผู้ขายจากที่คุณเคยซื้อ (แนะนำ) —</option>
              <?php foreach($buyersSellers as $sid=>$info): ?>
                <option value="<?= (int)$sid ?>"><?= htmlspecialchars($info['seller_name']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="small">หากต้องการรายงานผู้ใช้อื่น สามารถใส่รหัสผู้ใช้แทนได้</div>
            <input type="text" name="reported_user_id_manual" placeholder="หรือกรอกรหัสผู้ใช้... (ตัวเลข)">
          </div>
          <div>
            <label>สาเหตุ</label>
            <select name="reason" required>
              <option value="fraud">ฉ้อโกง/โกงเงิน</option>
              <option value="fake">สินค้าปลอม/ไม่ตรงปก</option>
              <option value="offensive">ข้อความไม่เหมาะสม</option>
              <option value="spam">สแปม/รบกวน</option>
              <option value="other">อื่น ๆ</option>
            </select>
          </div>
        </div>

        <div>
          <label>รายละเอียดเพิ่มเติม</label>
          <textarea name="details" placeholder="เล่าเหตุการณ์โดยย่อ (ถ้ามี)"></textarea>
        </div>

        <div style="text-align:right">
          <button class="btn">ส่งรายงาน</button>
        </div>
      </form>
    </div>

    <div class="panel">
      <h3 style="margin:0 0 10px 0">รายงานที่คุณเคยส่ง</h3>
      <?php if(!$myReports): ?>
        <div class="small">ยังไม่มีข้อมูล</div>
      <?php else: ?>
        <div class="list">
          <?php foreach($myReports as $rp): ?>
            <div class="item">
              <div><b><?= htmlspecialchars($rp['reported_name'] ?? 'เป้าหมายไม่ทราบ') ?></b>
                <?php 
                  $r_map = ['fraud'=>'โกงเงิน','fake'=>'สินค้าปลอม','offensive'=>'ไม่เหมาะสม','spam'=>'สแปม','other'=>'อื่นๆ'];
                  $s_map = ['open'=>'รอดำเนินการ','reviewing'=>'กำลังตรวจสอบ','done'=>'เสร็จสิ้น'];
                ?>
                <span class="badge"><?= htmlspecialchars($r_map[$rp['reason']] ?? $rp['reason']) ?></span>
                <span class="badge">สถานะ: <?= htmlspecialchars($s_map[$rp['status']] ?? $rp['status']) ?></span>
              </div>
              <?php if($rp['details']): ?>
                <div class="small" style="margin-top:6px"><?= nl2br(htmlspecialchars($rp['details'])) ?></div>
              <?php endif; ?>
              <div class="small" style="margin-top:6px">เมื่อ <?= htmlspecialchars($rp['created_at']) ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  <?php endif; ?>
</div>

<footer class="small">MSU Marketplace — feedback module</footer>

<script>
/* กรอง dropdown ออร์เดอร์ตามผู้ขายที่เลือก + เติม product_id ซ่อน */
const ratedSel  = document.getElementById('rated_user_id');
const orderSel  = document.getElementById('order_id');
const productId = document.getElementById('product_id');

function filterOrders(){
  if (!ratedSel || !orderSel) return;
  const seller = ratedSel.value;
  for (const opt of orderSel.options){
    if (!opt.value) { opt.hidden = false; continue; }
    opt.hidden = (String(opt.dataset.seller) !== String(seller));
  }
  orderSel.value = '';
  productId.value = '';
}
ratedSel && ratedSel.addEventListener('change', filterOrders);
orderSel && orderSel.addEventListener('change', ()=>{
  const opt = orderSel.selectedOptions[0];
  productId.value = opt ? (opt.dataset.product || '') : '';
});
filterOrders();

/* ถ้าใส่รหัสผู้ใช้เองในหน้า report ให้แทนค่าจากช่องเลือก */
const manual = document.querySelector('input[name="reported_user_id_manual"]');
if (manual){
  manual.addEventListener('input', ()=>{
    const v = manual.value.trim();
    const sel = document.querySelector('select[name="reported_user_id"]');
    if (/^\d+$/.test(v)){ sel.value=''; sel.disabled=true; }
    else { sel.disabled=false; }
  });
}
</script>
</body>
</html>
