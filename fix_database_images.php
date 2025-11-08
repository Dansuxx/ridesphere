<?php
header("Content-Type: text/plain");
echo "Fixing database structure for image storage...\n\n";

require_once 'db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "✗ Database connection failed!\n";
    exit;
}

try {
    // Remove old image column if exists
    $db->exec("ALTER TABLE vehicles DROP COLUMN IF EXISTS image");
    echo "✓ Removed old image column\n";
    
    // Add new image columns
    $db->exec("ALTER TABLE vehicles ADD COLUMN image_filename VARCHAR(255) AFTER status");
    $db->exec("ALTER TABLE vehicles ADD COLUMN image_path VARCHAR(500) AFTER image_filename");
    echo "✓ Added new image storage columns\n";
    
    // Update status enum to include 'pending'
    $db->exec("ALTER TABLE vehicles MODIFY COLUMN status ENUM('available', 'unavailable', 'booked', 'pending') DEFAULT 'available'");
    echo "✓ Updated status enum to include 'pending'\n";
    
    // Create uploads directory if it doesn't exist
    if (!file_exists('uploads')) {
        mkdir('uploads', 0777, true);
        echo "✓ Created uploads directory\n";
    }
    
    echo "\n✓ Database structure fixed successfully!\n";
    echo "✓ Uploads directory ready for image storage\n";
    
} catch(PDOException $e) {
    echo "✗ Error fixing database: " . $e->getMessage() . "\n";
}
?>