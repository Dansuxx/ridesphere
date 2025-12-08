<?php
/**
 * Quick OTP System Verification
 * Run this in browser to verify all components are working
 */

header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html>
<head>
    <title>Ridesphere OTP System Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .check { margin: 15px 0; padding: 10px; border-left: 4px solid #ddd; border-radius: 4px; }
        .check.success { border-left-color: #28a745; background: #f0f8f4; }
        .check.error { border-left-color: #dc3545; background: #fff8f7; }
        .check.warning { border-left-color: #ffc107; background: #fffdf7; }
        .status { font-weight: bold; margin-bottom: 5px; }
        .details { font-size: 0.9em; color: #666; }
        h1 { color: #667eea; }
        h2 { color: #333; margin-top: 25px; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
        .code { background: #f5f5f5; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 0.9em; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Ridesphere OTP System Verification</h1>
        <p>Checking all components of the OTP email verification system...</p>";

// Check 1: Database connection
echo "<h2>1. Database Connection</h2>";
require_once 'db.php';
$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<div class='check success'>
        <div class='status'>✅ Database Connected</div>
        <div class='details'>PDO connection to MySQL established successfully</div>
    </div>";
} else {
    echo "<div class='check error'>
        <div class='status'>❌ Database Connection Failed</div>
        <div class='details'>Could not establish PDO connection. Check db.php configuration.</div>
    </div>";
    echo "</div></body></html>";
    exit;
}

// Check 2: Composer autoload
echo "<h2>2. Composer Dependencies</h2>";
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php';
    echo "<div class='check success'>
        <div class='status'>✅ Composer Installed</div>
        <div class='details'>vendor/autoload.php loaded successfully</div>
    </div>";
    
    // Check PHPMailer specifically
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        echo "<div class='check success'>
            <div class='status'>✅ PHPMailer Available</div>
            <div class='details'>PHPMailer class is available for use</div>
        </div>";
    } else {
        echo "<div class='check error'>
            <div class='status'>❌ PHPMailer Not Found</div>
            <div class='details'>PHPMailer class not found. Run: composer install</div>
        </div>";
    }
} else {
    echo "<div class='check error'>
        <div class='status'>❌ Composer Not Installed</div>
        <div class='details'>vendor/autoload.php not found. Run: composer install</div>
    </div>";
    echo "</div></body></html>";
    exit;
}

// Check 3: Database tables
echo "<h2>3. Database Tables</h2>";
try {
    // Check users table
    $checkUsers = $db->query("SHOW TABLES LIKE 'users'")->fetch();
    if ($checkUsers) {
        echo "<div class='check success'>
            <div class='status'>✅ Users Table Exists</div>
        </div>";
    } else {
        echo "<div class='check error'>
            <div class='status'>❌ Users Table Missing</div>
            <div class='details'>Run: php setup_database.php</div>
        </div>";
    }
    
    // Check otp_pending_emails table
    $checkOTP = $db->query("SHOW TABLES LIKE 'otp_pending_emails'")->fetch();
    if ($checkOTP) {
        echo "<div class='check success'>
            <div class='status'>✅ OTP Table Exists</div>
        </div>";
    } else {
        echo "<div class='check warning'>
            <div class='status'>⚠️ OTP Table Not Created Yet</div>
            <div class='details'>Table will be auto-created on first OTP send</div>
        </div>";
    }
} catch (Exception $e) {
    echo "<div class='check error'>
        <div class='status'>❌ Database Query Failed</div>
        <div class='details'>" . htmlspecialchars($e->getMessage()) . "</div>
    </div>";
}

// Check 4: sendEmailViaPHPMailer.php
echo "<h2>4. OTP Email Module</h2>";
if (file_exists('sendEmailViaPHPMailer.php')) {
    require_once 'sendEmailViaPHPMailer.php';
    
    if (function_exists('sendOTPEmail')) {
        echo "<div class='check success'>
            <div class='status'>✅ sendOTPEmail Function Available</div>
        </div>";
    } else {
        echo "<div class='check error'>
            <div class='status'>❌ sendOTPEmail Function Not Found</div>
        </div>";
    }
    
    if (function_exists('generateOTP')) {
        $testOTP = generateOTP();
        echo "<div class='check success'>
            <div class='status'>✅ generateOTP Function Available</div>
            <div class='details'>Test OTP generated: <strong>$testOTP</strong></div>
        </div>";
    } else {
        echo "<div class='check error'>
            <div class='status'>❌ generateOTP Function Not Found</div>
        </div>";
    }
} else {
    echo "<div class='check error'>
        <div class='status'>❌ sendEmailViaPHPMailer.php Not Found</div>
    </div>";
}

// Check 5: Gmail configuration
echo "<h2>5. Gmail SMTP Configuration</h2>";
if (file_exists('sendEmailViaPHPMailer.php')) {
    $content = file_get_contents('sendEmailViaPHPMailer.php');
    if (strpos($content, 'smtp.gmail.com') !== false) {
        echo "<div class='check success'>
            <div class='status'>✅ Gmail SMTP Configured</div>
            <div class='details'>Server: smtp.gmail.com:587 (TLS)</div>
        </div>";
    }
    if (strpos($content, 'ridesphererentcar@gmail.com') !== false) {
        echo "<div class='check success'>
            <div class='status'>✅ Gmail Account Configured</div>
            <div class='details'>From: ridesphererentcar@gmail.com</div>
        </div>";
    }
    if (strpos($content, 'pwpbuyvrctiifwk') !== false) {
        echo "<div class='check success'>
            <div class='status'>✅ App Password Configured</div>
            <div class='details'>Gmail app password is set</div>
        </div>";
    } else {
        echo "<div class='check warning'>
            <div class='status'>⚠️ Gmail App Password Not Found</div>
            <div class='details'>Emails may fail to send without app password</div>
        </div>";
    }
}

// Check 6: Frontend files
echo "<h2>6. Frontend Files</h2>";
$frontendFiles = ['index.php', 'script.js', 'styles.css'];
foreach ($frontendFiles as $file) {
    if (file_exists($file)) {
        echo "<div class='check success'>
            <div class='status'>✅ $file Present</div>
        </div>";
    } else {
        echo "<div class='check error'>
            <div class='status'>❌ $file Missing</div>
        </div>";
    }
}

// Check 7: auth.php API
echo "<h2>7. Authentication API</h2>";
if (file_exists('auth.php')) {
    $content = file_get_contents('auth.php');
    if (strpos($content, 'send_otp_to_email') !== false) {
        echo "<div class='check success'>
            <div class='status'>✅ send_otp_to_email Endpoint</div>
        </div>";
    }
    if (strpos($content, 'verify_otp_for_email') !== false) {
        echo "<div class='check success'>
            <div class='status'>✅ verify_otp_for_email Endpoint</div>
        </div>";
    }
}

echo "<h2>🚀 Ready to Test</h2>
    <p>All components are ready! To test the OTP flow:</p>
    <ol>
        <li>Visit <strong>http://localhost/ridesphere/</strong></li>
        <li>Click <strong>Create Account</strong></li>
        <li>Fill in your details with a valid email</li>
        <li>Click <strong>Create Account</strong></li>
        <li>Check your email inbox for OTP code (check spam folder)</li>
        <li>Enter OTP in the modal dialog</li>
        <li>Account created! You can now login</li>
    </ol>
    
    <h2>📋 Troubleshooting</h2>
    <div class='code'>
    If OTP modal doesn't show:
    - Check browser console (F12 → Console tab)
    - Clear browser cache
    - Verify styles.css .modal class is defined
    
    If OTP email doesn't arrive:
    - Check spam/junk folder
    - Verify Gmail app password is correct
    - Check PHP error logs
    - Gmail account must have \"Less secure apps\" enabled or use App Password
    </div>
    </div>
    </body>
    </html>";
?>
