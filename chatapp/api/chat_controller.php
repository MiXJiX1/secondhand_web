<?php
/**
 * api_chat_controller.php
 * Handles logic for the chat interface (chat.php)
 */
session_start();
require_once __DIR__ . '/../../config/database.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../../php/login.php"); exit; }
$me = (int)$_SESSION['user_id'];
$requestId = isset($_GET['request_id']) ? trim($_GET['request_id']) : '';
$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$sellerUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

// Handle new chat initiation from product detail page without a request_id
if ($requestId === '' && $productId > 0 && $sellerUserId > 0) {
    if ($me === $sellerUserId) {
        die("คุณไม่สามารถแชทกับตัวเองได้");
    }
    
    $checkReq = $conn->prepare("SELECT request_id FROM chat_requests WHERE product_id = ? AND buyer_id = ? AND seller_id = ? LIMIT 1");
    $checkReq->bind_param("iii", $productId, $me, $sellerUserId);
    $checkReq->execute();
    $existingReq = $checkReq->get_result()->fetch_assoc();
    
    if ($existingReq) {
        $requestId = $existingReq['request_id'];
    } else {
        $requestId = 'REQ_' . bin2hex(random_bytes(8));
        $insReq = $conn->prepare("INSERT INTO chat_requests (request_id, product_id, buyer_id, seller_id) VALUES (?, ?, ?, ?)");
        $insReq->bind_param("siii", $requestId, $productId, $me, $sellerUserId);
        $insReq->execute();
    }
}

// Ensure at least one query fetches My Info for the Top Bar
$stmt_me = $conn->prepare("SELECT fname, lname, img FROM users WHERE user_id = ? LIMIT 1");
$stmt_me->bind_param("i", $me);
$stmt_me->execute();
$myInfo = $stmt_me->get_result()->fetch_assoc();
$myName = trim(($myInfo['fname']??'').' '.($myInfo['lname']??''));
$myAvatarText = mb_substr($myName, 0, 1) ?: 'U';
$myAvatarUrl = !empty($myInfo['img']) && $myInfo['img']!=='default.png' ? '/uploads/avatars/'.basename($myInfo['img']) : null;


function firstImageFromField(?string $s): ?string {
    if (!$s) return null;
    $s = trim($s);
    if ($s !== '' && $s[0] === '[') {
        $arr = json_decode($s, true);
        if (is_array($arr) && !empty($arr)) return basename((string)$arr[0]);
    }
    $parts = preg_split('/[|,;]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
    if ($parts && isset($parts[0])) return basename(trim($parts[0]));
    return basename($s);
}

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
    $upd = $conn->prepare("UPDATE messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND is_read = 0");
    $upd->bind_param("si", $requestId, $me);
    $upd->execute();

    $stmt = $conn->prepare("SELECT p.product_id, p.product_name, p.product_image, p.product_price, cr.seller_id, cr.buyer_id, us.fname AS seller_fname, us.lname AS seller_lname, us.img AS seller_img, ub.fname AS buyer_fname, ub.lname AS buyer_lname, ub.img AS buyer_img FROM chat_requests cr LEFT JOIN products p ON cr.product_id = p.product_id LEFT JOIN users us ON cr.seller_id = us.user_id LEFT JOIN users ub ON cr.buyer_id = ub.user_id WHERE cr.request_id = ? LIMIT 1");
    $stmt->bind_param("s", $requestId);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    if ($res) {
        $sellerId = (int)$res['seller_id'];
        $buyerId = (int)$res['buyer_id'];
        $productId = (int)$res['product_id'];
        $chatProductName = $res['product_name'] ?: "Unknown Item";
        $productPrice = $res['product_price'] ?? 0;
        
        $pImg = firstImageFromField($res['product_image']);
        $activeProductImgUrl = $pImg ? '/uploads/' . $pImg : null;

        if ($me === $sellerId) {
            $otherName = trim($res['buyer_fname'] . ' ' . $res['buyer_lname']) ?: 'Buyer';
            $otherImg = $res['buyer_img'] ? '/uploads/avatars/'.basename($res['buyer_img']) : '';
        } else {
            $otherName = trim($res['seller_fname'] . ' ' . $res['seller_lname']) ?: 'Seller';
            $otherImg = $res['seller_img'] ? '/uploads/avatars/'.basename($res['seller_img']) : '';
        }

        // Fetch latest order for this chat and product, along with rating status
        $stmt_ord = $conn->prepare("
            SELECT o.id AS order_id, o.order_no, o.status, o.amount, 
                   IF(r.rating_id IS NOT NULL, 1, 0) AS has_rated
            FROM orders o
            LEFT JOIN user_ratings r ON r.order_id = o.id AND r.rater_id = ?
            WHERE o.request_id = ? AND o.product_id = ? 
            ORDER BY o.id DESC LIMIT 1
        ");
        $stmt_ord->bind_param("isi", $me, $requestId, $productId);
        $stmt_ord->execute();
        $activeOrder = $stmt_ord->get_result()->fetch_assoc();
    }
    $stmt->close();
}
// Check if requested as a partial (for SPA loading)
$isPartial = isset($_GET['partial']) && $_GET['partial'] === 'true';
?>
