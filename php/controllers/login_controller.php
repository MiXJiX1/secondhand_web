<?php
// login_controller.php
session_start();
require_once __DIR__ . "/../../config/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
    $inputPassword = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($inputUsername !== '' && $inputPassword !== '') {
        $sql = "SELECT user_id, username, password, role, status FROM users WHERE username = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $inputUsername);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if (strtolower($row['status']) === 'banned') {
                $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'banned_login', 'พยายามเข้าสู่ระบบแต่ถูกแบน')";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param('is', $row['user_id'], $row['username']);
                $logStmt->execute();

                header('Location: login.php?error=banned&u='.urlencode($inputUsername));
                exit;
            }
            if (password_verify($inputPassword, $row['password']) || hash_equals($row['password'], $inputPassword)) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'] ?? 'user';
                
                $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'login', 'เข้าสู่ระบบสำเร็จ')";
                $logStmt = $conn->prepare($logSql);
                $logStmt->bind_param('is', $row['user_id'], $row['username']);
                $logStmt->execute();

                if ($_SESSION['role'] === 'admin') {
                    header('Location: ../admin/dashboard.php');
                } else {
                    header('Location: ../index.php');
                }
                exit;
            }
        }
    }
    header('Location: login.php?error=1');
    exit;
}
