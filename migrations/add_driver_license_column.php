<?php
// Migration: add_driver_license_column.php
// Run this via CLI: php migrations/add_driver_license_column.php
header("Content-Type: application/json");
require_once __DIR__ . "/../db.php";

$database = new Database();
$db = $database->getConnection();
if (!$db) {
    echo json_encode(["success" => false, "message" => "Database connection failed"]);
    exit;
}

try {
    $colCheckQ = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'driver_license'";
    $colStmt = $db->query($colCheckQ);
    $colRow = $colStmt->fetch(PDO::FETCH_ASSOC);
    $hasDriverLicense = ($colRow && intval($colRow['cnt']) > 0);

    if ($hasDriverLicense) {
        echo json_encode(["success" => true, "message" => "Column 'driver_license' already exists"]);
        exit;
    }

    $db->exec("ALTER TABLE users ADD COLUMN driver_license VARCHAR(64) NULL");
    echo json_encode(["success" => true, "message" => "Column 'driver_license' added to users table"]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
}
