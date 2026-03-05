<?php
require_once __DIR__ . '/controllers/payments_controller.php';
?>
<?php
$pageTitle = 'ภาพรวมการชำระเงิน';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
    <div>
        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
            <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">payments</span> 
            ภาพรวมการชำระเงิน
        </h3>
        <p class="text-sm text-slate-500">ประเภท: <span class="bg-primary/10 text-primary px-2 py-0.5 rounded-md font-semibold"><?= $tab === 'topup' ? 'เติมเครดิต' : 'ถอนเครดิต' ?></span></p>
    </div>
    <div class="flex items-center gap-2 max-w-xs w-full md:w-auto">
        <select class="w-full md:w-auto border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white" id="switchType">
            <option value="topup" <?= $tab === 'topup' ? 'selected' : '' ?>>เติมเครดิต</option>
            <option value="withdraw" <?= $tab === 'withdraw' ? 'selected' : '' ?>>ถอนเครดิต</option>
        </select>
    </div>
</div>

<!-- Summary Cards -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-sm text-slate-500 font-medium mb-1">จำนวนรายการ</div>
        <div class="text-2xl font-bold text-slate-900"><?= number_format(count($rows)) ?></div>
    </div>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-sm text-slate-500 font-medium mb-1">ยอดรวม (บาท)</div>
        <div class="text-2xl font-bold text-slate-900"><?= number_format($totalAmt, 2) ?></div>
    </div>
    <?php foreach ($sum as $k => $v): ?>
    <div class="bg-white p-5 rounded-2xl border border-slate-100 shadow-sm">
        <div class="text-sm text-slate-500 font-medium mb-1 capitalize">สถานะ: <?= h($k) ?></div>
        <div class="text-2xl font-bold text-slate-900"><?= number_format($v) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Main Content -->
<div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden mb-6">
    <?php if ($tab === 'topup'): ?>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1000px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[12px] text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4 w-16">#</th>
                        <th class="py-3 px-4">ผู้ใช้</th>
                        <th class="py-3 px-4 text-right">จำนวนเงิน</th>
                        <th class="py-3 px-4">ช่องทาง</th>
                        <th class="py-3 px-4">อ้างอิง</th>
                        <th class="py-3 px-4 text-center">สลิป</th>
                        <th class="py-3 px-4">สถานะ</th>
                        <th class="py-3 px-4">ยื่นเมื่อ / อนุมัติเมื่อ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="py-12 text-center text-slate-500 font-medium">— ไม่มีคำขอเติมเครดิต —</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-slate-500">#<?= (int)$r['topup_id'] ?></td>
                        <td class="py-3 px-4 font-semibold text-slate-900"><?= h($r['username'] ?? ('UID '.$r['user_id'])) ?></td>
                        <td class="py-3 px-4 text-right font-bold text-green-600"><?= number_format((float)$r['amount'], 2) ?> ฿</td>
                        <td class="py-3 px-4 text-slate-600"><?= h($r['method'] ?? '-') ?></td>
                        <td class="py-3 px-4"><code class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs font-mono border border-slate-200"><?= h($r['reference_no'] ?? '-') ?></code></td>
                        <td class="py-3 px-4 text-center">
                            <?php if (!empty($r['slip_path'])): ?>
                                <a href="<?= h(slip_href($r['slip_path'])) ?>" target="_blank" class="inline-flex items-center justify-center p-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="ดูสลิป">
                                    <span class="material-symbols-outlined text-[18px]">receipt</span>
                                </a>
                            <?php else: ?>
                                <span class="text-slate-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4">
                            <?php
                            $st = $r['status'];
                            $badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                            if ($st === 'approved' || $st === 'completed') $badgeClass = 'bg-green-50 text-green-700 border-green-200';
                            elseif ($st === 'pending') $badgeClass = 'bg-yellow-50 text-yellow-700 border-yellow-200';
                            elseif ($st === 'rejected') $badgeClass = 'bg-red-50 text-red-700 border-red-200';
                            ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider border <?= $badgeClass ?>">
                                <?= h($st) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-[12px] text-slate-500">
                            <div>ยื่น: <span class="text-slate-600"><?= h($r['created_at']) ?></span></div>
                            <?php if($r['approved_at']): ?> <div class="text-slate-400 mt-0.5">อนุมัติ: <?= h($r['approved_at']) ?></div> <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>

    <?php else: /* withdraw */ ?>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse min-w-[1200px]">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-[12px] text-slate-500 font-bold uppercase tracking-wider">
                        <th class="py-3 px-4 w-16">#</th>
                        <th class="py-3 px-4">ผู้ใช้</th>
                        <th class="py-3 px-4 text-right">จำนวนเงิน</th>
                        <th class="py-3 px-4">บัญชีรับเงิน</th>
                        <th class="py-3 px-4">รายการอ้างอิง</th>
                        <th class="py-3 px-4 text-center">สลิป</th>
                        <th class="py-3 px-4">สถานะ</th>
                        <th class="py-3 px-4">เวลาดำเนินการ</th>
                        <th class="py-3 px-4 w-48 text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!$rows): ?>
                    <tr><td colspan="9" class="py-12 text-center text-slate-500 font-medium">— ไม่มีคำขอถอนเครดิต —</td></tr>
                <?php else: foreach ($rows as $r): ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4 text-slate-500 align-top">#<?= (int)$r['withdraw_id'] ?></td>
                        <td class="py-3 px-4 font-semibold text-slate-900 align-top"><?= h($r['username'] ?? ('UID '.$r['user_id'])) ?></td>
                        <td class="py-3 px-4 text-right font-bold text-red-500 align-top"><?= number_format((float)$r['amount'], 2) ?> ฿</td>
                        <td class="py-3 px-4 align-top">
                            <div class="font-semibold text-slate-700 mb-0.5"><?= h($r['bank_name']) ?></div>
                            <div class="text-slate-600 text-xs font-mono tracking-wide"><?= h($r['bank_account']) ?></div>
                            <div class="text-slate-500 text-[11px] mt-0.5"><?= h($r['account_name']) ?></div>
                        </td>
                        <td class="py-3 px-4 align-top">
                            <div class="text-xs mb-1.5 flex items-center gap-1">
                                <span class="text-slate-400">Ref:</span> <code class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200"><?= h($r['ref_txn'] ?? '-') ?></code>
                            </div>
                            <?php if (!empty($r['trans_ref'])): ?>
                                <div class="text-xs mb-1.5 flex items-center gap-1">
                                    <span class="text-slate-400">TR:</span> <code class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded border border-slate-200"><?= h($r['trans_ref']) ?></code>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($r['verified_at'])): ?>
                                <div class="text-[11px] text-green-600 inline-flex items-center gap-1 mt-1 font-medium bg-green-50 px-2 py-0.5 rounded-full border border-green-100"><span class="material-symbols-outlined text-[14px]">check_circle</span> Verified <?= h(date('d/m/y H:i', strtotime($r['verified_at']))) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($r['reject_reason']) && $r['status']==='rejected'): ?>
                                <div class="text-[11px] text-red-600 mt-1 max-w-[150px] bg-red-50 px-2 py-1 rounded border border-red-100" title="<?= h($r['reject_reason']) ?>">เหตุผล: <?= h($r['reject_reason']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-center align-top">
                            <?php if (!empty($r['slip_path'])): ?>
                                <a href="<?= h(slip_href($r['slip_path'])) ?>" target="_blank" class="inline-flex items-center justify-center p-1.5 bg-blue-50 text-blue-600 rounded-lg hover:bg-blue-100 transition-colors" title="ดูสลิปโอนออก">
                                    <span class="material-symbols-outlined text-[18px]">receipt</span>
                                </a>
                            <?php else: ?>
                                <span class="text-slate-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 align-top">
                            <?php
                            $st = $r['status'];
                            $badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                            if ($st === 'approved' || $st === 'paid') $badgeClass = 'bg-green-50 text-green-700 border-green-200';
                            elseif ($st === 'pending' || $st === 'requested') $badgeClass = 'bg-yellow-50 text-yellow-700 border-yellow-200';
                            elseif ($st === 'rejected') $badgeClass = 'bg-red-50 text-red-700 border-red-200';
                            ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-md text-[11px] font-bold uppercase tracking-wider border <?= $badgeClass ?>">
                                <?= h($st) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-[12px] text-slate-500 align-top">
                            <div class="mb-1">ยื่น: <span class="text-slate-700"><?= h(date('d/m/y H:i', strtotime($r['created_at']))) ?></span></div>
                            <?php if($r['processed_at']): ?> <div class="text-slate-400">ดำเนินการ: <?= h(date('d/m/y H:i', strtotime($r['processed_at']))) ?></div> <?php endif; ?>
                        </td>
                        <td class="py-3 px-4 text-right align-top">
                            <div class="flex flex-col gap-2">
                                <?php if (in_array($r['status'], ['requested','pending','approved'])): ?>
                                    <!-- Unified Approve & Pay Action -->
                                    <div x-data="{ openApprove: false, openReject: false }">
                                        <div class="flex flex-col gap-2">
                                            <button @click="openApprove = !openApprove; openReject = false" class="w-full bg-green-600 hover:bg-green-700 text-white text-[11px] font-bold py-1.5 rounded-lg transition-all flex items-center justify-center gap-1 shadow-sm">
                                                <span class="material-symbols-outlined text-[16px]">payments</span> อนุมัติ & โอนเงิน
                                            </button>
                                            
                                            <button @click="openReject = !openReject; openApprove = false" class="w-full bg-red-50 hover:bg-red-100 text-red-600 text-[11px] font-bold py-1.5 rounded-lg transition-all flex items-center justify-center gap-1 border border-red-100">
                                                <span class="material-symbols-outlined text-[16px]">cancel</span> ปฏิเสธ
                                            </button>
                                        </div>

                                        <!-- Approve & Pay Form -->
                                        <div x-show="openApprove" x-cloak class="mt-2 p-3 bg-green-50 rounded-xl border border-green-100 text-left shadow-inner">
                                            <div class="mb-3 p-2 bg-white rounded-lg border border-green-200">
                                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">โอนไปยัง</p>
                                                <p class="text-xs font-bold text-slate-900"><?= h($r['bank_name']) ?></p>
                                                <p class="text-[11px] font-mono text-blue-600"><?= h($r['bank_account']) ?></p>
                                                <p class="text-[10px] text-slate-500"><?= h($r['account_name']) ?></p>
                                            </div>

                                            <form method="post" action="controllers/withdraw_action_controller.php" enctype="multipart/form-data" class="space-y-2">
                                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="withdraw_id" value="<?= (int)$r['withdraw_id'] ?>">
                                                <input type="hidden" name="action" value="mark_paid">
                                                
                                                <div>
                                                    <label class="block text-[10px] font-bold text-green-700 uppercase tracking-wider mb-1">เลขที่อ้างอิง</label>
                                                    <input type="text" name="trans_ref" class="w-full border border-green-200 rounded-lg px-2.5 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-green-400 bg-white" placeholder="Transaction Ref" required>
                                                </div>
                                                
                                                <div>
                                                    <label class="block text-[10px] font-bold text-green-700 uppercase tracking-wider mb-1">สลิปโอนเงิน</label>
                                                    <input type="file" name="slip" class="w-full text-[11px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[11px] file:font-semibold file:bg-green-100 file:text-green-700 hover:file:bg-green-200 cursor-pointer" accept=".jpg,.jpeg,.png,.pdf" required>
                                                </div>
                                                
                                                <button type="submit" class="w-full bg-green-600 text-white text-xs font-bold py-2 rounded-lg hover:bg-green-700 transition-colors mt-1 flex items-center justify-center gap-1.5">
                                                    <span class="material-symbols-outlined text-[16px]">check_circle</span> ยืนยันการโอน
                                                </button>
                                            </form>
                                        </div>

                                        <!-- Reject Form -->
                                        <div x-show="openReject" x-cloak class="mt-2 p-2 bg-red-50 rounded-lg border border-red-100 text-left">
                                            <form method="post" action="controllers/withdraw_action_controller.php">
                                                <input type="hidden" name="csrf_token" value="<?= h($_SESSION['csrf_token']) ?>">
                                                <input type="hidden" name="withdraw_id" value="<?= (int)$r['withdraw_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <textarea name="reason" class="w-full text-[10px] border border-red-200 rounded p-1 mb-1 focus:outline-none focus:ring-1 focus:ring-red-400" placeholder="เหตุผลที่ปฏิเสธ..." required></textarea>
                                                <button type="submit" class="w-full bg-red-600 text-white text-[10px] py-1 rounded hover:bg-red-700 transition-colors">ยืนยันปฏิเสธ</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <span class="text-slate-300 text-xs">—</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<div class="flex items-center justify-between text-sm text-slate-500 font-medium">
    <div>แสดง <?= number_format(count($rows)) ?> รายการ</div>
</div>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>

<script>
document.getElementById('switchType').addEventListener('change', function(){
  const url = new URL(location.href);
  url.searchParams.set('type', this.value);
  location.href = url.toString();
});
</script>
