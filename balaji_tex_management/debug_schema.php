<?php
require 'app/db.php';
try {
    $pdo = DB::conn();
    $stmt = $pdo->query('DESCRIBE workers');
    $columns = $stmt->fetchAll(PDO::FETCH_Column);
    print_r($columns);
} catch (Exception $e) {
    try {
        // Try sqlite if DESCRIBE fails
        $stmt = $pdo->query('PRAGMA table_info(workers)');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        print_r($columns);
    } catch (Exception $e2) {
        echo "Error: " . $e->getMessage() . "\n" . $e2->getMessage();
    }
}
