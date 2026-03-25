-- Balaji Tex Management - MySQL Schema
-- Run this on your Railway MySQL instance to initialize the database

CREATE DATABASE IF NOT EXISTS `balaji_tex` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `balaji_tex`;

-- Companies table
CREATE TABLE IF NOT EXISTS `companies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_name` VARCHAR(255) NOT NULL,
    `created_date` DATE NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Yarn types table
CREATE TABLE IF NOT EXISTS `yarn_types` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    UNIQUE KEY `unique_company_yarn` (`company_id`, `name`),
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Stocks table
CREATE TABLE IF NOT EXISTS `stocks` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `yarn_type_id` INT DEFAULT NULL,
    `cotton_type` VARCHAR(255) DEFAULT NULL,
    `bag_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    `total_bags` INT NOT NULL DEFAULT 0,
    `stock_type` VARCHAR(50) DEFAULT 'bag',  -- 'bag' or 'chippam'
    `date` DATE NOT NULL,
    `notes` TEXT DEFAULT NULL,
    `sold_bags` INT NOT NULL DEFAULT 0,
    `sold_cones` INT NOT NULL DEFAULT 0,
    `sold_weight` DECIMAL(10,3) NOT NULL DEFAULT 0.000,
    FOREIGN KEY (`yarn_type_id`) REFERENCES `yarn_types`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Workers table
CREATE TABLE IF NOT EXISTS `workers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Work logs table
CREATE TABLE IF NOT EXISTS `work_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `company_id` INT NOT NULL,
    `worker_id` INT NOT NULL,
    `work_date` DATE NOT NULL,
    `warps_count` INT NOT NULL DEFAULT 0,
    `rate_per_warp` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    `amount` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`worker_id`) REFERENCES `workers`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Advances table
CREATE TABLE IF NOT EXISTS `advances` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
