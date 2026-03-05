<?php
// includes/product_grid_partial.php
if ($rsActive && $rsActive->num_rows > 0) {
    while ($row = $rsActive->fetch_assoc()) {
        $p_id = (int)$row['product_id'];
        $u_id = (int)($row['user_id'] ?? $row['owner_id'] ?? 0);
        $isOwner = ($currentUserId > 0 && $u_id === $currentUserId);
        
        $firstImg = firstImageFromField($row['product_image']);
        $imgSrc = $firstImg ? $baseUrl . '/uploads/' . $firstImg : $baseUrl . '/assets/no-image.png';
        ?>
        <a href="<?= $baseUrl ?>/product/<?= $p_id ?>" class="group bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 hover:shadow-2xl hover:shadow-primary/10 hover:-translate-y-1 transition-all duration-500 cursor-pointer block">
            <div class="relative aspect-[4/5] overflow-hidden">
                <?php if ($isOwner): ?>
                <div class="absolute top-4 left-4 z-20 bg-primary text-slate-900 px-4 py-1.5 rounded-full text-[11px] font-black border-2 border-white/50 shadow-xl flex items-center gap-1.5 animate-pulse transition-transform group-hover:scale-105">
                    <span class="material-symbols-outlined text-[14px] fill-1">person_check</span>
                    สินค้าของคุณ
                </div>
                <?php endif; ?>
                
                <img src="<?= h($imgSrc) ?>" 
                     class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-700"
                     onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/no-image.png';"
                     alt="<?= h($row['product_name']) ?>">
                
                <div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-500 flex flex-col justify-end p-6">
                    <span class="text-primary font-bold text-sm">ดูรายละเอียด</span>
                </div>
            </div>
            
            <div class="p-5 flex flex-col gap-2">
                <div class="flex items-center gap-2">
                    <span class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 text-xs font-bold text-slate-500 dark:text-slate-400 rounded uppercase tracking-wider">
                        <?= h($row['category_name'] ?? $row['category'] ?? 'ทั่วไป') ?>
                    </span>
                </div>
                
                <h3 class="font-bold text-slate-900 dark:text-white line-clamp-1 group-hover:text-primary transition-colors text-lg">
                    <?= h($row['product_name']) ?>
                </h3>
                
                <div class="flex items-baseline gap-1 mt-1">
                    <span class="text-3xl font-black text-slate-900 dark:text-white tracking-tighter">
                        <?= formatPrice($row['product_price']) ?>
                    </span>
                </div>
                
                <?php if (!empty($row['location_name'])): ?>
                    <div class="flex items-center gap-1.5 text-slate-400 mt-1">
                        <span class="material-symbols-outlined text-[18px]">location_on</span>
                        <span class="text-sm font-medium truncate"><?= h($row['location_name']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </a>
        <?php
    }
} else {
    ?>
    <div class="col-span-full py-20 flex flex-col items-center justify-center text-center bg-white dark:bg-slate-900 rounded-3xl border-2 border-dashed border-slate-200 dark:border-slate-800">
        <div class="w-16 h-16 bg-slate-100 dark:bg-slate-800 rounded-full flex items-center justify-center mb-4">
            <span class="material-symbols-outlined text-slate-400 text-3xl">inventory_2</span>
        </div>
        <h3 class="text-lg font-bold text-slate-900 dark:text-white">ไม่พบสินค้าที่คุณต้องการ</h3>
        <p class="text-slate-500">ลองเปลี่ยนคำค้นหาหรือตัวกรองดูนะครับ</p>
    </div>
    <?php
}
?>
