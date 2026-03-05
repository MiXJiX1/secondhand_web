<?php
// Initialize database and helpers once
require_once __DIR__ . '/config/database.php';

// Simple Routing Logic
$requestUri = $_SERVER['REQUEST_URI'] ?? '';
$path = parse_url($requestUri, PHP_URL_PATH);

// Get base folder if any (to handle subdirectories)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? ''; 
$scriptDir = rtrim(dirname($scriptName), '/\\');
if ($scriptDir !== '/' && $scriptDir !== '\\' && strpos($path, $scriptDir) === 0) {
    $path = substr($path, strlen($scriptDir));
}
$path = ltrim($path, '/');

// Robustness: Strip .php extension if present so /login.php is treated as /login
if (str_ends_with(strtolower($path), '.php')) {
    $path = substr($path, 0, -4);
}

// Treat empty path or 'index' as home
if ($path === '' || $path === 'index') {
    $path = '/';
}

// Route Dispatcher
if (preg_match('/^product\/([0-9]+)$/', $path, $matches)) {
    $_GET['product_id'] = $matches[1];
    require_once __DIR__ . '/php/product_detail.php';
    exit;
}
if (preg_match('/^seller\/([0-9]+)$/', $path, $matches)) {
    $_GET['id'] = $matches[1];
    require_once __DIR__ . '/php/seller_profile.php';
    exit;
}

switch ($path) {
    case 'sell':        require_once __DIR__ . '/php/sell.php'; exit;
    case 'chat':        require_once __DIR__ . '/chatapp/chat_list.php'; exit;
    case 'profile':     require_once __DIR__ . '/php/profile.php'; exit;
    case 'topup':       require_once __DIR__ . '/php/topup.php'; exit;
    case 'topup-process': require_once __DIR__ . '/php/topup_process.php'; exit;
    case 'my-products': require_once __DIR__ . '/php/my_products.php'; exit;
    case 'exchange':    require_once __DIR__ . '/php/exchange.php'; exit;
    case 'edit-exchange': require_once __DIR__ . '/php/edit_exchange.php'; exit;
    case 'delete-product': require_once __DIR__ . '/php/delete_product.php'; exit;
    case 'login':       require_once __DIR__ . '/php/login.php'; exit;
    case 'register':    require_once __DIR__ . '/php/register.php'; exit;
    case 'logout':      require_once __DIR__ . '/php/logout.php'; exit;
    case 'sales-income': require_once __DIR__ . '/php/sales_income.php'; exit;
    case 'feedback':    require_once __DIR__ . '/php/feedback.php'; exit;
    case 'about':       require_once __DIR__ . '/php/help/getting-started.php'; exit;
    case 'forgot-password': require_once __DIR__ . '/php/forgot_password.php'; exit;
    case 'chat-window': require_once __DIR__ . '/chatapp/chat.php'; exit;
    // Admin routes
    case 'admin/dashboard':         require_once __DIR__ . '/admin/dashboard.php'; exit;
    case 'admin/users':             require_once __DIR__ . '/admin/users.php'; exit;
    case 'admin/products':          require_once __DIR__ . '/admin/products.php'; exit;
    case 'admin/payments':          require_once __DIR__ . '/admin/payments.php'; exit;
    case 'admin/categories':        require_once __DIR__ . '/admin/categories.php'; exit;
    case 'admin/support-tickets':   require_once __DIR__ . '/admin/support_tickets.php'; exit;
    case 'admin/ban-appeals':       require_once __DIR__ . '/admin/ban_appeals.php'; exit;
    case 'admin/bank-verifications': require_once __DIR__ . '/admin/bank_verifications.php'; exit;
    case 'admin/abuse-reports':     require_once __DIR__ . '/admin/admin_abuse_reports.php'; exit;
    case 'admin/stats':             require_once __DIR__ . '/admin/admin_stats.php'; exit;
    case 'admin/user-ratings':      require_once __DIR__ . '/admin/admin_user_ratings.php'; exit;
    case 'admin/user-status-action': require_once __DIR__ . '/admin/controllers/user_status_action_controller.php'; exit;
    case 'admin/user-stats':        require_once __DIR__ . '/admin/controllers/user_stats_controller.php'; exit;
    case 'admin/withdraw-action':   require_once __DIR__ . '/admin/controllers/withdraw_action_controller.php'; exit;
    case 'admin/topup-action':      require_once __DIR__ . '/admin/controllers/topup_action_controller.php'; exit;
}

// Default: Show Homepage
require_once __DIR__ . '/php/controllers/index_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายการสินค้า | Secondhand Market</title>
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
</head>
<body class="bg-background-light dark:bg-background-dark font-sans text-slate-900 dark:text-slate-100 min-h-screen">

    <?php include __DIR__ . '/includes/navbar_main.php'; ?>

    <main class="flex flex-1 flex-col items-center">
        <div class="w-full max-w-[1280px] px-6 lg:px-20 py-8">
            
            <!-- Search & Filter Section -->
            <form method="GET" class="flex flex-col gap-6 mb-10">
                <div class="flex flex-col md:flex-row gap-4 w-full">
                    <div class="flex flex-1 items-center bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-sm overflow-hidden px-4 focus-within:ring-2 focus-within:ring-primary/50 transition-all">
                        <span class="material-symbols-outlined text-slate-400">search</span>
                        <input name="search" value="<?= h($search) ?>" class="w-full border-none bg-transparent py-4 focus:ring-0 text-slate-900 dark:text-white placeholder:text-slate-400 outline-none" placeholder="ค้นหาสินค้า, อุปกรณ์ไอที, แฟชั่น..." type="text"/>
                        <button type="submit" class="bg-primary text-slate-900 px-6 py-2 rounded-lg font-bold hover:bg-opacity-90 transition-all">ค้นหา</button>
                    </div>
                </div>
                
                <div class="flex flex-wrap gap-4">
                    <div class="relative min-w-[180px]">
                        <select name="category" class="w-full appearance-none bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl pl-4 pr-10 py-3 text-slate-700 dark:text-slate-300 outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer shadow-sm">
                            <option value="">ทุกหมวดหมู่</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= h($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= h($cat) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none flex items-center justify-center text-slate-400">
                            <span class="material-symbols-outlined text-[20px]">expand_more</span>
                        </div>
                    </div>

                    <div class="relative min-w-[180px]">
                        <select name="price_range" class="w-full appearance-none bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl pl-4 pr-11 py-3 text-slate-700 dark:text-slate-300 outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer shadow-sm">
                            <option value="">ทุกราคา</option>
                            <option value="0-100" <?= $price_range === '0-100' ? 'selected' : '' ?>>0 - 100 บาท</option>
                            <option value="100-500" <?= $price_range === '100-500' ? 'selected' : '' ?>>100 - 500 บาท</option>
                            <option value="500-1000" <?= $price_range === '500-1000' ? 'selected' : '' ?>>500 - 1,000 บาท</option>
                            <option value="1000+" <?= $price_range === '1000+' ? 'selected' : '' ?>>มากกว่า 1,000 บาท</option>
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none flex items-center justify-center text-slate-400">
                            <span class="material-symbols-outlined text-[20px]">payments</span>
                        </div>
                    </div>

                    <div class="relative min-w-[200px]">
                        <select name="location" class="w-full appearance-none bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl pl-4 pr-11 py-3 text-slate-700 dark:text-slate-300 outline-none focus:ring-2 focus:ring-primary/30 transition-all cursor-pointer shadow-sm">
                            <option value="">ทุกพื้นที่</option>
                            <option value="หน้ามอ" <?= $location_filter === 'หน้ามอ' ? 'selected' : '' ?>>หน้ามอ (Front of MSU)</option>
                            <option value="หลังมอ" <?= $location_filter === 'หลังมอ' ? 'selected' : '' ?>>หลังมอ (Back of MSU)</option>
                            <option value="ขามเรียง" <?= $location_filter === 'ขามเรียง' ? 'selected' : '' ?>>ขามเรียง (Kham Riang)</option>
                            <option value="ในเมือง" <?= $location_filter === 'ในเมือง' ? 'selected' : '' ?>>ในเมือง (City Center)</option>
                            <option value="กันทรวิชัย" <?= $location_filter === 'กันทรวิชัย' ? 'selected' : '' ?>>กันทรวิชัย (Kantarawichai)</option>
                            <option value="อื่นๆ" <?= $location_filter === 'อื่นๆ' ? 'selected' : '' ?>>อื่นๆ (Other)</option>
                        </select>
                        <div class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none flex items-center justify-center text-slate-400">
                            <span class="material-symbols-outlined text-[20px]">location_on</span>
                        </div>
                    </div>
                </div>
            </form>

            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-2xl font-bold tracking-tight">Fresh Finds</h2>
                    <p class="text-slate-500 dark:text-slate-400">ค้นหาสิ่งที่ใช่ ในราคาที่ชอบ</p>
                    <p class="text-sm font-medium text-slate-500 mt-1">พบสินค้าทั้งหมด <span class="text-yellow-600 font-bold" id="totalRowsCount"><?= $totalRows ?></span> รายการ</p>
                </div>
            </div>

            <!-- Products Grid -->
            <div id="product-container" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                <?php include __DIR__ . '/includes/product_grid_partial.php'; ?>
            </div>

            <!-- Sold Section -->
            <?php if (count($soldProducts) > 0): ?>
            <div class="mt-20 pt-16 border-t border-slate-200 dark:border-slate-800">
                <h2 class="text-3xl font-bold mb-10 tracking-tight">สินค้าพึ่งขายออก</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8 opacity-75 grayscale-[0.3]">
                    <?php foreach ($soldProducts as $p): 
                        $firstImg = firstImageFromField($p['product_image']);
                        $imgSrc = $firstImg ? $baseUrl . '/uploads/' . $firstImg : $baseUrl . '/assets/default.png';
                    ?>
                        <div class="bg-white dark:bg-slate-900 rounded-2xl overflow-hidden border border-slate-200 dark:border-slate-800 transition-all">
                            <div class="aspect-[4/3] relative overflow-hidden">
                                <div class="absolute top-4 right-4 z-10 bg-red-600 text-white px-3 py-1 rounded-full text-[10px] font-bold shadow-lg uppercase tracking-tighter">SOLD OUT</div>
                                <img src="<?= htmlspecialchars($imgSrc) ?>" class="w-full h-full object-cover grayscale">
                            </div>
                            <div class="p-5">
                                <h3 class="font-bold text-slate-900 dark:text-white line-through opacity-50"><?= h($p['product_name']) ?></h3>
                                <p class="text-xl font-black text-slate-900 dark:text-white">฿<?= number_format((float)$p['product_price'], 0) ?></p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </main>

    <footer class="bg-white dark:bg-slate-900 border-t border-slate-200 dark:border-slate-800 mt-20 py-10 text-center text-slate-400 text-sm">
        &copy; 2024 Secondhand Market. All rights reserved.
    </footer>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const filterForm = document.querySelector('form');
        const productContainer = document.getElementById('product-container');
        const searchInput = filterForm.querySelector('input[name="search"]');
        const totalRowsCount = document.getElementById('totalRowsCount');

        let searchTimeout;

        const performFilter = () => {
            const formData = new FormData(filterForm);
            const params = new URLSearchParams(formData);
            params.set('ajax', '1');

            productContainer.style.opacity = '0.4';
            productContainer.style.filter = 'blur(1px)';

            fetch(`<?= $baseUrl ?>/index.php?${params.toString()}`)
                .then(res => res.text())
                .then(html => {
                    productContainer.innerHTML = html;
                    productContainer.style.opacity = '1';
                    productContainer.style.filter = 'none';
                    
                    params.delete('ajax');
                    const newRelativePathQuery = window.location.pathname + '?' + params.toString();
                    history.pushState(null, '', newRelativePathQuery);

                    const itemsCount = productContainer.querySelectorAll('a').length;
                    if (totalRowsCount) totalRowsCount.textContent = itemsCount;
                })
                .catch(err => {
                    console.error('Filter failed:', err);
                    productContainer.style.opacity = '1';
                    productContainer.style.filter = 'none';
                });
        };

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performFilter, 400);
        });

        filterForm.querySelectorAll('select').forEach(sel => {
            sel.addEventListener('change', performFilter);
        });

        filterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            performFilter();
        });
    });
    </script>
</body>
</html>
