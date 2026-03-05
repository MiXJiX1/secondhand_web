<?php
session_start();
if (!isLoggedIn()) {
    redirect($baseUrl . "/login");
}
$user_id = (int)$_SESSION['user_id'];

require_once __DIR__ . "/../../config/database.php";

// Database already provided by config/database.php ($pdo)

// โหลดข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT username, fname, lname FROM users WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
    throw new Exception("ไม่พบผู้ใช้", 404);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ตรวจ CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])) {
        $error = "CSRF token ไม่ถูกต้อง";
    } else {
        $new_username = trim($_POST['username'] ?? '');
        $fname = trim($_POST['fname'] ?? '');
        $lname = trim($_POST['lname'] ?? '');
        $new_password = $_POST['password'] ?? '';

        if ($new_username === '' || $fname === '' || $lname === '') {
            $error = "กรุณากรอกข้อมูลให้ครบ";
        } else {
            // เช็ค username ซ้ำ
            $check = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id != ?");
            $check->execute([$new_username, $user_id]);

            if ($check->rowCount() > 0) {
                $error = "ชื่อผู้ใช้นี้ถูกใช้แล้ว";
            } else {
                if ($new_password !== '') {
                    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                    $update = $pdo->prepare("UPDATE users SET username=?, fname=?, lname=?, password=? WHERE user_id=?");
                    $update->execute([$new_username, $fname, $lname, $hashed, $user_id]);
                } else {
                    $update = $pdo->prepare("UPDATE users SET username=?, fname=?, lname=? WHERE user_id=?");
                    $update->execute([$new_username, $fname, $lname, $user_id]);
                }

                // อัปเดต session
                $_SESSION['username'] = $new_username;
                $_SESSION['fname'] = $fname;
                $_SESSION['lname'] = $lname;

                redirect($baseUrl . "/profile?updated=true");
            }
        }
    }
}

// เตรียม CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrfToken = $_SESSION['csrf_token'];
