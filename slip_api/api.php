<?php
/**
 * Slip API Endpoint
 * 
 * Usage: Send a POST request with 'Authorization: Bearer <Your_Key>'
 * and the slip image in the 'file' field.
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/SlipScanner.php';

use SlipAPI\SlipScanner;

// 1. Authorization
$auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
$token = str_replace('Bearer ', '', $auth);

if ($token !== INTERNAL_API_KEY) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'message' => 'unauthorized']);
    exit;
}

// 2. File Check
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'no_file_uploaded_or_invalid_request']);
    exit;
}

// 3. Process
$scanner = new SlipScanner();
$result = $scanner->scanFile($_FILES['file']['tmp_name']);

// Transform result slightly to match expected API shape globally if needed
if ($result['ok']) {
    // Top-up process usually expects receiver -> bank -> id to match RECEIVER_BANK_ID
    $result['data']['receiver']['bank']['id'] = RECEIVER_BANK_ID;
}

echo json_encode($result);
