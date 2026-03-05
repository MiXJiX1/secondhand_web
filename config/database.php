<?php
// config/database.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../vendor/autoload.php';

// Load environment variables
try {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
} catch (Exception $e) {
    // If .env is missing in production, we might rely on actual environment variables
}

define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'secondhand_web');

define('PROMPTPAY_ID',     $_ENV['PROMPTPAY_ID'] ?? '');
define('RECEIVER_BANK_ID', $_ENV['RECEIVER_BANK_ID'] ?? '');
define('INTERNAL_API_KEY', $_ENV['INTERNAL_API_KEY'] ?? '');

define('PAYMENT_MODE', $_ENV['PAYMENT_MODE'] ?? 'development'); 
define('MSUPAY_ENDPOINT', $_ENV['MSUPAY_ENDPOINT'] ?? '');
define('MSUPAY_MERCHANT_ID', $_ENV['MSUPAY_MERCHANT_ID'] ?? '');
define('MSUPAY_SECRET', $_ENV['MSUPAY_SECRET'] ?? '');

// Calculate dynamic Base URL
$scriptName = $_SERVER['SCRIPT_NAME'] ?? ''; 
$baseUrl = rtrim(dirname($scriptName), '/\\');
$phpSelf = $_SERVER['PHP_SELF'] ?? '';
$dirName = basename(dirname($phpSelf));
if (in_array($dirName, ['php', 'ChatApp', 'chatapp', 'help', 'api', 'admin'])) {
    $baseUrl = rtrim(dirname(dirname($scriptName)), '/\\');
}
if ($baseUrl === '\\' || $baseUrl === '/') $baseUrl = '';

// 0) Initialize ErrorHandler & Helpers
require_once __DIR__ . '/../includes/ErrorHandler.php';
require_once __DIR__ . '/../includes/helpers.php';
ErrorHandler::initialize();

// 1) Establish PDO connection ($pdo)
$dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . ($_ENV['DB_CHARSET'] ?? 'utf8mb4');
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, 
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    throw new Exception("Database connection failed: " . $e->getMessage());
}
