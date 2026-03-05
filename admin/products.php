<?php
require_once __DIR__ . '/controllers/products_controller.php';
?>
<?php
$pageTitle = 'จัดการสินค้า';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Flash -->
<?php if ($flash): ?>
    <div class="mb-4 p-4 rounded-xl <?= $flash['type'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : ($flash['type'] === 'danger' ? 'bg-red-50 text-red-700 border border-red-200' : 'bg-yellow-50 text-yellow-700 border border-yellow-200') ?>">
        <?= htmlspecialchars($flash['msg']) ?>
    </div>
<?php endif; ?>

<!-- Header & Filters -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-4">
        <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">inventory_2</span> 
                จัดการสินค้า
            </h3>
            <p class="text-sm text-slate-500">แคตตาล็อกสินค้าทั้งหมด <strong class="text-slate-700"><?= number_format($total) ?></strong> รายการ</p>
        </div>
    </div>
    
    <hr class="border-slate-100 my-4">

    <form class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end" method="get">
        <div class="md:col-span-4">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ค้นหาชื่อสินค้า</label>
            <input type="text" name="q" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700" placeholder="พิมพ์ชื่อสินค้า..." value="<?= htmlspecialchars($q) ?>">
        </div>
        <div class="md:col-span-3">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">หมวดหมู่</label>
            <select name="category" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white">
                <option value="">ทุกหมวดหมู่</option>
                <?php foreach ($categories as $c): ?>
                    <option value="<?= htmlspecialchars($c) ?>" <?= $cat===$c?'selected':'' ?>><?= htmlspecialchars($c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">สถานะ</label>
            <select name="status" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white">
                <option value="">ทุกสถานะ</option>
                <?php foreach ($statuses as $k=>$v): ?>
                    <option value="<?= $k ?>" <?= $stat===$k?'selected':'' ?>><?= $v ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="md:col-span-2">
            <button class="w-full bg-slate-900 text-white rounded-xl px-4 py-2.5 text-sm font-semibold flex items-center justify-center gap-2 hover:bg-slate-800 transition-colors">
                <span class="material-symbols-outlined text-[18px]">search</span> ค้นหา
            </button>
        </div>
        <div class="md:col-span-1">
            <a href="?" class="w-full bg-slate-100 text-slate-600 rounded-xl px-4 py-2.5 text-sm font-semibold flex items-center justify-center hover:bg-slate-200 transition-colors">ล้าง</a>
        </div>
    </form>
</div>

<!-- Mobile Cards -->
<div class="block lg:hidden space-y-4 mb-6">
    <?php if (!$products): ?>
        <div class="bg-white rounded-2xl border border-slate-100 p-8 text-center text-slate-500 text-sm">— ไม่พบสินค้า —</div>
    <?php else: foreach ($products as $p):
        $imgFn  = firstImageFromField($p['product_image']);
        $imgSrc = $imgFn ? "../uploads/".rawurlencode($imgFn) : "../assets/img/no-image.png";
        
        $badgeClass = '';
        if ($p['status'] === 'active') $badgeClass = 'bg-green-100 text-green-700 border-green-200';
        elseif ($p['status'] === 'sold') $badgeClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
        elseif ($p['status'] === 'hidden') $badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
    ?>
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
            <div class="flex gap-4">
                <div class="w-20 h-20 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0">
                    <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='../assets/img/no-image.png';">
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex justify-between items-start mb-1">
                        <div class="truncate pr-2 font-bold text-slate-900"><?= htmlspecialchars($p['product_name']) ?></div>
                        <span class="px-2 py-0.5 rounded-md border text-[10px] font-bold uppercase shrink-0 <?= $badgeClass ?>"><?= htmlspecialchars($statuses[$p['status']] ?? $p['status']) ?></span>
                    </div>
                    <div class="text-xs text-slate-500 mb-0.5">หมวด: <?= htmlspecialchars($p['category']) ?> • ID: <?= (int)$p['product_id'] ?></div>
                    <div class="text-xs text-slate-500 mb-1">โดย: <strong class="text-slate-700"><?= htmlspecialchars($p['username'] ?? '—') ?></strong></div>
                    <?php if ($p['status']==='sold' && !empty($p['sold_at'])): ?>
                        <div class="text-[10px] text-green-600 font-medium bg-green-50 inline-block px-1.5 py-0.5 rounded mb-1">ปิดการขาย: <?= htmlspecialchars($p['sold_at']) ?></div>
                    <?php endif; ?>
                    <div class="flex items-center justify-between mt-2">
                        <div class="font-bold text-slate-900 text-sm">฿<?= number_format((float)$p['product_price'], 2) ?></div>
                        
                        <div class="relative">
                            <button type="button" onclick="toggleDropdown(this, event)" class="manage-btn p-1.5 bg-slate-50 text-slate-600 rounded-lg border border-slate-200 hover:bg-slate-100 transition-colors flex items-center justify-center">
                                <span class="material-symbols-outlined text-[18px]">more_vert</span>
                            </button>
                            <!-- Dropdown -->
                            <div class="dropdown-menu hidden absolute right-0 bottom-full mb-2 w-36 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50 text-left">
                                <form method="post" onsubmit="return confirm('ยืนยันลบสินค้านี้?')">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>">
                                    <button class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium flex items-center gap-2" type="submit">
                                        <span class="material-symbols-outlined text-[18px]">delete</span> ลบ
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; endif; ?>
</div>

<!-- Desktop Table -->
<div class="hidden lg:block bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[12px] text-slate-500 font-bold uppercase tracking-wider">
                    <th class="py-3 px-4 w-16">รูป</th>
                    <th class="py-3 px-4 w-1/3">ชื่อสินค้า</th>
                    <th class="py-3 px-4">หมวดหมู่</th>
                    <th class="py-3 px-4 text-right">ราคา</th>
                    <th class="py-3 px-4">ผู้โพสต์</th>
                    <th class="py-3 px-4">สถานะ</th>
                    <th class="py-3 px-4 w-16 text-center">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if (!$products): ?>
                    <tr><td colspan="7" class="py-12 text-center text-slate-500 font-medium">— ไม่พบสินค้า —</td></tr>
                <?php else: foreach ($products as $p):
                    $imgFn  = firstImageFromField($p['product_image']);
                    $imgSrc = $imgFn ? "../uploads/".rawurlencode($imgFn) : "../assets/img/no-image.png";
                    
                    $badgeClass = '';
                    if ($p['status'] === 'active') $badgeClass = 'bg-green-100 text-green-700 border-green-200';
                    elseif ($p['status'] === 'sold') $badgeClass = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                    elseif ($p['status'] === 'hidden') $badgeClass = 'bg-slate-100 text-slate-600 border-slate-200';
                ?>
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="py-3 px-4">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 border border-slate-200 overflow-hidden shrink-0 shadow-sm">
                                <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='../assets/img/no-image.png';">
                            </div>
                        </td>
                        <td class="py-3 px-4">
                            <div class="font-bold text-slate-900 mb-0.5"><?= htmlspecialchars($p['product_name']) ?></div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">ID: <?= (int)$p['product_id'] ?></span>
                                <?php if ($p['status']==='sold' && !empty($p['sold_at'])): ?>
                                    <span class="text-[10px] text-green-700 font-bold bg-green-100 border border-green-200 px-1.5 py-0.5 rounded">ปิดขาย: <?= htmlspecialchars($p['sold_at']) ?></span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="py-3 px-4 text-slate-600 font-medium"><?= htmlspecialchars($p['category']) ?></td>
                        <td class="py-3 px-4 text-right">
                            <span class="font-bold text-slate-900"><?= number_format((float)$p['product_price'], 2) ?></span>
                            <span class="text-xs text-slate-400 ml-0.5 font-mono">฿</span>
                        </td>
                        <td class="py-3 px-4 font-semibold text-slate-700"><?= htmlspecialchars($p['username'] ?? '—') ?></td>
                        <td class="py-3 px-4">
                            <span class="px-2 py-1 rounded-lg border text-[11px] font-bold uppercase tracking-wider <?= $badgeClass ?>">
                                <?= htmlspecialchars($statuses[$p['status']] ?? $p['status']) ?>
                            </span>
                        </td>
                        <td class="py-3 px-4 text-center relative">
                            <button type="button" onclick="toggleDropdown(this, event)" class="manage-btn p-1.5 text-slate-400 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors">
                                <span class="material-symbols-outlined text-[20px]">more_vert</span>
                            </button>
                            <!-- Dropdown -->
                            <div class="dropdown-menu hidden absolute right-8 top-10 w-36 bg-white rounded-xl shadow-lg border border-slate-100 py-1 z-50 text-left">
                                <form method="post" onsubmit="return confirm('ยืนยันลบสินค้านี้?')">
                                    <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf']) ?>">
                                    <input type="hidden" name="action" value="delete_product">
                                    <input type="hidden" name="id" value="<?= (int)$p['product_id'] ?>">
                                    <button class="w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium flex items-center gap-2" type="submit">
                                        <span class="material-symbols-outlined text-[18px]">delete</span> ลบ
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
    <div class="flex items-center justify-between">
        <div class="text-sm text-slate-500 hidden sm:block">
            แสดงหน้า <span class="font-bold text-slate-900"><?= $page ?></span> จาก <span class="font-bold text-slate-900"><?= $pages ?></span>
        </div>
        
        <?php
            $build = function($p) use ($q,$cat,$stat) {
                $params = http_build_query(['q'=>$q,'category'=>$cat,'status'=>$stat,'page'=>$p]);
                return "?$params";
            };
        ?>
        <nav class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl p-1 shadow-sm w-full sm:w-auto justify-center">
            <a href="<?= $page<=1 ? '#' : $build($page-1) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg <?= $page<=1 ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">
                <span class="material-symbols-outlined text-[20px]">chevron_left</span>
            </a>
            
            <?php for($i=max(1,$page-2); $i<=min($pages,$page+2); $i++): ?>
                <a href="<?= $build($i) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg text-sm font-semibold transition-colors <?= $i===$page ? 'bg-primary text-slate-900' : 'text-slate-600 hover:bg-slate-100' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
            
            <a href="<?= $page>=$pages ? '#' : $build($page+1) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg <?= $page>=$pages ? 'text-slate-300 pointer-events-none' : 'text-slate-600 hover:bg-slate-100' ?> transition-colors">
                <span class="material-symbols-outlined text-[20px]">chevron_right</span>
            </a>
        </nav>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>

<script>
    // ---------- Dropdown Logic ----------
    let activeDropdown = null;
    function toggleDropdown(button, event) {
        event.stopPropagation();
        const dropdown = button.nextElementSibling;
        
        if (activeDropdown && activeDropdown !== dropdown) {
            activeDropdown.classList.add('hidden');
        }
        
        dropdown.classList.toggle('hidden');
        activeDropdown = dropdown.classList.contains('hidden') ? null : dropdown;
    }
    
    document.addEventListener('click', (e) => {
        if (activeDropdown && !activeDropdown.contains(e.target) && !e.target.closest('.manage-btn')) {
            activeDropdown.classList.add('hidden');
            activeDropdown = null;
        }
    });
</script>
