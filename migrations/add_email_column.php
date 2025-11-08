<?php
require_once __DIR__ . '/../db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "Database connection failed\n";
    exit(1);
}

try {
    // Check if email column exists
    $q = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'";
    $stmt = $db->query($q);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row && intval($row['cnt']) === 0) {
        echo "Adding email column to users table...\n";
        $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) UNIQUE NULL AFTER address");
    } else {
        echo "Email column already exists or could not determine.\n";
    }

    // Make username nullable if it's not already
    $q2 = "SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'username'";
    $s2 = $db->query($q2);
    $r2 = $s2->fetch(PDO::FETCH_ASSOC);
    if ($r2 && $r2['IS_NULLABLE'] === 'NO') {
        echo "Altering username to allow NULL...\n";
        $db->exec("ALTER TABLE users MODIFY username VARCHAR(100) UNIQUE NULL");
    }

    echo "Migration completed.\n";
} catch (PDOException $e) {
    echo "Migration error: " . $e->getMessage() . "\n";
}
