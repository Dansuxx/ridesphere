<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';
    $database = new Database();
    $db = $database->getConnection();

    switch($action) {
        case 'send_message':
            sendMessage($db, $data);
            break;
        case 'get_messages':
            getMessages($db, $data);
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid action"]);
    }
}

function sendMessage($db, $data) {
    $query = "INSERT INTO messages (from_user_id, to_user_id, content) 
              VALUES (:from_user_id, :to_user_id, :content)";
    
    $stmt = $db->prepare($query);
    
    try {
        $stmt->execute([
            ':from_user_id' => $data['from_user_id'],
            ':to_user_id' => $data['to_user_id'],
            ':content' => $data['content']
        ]);
        
        echo json_encode(["success" => true, "message" => "Message sent"]);
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error sending message"]);
    }
}

function getMessages($db, $data) {
    $query = "SELECT m.*, u1.first_name as from_first_name, u2.first_name as to_first_name 
              FROM messages m 
              JOIN users u1 ON m.from_user_id = u1.id 
              JOIN users u2 ON m.to_user_id = u2.id 
              WHERE (m.from_user_id = :user_id1 AND m.to_user_id = :user_id2) 
                 OR (m.from_user_id = :user_id2 AND m.to_user_id = :user_id1) 
              ORDER BY m.created_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':user_id1' => $data['user_id1'],
        ':user_id2' => $data['user_id2']
    ]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "messages" => $messages]);
}
?>