<?php
/**
 * api_chat_controller.php
 * Handles logic for the chat interface (chat.php)
 */
require_once __DIR__ . '/../../config/database.php';

if (!isLoggedIn()) { 
    redirect($baseUrl . '/login');
}
$me = (int)$_SESSION['user_id'];
$requestId = isset($_GET['request_id']) ? trim($_GET['request_id']) : '';
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$sellerUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Handle new chat initiation from product detail page without a request_id
if ($requestId === '' && $productId > 0 && $sellerUserId > 0) {
    if ($me === $sellerUserId) {
        throw new Exception("คุณไม่สามารถแชทกับตัวเองได้", 400);
    }
    
    $checkReq = $pdo->prepare("SELECT request_id FROM chat_requests WHERE product_id = ? AND buyer_id = ? AND seller_id = ? LIMIT 1");
    $checkReq->execute([$productId, $me, $sellerUserId]);
    $existingReq = $checkReq->fetch();
    
    if ($existingReq) {
        $requestId = $existingReq['request_id'];
    } else {
        $requestId = 'REQ_' . bin2hex(random_bytes(8));
        $insReq = $pdo->prepare("INSERT INTO chat_requests (request_id, product_id, buyer_id, seller_id) VALUES (?, ?, ?, ?)");
        $insReq->execute([$requestId, $productId, $me, $sellerUserId]);
    }
}

// Ensure at least one query fetches My Info for the Top Bar
$stmt_me = $pdo->prepare("SELECT fname, lname, img FROM users WHERE user_id = ? LIMIT 1");
$stmt_me->execute([$me]);
$myInfo = $stmt_me->fetch();
$myName = trim(($myInfo['fname']??'').' '.($myInfo['lname']??''));
$myAvatarText = mb_substr($myName, 0, 1) ?: 'U';
$myAvatarUrl = !empty($myInfo['img']) && $myInfo['img']!=='default.png' ? $baseUrl . '/uploads/avatars/'.basename($myInfo['img']) : null;

// Fetch specific Active Chat Info
$chatProductName = "Select a conversation";
$activeProductImgUrl = null;
$sellerId = 0;
$buyerId = 0;
$otherName = '';
$otherImg = '';
$productPrice = 0;
$activeOrder = null;

if ($requestId !== '') {
    // Mark Read
    $upd = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND is_read = 0");
    $upd->execute([$requestId, $me]);

    $stmt = $pdo->prepare("SELECT p.product_id, p.product_name, p.product_image, p.product_price, cr.seller_id, cr.buyer_id, us.fname AS seller_fname, us.lname AS seller_lname, us.img AS seller_img, ub.fname AS buyer_fname, ub.lname AS buyer_lname, ub.img AS buyer_img FROM chat_requests cr LEFT JOIN products p ON cr.product_id = p.product_id LEFT JOIN users us ON cr.seller_id = us.user_id LEFT JOIN users ub ON cr.buyer_id = ub.user_id WHERE cr.request_id = ? LIMIT 1");
    $stmt->execute([$requestId]);
    $res = $stmt->fetch();
    if ($res) {
        $sellerId = (int)$res['seller_id'];
        $buyerId = (int)$res['buyer_id'];
        $productId = (int)$res['product_id'];
        $chatProductName = $res['product_name'] ?: "Unknown Item";
        $productPrice = $res['product_price'] ?? 0;
        
        $pImg = firstImageFromField($res['product_image']);
        $activeProductImgUrl = $pImg ? $baseUrl . '/uploads/' . $pImg : null;

        if ($me === $sellerId) {
            $otherName = trim($res['buyer_fname'] . ' ' . $res['buyer_lname']) ?: 'Buyer';
            $otherImg = $res['buyer_img'] ? $baseUrl . '/uploads/avatars/'.basename($res['buyer_img']) : '';
        } else {
            $otherName = trim($res['seller_fname'] . ' ' . $res['seller_lname']) ?: 'Seller';
            $otherImg = $res['seller_img'] ? $baseUrl . '/uploads/avatars/'.basename($res['seller_img']) : '';
        }

        // Fetch latest order for this chat and product, along with rating status
        $stmt_ord = $pdo->prepare("
            SELECT o.id AS order_id, o.order_no, o.status, o.amount, 
                   IF(r.rating_id IS NOT NULL, 1, 0) AS has_rated
            FROM orders o
            LEFT JOIN user_ratings r ON r.order_id = o.id AND r.rater_id = ?
            WHERE o.request_id = ? AND o.product_id = ? 
            ORDER BY o.id DESC LIMIT 1
        ");
        $stmt_ord->execute([$me, $requestId, $productId]);
        $activeOrder = $stmt_ord->fetch();
    }
}
// Check if requested as a partial (for SPA loading)
$isPartial = isset($_GET['partial']) && $_GET['partial'] === 'true';
?>
