<?php
session_start();
require_once __DIR__ . "/../../config/database.php";

if (!function_exists('json_response')) {
    function json_response($arr, $code = 200) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($arr, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $pwd      = $_POST['password'] ?? '';
    $fname    = trim($_POST['fname'] ?? '');
    $lname    = trim($_POST['lname'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));

    if (!$username || !$pwd || !$fname || !$lname || !$email) {
        json_response(['status' => 'error', 'message' => 'กรุณากรอกข้อมูลให้ครบถ้วน'], 400);
    }

    // Simple MSU Validation
    if (!preg_match('/^\d{11}$/', $username)) {
        json_response(['status' => 'error', 'message' => 'ชื่อผู้ใช้ต้องเป็นรหัสนิสิต 11 หลัก'], 400);
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || !str_ends_with($email, '@msu.ac.th')) {
        json_response(['status' => 'error', 'message' => 'อีเมลต้องเป็น @msu.ac.th'], 400);
    }

    // Check Duplicate
    $stmt = $conn->prepare("SELECT 1 FROM users WHERE username=? OR email=? LIMIT 1");
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        json_response(['status' => 'error', 'message' => 'ชื่อผู้ใช้หรืออีเมลนี้มีในระบบแล้ว'], 409);
    }

    // Avatar
    $avatar = 'default.png';
    if (!empty($_FILES['avatar']['name'])) {
        $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
        $tmp = $_FILES['avatar']['tmp_name'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $realMime = finfo_file($finfo, $tmp);
        finfo_close($finfo);
        
        $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (in_array($realMime, $allowed, true)) {
            $avatar = 'u_'.bin2hex(random_bytes(8)).'.'.$ext;
            move_uploaded_file($tmp, __DIR__.'/../../uploads/avatars/'.$avatar);
        }
    }

    $hash = password_hash($pwd, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, password, email, fname, lname, img, role, status) VALUES (?,?,?,?,?,?,'user','active')");
    $stmt->bind_param('ssssss', $username, $hash, $email, $fname, $lname, $avatar);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'user_new', 'สมัครสมาชิกใหม่ในระบบ')";
        $logStmt = $conn->prepare($logSql);
        $logStmt->bind_param('is', $new_id, $username);
        $logStmt->execute();

        json_response(['status' => 'success', 'message' => 'สมัครสมาชิกสำเร็จ']);
    } else {
        json_response(['status' => 'error', 'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล'], 500);
    }
}
