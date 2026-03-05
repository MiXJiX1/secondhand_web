<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

// CSRF
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$CSRF = $_SESSION['csrf'];

$flash = null;

// Fetch User Data Early (Needed for validation in POST actions)
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) { session_destroy(); header("Location: login.php"); exit; }

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // CSRF Check (Except for initial profile load)
    if ($action !== '' && (!isset($_POST['csrf']) || $_POST['csrf'] !== $CSRF)) {
        $flash = ['type'=>'error', 'msg'=>'การตรวจสอบความปลอดภัยล้มเหลว (CSRF)'];
    } else {
        // A) Upload Avatar
        if ($action === 'upload_avatar' && isset($_FILES['avatar'])) {
            $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
            $newName = 'u_'.bin2hex(random_bytes(6)).'.'.$ext;
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], __DIR__.'/../../uploads/avatars/'.$newName)) {
                $pdo->prepare("UPDATE users SET img=? WHERE user_id=?")->execute([$newName, $user_id]);
                $flash = ['type'=>'success', 'msg'=>'อัปเดตรูปโปรไฟล์เรียบร้อย'];
            }
        }
        
        // B) Update Personal Info
        if ($action === 'update_profile') {
            $fname = trim($_POST['fname'] ?? '');
            $lname = trim($_POST['lname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $bio   = trim($_POST['bio'] ?? '');
            
            if ($fname==='' || $lname==='' || $email==='') {
                $flash = ['type'=>'error', 'msg'=>'กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน'];
            } else {
                $st = $pdo->prepare("UPDATE users SET fname=?, lname=?, email=?, phone=?, bio=? WHERE user_id=?");
                $st->execute([$fname, $lname, $email, $phone, $bio, $user_id]);
                $flash = ['type'=>'success', 'msg'=>'บันทึกข้อมูลส่วนตัวเรียบร้อยแล้ว'];
            }
        }
        
        // C) Change Password
        if ($action === 'change_password') {
            $old_pass = $_POST['old_password'] ?? '';
            $new_pass = $_POST['new_password'] ?? '';
            $cfm_pass = $_POST['confirm_password'] ?? '';
            
            // Fetch current password
            $q = $pdo->prepare("SELECT password FROM users WHERE user_id=? LIMIT 1");
            $q->execute([$user_id]);
            $curr = $q->fetch();
            
            if (!password_verify($old_pass, $curr['password'])) {
                $flash = ['type'=>'error', 'msg'=>'รหัสผ่านเดิมไม่ถูกต้อง'];
            } elseif ($new_pass !== $cfm_pass) {
                $flash = ['type'=>'error', 'msg'=>'รหัสผ่านใหม่ไม่ตรงกัน'];
            } elseif (strlen($new_pass) < 6) {
                $flash = ['type'=>'error', 'msg'=>'รหัสผ่านใหม่ต้องมีความยาวอย่างน้อย 6 ตัวอักษร'];
            } else {
                $hashed = password_hash($new_pass, PASSWORD_DEFAULT);
                $pdo->prepare("UPDATE users SET password=? WHERE user_id=?")->execute([$hashed, $user_id]);
                $flash = ['type'=>'success', 'msg'=>'เปลี่ยนรหัสผ่านเรียบร้อยแล้ว'];
            }
        }
        
        // D) Update Bank Account
        if ($action === 'update_bank') {
            $bank_name = trim($_POST['bank_name'] ?? '');
            $bank_acc  = trim($_POST['bank_account'] ?? '');
            $bank_user = trim($_POST['bank_account_name'] ?? '');
            
            if ($bank_name==='' || $bank_acc==='' || $bank_user==='') {
                $flash = ['type'=>'error', 'msg'=>'กรุณากรอกข้อมูลบัญชีธนาคารให้ครบถ้วน'];
            } elseif ($user['bank_verified'] == 1) {
                // Prevent updating if already verified
                $flash = ['type'=>'error', 'msg'=>'บัญชีธนาคารของคุณได้รับการยืนยันแล้ว ไม่สามารถแก้ไขได้'];
            } else {
                $st = $pdo->prepare("UPDATE users SET bank_name=?, bank_account=?, bank_account_name=?, bank_verified=0 WHERE user_id=?");
                $st->execute([$bank_name, $bank_acc, $bank_user, $user_id]);
                $flash = ['type'=>'success', 'msg'=>'อัปเดตข้อมูลบัญชีธนาคารเรียบร้อย (รอการตรวจสอบ)'];
            }
        }

        // E) Request Withdrawal
        if ($action === 'request_withdrawal') {
            $amount = (float)($_POST['amount'] ?? 0);
            
            if ($user['bank_verified'] != 1) {
                $flash = ['type'=>'error', 'msg'=>'คุณต้องยืนยันบัญชีธนาคารก่อนจึงจะสามารถถอนเงินได้'];
            } elseif ($amount < 20) {
                $flash = ['type'=>'error', 'msg'=>'จำนวนเงินขั้นต่ำในการถอนคือ 20 บาท'];
            } elseif ($amount > $user['credit_balance']) {
                $flash = ['type'=>'error', 'msg'=>'ยอดเงินคงเหลือไม่เพียงพอ'];
            } else {
                $pdo->beginTransaction();
                try {
                    // Create withdrawal record
                    $requestId = 'WD' . strtoupper(bin2hex(random_bytes(6)));
                    $stmt = $pdo->prepare("INSERT INTO credit_withdrawals (user_id, amount, bank_name, bank_account, account_name, status, ref_txn, created_at) VALUES (?, ?, ?, ?, ?, 'requested', ?, NOW())");
                    $stmt->execute([$user_id, $amount, $user['bank_name'], $user['bank_account'], $user['bank_account_name'], $requestId]);
                    
                    // Deduct balance
                    $stmt = $pdo->prepare("UPDATE users SET credit_balance = credit_balance - ? WHERE user_id = ?");
                    $stmt->execute([$amount, $user_id]);
                    
                    // Record in ledger
                    $stmt = $pdo->prepare("INSERT INTO credit_ledger (user_id, change_amt, reason, ref_id, created_at) VALUES (?, ?, 'withdraw', ?, NOW())");
                    $stmt->execute([$user_id, -$amount, $requestId]);
                    
                    $pdo->commit();
                    $flash = ['type'=>'success', 'msg'=>'ส่งคำขอถอนเงินเรียบร้อยแล้ว กรุณารอการตรวจสอบจากแอดมิน'];
                    
                    // Update local user object for UI
                    $user['credit_balance'] -= $amount;
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $flash = ['type'=>'error', 'msg'=>'เกิดข้อผิดพลาด: ' . $e->getMessage()];
                }
            }
        }
    }
}

// Fetch User Data (Moved to top)

$avatarPath = !empty($user['img']) ? '../uploads/avatars/'.basename($user['img']) : '../assets/no-avatar.png';
$credit = (float)($user['credit_balance'] ?? 0);
$fullName = trim($user['fname'] . ' ' . $user['lname']) ?: $user['username'];
$joinDate = date('F Y', strtotime($user['created_at']));
$joinDateTh = date('M Y', strtotime($user['created_at']));

// Fetch Stats (Active Listings, Items Sold)
$stStats = $pdo->prepare("SELECT COUNT(CASE WHEN status='available' THEN 1 END) as active_count, COUNT(CASE WHEN status='sold' THEN 1 END) as sold_count FROM products WHERE user_id = ?");
$stStats->execute([$user_id]);
$stats = $stStats->fetch();
$activeCount = $stats['active_count'] ?? 0;
$soldCount = $stats['sold_count'] ?? 0;

// Fetch Average Rating
$stRating = $pdo->prepare("SELECT AVG(score) as avg_score, COUNT(*) as count FROM user_ratings WHERE rated_user_id = ?");
$stRating->execute([$user_id]);
$ratingData = $stRating->fetch();
$avgRating = $ratingData['avg_score'] ? number_format((float)$ratingData['avg_score'], 1) : '0.0';
$ratingCount = $ratingData['count'] ?? 0;

// Fetch Transaction History (Topups & Withdrawals)
$qHistory = $pdo->prepare("
    (SELECT 'topup' as type, amount, status, created_at, method as detail, slip_path, reference_no as ref
     FROM credit_topups 
     WHERE user_id = ?)
    UNION ALL
    (SELECT 'withdraw' as type, amount, status, created_at, bank_name as detail, slip_path, ref_txn as ref
     FROM credit_withdrawals 
     WHERE user_id = ?)
    ORDER BY created_at DESC
    LIMIT 20
");
$qHistory->execute([$user_id, $user_id]);
$history = $qHistory->fetchAll();

// Bank Logo Helper
$bankLogoMap = [
    'กสิกรไทย' => 'kbank.png',
    'ไทยพาณิชย์' => 'scb.png',
    'กรุงไทย' => 'ktb.png',
    'กรุงเทพ' => 'bbl.png',
    'กรุงศรีอยุธยา' => 'bay.png',
    'ทหารไทยธนชาต' => 'ttb.png',
    'ออมสิน' => 'gsb.png'
];

// Recent Purchases
$po = $pdo->prepare("SELECT o.*, p.product_name, p.product_image FROM orders o JOIN products p ON o.product_id=p.product_id WHERE o.user_id=? ORDER BY o.id DESC LIMIT 20");
$po->execute([$user_id]);
$purchases = $po->fetchAll();

// Recent Sales
$so = $pdo->prepare("
    SELECT o.*, p.product_name, p.product_image, b.username AS buyer_name 
    FROM orders o 
    JOIN products p ON o.product_id = p.product_id 
    JOIN users b ON o.user_id = b.user_id
    WHERE p.user_id = ? 
    ORDER BY o.id DESC 
    LIMIT 20
");
$so->execute([$user_id]);
$sales = $so->fetchAll();

// User location
// Hardcoded to Thailand for now as there is no specific field
$location = "Thailand";

// Fetch Favorites
$sf = $pdo->prepare("SELECT f.*, p.product_name, p.product_image, p.product_price, p.location_name 
                     FROM favorites f 
                     JOIN products p ON f.product_id = p.product_id 
                     WHERE f.user_id = ? 
                     ORDER BY f.created_at DESC");
$sf->execute([$user_id]);
$favorites = $sf->fetchAll();

// For Navbar
$currentUserId = $user_id;

$userDisplayName = '';
$userAvatarImage = '';
$userAvatarText = '🙂';
if ($stmtNav = $pdo->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1")) {
    $stmtNav->execute([$currentUserId]);
    $u = $stmtNav->fetch();
    if ($u) {
        $userAvatarImage = !empty($u['img']) ? '../uploads/avatars/'.basename($u['img']) : '';
        $fn = trim((string)($u['fname'] ?? ''));
        $ln = trim((string)($u['lname'] ?? ''));
        if ($fn !== '' || $ln !== '') {
            $userDisplayName = trim($fn . ' ' . $ln);
        } else {
            $userDisplayName = (string)($u['username'] ?? ($_SESSION['username'] ?? ''));
        }
        $parts = preg_split('/\s+/', $userDisplayName, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts) {
            $userAvatarText = mb_substr($parts[0], 0, 1, 'UTF-8') . (isset($parts[1]) ? mb_substr($parts[1], 0, 1, 'UTF-8') : '');
        }
    }
}
