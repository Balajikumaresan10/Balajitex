<?php
/**
 * One-time database setup script.
 * Visit /setup.php on Railway to create all tables.
 * DELETE this file after setup is complete.
 */

require __DIR__ . '/app/db.php';

$pdo = DB::conn();
$errors = [];
$success = [];

$statements = [
    'companies' => "CREATE TABLE IF NOT EXISTS `companies` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_name` VARCHAR(255) NOT NULL,
        `created_date` DATE NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'yarn_types' => "CREATE TABLE IF NOT EXISTS `yarn_types` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT NOT NULL,
        `name` VARCHAR(255) NOT NULL,
        UNIQUE KEY `unique_company_yarn` (`company_id`, `name`),
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'stocks' => "CREATE TABLE IF NOT EXISTS `stocks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `yarn_type_id` INT DEFAULT NULL,
        `cotton_type` VARCHAR(255) DEFAULT NULL,
        `bag_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
        `total_bags` INT NOT NULL DEFAULT 0,
        `stock_type` VARCHAR(50) DEFAULT 'bag',
        `date` DATE NOT NULL,
        `notes` TEXT DEFAULT NULL,
        `sold_bags` INT NOT NULL DEFAULT 0,
        `sold_cones` INT NOT NULL DEFAULT 0,
        `sold_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
        FOREIGN KEY (`yarn_type_id`) REFERENCES `yarn_types`(`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'workers' => "CREATE TABLE IF NOT EXISTS `workers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(255) NOT NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'work_logs' => "CREATE TABLE IF NOT EXISTS `work_logs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT NOT NULL,
        `worker_id` INT NOT NULL,
        `work_date` DATE NOT NULL,
        `warps_count` INT NOT NULL DEFAULT 0,
        `rate_per_warp` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'advances' => "CREATE TABLE IF NOT EXISTS `advances` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT NOT NULL,
        `worker_id` INT NOT NULL,
        `advance_date` DATE NOT NULL,
        `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        `note` TEXT DEFAULT NULL,
        `settled` TINYINT(1) NOT NULL DEFAULT 0,
        `paid_amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($statements as $table => $sql) {
    try {
        $pdo->exec($sql);
        $success[] = "✅ Table <strong>$table</strong> created (or already exists)";
    } catch (Exception $e) {
        $errors[] = "❌ Table <strong>$table</strong>: " . $e->getMessage();
    }
}

// Verify tables exist
$existing = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html>
<head>
    <title>DB Setup - Balaji Tex</title>
    <style>
        body { font-family: sans-serif; max-width: 700px; margin: 40px auto; padding: 20px; }
        h1 { color: #1a1a2e; }
        .success { color: #16a34a; margin: 6px 0; }
        .error { color: #dc2626; margin: 6px 0; }
        .tables { background: #f0fdf4; border: 1px solid #86efac; padding: 16px; border-radius: 8px; margin-top: 20px; }
        .warn { background: #fef9c3; border: 1px solid #fde047; padding: 16px; border-radius: 8px; margin-top: 20px; }
    </style>
</head>
<body>
    <h1>🗄️ Balaji Tex — Database Setup</h1>

    <h2>Results</h2>
    <?php foreach ($success as $msg): ?>
        <p class="success"><?php echo $msg; ?></p>
    <?php endforeach; ?>
    <?php foreach ($errors as $msg): ?>
        <p class="error"><?php echo $msg; ?></p>
    <?php endforeach; ?>

    <div class="tables">
        <strong>Tables in database:</strong><br>
        <?php echo implode(', ', $existing) ?: 'None'; ?>
    </div>

    <?php if (empty($errors)): ?>
    <div class="warn">
        ⚠️ <strong>Setup complete!</strong> Please delete <code>setup.php</code> from your repo for security.
        <br><br>
        <a href="index.php">→ Go to the App</a>
    </div>
    <?php endif; ?>
</body>
</html>
