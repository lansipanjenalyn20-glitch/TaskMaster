<?php
$host = 'localhost';
$db   = 'taskmaster_db';$user = 'root';
$pass = '';$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    // Connect without database name first to ensure database creation if missing
    $pdo = new PDO($dsn,$user, $pass,$options);
    
    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
    $pdo->exec("USE `$db`;");

    // Create users table if not exists
    $users_sql = "CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `username` VARCHAR(50) NOT NULL UNIQUE,
            `password` VARCHAR(255) NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP    
    ) ENGINE=InnoDB;";
    
    $pdo->exec($users_sql);

    // Create tasks table with user_id relationship if not exists
    $tasks_sql = "CREATE TABLE IF NOT EXISTS `tasks` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(255) NOT NULL,
        `description` TEXT NULL,
        `priority` ENUM('Low', 'Medium', 'High') DEFAULT 'Medium',
        `due_date` DATE NULL,
        `category` VARCHAR(100) DEFAULT 'General',
        `completed` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB;";
    
    $pdo->exec($tasks_sql);

} catch (\PDOException $e) {
    die("Database Connection & Setup Failed: " . $e->getMessage());
}
?>