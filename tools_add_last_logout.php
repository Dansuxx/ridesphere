<?php
require_once __DIR__ . '/db.php';
$db = (new Database())->getConnection();
if (!$db) { echo json_encode(['success'=>false,'message'=>'DB connection failed']); exit; }
try {
    $colCheckQ = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_logout'";
    $colStmt = $db->query($colCheckQ);
    $colRow = $colStmt->fetch(PDO::FETCH_ASSOC);
    $hasLastLogout = ($colRow && intval($colRow['cnt']) > 0);
    if ($hasLastLogout) {
        echo json_encode(['success'=>true,'message'=>'Column already exists']);
        exit;
    }
    $db->exec("ALTER TABLE users ADD COLUMN last_logout DATETIME NULL DEFAULT NULL");
    echo json_encode(['success'=>true,'message'=>'Column added']);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>$e->getMessage()]);
}
?>