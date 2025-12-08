<?php
// tools_list_users.php - simple CLI/web helper to list a few users (id, email, last_logout)
require_once __DIR__ . '/db.php';
$db = (new Database())->getConnection();
if (!$db) { echo json_encode(['success'=>false,'message'=>'DB connection failed']); exit; }
try {
    $stmt = $db->prepare("SELECT id, email, username, role, last_logout FROM users ORDER BY id ASC LIMIT 10");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    header('Content-Type: application/json');
    echo json_encode(['success'=>true, 'rows'=>$rows], JSON_PRETTY_PRINT);
} catch (Exception $e) {
    echo json_encode(['success'=>false, 'message'=>$e->getMessage()]);
}
?>