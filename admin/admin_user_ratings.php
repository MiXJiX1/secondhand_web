<?php
require_once __DIR__ . '/controllers/admin_user_ratings_controller.php';
?>
<?php
$pageTitle = 'รีวิว/เรตติ้งผู้ใช้';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Header & Summary -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2 mb-1">
                <span class="material-symbols-outlined text-yellow-500" style="font-variation-settings: 'FILL' 1;">star</span> 
                รีวิว/เรตติ้งผู้ใช้
            </h3>
            <div class="flex items-center gap-3 text-sm text-slate-500">
                <span>ทั้งหมด <strong class="text-slate-700"><?= number_format($avgRow['cnt']) ?></strong> รีวิว</span>
                <span class="w-1 h-1 rounded-full bg-slate-300"></span>
                <span class="flex items-center gap-1">
                    ค่าเฉลี่ยระบบ 
                    <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded-md font-bold text-xs"><?= number_format($avgRow['avg_score'], 2) ?>/5</span>
                </span>
            </div>
        </div>
        <div class="flex items-center gap-2 w-full md:w-auto">
            <select id="pageSwitch" class="w-full md:w-auto border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white min-w-[200px]">
                <option value="ratings" selected>รีวิว/เรตติ้งผู้ใช้</option>
                <option value="abuse">ข้อร้องเรียน</option>
            </select>
        </div>
    </div>
</div>

<script>
  document.getElementById('pageSwitch')?.addEventListener('change', e => {
    const v = e.target.value;
    location.href = (v === 'abuse') ? 'admin_abuse_reports.php' : 'admin_user_ratings.php';
  });
</script>

<!-- Distribution -->
<div class="grid grid-cols-2 lg:grid-cols-5 gap-4 mb-6">
    <?php for($i = 5; $i >= 1; $i--): ?>
    <div class="bg-white rounded-2xl p-4 border border-slate-100 shadow-sm text-center flex flex-col items-center justify-center">
        <div class="text-sm font-bold text-slate-700 mb-1"><?= $i ?> ดาว</div>
        <div class="text-yellow-400 text-lg tracking-widest mb-1" style="font-family: 'Times text', serif;">
            <?= str_repeat('★', $i) ?><span class="text-slate-200"><?= str_repeat('★', 5 - $i) ?></span>
        </div>
        <div class="text-xs text-slate-500 font-medium"><?= number_format($distMap[$i]) ?> รีวิว</div>
    </div>
    <?php endfor; ?>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <form method="get" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
        <div class="md:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ค้นหา (ชื่อ/สินค้า/คอมเมนต์)</label>
            <input type="text" name="q" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50" value="<?= h($q) ?>" placeholder="เช่น ผู้ขายใจดี / เก้าอี้ไม้ / ส่งของไว">
        </div>
        
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">คะแนน</label>
            <select name="score" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50">
                <option value="">ทุกคะแนน</option>
                <?php for($i = 5; $i >= 1; $i--): ?>
                    <option value="<?= $i ?>" <?= $score === (string)$i ? 'selected' : '' ?>><?= $i ?> ดาว</option>
                <?php endfor; ?>
            </select>
        </div>
        
        <div class="md:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ผู้ถูกให้คะแนน</label>
            <select name="rated_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50">
                <option value="0">ทั้งหมด</option>
                <?php foreach($usersList as $u): ?>
                    <option value="<?= (int)$u['user_id'] ?>" <?= $ratedId === (int)$u['user_id'] ? 'selected' : '' ?>>
                        <?= h($u['username']) ?> (#<?= (int)$u['user_id'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="md:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ผู้ให้คะแนน</label>
            <select name="rater_id" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50">
                <option value="0">ทั้งหมด</option>
                <?php foreach($usersList as $u): ?>
                    <option value="<?= (int)$u['user_id'] ?>" <?= $raterId === (int)$u['user_id'] ? 'selected' : '' ?>>
                        <?= h($u['username']) ?> (#<?= (int)$u['user_id'] ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ตั้งแต่</label>
            <input type="date" name="d1" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50" value="<?= h($d1) ?>">
        </div>
        
        <div class="md:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ถึง</label>
            <input type="date" name="d2" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50" value="<?= h($d2) ?>">
        </div>

        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ต่อหน้า</label>
            <select name="per" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-slate-50">
                <?php foreach([20, 50, 100] as $pp): ?>
                    <option value="<?= $pp ?>" <?= $per === $pp ? 'selected' : '' ?>><?= $pp ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="md:col-span-2">
            <button type="submit" class="w-full bg-slate-900 text-white rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-slate-800 transition-colors flex items-center justify-center gap-2">
                <span class="material-symbols-outlined text-[18px]">search</span> ค้นหา
            </button>
        </div>

        <div class="md:col-span-2">
            <a href="admin_user_ratings.php" class="w-full border border-slate-200 text-slate-600 rounded-xl px-4 py-2.5 text-sm font-semibold hover:bg-slate-50 hover:text-slate-900 transition-colors flex items-center justify-center gap-2 flex-col-center">
                <span class="material-symbols-outlined text-[18px]">clear_all</span> ล้าง
            </a>
        </div>
    </form>
</div>

<!-- Main Content -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1200px]">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[12px] text-slate-500 font-bold uppercase tracking-wider">
                    <th class="py-3 px-4 w-16">#</th>
                    <th class="py-3 px-4 w-40">ผู้ให้คะแนน</th>
                    <th class="py-3 px-4 w-40">ผู้ถูกให้คะแนน</th>
                    <th class="py-3 px-4 w-32 text-center">คะแนน</th>
                    <th class="py-3 px-4 w-48">สินค้า</th>
                    <th class="py-3 px-4 w-24">ออเดอร์</th>
                    <th class="py-3 px-4">คอมเมนต์</th>
                    <th class="py-3 px-4 w-32">วันที่</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if(!$rows): ?>
                    <tr><td colspan="8" class="py-12 text-center text-slate-500 font-medium">— ไม่พบรีวิว —</td></tr>
                <?php else: foreach($rows as $r): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-slate-500" valign="top">#<?= (int)$r['rating_id'] ?></td>
                        <td class="py-3 px-4" valign="top">
                            <div class="font-semibold text-slate-900"><?= h($r['rater_username'] ?? ('#'.$r['rater_id'])) ?></div>
                            <div class="text-[11px] text-slate-500">UID: <?= (int)$r['rater_id'] ?></div>
                        </td>
                        <td class="py-3 px-4" valign="top">
                            <div class="font-semibold text-slate-900"><?= h($r['rated_username'] ?? ('#'.$r['rated_user_id'])) ?></div>
                            <div class="text-[11px] text-slate-500">UID: <?= (int)$r['rated_user_id'] ?></div>
                        </td>
                        <td class="py-3 px-4 text-center" valign="top">
                            <div class="text-yellow-400 text-base tracking-widest inline-flex" style="font-family: 'Times text', serif;">
                                <?= str_repeat('★', (int)$r['score']) ?><span class="text-slate-200"><?= str_repeat('★', 5 - (int)$r['score']) ?></span>
                            </div>
                        </td>
                        <td class="py-3 px-4" valign="top">
                            <div class="text-slate-700 line-clamp-2" title="<?= h($r['product_name'] ?? ('#'.$r['product_id'])) ?>">
                                <?= h($r['product_name'] ?? ('#'.$r['product_id'])) ?>
                            </div>
                        </td>
                        <td class="py-3 px-4" valign="top">
                            <span class="inline-flex items-center px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs font-mono font-medium border border-slate-200">
                                #<?= (int)$r['order_id'] ?>
                            </span>
                        </td>
                        <td class="py-3 px-4" valign="top">
                            <p class="text-slate-600 italic whitespace-pre-wrap break-words max-w-md m-0"><?= h($r['comment'] ?? '') ?></p>
                        </td>
                        <td class="py-3 px-4 text-[11px] text-slate-500" valign="top">
                            <?= h(date('d/m/y H:i', strtotime($r['created_at']))) ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <?php if($pages > 1):
        $build = function($p) use ($q,$score,$ratedId,$raterId,$d1,$d2,$per){
            return '?'.http_build_query([
                'q'=>$q, 'score'=>$score, 'rated_id'=>$ratedId, 'rater_id'=>$raterId,
                'd1'=>$d1, 'd2'=>$d2, 'per'=>$per, 'page'=>$p
            ]);
        };
    ?>
    <div class="bg-slate-50 border-t border-slate-100 px-4 py-3 flex flex-col md:flex-row items-center justify-between gap-3">
        <div class="text-xs text-slate-500 font-medium">
            ทั้งหมด <?= number_format($total) ?> รีวิว • หน้า <?= $page ?>/<?= $pages ?>
        </div>
        <div class="flex gap-1">
            <a href="<?= $build($page-1) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?>">
                <span class="material-symbols-outlined text-[18px]">chevron_left</span>
            </a>
            
            <?php for($i = max(1, $page-2); $i <= min($pages, $page+2); $i++): ?>
                <a href="<?= $build($i) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-sm font-medium transition-colors <?= $i === $page ? 'bg-primary text-white border-primary shadow-sm' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 hover:text-slate-900' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <a href="<?= $build($page+1) ?>" class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700 transition-colors <?= $page >= $pages ? 'pointer-events-none opacity-50' : '' ?>">
                <span class="material-symbols-outlined text-[18px]">chevron_right</span>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>
