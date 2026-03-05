<?php
require_once __DIR__ . '/controllers/dashboard_controller.php';
$pageTitle = 'แดชบอร์ด';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                <span class="material-symbols-outlined">group</span>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">ผู้ใช้ทั้งหมด</p>
                <p class="text-2xl font-black text-slate-900"><?= number_format($totalUsers) ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-orange-50 rounded-2xl flex items-center justify-center text-orange-600">
                <span class="material-symbols-outlined">inventory_2</span>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">สินค้าทั้งหมด</p>
                <p class="text-2xl font-black text-slate-900"><?= number_format($totalProducts) ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center text-green-600">
                <span class="material-symbols-outlined">shopping_cart</span>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">คำสั่งซื้อ</p>
                <p class="text-2xl font-black text-slate-900"><?= number_format($paidOrders) ?></p>
            </div>
        </div>
    </div>
    <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-primary/10 rounded-2xl flex items-center justify-center text-primary-dark">
                <span class="material-symbols-outlined">account_balance_wallet</span>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-500 uppercase tracking-wider">ยอดเงินหมุนเวียน</p>
                <p class="text-2xl font-black text-slate-900">฿<?= number_format($sumPaid, 2) ?></p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Activity Feed -->
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="p-6 border-b border-slate-50 flex items-center justify-between">
                <h3 class="font-bold text-slate-900 flex items-center gap-2">
                    <span class="material-symbols-outlined text-primary">analytics</span>
                    กิจกรรมล่าสุด
                </h3>
            </div>
            <div class="divide-y divide-slate-50">
                <?php if (empty($feed)): ?>
                    <div class="p-12 text-center text-slate-400">
                        <span class="material-symbols-outlined text-4xl mb-2 opacity-20">history</span>
                        <p>ไม่มีกิจกรรมล่าสุด</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($feed as $item): 
                        $icon = 'info';
                        $color = 'text-slate-400 bg-slate-50';
                        switch($item['type']) {
                            case 'user_new': $icon = 'person_add'; $color = 'text-blue-600 bg-blue-50'; break;
                            case 'product_add': $icon = 'add_shopping_cart'; $color = 'text-orange-600 bg-orange-50'; break;
                            case 'order': $icon = 'shopping_bag'; $color = 'text-green-600 bg-green-50'; break;
                            case 'topup': $icon = 'add_circle'; $color = 'text-emerald-600 bg-emerald-50'; break;
                            case 'withdraw': $icon = 'payments'; $color = 'text-red-600 bg-red-50'; break;
                            case 'report': $icon = 'report'; $color = 'text-red-600 bg-red-50'; break;
                        }
                    ?>
                        <div class="p-4 hover:bg-slate-50 transition-colors flex items-start gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 <?= $color ?>">
                                <span class="material-symbols-outlined text-[20px]"><?= $icon ?></span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-slate-900 font-medium">
                                    <span class="font-bold text-slate-900"><?= htmlspecialchars($item['username']) ?></span>
                                    <?= htmlspecialchars($item['action']) ?>
                                </p>
                                <p class="text-xs text-slate-400 mt-0.5"><?= date('d M Y • H:i', strtotime($item['ts'])) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions / Alerts -->
    <div class="space-y-6">
        <?php if ($pendingBankCount > 0): ?>
            <div class="bg-yellow-50 border border-yellow-200 rounded-3xl p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-yellow-600 border border-yellow-100 shadow-sm shrink-0">
                        <span class="material-symbols-outlined">account_balance</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-yellow-900 mb-1">รอยืนยันบัญชีธนาคาร</h4>
                        <p class="text-sm text-yellow-700 mb-4 items-stretch leading-relaxed">มีผู้ใช้ <?= $pendingBankCount ?> รายที่ต้องการให้ยืนยันบัญชีธนาคารเพื่อถอนเงิน</p>
                        <a href="bank_verifications.php" class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-500 text-slate-900 font-bold py-2 px-4 rounded-xl text-sm transition-all shadow-sm active:scale-95">
                            จัดการคำขอ
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($openReports > 0): ?>
            <div class="bg-red-50 border border-red-200 rounded-3xl p-6 shadow-sm">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 bg-white rounded-2xl flex items-center justify-center text-red-600 border border-red-100 shadow-sm shrink-0">
                        <span class="material-symbols-outlined">flag</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-red-900 mb-1">รายงานที่ต้องตรวจสอบ</h4>
                        <p class="text-sm text-red-700 mb-4 items-stretch leading-relaxed">มีรายการที่ถูกรายงาน/ร้องเรียนทั้งหมด <?= $openReports ?> รายการ</p>
                        <a href="reports.php" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-xl text-sm transition-all shadow-sm active:scale-95">
                            ไปที่ระบบรายงาน
                            <span class="material-symbols-outlined text-[18px]">chevron_right</span>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="bg-white rounded-3xl border border-slate-100 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary">speed</span>
                ทางลัดระบบ
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <a href="payments.php?type=withdraw" class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 text-center transition-all group">
                    <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors block mb-1">payments</span>
                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">ถอนเงิน</span>
                </a>
                <a href="payments.php?type=topup" class="p-3 rounded-2xl bg-slate-50 hover:bg-slate-100 text-center transition-all group">
                    <span class="material-symbols-outlined text-slate-400 group-hover:text-primary transition-colors block mb-1">account_balance_wallet</span>
                    <span class="text-[11px] font-bold text-slate-600 uppercase tracking-wider">เติมเงิน</span>
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>
