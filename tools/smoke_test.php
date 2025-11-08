<?php
// Simple smoke test for signup -> send_verification -> verify -> login
// Usage: php tools/smoke_test.php

function http_post_json($url, $data) {
    $ch = curl_init($url);
    $payload = json_encode($data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    $resp = curl_exec($ch);
    $err = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err) throw new Exception("HTTP error: $err");
    return ['code' => $code, 'body' => $resp, 'json' => json_decode($resp, true)];
}

$base = 'http://localhost:8080/ridesphere/auth.php';
try {
    // 1) signup
    $email = 'smoketest+' . bin2hex(random_bytes(4)) . '@example.com';
    $signupData = [
        'action' => 'signup',
        'firstName' => 'Smoke',
        'lastName' => 'Test',
        'email' => $email,
        'password' => 'Sm0keTest!'
    ];
    echo "Signing up user: $email\n";
    $r = http_post_json($base, $signupData);
    echo "Signup response: " . $r['body'] . "\n";
    if (empty($r['json']) || empty($r['json']['success']) || $r['json']['success'] !== true) {
        throw new Exception('Signup failed');
    }

    // 2) send_verification
    $send = http_post_json($base, ['action' => 'send_verification', 'email' => $email]);
    echo "send_verification response: " . $send['body'] . "\n";
    if (empty($send['json']) || empty($send['json']['success'])) {
        throw new Exception('send_verification failed');
    }

    // 3) read token from DB directly
    require_once __DIR__ . '/../db.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT verification_token FROM users WHERE email = :e');
    $stmt->execute([':e' => $email]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['verification_token'])) {
        throw new Exception('No verification token found in DB');
    }
    $token = $row['verification_token'];
    echo "Found token: $token\n";

    // 4) verify_email
    $verify = http_post_json($base, ['action' => 'verify_email', 'token' => $token]);
    echo "verify response: " . $verify['body'] . "\n";
    if (empty($verify['json']) || empty($verify['json']['success'])) {
        throw new Exception('verify failed');
    }

    // 5) login
    $login = http_post_json($base, ['action' => 'login', 'email' => $email, 'password' => 'Sm0keTest!']);
    echo "login response: " . $login['body'] . "\n";
    if (empty($login['json']) || empty($login['json']['success']) || $login['json']['success'] !== true) {
        throw new Exception('login failed');
    }

    echo "Smoke test completed successfully.\n";
} catch (Exception $e) {
    echo "Smoke test failed: " . $e->getMessage() . "\n";
    exit(1);
}
