<?php
// Safe migration: add `image` LONGTEXT column to `vehicles` if it doesn't exist.
require_once __DIR__ . '/../db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "Database connection failed\n";
    exit(1);
}

try {
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'vehicles' AND COLUMN_NAME = 'image'");
    $stmt->execute();
    $exists = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($exists) {
        echo "Column 'image' already exists on vehicles table.\n";
        exit(0);
    }

    $db->exec("ALTER TABLE vehicles ADD COLUMN image LONGTEXT NULL AFTER status");
    echo "Column 'image' added successfully.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
