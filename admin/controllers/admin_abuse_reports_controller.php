<?php
/* admin_abuse_reports.php — จัดการข้อร้องเรียน */
ini_set('display_errors',1); ini_set('display_startup_errors',1); error_reporting(E_ALL);
session_start();
if (!isset($_SESSION['user_id'])) { header('Location: ../login.php'); exit; }
if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); die('Forbidden'); }

require_once __DIR__ . "/../../config/database.php";

/* ---------- สร้างตารางติดตามแบบเบา ๆ (ถ้ายังไม่มี) ---------- */
$pdo->exec("
  CREATE TABLE IF NOT EXISTS abuse_report_followers (
  report_id INT NOT NULL,
  admin_id  INT NOT NULL,
  followed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (report_id, admin_id),
  INDEX(admin_id),
  CONSTRAINT fk_fol_report FOREIGN KEY (report_id) REFERENCES abuse_reports(report_id) ON DELETE CASCADE,
  CONSTRAINT fk_fol_admin  FOREIGN KEY (admin_id)  REFERENCES users(user_id)       ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

/* ---------- CSRF ---------- */
if (empty($_SESSION['csrf_admin'])) $_SESSION['csrf_admin'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_admin'];

/* ---------- Helpers ---------- */
if(!function_exists('h')){ function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
if(!function_exists('has_col')){
function has_col(PDO $pdo, string $table, string $col): bool {
  $q=$pdo->prepare("SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? LIMIT 1");
  $q->execute([$table,$col]); return (bool)$q->fetchColumn();
}
}
/* ตรวจคอลัมน์จริงของคุณ */
$A_HAS_USER   = has_col($pdo,'abuse_report_actions','user_id');
$A_HAS_ADMIN  = has_col($pdo,'abuse_report_actions','admin_id');
$A_HAS_NOTE   = has_col($pdo,'abuse_report_actions','action_note');
$A_HAS_NSTAT  = has_col($pdo,'abuse_report_actions','new_status');

$N_HAS_USER   = has_col($pdo,'abuse_report_notes','user_id');
$N_HAS_ADMIN  = has_col($pdo,'abuse_report_notes','admin_id');

/* ฟิลเตอร์ */
$q     = trim($_GET['q'] ?? '');
$stat  = $_GET['status'] ?? '';
$d1    = trim($_GET['d1'] ?? '');
$d2    = trim($_GET['d2'] ?? '');
$page  = max(1,(int)($_GET['page'] ?? 1));
$per   = min(100,max(10,(int)($_GET['per'] ?? 20)));
$off   = ($page-1)*$per;

/* ---------- ฟังก์ชัน insert ที่ประกอบ column อัตโนมัติ ---------- */
if(!function_exists('insert_action')){
function insert_action(PDO $pdo, int $rid, int $actorId, string $type, ?string $note=null, ?string $newStatus=null,
                       bool $A_HAS_USER=false, bool $A_HAS_ADMIN=false, bool $A_HAS_NOTE=false, bool $A_HAS_NSTAT=false){
  $cols = ['report_id','action_type'];
  $vals = [$rid, $type];
  if ($A_HAS_USER)  { $cols[] = 'user_id';  $vals[] = $actorId; }
  if ($A_HAS_ADMIN) { $cols[] = 'admin_id'; $vals[] = $actorId; }
  if ($A_HAS_NOTE && $note!==null) { $cols[]='action_note'; $vals[]=$note; }
  if ($A_HAS_NSTAT && $newStatus!==null){ $cols[]='new_status'; $vals[]=$newStatus; }
  $ph = implode(',', array_fill(0,count($cols),'?'));
  $sql = "INSERT INTO abuse_report_actions (".implode(',',$cols).") VALUES ($ph)";
  $st = $pdo->prepare($sql); $st->execute($vals);
}
}
if(!function_exists('insert_note')){
function insert_note(PDO $pdo, int $rid, int $actorId, string $note, bool $N_HAS_USER=false, bool $N_HAS_ADMIN=false){
  $cols = ['report_id','note']; $vals = [$rid,$note];
  // ถ้าตารางบังคับ user_id/admin_id ไม่ให้ว่าง ให้ใส่ทั้งคู่ (ถ้ามี)
  if ($N_HAS_USER)  { $cols[]='user_id';  $vals[]=$actorId; }
  if ($N_HAS_ADMIN) { $cols[]='admin_id'; $vals[]=$actorId; }
  $ph = implode(',', array_fill(0,count($cols),'?'));
  $sql = "INSERT INTO abuse_report_notes (".implode(',',$cols).") VALUES ($ph)";
  $st = $pdo->prepare($sql); $st->execute($vals);
}
}

/* ---------- POST actions ---------- */
$msg = '';
if ($_SERVER['REQUEST_METHOD']==='POST' && hash_equals($csrf, $_POST['csrf'] ?? '')) {
  $rid  = (int)($_POST['report_id'] ?? 0);
  $me   = (int)$_SESSION['user_id'];

  // เพิ่มบันทึกตรวจสอบ
  if ($_POST['action']==='add_note' && $rid>0) {
    $note = trim($_POST['note'] ?? '');
    if ($note==='') {
      $msg = 'โปรดกรอกบันทึก';
    } else {
      // กัน FK: ต้องมี report จริง
      $chk = $pdo->prepare("SELECT 1 FROM abuse_reports WHERE report_id=? LIMIT 1");
      $chk->execute([$rid]);
      if (!$chk->fetchColumn()) {
        $msg = 'ไม่พบรายงานนี้ (อาจถูกลบ) — เพิ่มบันทึกไม่สำเร็จ';
      } else {
        insert_note($pdo,$rid,$me,$note,$N_HAS_USER,$N_HAS_ADMIN);
        // log action
        insert_action($pdo,$rid,$me,'note',$note,null,$A_HAS_USER,$A_HAS_ADMIN,$A_HAS_NOTE,$A_HAS_NSTAT);
        $msg = 'เพิ่มบันทึกแล้ว';
      }
    }
  }

  // เปลี่ยนสถานะ
  if ($_POST['action']==='update_status' && $rid>0) {
    $new = $_POST['new_status'] ?? '';
    if (in_array($new, ['open','reviewing','done'], true)) {
      $pdo->prepare("UPDATE abuse_reports SET status=? WHERE report_id=?")->execute([$new,$rid]);
      insert_action($pdo,$rid,$me,'status_change',("→ ".$new),$new,$A_HAS_USER,$A_HAS_ADMIN,$A_HAS_NOTE,$A_HAS_NSTAT);
      $msg = 'อัปเดตสถานะเรียบร้อย';
    }
  }

  // ติดตาม/เลิกติดตาม (เก็บใน abuse_report_followers)
  if ($_POST['action']==='toggle_follow' && $rid>0) {
    $ex = $pdo->prepare("SELECT 1 FROM abuse_report_followers WHERE report_id=? AND admin_id=?");
    $ex->execute([$rid,$me]);
    if ($ex->fetchColumn()) {
      $pdo->prepare("DELETE FROM abuse_report_followers WHERE report_id=? AND admin_id=?")->execute([$rid,$me]);
      $msg = 'เลิกติดตามรายงานแล้ว';
    } else {
      // กัน FK
      $chk = $pdo->prepare("SELECT 1 FROM abuse_reports WHERE report_id=? LIMIT 1");
      $chk->execute([$rid]);
      if ($chk->fetchColumn()) {
        $pdo->prepare("INSERT INTO abuse_report_followers (report_id, admin_id) VALUES (?,?)")->execute([$rid,$me]);
        $msg = 'ติดตามรายงานแล้ว';
      } else {
        $msg = 'ไม่พบรายงานนี้ (อาจถูกลบ) — ติดตามไม่สำเร็จ';
      }
    }
  }
}

/* ---------- WHERE ---------- */
$W = ["1=1"]; $P=[];
if ($q!==''){
  $W[]="(ar.details LIKE ? OR ar.reason LIKE ? OR ar.report_id = ?)";
  array_push($P, "%$q%","%$q%", (ctype_digit($q)? (int)$q : 0));
}
if (in_array($stat,['open','reviewing','done'],true)){ $W[]="ar.status=?"; $P[]=$stat; }
if ($d1 && preg_match('/^\d{4}-\d{2}-\d{2}$/',$d1)){ $W[]="ar.created_at >= ?"; $P[]="$d1 00:00:00"; }
if ($d2 && preg_match('/^\d{4}-\d{2}-\d{2}$/',$d2)){ $W[]="ar.created_at <= ?"; $P[]="$d2 23:59:59"; }
$W = implode(' AND ',$W);

/* ---------- count + list ---------- */
$st = $pdo->prepare("
  SELECT COUNT(*)
  FROM abuse_reports ar
  JOIN users ru ON ru.user_id=ar.reporter_id
  WHERE $W
"); $st->execute($P); $total=(int)$st->fetchColumn();
$pages = max(1,(int)ceil($total/$per));
$per_i=(int)$per; $off_i=(int)$off;

$st = $pdo->prepare("
  SELECT ar.*, ru.username AS reporter_name
  FROM abuse_reports ar
  JOIN users ru ON ru.user_id=ar.reporter_id
  WHERE $W
  ORDER BY ar.report_id DESC
  LIMIT $per_i OFFSET $off_i
"); $st->execute($P); $rows=$st->fetchAll();

/* ---------- load notes (ล่าสุด) ---------- */
$notesByReport = [];
if ($rows){
  $ids = implode(',', array_fill(0,count($rows),'?'));
  $qN  = $pdo->prepare("
    SELECT n.*, u.username AS admin_name
    FROM abuse_report_notes n
    JOIN users u ON u.user_id = ".($N_HAS_ADMIN ? "n.admin_id" : "n.user_id")."
    WHERE n.report_id IN ($ids)
    ORDER BY n.created_at DESC
    LIMIT 300
  ");
  $qN->execute(array_column($rows,'report_id'));
  while($n=$qN->fetch()){ $notesByReport[(int)$n['report_id']][]=$n; }
}

/* ---------- followed? ---------- */
$followed = [];
if ($rows){
  $ids = implode(',', array_fill(0,count($rows),'?'));
  $qF = $pdo->prepare("SELECT report_id FROM abuse_report_followers WHERE admin_id=? AND report_id IN ($ids)");
  $params = array_merge([$_SESSION['user_id']], array_column($rows,'report_id'));
  $qF->execute($params);
  while($f=$qF->fetch()){ $followed[(int)$f['report_id']] = true; }
}
