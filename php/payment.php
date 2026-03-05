<?php
require_once __DIR__ . '/controllers/payment_controller.php';
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <title>ชำระเงิน - <?= h($product['product_name']) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- ไว้ตามเดิม ถ้ามีไฟล์ -->
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="payment.css">
    <link rel="stylesheet" href="../assets/css/main.css">
<link rel="stylesheet" href="../assets/css/php-payment.css">
</head>
<body>

<div class="payment-container">
    <h2>ยืนยันการสั่งซื้อ</h2>

    <div class="product-info">
        <img src="<?= h($imgSrc) ?>" alt="<?= h($product['product_name']) ?>" onerror="this.onerror=null; this.src='/assets/default.png';">
        <div>
            <h3 style="margin:0 0 4px 0"><?= h($product['product_name']) ?></h3>
            <p style="margin:2px 0">หมวดหมู่: <?= h($product['category'] ?? '-') ?></p>
            <p style="margin:2px 0">ราคา: <strong><?= number_format((float)$product['product_price'], 2) ?> บาท</strong></p>
        </div>
    </div>

    <form action="order_success.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= h($csrf) ?>">
        <input type="hidden" name="product_id" value="<?= (int)$product['product_id'] ?>">

        <label for="customer_name">ชื่อผู้รับสินค้า:</label>
        <input type="text" name="customer_name" id="customer_name" required>

        <label for="phone">เบอร์โทรศัพท์:</label>
        <input type="text" name="phone" id="phone" required pattern="^0\d{8,9}$" placeholder="เช่น 0812345678">

        <label for="address">ที่อยู่สำหรับจัดส่ง:</label>
        <textarea name="address" id="address" required rows="3"></textarea>

        <label for="payment_method">วิธีชำระเงิน:</label>
        <select name="payment_method" id="payment_method" required>
            <option value="">-- กรุณาเลือก --</option>
            <option value="เก็บเงินปลายทาง">เก็บเงินปลายทาง</option>
            <option value="MSU.PAY">MSU.PAY</option>
        </select>

        <button type="submit">ยืนยันการสั่งซื้อ</button>
    </form>
</div>

</body>
</html>
