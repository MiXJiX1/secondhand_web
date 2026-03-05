<?php
session_start();

/* ---------- ตรวจสิทธิ์แอดมิน ---------- */
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../login.php");
  exit();
}

/* ---------- DB: ใช้ config กลาง ---------- */
require_once __DIR__ . "/../../config/database.php";

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_admin'])) {
  $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['csrf_admin'];

/* ---------- utils ---------- */
if(!function_exists('slugify')){
function slugify($text) {
  $text = strtolower(trim($text));
  $text = preg_replace('/[^a-z0-9\- ]/u', '', $text);
  $text = preg_replace('/\s+/', '-', $text);
  $text = preg_replace('/-+/', '-', $text);
  return $text ?: 'cat-'.bin2hex(random_bytes(3));
}
}
if(!function_exists('flash')){
function flash($msg, $type='success'){
  $_SESSION['flash'] = ['m'=>$msg,'t'=>$type];
}
}
if(!function_exists('uniqueSlug')){
function uniqueSlug(PDO $pdo, string $baseSlug, int $excludeId = 0): string {
  $slug = $baseSlug ?: 'cat-'.bin2hex(random_bytes(3));
  $check = $pdo->prepare("SELECT id FROM categories WHERE slug=? AND id<>? LIMIT 1");
  $n = 1;
  $candidate = $slug;
  while (true) {
    $check->execute([$candidate, $excludeId]);
    if (!$check->fetch()) return $candidate;
    $n++;
    $candidate = $slug . '-' . $n;
  }
}
}

/* ---------- actions ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!hash_equals($csrf, $_POST['csrf'] ?? '')) {
    http_response_code(400); exit('Bad CSRF');
  }

  $action = $_POST['action'] ?? '';
  try {
    if ($action === 'create') {
      $name = trim($_POST['name'] ?? '');
      $sort = (int)($_POST['sort'] ?? 0);
      if ($name === '') throw new Exception('ใส่ชื่อหมวดก่อน');

      $dup = $pdo->prepare("SELECT id FROM categories WHERE name=? LIMIT 1");
      $dup->execute([$name]);
      if ($dup->fetch()) throw new Exception('มีหมวดชื่อนี้อยู่แล้ว');

      $slug = uniqueSlug($pdo, slugify($name));

      $stmt = $pdo->prepare("INSERT INTO categories(name,slug,sort_order) VALUES(?,?,?)");
      $stmt->execute([$name,$slug,$sort]);
      flash('เพิ่มหมวดเรียบร้อย');
    }

    if ($action === 'update') {
      $id   = (int)($_POST['id'] ?? 0);
      $name = trim($_POST['name'] ?? '');
      $sort = (int)($_POST['sort'] ?? 0);
      if ($id<=0 || $name==='') throw new Exception('ข้อมูลไม่ครบ');

      $old = $pdo->prepare("SELECT name, slug FROM categories WHERE id=?");
      $old->execute([$id]);
      $row = $old->fetch();
      if (!$row) throw new Exception('ไม่พบหมวดนี้');

      $oldName = (string)$row['name'];
      $newName = $name;

      $dup = $pdo->prepare("SELECT id FROM categories WHERE name=? AND id<>? LIMIT 1");
      $dup->execute([$newName, $id]);
      if ($dup->fetch()) throw new Exception('มีหมวดชื่อนี้อยู่แล้ว');

      $baseSlug = slugify($newName);
      $slug = uniqueSlug($pdo, $baseSlug, $id);

      $pdo->beginTransaction();
      $u = $pdo->prepare("UPDATE categories SET name=?, slug=?, sort_order=? WHERE id=?");
      $u->execute([$newName,$slug,$sort,$id]);

      if ($oldName !== $newName) {
        $p = $pdo->prepare("UPDATE products SET category=? WHERE category=?");
        $p->execute([$newName,$oldName]);
      }

      $pdo->commit();
      flash('แก้ไขหมวดเรียบร้อย');
    }

    if ($action === 'delete') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id<=0) throw new Exception('ไม่พบหมวด');

      $chk = $pdo->prepare("SELECT name, slug FROM categories WHERE id=?");
      $chk->execute([$id]);
      $row = $chk->fetch();
      if (!$row) throw new Exception('ไม่พบหมวดนี้');

      if ($row['name'] === 'others' || $row['slug'] === 'others') {
        throw new Exception('หมวด others ห้ามลบ');
      }

      $pdo->beginTransaction();
      $pdo->exec("INSERT IGNORE INTO categories(name,slug,sort_order) VALUES('others','others',9999)");

      $migrate = $pdo->prepare("UPDATE products SET category='others' WHERE category=?");
      $migrate->execute([$row['name']]);

      $del = $pdo->prepare("DELETE FROM categories WHERE id=?");
      $del->execute([$id]);

      $pdo->commit();
      flash('ลบหมวดเรียบร้อย (ย้ายสินค้าไป others แล้ว)');
    }
  } catch(Exception $e){
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash($e->getMessage(),'danger');
  }

  header("Location: categories.php"); exit();
}

/* ---------- fetch list ---------- */
$cats = $pdo->query("
  SELECT c.*,
    (SELECT COUNT(*) FROM products p WHERE p.category = c.name) AS items
  FROM categories c
  ORDER BY sort_order ASC, name ASC
")->fetchAll();

$flash = $_SESSION['flash'] ?? null; unset($_SESSION['flash']);
