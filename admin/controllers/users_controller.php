<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
  header("Location: ../php/login.php"); exit();
}

/* ===== CSRF ===== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf = $_SESSION['csrf_token'];

/* ===== DB ===== */
require_once __DIR__ . "/../../config/database.php";
// PDO is provided by database.php ($pdo)

try {
  $stmt = $pdo->query("
    SELECT 
      u.user_id, u.username, u.email, u.fname, u.lname,
      u.role, u.status, u.img, u.credit_balance
    FROM users u
    ORDER BY u.user_id DESC
  ");
  $users = $stmt->fetchAll();

  $countStmt = $pdo->query("SELECT COUNT(*) AS total_users FROM users");
  $totalUsers = (int)($countStmt->fetch()['total_users'] ?? 0);

} catch (PDOException $e) {
  throw new Exception("Database error: ".$e->getMessage());
}
