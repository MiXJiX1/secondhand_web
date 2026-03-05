<?php
// admin/layouts/admin_sidebar.php
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);

// Count pending bank verifications for notification badge
if (isset($pdo)) {
    $stBankCount = $pdo->query("SELECT COUNT(*) as count FROM users WHERE bank_account IS NOT NULL AND bank_verified = 0");
    $pendingBankCount = (int)($stBankCount->fetch()['count'] ?? 0);
} else {
    $pendingBankCount = 0;
}

$adminBase = ($baseUrl ?? '') . '/admin';

$menuItems = [
    ['icon' => 'dashboard',        'title' => 'แดชบอร์ด',           'url' => $adminBase . '/dashboard'],
    ['icon' => 'group',            'title' => 'จัดการผู้ใช้งาน',      'url' => $adminBase . '/users'],
    ['icon' => 'inventory_2',      'title' => 'จัดการสินค้า',         'url' => $adminBase . '/products'],
    ['icon' => 'category',         'title' => 'จัดการหมวดหมู่',       'url' => $adminBase . '/categories'],
    ['icon' => 'payments',         'title' => 'การชำระเงิน',          'url' => $adminBase . '/payments'],
    ['icon' => 'account_balance',  'title' => 'ยืนยันบัญชีธนาคาร',    'url' => $adminBase . '/bank-verifications', 'badge' => $pendingBankCount],
    ['icon' => 'bar_chart',        'title' => 'สถิติระบบ',             'url' => $adminBase . '/stats'],
    ['icon' => 'star_rate',        'title' => 'คะแนนผู้ใช้',           'url' => $adminBase . '/user-ratings'],
    ['icon' => 'support_agent',    'title' => 'ติดต่อผู้ดูแล',         'url' => $adminBase . '/support-tickets'],
    ['icon' => 'gavel',            'title' => 'คำร้องปลดแบน',         'url' => $adminBase . '/ban-appeals'],
    ['icon' => 'flag',             'title' => 'รายงานผู้ใช้',          'url' => $adminBase . '/abuse-reports'],
];
?>

<!-- Mobile Sidebar Overlay (Hidden by default) -->
<div id="mobile-overlay" class="fixed inset-0 bg-slate-900/50 z-20 hidden lg:hidden" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside id="sidebar" class="fixed inset-y-0 left-0 bg-white w-[260px] border-r border-slate-200 z-30 transform -translate-x-full lg:translate-x-0 lg:static lg:block transition-transform duration-300 flex flex-col shadow-sm lg:shadow-none">
    
    <!-- Logo area -->
    <div class="h-16 flex items-center px-6 border-b border-slate-100 flex-shrink-0">
        <a href="<?= $adminBase ?>/dashboard" class="flex items-center gap-3">
            <div class="w-8 h-8 bg-primary rounded-xl flex items-center justify-center font-bold text-slate-900 text-lg">M</div>
            <span class="font-bold text-lg tracking-tight text-slate-800">Admin Panel</span>
        </a>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
        <?php foreach ($menuItems as $item): 
            $isActive = rtrim($currentPath, '/') === rtrim(parse_url($item['url'], PHP_URL_PATH), '/');
            $activeClass = $isActive ? 'bg-primary/10 text-slate-900 font-semibold' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900';
            $iconClass = $isActive ? 'text-primary' : 'text-slate-400';
        ?>
            <a href="<?= htmlspecialchars($item['url']) ?>" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-colors <?= $activeClass ?>">
                <span class="material-symbols-outlined <?= $iconClass ?> text-[20px]" <?= $isActive ? 'style="font-variation-settings:\'FILL\' 1;"' : '' ?>><?= $item['icon'] ?></span>
                <span class="text-sm flex-1"><?= htmlspecialchars($item['title']) ?></span>
                <?php if (isset($item['badge']) && $item['badge'] > 0): ?>
                    <span class="bg-red-500 text-white text-[10px] font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center"><?= $item['badge'] ?></span>
                <?php endif; ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Bottom Action -->
    <div class="p-4 border-t border-slate-100">
        <a href="<?= ($baseUrl ?? '') ?>/logout" class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-red-600 hover:bg-red-50 hover:text-red-700 transition-colors">
            <span class="material-symbols-outlined text-[20px]">logout</span>
            <span class="text-sm font-semibold">ออกจากระบบ</span>
        </a>
    </div>
</aside>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        
        if (sidebar.classList.contains('-translate-x-full')) {
            sidebar.classList.remove('-translate-x-full');
            overlay.classList.remove('hidden');
        } else {
            sidebar.classList.add('-translate-x-full');
            overlay.classList.add('hidden');
        }
    }
</script>
