<?php
/**
 * index_controller.php
 * Handles logic for the main product listing page (index.php)
 */
require_once __DIR__ . "/../../config/database.php";

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
    include __DIR__ . '/../../includes/product_grid_partial.php';
    exit;
}

// 5. Fetch Sold Products (for bottom section)
$stmt_sold = $pdo->query("SELECT p.* FROM products p WHERE p.status = 'sold' ORDER BY p.updated_at DESC LIMIT 4");
$soldProducts = $stmt_sold->fetchAll();

$totalRows = count($products);
$currentUserId = $_SESSION['user_id'] ?? 0;
?>
