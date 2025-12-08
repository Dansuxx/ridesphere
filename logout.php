<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';

// Simple logout endpoint — primarily used to record last_logout timestamp for users.
// The app is stateless (no server-side session); frontend should still clear local state.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }

    $user_id = isset($data['user_id']) ? intval($data['user_id']) : 0;

    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    try {
        if ($user_id > 0) {
            // If column last_logout doesn't exist, ignore silently
            $colCheckQ = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'last_logout'";
            $colStmt = $db->query($colCheckQ);
            $colRow = $colStmt->fetch(PDO::FETCH_ASSOC);
            $hasLastLogout = ($colRow && intval($colRow['cnt']) > 0);

            if ($hasLastLogout) {
                $stmt = $db->prepare("UPDATE users SET last_logout = NOW() WHERE id = :id");
                $stmt->execute([':id' => $user_id]);
            }
        }

        // Return success regardless to keep client flow simple
        echo json_encode(['success' => true, 'message' => 'Logged out']);
    } catch (Exception $e) {
        error_log('logout.php error: ' . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Server error during logout']);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>