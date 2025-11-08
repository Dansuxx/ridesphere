<?php
require_once __DIR__ . '/../db.php';
$db = (new Database())->getConnection();
$email = 'boncalesdanryljames@gmail.com';
$stmt = $db->prepare('SELECT id, username, email, email_verified FROM users WHERE username = :u OR email = :e');
$stmt->execute([':u' => $email, ':e' => $email]);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows, JSON_PRETTY_PRINT);
