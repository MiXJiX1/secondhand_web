<?php
// api/categories_list.php
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once __DIR__ . '/../config/database.php';

$items = [];
try {
    // Check if categories table exists
    $st = $pdo->query("SHOW TABLES LIKE 'categories'");
    if ($st->fetch()) {
        $sql = "SELECT name, slug, sort_order FROM categories ORDER BY sort_order ASC, name ASC";
        $rs = $pdo->query($sql);
        while ($row = $rs->fetch()) {
            $items[] = [
                'value' => $row['name'],
                'label' => $row['name'],
            ];
        }
    } else {
        // Fallback
        $defaults = ['electronics','fashion','furniture','vehicle','gameandtoys','household','sport','music','others'];
        foreach ($defaults as $v) $items[] = ['value'=>$v,'label'=>$v];
    }
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>'query_failed: ' . $e->getMessage()]);
    exit;
}

$ver = sha1(json_encode($items, JSON_UNESCAPED_UNICODE));
echo json_encode(['ok'=>true, 'ver'=>$ver, 'items'=>$items], JSON_UNESCAPED_UNICODE);
