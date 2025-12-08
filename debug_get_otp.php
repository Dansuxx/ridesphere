<?php
// debug_get_otp.php
// Safe debug endpoint to read the latest OTP for a given email.
// Usage (browser): http://localhost/ridesphere/debug_get_otp.php?email=you@example.com

header('Content-Type: application/json');
require_once __DIR__ . '/db.php';

try {
    $email = isset($_GET['email']) ? trim($_GET['email']) : '';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Provide a valid email as ?email=you@example.com']);
        exit(0);
    }

    $db = (new Database())->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit(0);
    }

    $stmt = $db->prepare('SELECT otp_code, otp_expires, created_at FROM otp_pending_emails WHERE email = :email ORDER BY created_at DESC LIMIT 1');
    $stmt->execute([':email' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'No OTP found for that email']);
    } else {
        echo json_encode(['success' => true, 'otp' => $row]);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

?>
