<?php
// admin/layouts/admin_topbar.php
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
?>
<!-- Main Content Wrapper -->
<div class="flex-1 flex flex-col min-w-0 overflow-hidden bg-slate-50/50">
    
    <!-- Topbar -->
    <header class="bg-white h-16 border-b border-slate-200 flex items-center justify-between px-4 sm:px-6 lg:px-8 z-10 flex-shrink-0">
        
        <!-- Left: Hamburger & Title -->
        <div class="flex items-center gap-4">
            <button onclick="toggleSidebar()" class="lg:hidden w-10 h-10 flex items-center justify-center rounded-xl bg-slate-50 text-slate-600 hover:bg-slate-100 transition-colors active:scale-95">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <h1 class="text-[17px] sm:text-lg font-bold text-slate-800 tracking-tight truncate"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>

        <!-- Right: Profile -->
        <div class="flex items-center gap-3">
            <a href="../index.php" target="_blank" class="hidden sm:flex items-center gap-2 px-3 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 rounded-lg transition-colors">
                <span class="material-symbols-outlined text-[18px]">open_in_new</span>
                <span>ไปหน้าแรก</span>
            </a>
            <div class="w-px h-6 bg-slate-200 mx-1 hidden sm:block"></div>
            <div class="flex items-center gap-2">
                <div class="text-right hidden sm:block">
                    <div class="text-[13px] font-bold text-slate-900 leading-tight"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                    <div class="text-[11px] text-slate-500 font-medium">Administrator</div>
                </div>
                <div class="w-10 h-10 rounded-full bg-slate-100 flex items-center justify-center border border-slate-200 text-slate-500 font-bold overflow-hidden shadow-sm">
                    <?php if (!empty($_SESSION['img']) && $_SESSION['img'] !== 'default.png'): ?>
                        <img src="../uploads/avatars/<?= htmlspecialchars($_SESSION['img']) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                        <?= mb_strtoupper(mb_substr($_SESSION['username'] ?? 'A', 0, 1)) ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </header>

    <!-- Main Scrollable Area -->
    <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
