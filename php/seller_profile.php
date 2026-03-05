<?php
require_once __DIR__ . '/controllers/seller_profile_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ขาย: <?= htmlspecialchars($fullName) ?> | Midnight Premium</title>
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
<main class="flex-1 w-full max-w-5xl mx-auto px-6 py-8">
    
    <!-- Profile Header Section -->
    <div class="bg-white rounded-3xl p-8 border border-slate-200 shadow-sm mb-8">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-6 pb-8 border-b border-slate-100 relative">
            
            <!-- User Identity -->
            <div class="flex items-center gap-6">
                <!-- Avatar -->
                <div class="relative w-28 h-28 flex-shrink-0">
                    <div class="w-full h-full rounded-full ring-4 ring-primary/20 overflow-hidden bg-slate-100 flex items-center justify-center border-2 border-white shadow-sm">
                        <?php if ($avatarPath !== $baseUrl . '/assets/no-avatar.png'): ?>
                            <img src="<?= htmlspecialchars($avatarPath) ?>" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/no-avatar.png';">
                        <?php else: ?>
                            <span class="text-4xl">🙂</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Info -->
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-1">
                        <h1 class="text-3xl font-bold text-slate-900 tracking-tight mr-1"><?= htmlspecialchars($fullName) ?></h1>
                        <?php if (isset($seller['role']) && $seller['role'] === 'admin'): ?>
                            <span class="bg-purple-100 text-purple-700 text-xs font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-purple-200"><span class="material-symbols-outlined text-[12px]">shield_person</span> Admin</span>
                        <?php endif; ?>
                        <span class="bg-blue-100 text-blue-700 text-xs font-bold px-2 py-0.5 rounded-full flex items-center gap-1 border border-blue-200"><span class="material-symbols-outlined text-[12px]">verified</span> Verified</span>
                    </div>
                    <div class="flex flex-col sm:flex-row gap-2 sm:gap-6 text-sm text-slate-500 font-medium mt-3">
                        <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">calendar_month</span> สมาชิกตั้งแต่ <?= $joinDateTh ?></span>
                        <span class="flex items-center gap-1.5"><span class="material-symbols-outlined text-[18px]">location_on</span> <?= $location ?></span>
                    </div>
                </div>
            </div>
            
        </div>
        
        <!-- Bio Section -->
        <?php if (!empty($seller['bio'])): ?>
            <div class="mt-6 p-5 bg-slate-50 border border-slate-100 rounded-2xl">
                <h3 class="text-sm font-bold text-slate-800 mb-2 flex items-center gap-1">
                    <span class="material-symbols-outlined text-[16px]">info</span> เกี่ยวกับฉัน (Bio)
                </h3>
                <p class="text-sm text-slate-600 leading-relaxed whitespace-pre-line"><?= nl2br(htmlspecialchars($seller['bio'])) ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Statistics -->
        <div class="grid grid-cols-3 gap-4 mt-8 divide-x divide-slate-100">
            <div class="text-center">
                <p class="text-3xl font-bold text-slate-900 mb-1"><?= $activeCount ?></p>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">กำลังลงขาย (ACTIVE)</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-slate-900 mb-1"><?= $soldCount ?></p>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">ขายแล้ว (SOLD)</p>
            </div>
            <div class="text-center">
                <p class="text-3xl font-bold text-slate-900 mb-1 flex items-center justify-center gap-1"><?= $avgRating ?> <span class="material-symbols-outlined text-yellow-400 text-[24px]">star</span></p>
                <p class="text-[10px] uppercase font-bold text-slate-400 tracking-wider">คะแนนผู้ใช้ (<?= $ratingCount ?> รีวิว)</p>
            </div>
        </div>
    </div>

    <!-- Seller's Products -->
    <div class="mt-8">
        <h2 class="text-xl font-bold text-slate-900 mb-6 tracking-tight">สินค้าของ <?= htmlspecialchars($fullName) ?></h2>
        
        <?php if (count($products) > 0): ?>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 sm:gap-6">
                <?php foreach ($products as $p): 
                    $imgField = $p['product_image'];
                    $img = $baseUrl . '/assets/default.png';
                    if ($imgField) {
                        if ($imgField[0] === '[') {
                            $arr = json_decode($imgField, true);
                            if (!empty($arr)) $img = $baseUrl . '/uploads/'.basename($arr[0]);
                        } else {
                            $img = $baseUrl . '/uploads/'.basename(explode('|', $imgField)[0]);
                        }
                    }
                ?>
                <a href="<?= $baseUrl ?>/product/<?= $p['product_id'] ?>" class="group bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md hover:border-primary transition-all flex flex-col h-full">
                    <div class="aspect-square bg-slate-100 relative overflow-hidden">
                        <img src="<?= htmlspecialchars($img) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" alt="<?= htmlspecialchars($p['product_name']) ?>" onerror="this.onerror=null; this.src='<?= $baseUrl ?>/assets/default.png';">
                        <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/50 to-transparent p-3 pt-10">
                            <span class="text-white font-bold tracking-tight drop-shadow-md">฿<?= number_format((float)$p['product_price'], 2) ?></span>
                        </div>
                    </div>
                    <div class="p-4 flex flex-col flex-1">
                        <h3 class="font-bold text-slate-900 text-sm line-clamp-2 mb-1 group-hover:text-yellow-600 transition-colors"><?= htmlspecialchars($p['product_name']) ?></h3>
                        <?php if(!empty($p['location_name'])): ?>
                            <p class="text-xs text-slate-400 flex items-center gap-1 mt-auto pt-2">
                                <span class="material-symbols-outlined text-[14px]">location_on</span>
                                <span class="truncate"><?= htmlspecialchars($p['location_name']) ?></span>
                            </p>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-white border border-slate-200 rounded-3xl p-12 text-center text-slate-500 flex flex-col items-center justify-center min-h-[300px]">
                <span class="material-symbols-outlined text-6xl text-slate-200 mb-4">inventory_2</span>
                <p class="text-lg font-bold text-slate-700">ไม่มีสินค้าลงขาย</p>
                <p class="text-sm">ผู้ขายรายนี้ยังไม่มีสินค้าที่กำลังลงขายในขณะนี้</p>
            </div>
        <?php endif; ?>
    </div>
</main>

<!-- Footer -->
<footer class="mt-auto py-8 text-center text-sm text-slate-400 font-medium border-t border-slate-100 w-full" style="background:#f8fafc">
    <div class="flex items-center justify-center gap-2 mb-2 text-slate-800">
        <div class="w-5 h-5 bg-primary rounded flex items-center justify-center">
            <span class="material-symbols-outlined text-[14px]">storefront</span>
        </div>
        <span class="font-bold tracking-tight">Marketplace</span>
    </div>
    <p>&copy; 2024 Marketplace Inc. All rights reserved.</p>
</footer>

</body>
</html>
