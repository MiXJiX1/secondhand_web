<?php
require_once __DIR__ . '/controllers/admin_abuse_reports_controller.php';
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>ข้อร้องเรียน</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/admin-admin_abuse_reports.css">
</head>
<body>
<div class="container py-4">

  <div class="d-flex align-items-center gap-2 mb-3">
    <div>
      <h3 class="mb-1">📣 ข้อร้องเรียน</h3>
      <div class="muted">ทั้งหมด <?= number_format($total) ?> เรื่อง</div>
    </div>
    <!-- TOP SWITCH -->
    <div class="ms-auto" style="min-width:260px">
      <select id="pageSwitch" class="form-select">
        <option value="ratings">รีวิว/เรตติ้งผู้ใช้</option>
        <option value="abuse" selected>ข้อร้องเรียน</option>
      </select>
    </div>
  </div>

  <script>
    document.getElementById('pageSwitch')?.addEventListener('change', e=>{
      const v=e.target.value;
      location.href = (v==='ratings') ? 'admin_user_ratings.php' : 'admin_abuse_reports.php';
    });
  </script>

  <!-- Filters -->
  <div class="card-soft p-3 mb-3">
    <form class="row g-2 align-items-end" method="get">
      <div class="col-12 col-md-3">
        <label class="form-label mb-1">ค้นหา</label>
        <input name="q" class="form-control" value="<?= h($q) ?>" placeholder="รายละเอียด/เหตุผล/รหัสรายงาน">
      </div>
      <div class="col-6 col-md-3">
        <label class="form-label mb-1">สถานะ</label>
        <select name="status" class="form-select">
          <option value="">ทั้งหมด</option>
          <?php foreach(['open'=>'เปิด','reviewing'=>'กำลังตรวจสอบ','done'=>'เสร็จสิ้น'] as $k=>$v): ?>
            <option value="<?= $k ?>" <?= $stat===$k?'selected':'' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1">ตั้งแต่</label>
        <input type="date" name="d1" class="form-control" value="<?= h($d1) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1">ถึง</label>
        <input type="date" name="d2" class="form-control" value="<?= h($d2) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label mb-1">ต่อหน้า</label>
        <select name="per" class="form-select">
          <?php foreach([20,50,100] as $pp): ?>
            <option value="<?= $pp ?>" <?= $per===$pp?'selected':'' ?>><?= $pp ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-3">
        <button class="btn btn-dark w-100">ค้นหา</button>
      </div>
      <div class="col-6 col-md-2">
        <a href="admin_abuse_reports.php" class="btn btn-outline-secondary w-100">ล้าง</a>
      </div>
    </form>
  </div>

  <?php if($msg): ?>
    <div class="alert alert-success"><?= h($msg) ?></div>
  <?php endif; ?>

  <?php if(!$rows): ?>
    <div class="text-center muted py-5">— ไม่พบข้อร้องเรียน —</div>
  <?php else: foreach($rows as $r): ?>
    <div class="card-soft p-3 mb-3">
      <div class="d-flex flex-wrap align-items-center gap-2">
        <div class="me-auto">
          <b>#<?= (int)$r['report_id'] ?></b>
          <span class="badge bg-secondary">ผู้แจ้ง: <?= h($r['reporter_name']) ?> (#<?= (int)$r['reporter_id'] ?>)</span>
          <span class="badge badge-status bg-<?= $r['status']==='done'?'success':($r['status']==='reviewing'?'warning text-dark':'primary') ?>">
            สถานะ: <?= h($r['status']) ?>
          </span>
        </div>
        <!-- ติดตาม/เลิกติดตาม -->
        <form method="post" class="d-inline">
          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
          <input type="hidden" name="action" value="toggle_follow">
          <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
          <button class="btn btn-<?= !empty($followed[(int)$r['report_id']])?'outline-secondary':'primary' ?>">
            <?= !empty($followed[(int)$r['report_id']]) ? 'เลิกติดตาม' : 'ติดตาม' ?>
          </button>
        </form>
        <!-- เปลี่ยนสถานะ -->
        <form method="post" class="d-flex align-items-center gap-2">
          <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
          <input type="hidden" name="action" value="update_status">
          <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
          <select name="new_status" class="form-select form-select-sm">
            <?php foreach(['open','reviewing','done'] as $s): ?>
              <option value="<?= $s ?>" <?= $r['status']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <button class="btn btn-dark btn-sm">อัปเดต</button>
        </form>
      </div>

      <?php if(!empty($r['details'])): ?>
        <div class="mt-1 note"><?= h($r['details']) ?></div>
      <?php endif; ?>
      <div class="muted small mt-1">สร้างเมื่อ <?= h($r['created_at']) ?></div>

      <!-- เพิ่มบันทึก -->
      <form method="post" class="mt-3">
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="action" value="add_note">
        <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
        <textarea name="note" class="form-control" rows="2" placeholder="จดบันทึกการตรวจสอบ..."></textarea>
        <div class="text-end mt-2">
          <button class="btn btn-outline-dark">เพิ่มบันทึก</button>
        </div>
      </form>

      <!-- แสดงบันทึกล่าสุด -->
      <?php if(!empty($notesByReport[(int)$r['report_id']])): ?>
        <div class="mt-3">
          <b>บันทึกล่าสุด</b>
          <div class="list-group mt-2">
            <?php foreach(array_slice($notesByReport[(int)$r['report_id']],0,5) as $n): ?>
              <div class="list-group-item">
                <div class="small muted">
                  <?= h($n['admin_name'] ?? ('#'.($N_HAS_ADMIN ? $n['admin_id'] : $n['user_id']))) ?> • <?= h($n['created_at']) ?>
                </div>
                <div class="note mt-1"><?= h($n['note']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; endif; ?>

  <!-- Pagination -->
  <?php if($pages>1):
    $build=function($p) use($q,$stat,$d1,$d2,$per){ return '?'.http_build_query(compact('q','stat','d1','d2','per')+['page'=>$p]); };
  ?>
    <nav>
      <ul class="pagination">
        <li class="page-item <?= $page<=1?'disabled':'' ?>"><a class="page-link" href="<?= $build($page-1) ?>">«</a></li>
        <?php for($i=max(1,$page-2);$i<=min($pages,$page+2);$i++): ?>
          <li class="page-item <?= $i===$page?'active':'' ?>"><a class="page-link" href="<?= $build($i) ?>"><?= $i ?></a></li>
        <?php endfor; ?>
        <li class="page-item <?= $page>=$pages?'disabled':'' ?>"><a class="page-link" href="<?= $build($page+1) ?>">»</a></li>
      </ul>
      <div class="muted small">หน้า <?= $page ?>/<?= $pages ?> • ทั้งหมด <?= number_format($total) ?></div>
    </nav>
  <?php endif; ?>

</div>
</body>
</html>
