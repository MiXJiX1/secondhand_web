<?php
/**
 * index.php - Product Listing Page
 */
session_start();
require_once __DIR__ . "/config/database.php";

// 1. Fetch Categories for Filter
$stmt_cats = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
$categories = $stmt_cats->fetchAll(PDO::FETCH_COLUMN);

// 2. Handle Filters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$location_filter = $_GET['location'] ?? '';
$price_range = $_GET['price_range'] ?? '';

$where = ["p.status = 'active'"];
$params = [];

if ($search !== '') {
    $where[] = "(p.product_name LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($category !== '') {
    $where[] = "p.category = ?";
    $params[] = $category;
}
if ($location_filter !== '') {
    $where[] = "p.location_name = ?";
    $params[] = $location_filter;
}
if ($price_range !== '') {
    if ($price_range === '1000+') {
        $where[] = "p.product_price >= 1000";
    } else {
        $parts = explode('-', $price_range);
        if (count($parts) === 2) {
            $where[] = "p.product_price BETWEEN ? AND ?";
            $params[] = (float)$parts[0];
            $params[] = (float)$parts[1];
        }
    }
}

$whereSql = implode(" AND ", $where);

// 3. Fetch Products
$sql = "SELECT p.* 
        FROM products p 
        WHERE $whereSql 
        ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Helper for grid partial (used in include)
$rsActive = new class($products) {
    private $items;
    public $num_rows;
    public function __construct($items) { $this->items = $items; $this->num_rows = count($items); }
    public function fetch_assoc() { return array_shift($this->items); }
};

// 4. Handle AJAX request for filtering
if (isset($_GET['ajax'])) {
    $currentUserId = $_SESSION['user_id'] ?? 0;
    // Helper function used in partial
    if (!function_exists('firstImageFromField')) {
        function firstImageFromField($field) {
            if (!$field) return null;
            $field = trim($field);
            if ($field[0] === '[') {
                $arr = json_decode($field, true);
                if ($arr && !empty($arr)) return basename($arr[0]);
            }
            $parts = preg_split('/[|,;]+/', $field, -1, PREG_SPLIT_NO_EMPTY);
            return $parts ? basename($parts[0]) : basename($field);
        }
    }
    include __DIR__ . '/includes/product_grid_partial.php';
    exit;
}

// 5. Fetch Sold Products (for bottom section)
$stmt_sold = $pdo->query("SELECT p.* FROM products p WHERE p.status = 'sold' ORDER BY p.updated_at DESC LIMIT 4");
$soldProducts = $stmt_sold->fetchAll();

$totalRows = count($products);
$currentUserId = $_SESSION['user_id'] ?? 0;

// Helper function for grid partial
if (!function_exists('firstImageFromField')) {
    function firstImageFromField($field) {
        if (!$field) return null;
        $field = trim($field);
        if ($field[0] === '[') {
            $arr = json_decode($field, true);
            if ($arr && !empty($arr)) return basename($arr[0]);
        }
        $parts = preg_split('/[|,;]+/', $field, -1, PREG_SPLIT_NO_EMPTY);
        return $parts ? basename($parts[0]) : basename($field);
    }
}
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
                        $imgSrc = $firstImg ? $baseUrl . '/uploads/' . $firstImg : $baseUrl . '/assets/no-image.png';
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
