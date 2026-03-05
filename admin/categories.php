<?php
require_once __DIR__ . '/controllers/categories_controller.php';
?>
<?php
$pageTitle = 'จัดการหมวดหมู่';
require_once __DIR__ . '/layouts/admin_header.php';
require_once __DIR__ . '/layouts/admin_sidebar.php';
require_once __DIR__ . '/layouts/admin_topbar.php';
?>

<!-- Flash -->
<?php if ($flash): ?>
    <div class="mb-6 p-4 rounded-xl flex items-center gap-3 <?= $flash['t'] === 'success' ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' ?>">
        <span class="material-symbols-outlined"><?= $flash['t'] === 'success' ? 'check_circle' : 'error' ?></span>
        <?= htmlspecialchars($flash['m']) ?>
    </div>
<?php endif; ?>

<!-- Header & Add Form -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5 mb-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
        <div>
            <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                <span class="material-symbols-outlined text-primary" style="font-variation-settings: 'FILL' 1;">folder</span> 
                จัดการหมวดหมู่
            </h3>
            <p class="text-sm text-slate-500">หมวดหมู่ทั้งหมด <strong class="text-slate-700"><?= number_format(count($cats)) ?></strong> รายการ</p>
        </div>
        <a href="products.php" class="inline-flex items-center justify-center gap-2 bg-slate-100 text-slate-700 rounded-xl px-4 py-2 text-sm font-semibold hover:bg-slate-200 transition-colors">
            <span class="material-symbols-outlined text-[18px]">inventory_2</span> 
            จัดการสินค้า
        </a>
    </div>

    <form method="post" class="bg-slate-50 p-4 rounded-xl border border-slate-100">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
        <input type="hidden" name="action" value="create">
        
        <div class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
            <div class="md:col-span-8">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ชื่อหมวดใหม่</label>
                <input type="text" name="name" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white" placeholder="เช่น gadgets, books, services" required>
            </div>
            <div class="md:col-span-2">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wide mb-1">ลำดับ</label>
                <input type="number" name="sort" class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white" value="0">
            </div>
            <div class="md:col-span-2">
                <button type="submit" class="w-full bg-slate-900 text-white rounded-xl px-4 py-2.5 text-sm font-semibold flex items-center justify-center gap-2 hover:bg-slate-800 transition-colors">
                    <span class="material-symbols-outlined text-[18px]">add</span> เพิ่ม
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Category List -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden mb-6">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[800px]">
            <thead>
                <tr class="bg-slate-50 border-b border-slate-100 text-[12px] text-slate-500 font-bold uppercase tracking-wider">
                    <th class="py-3 px-4 w-1/3">ชื่อหมวด</th>
                    <th class="py-3 px-4">Slug</th>
                    <th class="py-3 px-4 w-24">ลำดับ</th>
                    <th class="py-3 px-4">จำนวนสินค้า</th>
                    <th class="py-3 px-4 w-32 text-right">จัดการ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 text-sm">
                <?php if(!$cats): ?>
                    <tr><td colspan="5" class="py-12 text-center text-slate-500 font-medium">— ยังไม่มีหมวดหมู่ —</td></tr>
                <?php else: foreach($cats as $c): ?>
                    <tr class="hover:bg-slate-50 transition-colors group">
                        <form method="post" onsubmit="return true;">
                            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            
                            <td class="py-3 px-4">
                                <input type="text" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-900 font-semibold mb-1" name="name" value="<?= htmlspecialchars($c['name']) ?>" required>
                                <div class="text-[11px] text-slate-400">สร้างเมื่อ: <span class="text-slate-500"><?= htmlspecialchars($c['created_at'] ?? '') ?></span></div>
                            </td>
                            <td class="py-3 px-4">
                                <code class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-xs border border-slate-200 font-mono"><?= htmlspecialchars($c['slug']) ?></code>
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary/50 text-slate-700 bg-white" name="sort" value="<?= (int)$c['sort_order'] ?>">
                            </td>
                            <td class="py-3 px-4">
                                <div class="inline-flex items-center gap-1.5 bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg border border-blue-200 font-bold text-xs">
                                    <span class="material-symbols-outlined text-[14px]">inventory_2</span>
                                    <?= number_format((int)$c['items']) ?>
                                </div>
                            </td>
                            <td class="py-3 px-4 text-right align-top pt-4">
                                <div class="flex items-center justify-end gap-2">
                                    <button type="submit" name="action" value="update" class="p-1.5 bg-slate-100 text-slate-600 hover:bg-slate-200 hover:text-slate-900 rounded-lg transition-colors" title="บันทึก">
                                        <span class="material-symbols-outlined text-[18px]">save</span>
                                    </button>
                                    
                                    <?php if (($c['name'] ?? '') !== 'others' && ($c['slug'] ?? '') !== 'others'): ?>
                                        <button type="submit" name="action" value="delete" class="p-1.5 bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 rounded-lg transition-colors" onclick="return confirm('ลบหมวด “<?= htmlspecialchars($c['name']) ?>”?\nสินค้าจะถูกย้ายไปหมวด others อัตโนมัติ');" title="ลบ">
                                            <span class="material-symbols-outlined text-[18px]">delete</span>
                                        </button>
                                    <?php else: ?>
                                        <button type="button" class="p-1.5 bg-slate-50 text-slate-400 rounded-lg opacity-50 cursor-not-allowed" disabled title="ห้ามลบหมวดนี้">
                                            <span class="material-symbols-outlined text-[18px]">lock</span>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <div class="bg-slate-50 border-t border-slate-100 p-3 text-xs text-slate-500 font-medium">
        <span class="material-symbols-outlined text-[14px] inline-block align-text-bottom mr-1">info</span>
        ถ้าแก้ชื่อหมวด ระบบจะอัปเดตสินค้าในหมวดนั้นให้เป็นชื่อใหม่อัตโนมัติ
    </div>
</div>

<?php require_once __DIR__ . '/layouts/admin_footer.php'; ?>
