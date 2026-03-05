require_once __DIR__ . "/../../config/database.php";

/* ── Auth ─────────────────────────────────────────────── */
if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: " . ($baseUrl ?? '') . "/login"); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header('Location: ' . ($baseUrl ?? '') . '/admin/users'); exit();
}
if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
  throw new Exception('CSRF invalid', 400);
}

$pdo->exec("SET NAMES utf8mb4");

/* ── Inputs ───────────────────────────────────────────── */
$action = $_POST['action'] ?? '';
$userId = (int)($_POST['user_id'] ?? 0);
$adminId = (int)($_SESSION['user_id'] ?? 0);

if ($userId <= 0 || !in_array($action, ['ban','unban','delete','upgrade_admin'], true)) {
  header('Location: ' . ($baseUrl ?? '') . '/admin/users'); exit();
}
  header('Location: ' . ($baseUrl ?? '') . '/admin/users'); exit();
}

/* ── ตรวจสิทธิ์เป้าหมาย ─────────────────────────────── */
$chk = $pdo->prepare("SELECT user_id, role FROM users WHERE user_id=?");
$chk->execute([$userId]);
$target = $chk->fetch();
if (!$target) { header('Location: ' . ($baseUrl ?? '') . '/admin/users'); exit(); }

if ($target['role'] === 'admin') {
  // ไม่อนุญาตให้แบน/เลิกแบนแอดมินผ่านหน้าปกติ
  header('Location: ' . ($baseUrl ?? '') . '/admin/users?err=admin_protected'); exit();
}
if ($userId === $adminId && $action === 'ban') {
  // กันแอดมินแบนตัวเอง
  header('Location: ' . ($baseUrl ?? '') . '/admin/users?err=self_ban_blocked'); exit();
}

/* ── ดำเนินการ ───────────────────────────────────────── */
try {
  $pdo->beginTransaction();

  if ($action === 'ban') {
    $reason = trim($_POST['ban_reason'] ?? '');
    if ($reason === '') $reason = 'ไม่ระบุเหตุผล';
    // จำกัดความยาวป้องกัน text ยาวผิดปกติ
    if (mb_strlen($reason) > 1000) $reason = mb_substr($reason, 0, 1000);

    $u = $pdo->prepare("
      UPDATE users
      SET status='banned', ban_reason=:r, banned_at=NOW()
      WHERE user_id=:id
      LIMIT 1
    ");
    $u->execute([':r'=>$reason, ':id'=>$userId]);

    // log แบบทางเลือก
    if ($pdo->query("SHOW TABLES LIKE 'activity_log'")->rowCount() > 0) {
      $insLog = $pdo->prepare("INSERT INTO activity_log (username, action) VALUES (?, ?)");
      $adminName = $_SESSION['username'] ?? ('admin#'.$adminId);
      $insLog->execute([$adminName, "แบนผู้ใช้ #{$userId} เหตุผล: {$reason}"]);
    }

    $pdo->commit();
    header('Location: ' . ($baseUrl ?? '') . '/admin/users?ban=1'); exit();
  }

  if ($action === 'unban') {
    $u = $pdo->prepare("
      UPDATE users
      SET status='active', ban_reason=NULL, banned_at=NULL
      WHERE user_id=:id
      LIMIT 1
    ");
    $u->execute([':id'=>$userId]);

    if ($pdo->query("SHOW TABLES LIKE 'activity_log'")->rowCount() > 0) {
      $insLog = $pdo->prepare("INSERT INTO activity_log (username, action) VALUES (?, ?)");
      $adminName = $_SESSION['username'] ?? ('admin#'.$adminId);
      $insLog->execute([$adminName, "ปลดแบนผู้ใช้ #{$userId}"]);
    }

    $pdo->commit();
    header('Location: ' . ($baseUrl ?? '') . '/admin/users?unban=1'); exit();
  }

  if ($action === 'delete') {
    // Delete user (might need to handle FKs depending on DB design)
    // For safety, let's just delete the user record. If there are FKs without CASCADE, this will fail and be caught by the catch block.
    $st = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
    $st->execute([$userId]);
    
    $pdo->commit();
    header('Location: ' . ($baseUrl ?? '') . '/admin/users?deleted=1'); exit();
  }

  if ($action === 'upgrade_admin') {
    $st = $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?");
    $st->execute([$userId]);
    
    $pdo->commit();
    header('Location: ' . ($baseUrl ?? '') . '/admin/users?upgraded=1'); exit();
  }

  // เงื่อนไขอื่น ๆ (ไม่ควรถึงตรงนี้)
  if ($pdo->inTransaction()) $pdo->rollBack();
  header('Location: ' . ($baseUrl ?? '') . '/admin/users'); exit();

} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  throw $e;
}
