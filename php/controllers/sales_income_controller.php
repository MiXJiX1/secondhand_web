<?php
// sales_income.php — รายรับจากการขายของผู้ใช้ปัจจุบัน
error_reporting(E_ALL);
ini_set('display_errors', 1);

// โหลดคอนฟิกกลาง (เริ่ม session/เชื่อม DB ที่นี่ได้)
require_once __DIR__ . "/../../config/database.php";

// เผื่อ config ยังไม่ได้เริ่ม session
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) { header("Location: login.php"); exit; }

/* ฟิลเตอร์ช่วงวันที่ (ไม่บังคับ) + validate */
$start = $_GET['start'] ?? '';
$end   = $_GET['end']   ?? '';
if ($start && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)) $start = '';
if ($end   && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end))   $end   = '';

$w    = " WHERE p.user_id=? AND o.status IN ('paid','released','completed') ";
$bind = [$user_id];

if ($start !== '') { $w .= " AND DATE(o.paid_at) >= ? "; $bind[] = $start; }
if ($end   !== '') { $w .= " AND DATE(o.paid_at) <= ? "; $bind[] = $end; }

/* รวมยอด */
$sqlSum = "SELECT 
            COALESCE(SUM(o.amount),0) AS total_sum,
            COALESCE(SUM(CASE WHEN DATE(o.paid_at)=CURDATE() THEN o.amount END),0) AS today_sum,
            COALESCE(SUM(CASE WHEN YEAR(o.paid_at)=YEAR(CURDATE()) AND MONTH(o.paid_at)=MONTH(CURDATE()) THEN o.amount END),0) AS month_sum
          FROM orders o
          JOIN products p ON p.product_id = o.product_id
          $w";
$sumStmt = $pdo->prepare($sqlSum);
$sumStmt->execute($bind);
$sum = $sumStmt->fetch();

/* รายการคำสั่งซื้อ (ล่าสุด) */
$sqlList = "SELECT 
              o.order_no, o.amount, o.paid_at,
              b.username AS buyer_name,
              p.product_name, p.product_image
            FROM orders o
            JOIN products p ON p.product_id = o.product_id
            JOIN users b    ON b.user_id = o.user_id  -- ผู้ซื้อ
            $w
            ORDER BY o.paid_at DESC, o.order_no DESC
            LIMIT 100";
$listStmt = $pdo->prepare($sqlList);
$listStmt->execute($bind);
$rows = $listStmt->fetchAll();
