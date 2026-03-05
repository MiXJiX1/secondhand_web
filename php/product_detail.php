<?php
require_once __DIR__ . '/controllers/product_detail_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($product['product_name']) ?> | Secondhand Market</title>
    <script src="https://cdn.tailwindcss.com?plugins=forms,container-queries"></script>
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet"/>
    <script>
        tailwind.config = {
            darkMode: "class",
            theme: {
                extend: {
                    colors: { 
                        "primary": "#f9e71f",
                        "background-light": "#f8f8f5",
                        "background-dark": "#23210f",
                    },
                    fontFamily: { sans: ["Prompt", "sans-serif"] },
                }
            }
        };
    </script>
    <!-- Leaflet -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-background-light dark:bg-background-dark font-sans text-slate-900 dark:text-slate-100 min-h-screen">

    <?php include __DIR__ . '/../includes/navbar_main.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 sm:py-16">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-10 xl:gap-16 items-start">
            
            <!-- LEFT: Image Section -->
            <div class="lg:col-span-7 space-y-6 lg:sticky lg:top-24 w-full max-w-[800px] mx-auto lg:mx-0">
                <div class="relative w-full overflow-hidden bg-white dark:bg-slate-900 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-2xl" 
                     style="aspect-ratio: 1/1; height: auto;">
                    <img id="mainImage" src="<?= $baseUrl ?>/<?= h($images[0]) ?>" 
                         class="absolute inset-0 w-full h-full object-contain transition-opacity duration-300 opacity-100">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/5 to-transparent pointer-events-none"></div>
                </div>
                
                <?php if (count($images) > 1): ?>
                <div class="flex gap-3 overflow-x-auto pb-2 no-scrollbar px-1 min-h-[70px]">
                    <?php foreach ($images as $idx => $img): ?>
                        <div class="thumb-btn flex-shrink-0 w-16 h-16 rounded-xl overflow-hidden border-2 cursor-pointer transition-all hover:scale-105 <?= $idx === 0 ? 'border-primary' : 'border-transparent opacity-70 hover:opacity-100' ?>"
                             onclick="updateMainImage(this, '<?= $baseUrl ?>/<?= h($img) ?>')">
                            <img src="<?= $baseUrl ?>/<?= h($img) ?>" class="w-full h-full object-cover pointer-events-none">
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Seller Card -->
                <div class="seller-card flex items-center gap-6 p-6 bg-white dark:bg-slate-800/50 rounded-[2.5rem] border border-slate-100 dark:border-slate-800 shadow-sm">
                    <div class="w-20 h-20 bg-primary rounded-full flex items-center justify-center overflow-hidden border-4 border-white dark:border-slate-700 shadow-md flex-shrink-0">
                        <?php if (!empty($product['profile_img'])): ?>
                            <img src="<?= $baseUrl ?>/uploads/avatars/<?= h($product['profile_img']) ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-slate-900 font-bold text-3xl uppercase"><?= mb_substr($product['fname'] ?: $product['seller_username'], 0, 1) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h4 class="text-xl font-bold text-slate-900 dark:text-white truncate">ขายโดย: <?= h($product['fname'] . ' ' . $product['lname']) ?></h4>
                        <div class="flex items-center gap-2 mt-1">
                            <span class="text-sm text-yellow-500 font-bold">⭐⭐⭐⭐⭐</span>
                            <span class="text-xs text-slate-400 font-bold">(5.0)</span>
                        </div>
                        <div class="mt-3">
                            <a href="<?= $baseUrl ?>/php/seller_profile.php?id=<?= (int)$product['user_id'] ?>" 
                               class="inline-flex items-center gap-2 bg-primary text-slate-900 px-5 py-2.5 rounded-xl text-sm font-black hover:shadow-lg hover:shadow-primary/20 transition-all active:scale-95 uppercase tracking-wider">
                                ดูสินค้าอื่นๆ ของผู้ขายรายนี้
                                <span class="material-symbols-outlined text-sm">chevron_right</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Info Section -->
            <div class="lg:col-span-5 flex flex-col gap-6">
                <div class="product-header space-y-3">
                    <div class="flex flex-wrap gap-2 mb-1">
                        <?php if ($isOwner): ?>
                        <span class="inline-block px-6 py-2 bg-slate-900 dark:bg-primary text-primary dark:text-slate-900 text-[14px] font-black rounded-full uppercase tracking-widest shadow-lg border-2 border-primary animate-pulse flex items-center gap-2">
                            <span class="material-symbols-outlined text-[18px] fill-1">person_check</span>
                            สินค้าของคุณ
                        </span>
                        <?php endif; ?>
                        <span class="inline-block px-10 py-2 bg-primary text-white text-[14px] font-black rounded-full uppercase tracking-widest shadow-sm">
                            <?= h(strtoupper($product['category'] ?? 'ทั่วไป')) ?>
                        </span>
                        <?php if (!empty($product['location_name'])): ?>
                        <span class="inline-block px-10 py-2 bg-primary/20 text-yellow-700 text-[14px] font-bold rounded-full border border-primary/10 shadow-sm flex items-center gap-1">
                            📍 <?= h($product['location_name']) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <h1 class="text-5xl font-bold text-slate-900 dark:text-white leading-tight tracking-tight"><?= h($product['product_name']) ?></h1>
                    <div class="text-5xl font-black text-primary flex items-baseline gap-2">
                        <span class="text-[80px] leading-none -mb-3 -ml-2">฿</span><?= number_format((float)$product['product_price'], 0) ?>
                        <small class="text-slate-400 text-sm font-medium">บาท</small>
                    </div>
                </div>

                <!-- Action Box -->
                <div class="action-box flex gap-3">
                    <?php if ($isOwner): ?>
                    <a href="<?= $baseUrl ?>/php/edit_product.php?id=<?= (int)$product['product_id'] ?>" 
                       class="flex-[3] flex items-center justify-center gap-3 bg-white dark:bg-slate-800 border-2 border-primary text-slate-900 dark:text-white py-4 rounded-2xl font-black text-lg hover:bg-primary hover:text-slate-900 transition-all active:scale-95 group">
                        <span class="material-symbols-outlined group-hover:rotate-12 transition-transform">edit_note</span> 
                        แก้ไขข้อมูลสินค้า
                    </a>
                    <?php else: ?>
                    <a href="<?= $baseUrl ?>/ChatApp/chat_list.php?product_id=<?= (int)$product['product_id'] ?>&user_id=<?= (int)$product['user_id'] ?>" 
                       class="flex-[3] flex items-center justify-center gap-3 bg-primary text-slate-900 py-4 rounded-2xl font-black text-lg hover:shadow-lg hover:shadow-primary/20 transition-all active:scale-95 group">
                        <span class="material-symbols-outlined group-hover:scale-110 transition-transform">chat_bubble</span> 
                        เริ่มพูดคุยกับผู้ขาย
                    </a>
                    <?php endif; ?>
                    <button id="favBtn" onclick="toggleFavorite(<?= (int)$product['product_id'] ?>)" 
                            class="flex-1 flex items-center justify-center gap-2 <?= $isFavorited ? 'bg-red-500 text-white border-red-500' : 'bg-white dark:bg-slate-800 border-2 border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500' ?> py-4 rounded-2xl font-bold hover:border-primary hover:text-primary transition-all group">
                        <span class="material-symbols-outlined group-hover:scale-110 transition-transform" style="<?= $isFavorited ? "font-variation-settings: 'FILL' 1;" : "" ?>">favorite</span>
                    </button>
                </div>

                <!-- Description Panel -->
                <div class="desc-panel bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-50 dark:border-slate-800 shadow-sm space-y-3">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-base">description</span> รายละเอียดสินค้า
                    </h3>
                    <div class="h-px bg-slate-50 dark:bg-slate-800 w-full mb-2"></div>
                    <div class="text-slate-600 dark:text-slate-400 leading-relaxed text-base">
                        <?= nl2br(h($product['description'] ?: 'ไม่มีรายละเอียดเพิ่มเติม')) ?>
                    </div>
                </div>

                <!-- Location Panel -->
                <div class="desc-panel bg-white dark:bg-slate-900 p-8 rounded-[2rem] border border-slate-50 dark:border-slate-800 shadow-sm space-y-4">
                    <h3 class="text-lg font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <span class="material-symbols-outlined text-primary text-base">location_on</span> สถานที่นัดรับ
                    </h3>
                    <div class="space-y-4">
                        <p class="text-slate-700 dark:text-slate-300 font-medium text-sm flex items-center gap-2">
                            📍 <?= h($product['location_name'] ?: 'ติดต่อสอบถามผ่านแชท') ?>
                        </p>
                        
                        <?php if (!empty($lat) && !empty($lng)): ?>
                        <div id="map" class="w-full h-[220px] rounded-2xl border border-slate-50 dark:border-slate-800 z-10 shadow-inner"></div>
                        <input type="hidden" id="mapLat" value="<?= h($lat) ?>">
                        <input type="hidden" id="mapLng" value="<?= h($lng) ?>">
                        <?php endif; ?>
                        
                        <div class="flex justify-between items-center">
                            <p class="text-[9px] text-slate-400 uppercase tracking-widest">อัปเดตเมื่อ: <?= date('d M Y', strtotime($product['updated_at'] ?? $product['created_at'])) ?></p>
                            <button onclick="shareProduct()" class="flex items-center gap-1 text-slate-400 hover:text-primary transition-colors font-bold text-[9px] uppercase tracking-widest">
                                <span class="material-symbols-outlined text-[14px]">share</span> แชร์
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 mt-20 py-10 text-center text-slate-400 text-sm">
        &copy; 2024 Secondhand Market. All rights reserved.
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const latEl = document.getElementById('mapLat');
        const lngEl = document.getElementById('mapLng');
        if (latEl && lngEl) {
            const lat = parseFloat(latEl.value);
            const lng = parseFloat(lngEl.value);
            if (!isNaN(lat) && !isNaN(lng)) {
                const map = L.map('map').setView([lat, lng], 16);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap'
                }).addTo(map);
                L.marker([lat, lng]).addTo(map).bindPopup("<b>สถานที่นัดรับ</b><br><?= addslashes(h($product['product_name'])) ?>").openPopup();
            }
        }
    });

    function updateMainImage(el, src) {
        const mainImg = document.getElementById('mainImage');
        if (mainImg.src === src) return;
        mainImg.style.opacity = '0';
        setTimeout(() => {
            const tempImg = new Image();
            tempImg.src = src;
            tempImg.onload = () => {
                mainImg.src = src;
                mainImg.style.opacity = '1';
            };
            document.querySelectorAll('.thumb-btn').forEach(t => {
                t.classList.remove('border-primary');
                t.classList.add('border-transparent', 'opacity-70');
            });
            el.classList.add('border-primary');
            el.classList.remove('border-transparent', 'opacity-70');
        }, 150);
    }

    function toggleFavorite(pid) {
        fetch('<?= $baseUrl ?>/api/favorite.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'product_id=' + pid
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const btn = document.getElementById('favBtn');
                const icon = btn.querySelector('.material-symbols-outlined');
                if (data.is_favorited) {
                    btn.classList.add('bg-red-500', 'text-white', 'border-red-500');
                    btn.classList.remove('bg-white', 'dark:bg-slate-800', 'text-slate-400', 'dark:text-slate-500', 'border-slate-200', 'dark:border-slate-700', 'hover:text-primary', 'hover:border-primary');
                    icon.style.fontVariationSettings = "'FILL' 1";
                } else {
                    btn.classList.remove('bg-red-500', 'text-white', 'border-red-500');
                    btn.classList.add('bg-white', 'dark:bg-slate-800', 'text-slate-400', 'dark:text-slate-500', 'border-slate-200', 'dark:border-slate-700', 'hover:text-primary', 'hover:border-primary');
                    icon.style.fontVariationSettings = "'FILL' 0";
                }
            } else if (data.error === 'not_logged_in') {
                window.location.href = '<?= $baseUrl ?>/php/login.php';
            }
        })
        .catch(err => console.error('Error toggling favorite:', err));
    }

    function shareProduct() {
        if (navigator.share) {
            navigator.share({
                title: '<?= addslashes(h($product['product_name'])) ?>',
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(window.location.href);
            alert('คัดลอกลิงก์เรียบร้อยแล้ว');
        }
    }
    </script>
</body>
</html>
