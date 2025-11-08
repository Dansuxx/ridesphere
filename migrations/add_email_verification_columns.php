<?php
require_once __DIR__ . '/../db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "Database connection failed\n";
    exit(1);
}

try {
    // Ensure 'email' column exists (legacy installs may not have it)
    $q = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'";
    $r = $db->query($q)->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        echo "Adding column email...\n";
        // Add email column nullable to avoid breaking legacy rows
        $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(255) NULL AFTER username");
    } else {
        echo "email column already exists\n";
    }

    // Check and add email_verified
    $q = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified'";
    $r = $db->query($q)->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        echo "Adding column email_verified...\n";
        $db->exec("ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER email");
    } else {
        echo "email_verified already exists\n";
    }

    // verification_token
    $q = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_token'";
    $r = $db->query($q)->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        echo "Adding column verification_token...\n";
        $db->exec("ALTER TABLE users ADD COLUMN verification_token VARCHAR(128) NULL AFTER email_verified");
    } else {
        echo "verification_token already exists\n";
    }

    // verification_expires
    $q = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'verification_expires'";
    $r = $db->query($q)->fetch(PDO::FETCH_ASSOC);
    if (intval($r['cnt']) === 0) {
        echo "Adding column verification_expires...\n";
        $db->exec("ALTER TABLE users ADD COLUMN verification_expires DATETIME NULL AFTER verification_token");
    } else {
        echo "verification_expires already exists\n";
    }

    echo "Migration complete.\n";
} catch (PDOException $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
