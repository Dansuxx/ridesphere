<?php
header("Content-Type: text/plain");

echo "Testing database connection...\n\n";

try {
    $host = "localhost";
    $dbname = "ridesphere";
    $username = "root";
    $password = "";
    
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Database connection SUCCESSFUL!\n";
    
    // Check if tables exist
    $tables = ['users', 'vehicles', 'messages', 'bookings'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table '$table' exists\n";
        } else {
            echo "✗ Table '$table' DOES NOT exist\n";
        }
    }
    
} catch(PDOException $e) {
    echo "✗ Database connection FAILED: " . $e->getMessage() . "\n";
    echo "Trying to create database...\n";
    
    // Try to create database
    try {
        $conn = new PDO("mysql:host=$host", $username, $password);
        $conn->exec("CREATE DATABASE IF NOT EXISTS $dbname");
        echo "✓ Database created successfully\n";
    } catch(PDOException $e2) {
        echo "✗ Could not create database: " . $e2->getMessage() . "\n";
    }
}
?>