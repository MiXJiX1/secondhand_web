<?php
/**
 * admin/controllers/user_stats_controller.php
 * Provides JSON data for user statistics modal in admin area.
 */
require_once __DIR__ . "/../../config/database.php";

header('Content-Type: application/json');

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['ok' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Invalid User ID']);
    exit;
}

try {
    // 1. Basic user info
    $stmt = $pdo->prepare("SELECT username, fname, lname, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    if (!$user) {
        echo json_encode(['ok' => false, 'error' => 'User not found']);
        exit;
    }

    // 2. Counts
    // Sold: products with status 'sold'
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE user_id = ? AND status = 'sold'");
    $stmt->execute([$userId]);
    $soldCount = (int)$stmt->fetchColumn();

    // Bought: orders where this user is the buyer (if orders table exists)
    // Looking at database schema learnings: orders table exists.
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $boughtCount = (int)$stmt->fetchColumn();

    // Swap count: if there's an exchange system
    // Looking at index.php: exchange routes exist.
    // Assuming an exchange/swap table or order type. 
    // Let's check for exchange-related tables.
    $swapCount = 0;
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM product_exchanges WHERE (requester_id = ? OR target_user_id = ?) AND status = 'completed'");
        $stmt->execute([$userId, $userId]);
        $swapCount = (int)$stmt->fetchColumn();
    } catch (Exception $e) { /* ignore if table missing */ }

    // 3. Financials
    // Purchases amount
    $stmt = $pdo->prepare("SELECT SUM(total_price) FROM orders WHERE buyer_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $purchasesAmount = (float)($stmt->fetchColumn() ?? 0);

    // Sales amount
    $stmt = $pdo->prepare("SELECT SUM(total_price) FROM orders WHERE seller_id = ? AND status = 'completed'");
    $stmt->execute([$userId]);
    $salesAmount = (float)($stmt->fetchColumn() ?? 0);

    // 4. Ratings
    $stmt = $pdo->prepare("SELECT AVG(score) as avg_score, COUNT(*) as cnt FROM user_ratings WHERE rated_user_id = ?");
    $stmt->execute([$userId]);
    $ratingData = $stmt->fetch();
    $avgScore = (float)($ratingData['avg_score'] ?? 0);
    $ratingCount = (int)($ratingData['cnt'] ?? 0);

    // 5. Latest Ratings
    $stmt = $pdo->prepare("SELECT score, comment, created_at FROM user_ratings WHERE rated_user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$userId]);
    $latestRatings = $stmt->fetchAll();

    echo json_encode([
        'ok' => true,
        'data' => [
            'username' => $user['username'],
            'fullname' => trim(($user['fname'] ?? '') . ' ' . ($user['lname'] ?? '')),
            'member_since' => $user['created_at'],
            'sold_count' => $soldCount,
            'bought_count' => $boughtCount,
            'swap_count' => $swapCount,
            'sales_amount' => $salesAmount,
            'purchases_amount' => $purchasesAmount,
            'avg_score' => $avgScore,
            'rating_count' => $ratingCount,
            'latest_ratings' => $latestRatings
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
