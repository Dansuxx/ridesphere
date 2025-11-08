<?php
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Ridesphere Setup</title>
</head>
<body>
    <h1>Ridesphere Database Setup</h1>
    <?php
    require_once 'db.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";
        
        // Run table creation
        include 'create_tables.php';
        
    } else {
        echo "<p style='color: red;'>✗ Database connection failed!</p>";
        echo "<p>Please make sure:</p>";
        echo "<ul>";
        echo "<li>XAMPP/WAMP is running</li>";
        echo "<li>MySQL is started</li>";
        echo "<li>Database 'ridesphere' exists</li>";
        echo "</ul>";
    }
    ?>
</body>
</html>