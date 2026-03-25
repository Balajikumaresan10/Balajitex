<?php
require_once 'app/db.php';
$pdo = DB::conn();

try {
    // Add missing columns to workers table
    $pdo->exec("ALTER TABLE workers ADD COLUMN IF NOT EXISTS per_day_salary DECIMAL(10,2) DEFAULT 0.00");
    $pdo->exec("ALTER TABLE workers ADD COLUMN IF NOT EXISTS days_worked INT DEFAULT 0");
    $pdo->exec("ALTER TABLE workers ADD COLUMN IF NOT EXISTS total_salary DECIMAL(10,2) DEFAULT 0.00");
    
    echo "<h1>Database Updated!</h1>";
    echo "Added per_day_salary, days_worked, and total_salary columns to workers table.";
} catch (Exception $e) {
    echo "<h1>Error:</h1>" . $e->getMessage();
}
?>
