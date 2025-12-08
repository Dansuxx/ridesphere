<?php
// debug_create_otp_cli.php
// Usage (CLI): C:\xampp\php\php.exe -f debug_create_otp_cli.php you@example.com

if (php_sapi_name() !== 'cli') {
    echo "This script is CLI only.\n";
    exit(1);
}

$email = $argv[1] ?? '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Usage: php debug_create_otp_cli.php your.email@example.com']) . PHP_EOL;
    exit(1);
}

require_once __DIR__ . '/db.php';
$db = (new Database())->getConnection();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']) . PHP_EOL;
    exit(1);
}

// Ensure table exists
$db->exec("CREATE TABLE IF NOT EXISTS otp_pending_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_expires DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

$otp = generateOTP();
$expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));

$stmt = $db->prepare("INSERT INTO otp_pending_emails (email, otp_code, otp_expires) VALUES (:email, :otp, :expires) ON DUPLICATE KEY UPDATE otp_code = :otp, otp_expires = :expires");
$stmt->execute([':email' => $email, ':otp' => $otp, ':expires' => $expires]);

echo json_encode(['success' => true, 'email' => $email, 'otp' => $otp, 'expires' => $expires]) . PHP_EOL;

?>
