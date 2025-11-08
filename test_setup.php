<?php
header("Content-Type: text/plain");
echo "Testing Ridesphere Setup\n";
echo "========================\n\n";

// Test database
echo "1. Testing Database:\n";
try {
    require_once 'db.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✓ Database connection: SUCCESS\n";
        
        // Check if users table exists and has data
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Users table: " . $result['count'] . " users found\n";
    } else {
        echo "✗ Database connection: FAILED\n";
    }
} catch(Exception $e) {
    echo "✗ Database error: " . $e->getMessage() . "\n";
}

echo "\n2. Testing File Structure:\n";
$files = ['auth.php', 'db.php', 'vehicles.php', 'messages.php', 'index.php'];
foreach ($files as $file) {
    if (file_exists($file)) {
        echo "✓ $file: FOUND\n";
    } else {
        echo "✗ $file: MISSING\n";
    }
}

echo "\n3. Testing PHP Configuration:\n";
echo "PHP Version: " . phpversion() . "\n";
echo "JSON Support: " . (function_exists('json_decode') ? 'YES' : 'NO') . "\n";
echo "PDO Support: " . (class_exists('PDO') ? 'YES' : 'NO') . "\n";
?>