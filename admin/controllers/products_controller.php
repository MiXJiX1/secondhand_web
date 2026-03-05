<?php
session_start();
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php"); exit();
}

require_once __DIR__ . "/../../config/database.php";
$pdo->exec("SET NAMES utf8mb4");

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

/* ---------- Flash (PRG) ---------- */
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ---------- Delete product (POST ในหน้านี้เลย) ---------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && (($_POST['action'] ?? '')==='delete_product')) {
  try {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'] ?? '')) {
      throw new RuntimeException('CSRF ไม่ถูกต้อง');
    }
    $id = (int)($_POST['id'] ?? 0);
    if ($id<=0) throw new RuntimeException('ไม่พบรหัสสินค้า');

    // ถ้ามีออเดอร์อ้างอิง -> ซ่อนแทน (กัน FK)
    $chk = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE product_id=?");
    $chk->execute([$id]);
    $hasOrders = (int)$chk->fetchColumn() > 0;

    if ($hasOrders) {
      $pdo->prepare("UPDATE products SET status='hidden' WHERE product_id=?")->execute([$id]);
      $_SESSION['flash'] = ['type'=>'warning','msg'=>'สินค้ามีออเดอร์แล้ว จึงซ่อนสินค้าแทนการลบ'];
    } else {
      // เก็บไฟล์รูปไว้ลบหลังลบแถว
      $qImg = $pdo->prepare("SELECT product_image FROM products WHERE product_id=?");
      $qImg->execute([$id]);
      $imgField = (string)($qImg->fetchColumn() ?? '');

      // ลบแถวสินค้า
      $pdo->prepare("DELETE FROM products WHERE product_id=?")->execute([$id]);

      // แตกไฟล์รูปและลบออกจากโฟลเดอร์
      $files = [];
      if ($imgField!=='') {
        if ($imgField[0]==='[') {
          $arr = json_decode($imgField, true);
          if (is_array($arr)) $files = array_map('basename', $arr);
        } else {
          $parts = preg_split('/[|,;]+/', $imgField, -1, PREG_SPLIT_NO_EMPTY);
          $files = $parts ? array_map('basename', $parts) : [basename($imgField)];
        }
      }
      $projectRoot = realpath(__DIR__ . '/../..');
      $dirFs = $projectRoot . '/uploads/';
      foreach ($files as $fn) {
        $p = $dirFs . $fn;
        if (is_file($p)) @unlink($p);
      }

      $_SESSION['flash'] = ['type'=>'success','msg'=>'ลบสินค้าเรียบร้อย'];
    }
  } catch (Throwable $e) {
    $_SESSION['flash'] = ['type'=>'danger','msg'=>$e->getMessage()];
  }
  // PRG: กลับมาหน้าเดิมพร้อม query เดิม
  header('Location: ' . $_SERVER['REQUEST_URI']);
  exit;
}

/* ---------- Filters & pagination ---------- */
$q     = trim($_GET['q'] ?? '');
$cat   = trim($_GET['category'] ?? '');
$stat  = trim($_GET['status'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = 20;
$offset= ($page-1)*$limit;

/* statuses & categories (static แบบเบา ๆ) */
$categories = ['electronics','fashion','furniture','vehicle','gameandtoys','household','sport','music','others'];
$statuses   = ['active'=>'แสดง','sold'=>'ขายแล้ว','hidden'=>'ซ่อน'];

/* ---------- Build query ---------- */
$where = ["1=1"];
$args  = [];
if ($q !== '')     { $where[] = "p.product_name LIKE ?"; $args[] = "%$q%"; }
if ($cat !== '')   { $where[] = "p.category = ?";        $args[] = $cat; }
if ($stat !== '' && array_key_exists($stat, $statuses)) { $where[] = "p.status = ?"; $args[] = $stat; }
$whereSql = implode(' AND ', $where);

/* count for pagination */
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$stmt->execute($args);
$total = (int)$stmt->fetchColumn();
$pages = max(1, (int)ceil($total/$limit));

/* main list */
$sql = "
  SELECT p.product_id, p.product_name, p.category, p.product_price, p.product_image,
         p.status, p.sold_at, p.user_id,
         u.username
  FROM products p
  LEFT JOIN users u ON u.user_id = p.user_id
  WHERE $whereSql
  ORDER BY p.product_id DESC
  LIMIT $limit OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$products = $stmt->fetchAll();

/* helper: get first image safely */
if(!function_exists('firstImageFromField')){
function firstImageFromField(?string $s): ?string {
  if (!$s) return null;
  $s = trim($s);
  if ($s !== '' && $s[0] === '[') {
    $a = json_decode($s, true);
    if (is_array($a) && !empty($a)) return basename((string)$a[0]);
  }
  $parts = preg_split('/[|,;]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
  if ($parts && isset($parts[0])) return basename(trim($parts[0]));
  return basename($s);
}
}
