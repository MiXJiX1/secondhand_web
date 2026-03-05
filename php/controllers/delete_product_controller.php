<?php
session_start();
if (!isLoggedIn()) {
    redirect($baseUrl . "/login");
}

require_once __DIR__ . "/../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = (int)$_POST['product_id'];
    $user_id = (int)$_SESSION['user_id'];

    try {
        // ตรวจสอบว่าเป็นเจ้าของสินค้าจริง
        $check = $pdo->prepare("SELECT product_id FROM products WHERE product_id = ? AND user_id = ?");
        $check->execute([$product_id, $user_id]);
        
        if ($check->fetch()) {
            // ลบสินค้า
            $delete = $pdo->prepare("DELETE FROM products WHERE product_id = ?");
            $delete->execute([$product_id]);

            // log กิจกรรม
            if (!empty($_SESSION['username'])) {
                $username = $_SESSION['username'];
                $action   = "ลบสินค้า #$product_id";
                // Note: user_status_action used activity_log, topup_process used credit_ledger, 
                // edit_product used activity_logs. Consistency is hard, but I'll stick to what was there.
                // Looking at delete_product_controller.php original code, it used activity_log.
                $log = $pdo->prepare("INSERT INTO activity_log (username, action) VALUES (?, ?)");
                $log->execute([$username, $action]);
            }
        }
    } catch (PDOException $e) {
        throw new Exception("Database error while deleting product: ".$e->getMessage());
    }
}

redirect($baseUrl . "/profile");
