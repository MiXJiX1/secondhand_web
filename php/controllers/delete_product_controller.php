<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// include config (เก็บค่าคงที่)
require_once __DIR__ . "/../../config/database.php";

// $conn is provided by database.php
$conn->set_charset("utf8mb4");

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = intval($_POST['product_id']);
    $user_id = $_SESSION['user_id'];

    // ตรวจสอบว่าเป็นเจ้าของสินค้าจริง
    $check = $conn->prepare("SELECT product_id FROM products WHERE product_id = ? AND user_id = ?");
    $check->bind_param("ii", $product_id, $user_id);
    $check->execute();
    $result = $check->get_result();

    if ($result && $result->num_rows > 0) {
        // ลบสินค้า
        $delete = $conn->prepare("DELETE FROM products WHERE product_id = ?");
        $delete->bind_param("i", $product_id);
        $delete->execute();

        // log กิจกรรม
        if (!empty($_SESSION['username'])) {
            $username = $_SESSION['username'];
            $action   = "ลบสินค้า #$product_id";
            $log = $conn->prepare("INSERT INTO activity_log (username, action) VALUES (?, ?)");
            $log->bind_param("ss", $username, $action);
            $log->execute();
            $log->close();
        }

        $delete->close();
    }

    $check->close();
}

// $conn->close(); // Optional: better to avoid closing the global db connection here if other parts use it, but since it's an action, it's fine.

header("Location: profile.php"); // Updated my_products.php to profile.php, or rather keep the same
exit();
