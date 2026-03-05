<?php
// api/categories_list.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/database.php';
// For PDO, connection errors are typically handled by exceptions when the PDO object is instantiated.
// The database.php file should handle connection errors, or this block needs to be adapted for PDO exceptions.
// Assuming $pdo is already connected and ready to use, or exceptions will be caught by the later try/catch.
// if ($pdo->connect_error) { // PDO does not have a direct connect_error property
//   http_response_code(500);
//   echo json_encode(['ok'=>false,'error'=>'db_connect']);
//   exit;
// }
// PDO charset is usually set during connection or via ATTR_CHARSET
// $pdo->set_charset('utf8mb4'); // This is for mysqli, not PDO

$items = [];
try {
  // ถ้ามีตาราง categories ให้ดึงแบบไดนามิก
  $q = $mysqli->query("SHOW TABLES LIKE 'categories'");
  if ($q && $q->num_rows > 0) {
    $sql = "SELECT name, slug, sort_order FROM categories ORDER BY sort_order ASC, name ASC";
    $rs  = $mysqli->query($sql);
    while ($row = $rs->fetch_assoc()) {
      $items[] = [
        'value' => $row['name'],   // ใช้ name เป็นค่าที่ส่งไป filter
        'label' => $row['name'],   // ป้ายแสดงผล (จะ map เป็นภาษาไทยในฝั่งหน้าเว็บได้)
      ];
    }
  } else {
    // Fallback ถ้ายังไม่มีตาราง (กันเว็บพัง)
    $defaults = ['electronics','fashion','furniture','vehicle','gameandtoys','household','sport','music','others'];
    foreach ($defaults as $v) $items[] = ['value'=>$v,'label'=>$v];
  }
} catch (\Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>'query_failed']);
  exit;
}

// ทำเวอร์ชันสำหรับแคชฝั่ง client (ถ้าข้อมูลไม่เปลี่ยนจะไม่ re-render)
$ver = sha1(json_encode($items, JSON_UNESCAPED_UNICODE));

echo json_encode(['ok'=>true, 'ver'=>$ver, 'items'=>$items], JSON_UNESCAPED_UNICODE);
