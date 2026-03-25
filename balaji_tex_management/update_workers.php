<?php
require_once 'app/db.php';
$pdo = DB::conn();

try {
    // Helper function to add column if it doesn't exist (MySQL/SQLite compatible check)
    function addColumnIfNeeded($pdo, $table, $column, $definition) {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
            return true;
        }
        return false;
    }

    $added = [];
    if (addColumnIfNeeded($pdo, 'workers', 'days_worked', "INT DEFAULT 0")) $added[] = 'days_worked';
    if (addColumnIfNeeded($pdo, 'workers', 'total_salary', "DECIMAL(10,2) DEFAULT 0.00")) $added[] = 'total_salary';
    
    echo "<h1>Database Update Status</h1>";
    if (empty($added)) {
        echo "<p>No updates needed. Columns already exist.</p>";
    } else {
        echo "<p>Successfully added: " . implode(', ', $added) . "</p>";
    }
} catch (Exception $e) {
    echo "<h1>Error:</h1>" . $e->getMessage();
}
?>
