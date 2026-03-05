<?php
require_once __DIR__ . '/controllers/my_products_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สินค้าของฉัน | Midnight Premium</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: { primary: "#f9e71f" },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased min-h-screen flex flex-col">

<!-- Top Navbar -->
<?php 
$current_page = basename($_SERVER['PHP_SELF']);
include __DIR__ . '/../includes/navbar_main.php'; 
?>

<!-- Main Content -->
<main class="flex-1 w-full max-w-7xl mx-auto px-6 py-8">
    
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">สินค้าของคุณ</h1>
            <p class="text-slate-500 mt-1 uppercase text-xs font-semibold tracking-wider">จัดการรายการลงขายทั้งหมดที่นี่</p>
        </div>
        <a href="sell.php" class="inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 px-6 rounded-full transition-colors shadow-sm whitespace-nowrap">
            <span class="material-symbols-outlined text-[18px]">add</span> ลงขายสินค้าใหม่
        </a>
    </div>

    <?php if (empty($rows)): ?>
        <!-- Empty State -->
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-sm flex flex-col items-center justify-center min-h-[50vh]">
            <div class="w-24 h-24 bg-primary/20 text-yellow-600 rounded-full flex items-center justify-center mb-6">
                <span class="material-symbols-outlined text-5xl">inventory_2</span>
            </div>
            <h2 class="text-2xl font-bold text-slate-900 mb-2">ยังไม่มีสินค้าในร้านของคุณ</h2>
            <p class="text-slate-500 mb-8 max-w-md">เริ่มสร้างรายได้จากการขายสินค้าที่คุณไม่ได้ใช้แล้ววันนี้! ลงขายง่ายๆ เพียงไม่กี่ขั้นตอน</p>
            <a href="sell.php" class="bg-primary hover:bg-yellow-400 text-slate-900 font-bold py-3 px-8 rounded-full transition-colors shadow-md hover:shadow-lg inline-flex items-center gap-2">
                <span class="material-symbols-outlined">add_circle</span> ลงขายสินค้าชิ้นแรก
            </a>
        </div>
    <?php else: ?>
        <!-- Products Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-6">
            <?php foreach ($rows as $row): 
                $first = firstImageFromField($row['product_image']);
                $imgSrc = $first ? '/uploads/'.$first : '/assets/no-image.png';
                $isSold = ($row['status'] === 'sold');
            ?>
            <div class="group bg-white rounded-2xl overflow-hidden border border-slate-200 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col relative <?= $isSold ? 'opacity-80' : '' ?>">
                
                <?php if ($isSold): ?>
                    <div class="absolute top-3 left-3 bg-red-600 font-bold text-white text-[10px] uppercase tracking-wider px-2 py-1 rounded shadow z-10">SOLD OUT</div>
                <?php endif; ?>

                <div class="w-full aspect-[4/3] bg-slate-100 overflow-hidden relative">
                    <img src="<?= h($imgSrc) ?>" alt="<?= h($row['product_name']) ?>" onerror="this.onerror=null; this.src='/assets/default.png';" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 <?= $isSold ? 'grayscale' : '' ?>">
                    
                    <!-- Hover Action Overlay -->
                    <div class="absolute inset-0 bg-slate-900/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-3 backdrop-blur-sm <?= $isSold ? 'hidden' : '' ?>">
                        <a href="edit_product.php?id=<?= (int)$row['product_id'] ?>" class="w-10 h-10 bg-white rounded-full flex items-center justify-center text-slate-900 hover:text-primary transition-colors tooltip tooltip-top" title="แก้ไข">
                            <span class="material-symbols-outlined">edit</span>
                        </a>
                        <form action="delete_product.php" method="POST" onsubmit="return confirm('ยืนยันการลบที่กู้คืนไม่ได้?');" class="inline">
                            <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
                            <input type="hidden" name="product_id" value="<?= (int)$row['product_id'] ?>">
                            <button type="submit" class="w-10 h-10 bg-red-500 hover:bg-red-600 rounded-full flex items-center justify-center text-white transition-colors tooltip tooltip-top" title="ลบสินค้า">
                                <span class="material-symbols-outlined">delete</span>
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="p-4 flex flex-col flex-1">
                    <div class="flex justify-between items-start mb-2 gap-2">
                        <span class="text-[10px] px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 font-medium whitespace-nowrap"><?= h($row['category'] ?: 'ไม่มีหมวดหมู่') ?></span>
                        <div class="font-bold text-slate-900 bg-primary/20 text-yellow-700 px-2 py-0.5 rounded text-xs whitespace-nowrap">฿<?= number_format((float)$row['product_price'], 2) ?></div>
                    </div>
                    <?php if (!empty($row['location_name'])): ?>
                        <div class="flex items-center gap-1 text-slate-400 mb-1">
                            <span class="material-symbols-outlined text-[16px]">location_on</span>
                            <span class="text-sm font-medium truncate"><?= h($row['location_name']) ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <h3 class="font-bold text-sm text-slate-900 line-clamp-2 leading-snug flex-1 mb-3 group-hover:text-primary transition-colors">
                        <?= h($row['product_name']) ?>
                    </h3>
                    
                    <a href="product_detail.php?id=<?= (int)$row['product_id'] ?>" class="w-full block text-center bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold py-2 rounded-lg transition-colors">ดูหน้ารายละเอียด</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</main>

<script>
    // Tooltip logic can be added here if needed, or rely on native titles.
</script>
</body>
</html>
