<?php
// CLI helper to fetch latest OTP for an email. Run with PHP CLI:
// C:\xampp\php\php.exe -f debug_get_otp_cli.php your.email@example.com

if (php_sapi_name() !== 'cli') {
    echo "This script is for CLI only.\n";
    exit(1);
}

$email = $argv[1] ?? '';
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Usage: php debug_get_otp_cli.php your.email@example.com']) . PHP_EOL;
    exit(1);
}

require_once __DIR__ . '/db.php';
$db = (new Database())->getConnection();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']) . PHP_EOL;
    exit(1);
}
$stmt = null;
// Ensure the OTP table exists (safe migration)
$db->exec("CREATE TABLE IF NOT EXISTS otp_pending_emails (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    otp_expires DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

$stmt = $db->prepare('SELECT otp_code, otp_expires, created_at FROM otp_pending_emails WHERE email = :email ORDER BY created_at DESC LIMIT 1');
$stmt->execute([':email' => $email]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No OTP found for that email']) . PHP_EOL;
} else {
    echo json_encode(['success' => true, 'otp' => $row]) . PHP_EOL;
}

?>
