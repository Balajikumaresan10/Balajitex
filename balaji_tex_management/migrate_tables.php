<?php
require_once __DIR__ . '/app/db.php';

try {
    $pdo = DB::conn();
    echo "Connected to database.\n";

    // 1. Create purchased_stocks table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `purchased_stocks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT NOT NULL,
        `yarn_type_id` INT NOT NULL,
        `date_purchased` DATE NOT NULL,
        `supplier_name` VARCHAR(255) NOT NULL,
        `bag_count` INT NOT NULL,
        `weight_per_bag` DECIMAL(10,3) NOT NULL,
        `total_weight` DECIMAL(10,3) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`yarn_type_id`) REFERENCES `yarn_types`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table purchased_stocks created or already exists.\n";

    // 2. Create stock_sales table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `stock_sales` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `company_id` INT NOT NULL,
        `yarn_type_id` INT NOT NULL,
        `stock_type` ENUM('chippam', 'bag') NOT NULL,
        `sold_date` DATE NOT NULL,
        `quantity` INT NOT NULL,
        `weight_per_unit` DECIMAL(10,3) NOT NULL,
        `total_weight` DECIMAL(10,3) NOT NULL,
        `notes` TEXT,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`yarn_type_id`) REFERENCES `yarn_types`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "Table stock_sales created or already exists.\n";

    // 3. Update stocks table with new columns if they don't exist
    $cols = $pdo->query("SHOW COLUMNS FROM stocks")->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('sold_bags', $cols)) {
        $pdo->exec("ALTER TABLE stocks ADD COLUMN sold_bags INT NOT NULL DEFAULT 0");
        echo "Added column sold_bags to stocks table.\n";
    }
    if (!in_array('sold_weight', $cols)) {
        $pdo->exec("ALTER TABLE stocks ADD COLUMN sold_weight DECIMAL(10,3) NOT NULL DEFAULT 0.000");
        echo "Added column sold_weight to stocks table.\n";
    }
    if (!in_array('yarn_type_id', $cols)) {
        $pdo->exec("ALTER TABLE stocks ADD COLUMN yarn_type_id INT DEFAULT NULL, ADD FOREIGN KEY (yarn_type_id) REFERENCES yarn_types(id) ON DELETE SET NULL");
        echo "Added column yarn_type_id to stocks table.\n";
    }

    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
