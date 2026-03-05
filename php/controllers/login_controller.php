<?php
// login_controller.php
require_once __DIR__ . "/../../config/database.php";

if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(24));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        redirect($baseUrl . '/login?error=csrf');
    }
    $inputUsername = isset($_POST['username']) ? trim($_POST['username']) : '';
    $inputPassword = isset($_POST['password']) ? (string)$_POST['password'] : '';

    if ($inputUsername !== '' && $inputPassword !== '') {
        try {
            $sql = "SELECT user_id, username, password, role, status FROM users WHERE username = ? LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$inputUsername]);
            $row = $stmt->fetch();

            if ($row) {
                if (strtolower((string)$row['status']) === 'banned') {
                    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'banned_login', 'พยายามเข้าสู่ระบบแต่ถูกแบน')";
                    $logStmt = $pdo->prepare($logSql);
                    $logStmt->execute([$row['user_id'], $row['username']]);

                    redirect($baseUrl . '/login?error=banned&u=' . urlencode($inputUsername));
                }
                if (password_verify($inputPassword, $row['password']) || hash_equals($row['password'], $inputPassword)) {
                    session_regenerate_id(true);
                    $_SESSION['user_id'] = $row['user_id'];
                    $_SESSION['username'] = $row['username'];
                    $_SESSION['role'] = $row['role'] ?? 'user';
                    
                    $logSql = "INSERT INTO activity_logs (user_id, username, action_type, description) VALUES (?, ?, 'login', 'เข้าสู่ระบบสำเร็จ')";
                    $logStmt = $pdo->prepare($logSql);
                    $logStmt->execute([$row['user_id'], $row['username']]);

                    if ($_SESSION['role'] === 'admin') {
                        redirect($baseUrl . '/admin/dashboard');
                    } else {
                        redirect($baseUrl . '/');
                    }
                }
            }
        } catch (PDOException $e) {
            throw new Exception("Database error during login: " . $e->getMessage());
        }
    }
    redirect($baseUrl . '/login?error=1');
}
