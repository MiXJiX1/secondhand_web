<?php
// includes/config.php

const DB_HOST = 'localhost';
const DB_USER = 'root';
const DB_PASS = '';
const DB_NAME = 'secondhand_web';

const PROMPTPAY_ID     = '0931898053'; // PromptPay
const RECEIVER_BANK_ID = '004';        // KBank = 004 
const INTERNAL_API_KEY = '2708758b587a625aefee31dcc4cd2b479c8e799a3fbcae4340d15bbb977bbfdf'; 

define('PAYMENT_MODE', 'development'); // 'production' | 'sandbox' | 'disabled'
define('MSUPAY_ENDPOINT', 'https://<REAL_MSUPAY_GATEWAY>/checkout'); // ของจริงเท่านั้น

// Calculate dynamic Base URL
$scriptName = $_SERVER['SCRIPT_NAME']; 
// If estamos en /php/xxx.php, dirname is /php, we want the root
// Actually, it's safer to use a more robust way or just copy from twig.php but adjust
$baseUrl = rtrim(dirname($scriptName), '/\\');
// If we are in a subdirectory like /php/ or /ChatApp/, we need to go up
if (basename(dirname($_SERVER['PHP_SELF'])) == 'php' || basename(dirname($_SERVER['PHP_SELF'])) == 'ChatApp' || basename(dirname($_SERVER['PHP_SELF'])) == 'help') {
    $baseUrl = rtrim(dirname(dirname($scriptName)), '/\\');
}
if ($baseUrl === '\\') $baseUrl = '';

/**
 * Global Helper for safe HTML output (UTF-8)
 */
function h($s) {
    if ($s === null) return '';
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// 1) Establish MySQLi connection ($conn)
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
     $mysqli = $conn; 
     $conn->set_charset("utf8mb4");
 } catch (Exception $e) {
    die("Database connection failed (mysqli): " . $e->getMessage());
}

// 2) Establish PDO connection ($pdo)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    die("Database connection failed (PDO): " . $e->getMessage());
}
// MSUPAY Configuration
define('MSUPAY_MERCHANT_ID', 'YOUR_MERCHANT_ID');
define('MSUPAY_SECRET', 'YOUR_SECRET_KEY');
// Probable old PROMPTPAY_ID removed
 // From topup_process.php observation
