<?php
// debug_check_otp.php
// Usage (web): http://localhost/ridesphere/debug_check_otp.php?email=you@example.com
// Usage (CLI): php debug_check_otp.php you@example.com

if (php_sapi_name() === 'cli') {
    $email = $argv[1] ?? '';
} else {
    $email = $_GET['email'] ?? '';
}

header('Content-Type: application/json');

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Provide a valid email as ?email= or CLI arg']);
    exit;
}

require_once __DIR__ . '/db.php';
$db = (new Database())->getConnection();
if (!$db) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM otp_pending_emails WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo json_encode(['success' => true, 'row' => $row]);
    } else {
        echo json_encode(['success' => false, 'message' => 'No OTP row for that email']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

?>