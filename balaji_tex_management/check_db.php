<?php
require_once __DIR__ . '/app/db.php';
$pdo = DB::conn();
echo 'Database file: ' . $pdo->query('PRAGMA database_list')->fetchColumn() . '<br>';
echo 'Current working directory: ' . getcwd() . '<br>';
echo 'App DB path: ' . __DIR__ . '/app/db.php<br>';
echo 'SQLite file path in DB class: ' . (defined('DB_PATH') ? DB_PATH : 'Not defined') . '<br>';
// Test insert
try {
    $testStmt = $pdo->prepare('INSERT INTO stock_log (company_id, yarn_type_id, yarn_type_name, type, bag_count, net_weight, created_at) VALUES (?,?,?,?,?,?,?)');
    $testStmt->execute([1, 1, 'Test Yarn', 'Test', 1, 1.0, date('Y-m-d H:i:s')]);
    echo 'Test insert successful. Last insert ID: ' . $pdo->lastInsertId() . '<br>';
    // Clean up test
    $pdo->exec('DELETE FROM stock_log WHERE yarn_type_name = "Test Yarn"');
} catch (Exception $e) {
    echo 'Insert failed: ' . $e->getMessage() . '<br>';
}
// Check if data persists
$count = $pdo->query('SELECT COUNT(*) FROM stock_log')->fetchColumn();
echo 'Total records in stock_log now: ' . $count . '<br>';
?>
