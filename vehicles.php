<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Prefer $_POST (multipart/form-data or regular POST) when available
    $postAction = $_POST['action'] ?? null;
    $database = new Database();
    $db = $database->getConnection();

    if ($postAction) {
        // Handle file uploads first
        if (isset($_FILES['vehicle_image']) && $postAction == 'add_vehicle_with_image') {
            handleVehicleWithImage();
            exit;
        } elseif (isset($_FILES['vehicle_image']) && $postAction == 'update_vehicle') {
            handleUpdateWithImage();
            exit;
        }

        // No file upload; use $_POST data (works for FormData without files or application/x-www-form-urlencoded)
        $action = $postAction;
        $data = $_POST;

        switch($action) {
            case 'add_vehicle':
                addVehicle($db, $data);
                break;
            case 'update_vehicle':
                updateVehicle($db, $data);
                break;
            case 'delete_vehicle':
                deleteVehicle($db, $data);
                break;
            case 'get_vehicles':
                getVehicles($db);
                break;
            case 'get_owner_vehicles':
                getOwnerVehicles($db, $data);
                break;
            case 'update_vehicle_status':
                updateVehicleStatus($db, $data);
                break;
            case 'get_vehicle_details':
                getVehicleDetails($db, $data);
                break;
            default:
                echo json_encode(["success" => false, "message" => "Invalid action"]);
        }
        exit;
    }

    // Fallback: expect JSON body
    $data = json_decode(file_get_contents("php://input"), true);
    $action = $data['action'] ?? '';

    switch($action) {
        case 'add_vehicle':
            addVehicle($db, $data);
            break;
        case 'update_vehicle':
            updateVehicle($db, $data);
            break;
        case 'delete_vehicle':
            deleteVehicle($db, $data);
            break;
        case 'get_vehicles':
            getVehicles($db);
            break;
        case 'get_owner_vehicles':
            getOwnerVehicles($db, $data);
            break;
        case 'update_vehicle_status':
            updateVehicleStatus($db, $data);
            break;
        case 'get_vehicle_details':
            getVehicleDetails($db, $data);
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid action"]);
    }
    exit;
}

function handleUpdateWithImage() {
    $database = new Database();
    $db = $database->getConnection();

    try {
        $vehicle_id = $_POST['vehicle_id'] ?? '';
        $owner_id = $_POST['owner_id'] ?? '';

        if (empty($vehicle_id) || empty($owner_id)) {
            echo json_encode(["success" => false, "message" => "vehicle_id and owner_id are required"]);
            return;
        }

        $image_filename = null;
        $image_path = null;

        if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_extension = pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $unique_filename;

            if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $target_path)) {
                $image_filename = $unique_filename;
                $image_path = $target_path;
            }
        }

        // Collect other fields
        $data = [];
        $fields = ['name','type','rate','hours','location','description','status'];
        foreach ($fields as $f) {
            if (isset($_POST[$f])) $data[$f] = $_POST[$f];
        }

        if ($image_filename) {
            $data['image_filename'] = $image_filename;
            $data['image_path'] = $image_path;
        }

        $data['vehicle_id'] = $vehicle_id;
        $data['owner_id'] = $owner_id;

        // Call updateVehicle with assembled data
        updateVehicle($db, $data);
    } catch (Exception $e) {
        echo json_encode(["success" => false, "message" => "Error processing update: " . $e->getMessage()]);
    }
}

function handleVehicleWithImage() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $name = $_POST['name'] ?? '';
        $type = $_POST['type'] ?? '';
        $rate = $_POST['rate'] ?? '';
        $hours = $_POST['hours'] ?? '';
        $location = $_POST['location'] ?? '';
        $description = $_POST['description'] ?? '';
        $owner_id = $_POST['owner_id'] ?? '';
        
        if(empty($name) || empty($type) || empty($rate) || empty($hours) || empty($location) || empty($owner_id)) {
            echo json_encode(["success" => false, "message" => "All required fields must be filled"]);
            return;
        }
        
        // Handle file upload
        $image_filename = '';
        $image_path = '';
        
        if (isset($_FILES['vehicle_image']) && $_FILES['vehicle_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['vehicle_image']['name'], PATHINFO_EXTENSION);
            $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
            $target_path = $upload_dir . $unique_filename;
            
            if (move_uploaded_file($_FILES['vehicle_image']['tmp_name'], $target_path)) {
                $image_filename = $unique_filename;
                $image_path = $target_path;
            }
        }
        
        // Insert vehicle into database
        $query = "INSERT INTO vehicles (name, type, rate, hours, location, description, owner_id, image_filename, image_path) 
                  VALUES (:name, :type, :rate, :hours, :location, :description, :owner_id, :image_filename, :image_path)";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':name' => $name,
            ':type' => $type,
            ':rate' => floatval($rate),
            ':hours' => $hours,
            ':location' => $location,
            ':description' => $description,
            ':owner_id' => intval($owner_id),
            ':image_filename' => $image_filename,
            ':image_path' => $image_path
        ]);
        
        $vehicle_id = $db->lastInsertId();
        
        echo json_encode([
            "success" => true, 
            "message" => "Vehicle added successfully!", 
            "vehicle_id" => $vehicle_id,
            "image_path" => $image_path
        ]);
        
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error adding vehicle: " . $e->getMessage()]);
    }
}

function addVehicle($db, $data) {
    $query = "INSERT INTO vehicles (name, type, rate, hours, location, description, owner_id, image_filename, image_path) 
              VALUES (:name, :type, :rate, :hours, :location, :description, :owner_id, :image_filename, :image_path)";
    
    $stmt = $db->prepare($query);
    
    try {
        $stmt->execute([
            ':name' => $data['name'],
            ':type' => $data['type'],
            ':rate' => $data['rate'],
            ':hours' => $data['hours'],
            ':location' => $data['location'],
            ':description' => $data['description'] ?? '',
            ':owner_id' => $data['owner_id'],
            ':image_filename' => $data['image_filename'] ?? '',
            ':image_path' => $data['image_path'] ?? ''
        ]);
        
        echo json_encode(["success" => true, "message" => "Vehicle added successfully!", "vehicle_id" => $db->lastInsertId()]);
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error adding vehicle: " . $e->getMessage()]);
    }
}

function getVehicles($db) {
    $query = "SELECT v.*, u.first_name, u.last_name, u.phone_number 
              FROM vehicles v 
              JOIN users u ON v.owner_id = u.id 
              ORDER BY v.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert image paths to full URLs
    foreach ($vehicles as &$vehicle) {
        if (!empty($vehicle['image_path'])) {
            $vehicle['image_url'] = getBaseUrl() . $vehicle['image_path'];
        }
    }
    
    echo json_encode(["success" => true, "vehicles" => $vehicles]);
}

function getOwnerVehicles($db, $data) {
    $query = "SELECT * FROM vehicles WHERE owner_id = :owner_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':owner_id' => $data['owner_id']]);
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert image paths to full URLs
    foreach ($vehicles as &$vehicle) {
        if (!empty($vehicle['image_path'])) {
            $vehicle['image_url'] = getBaseUrl() . $vehicle['image_path'];
        }
    }
    
    echo json_encode(["success" => true, "vehicles" => $vehicles]);
}

function getVehicleDetails($db, $data) {
    try {
        $vehicle_id = $data['vehicle_id'] ?? '';
        
        if(empty($vehicle_id)) {
            echo json_encode(["success" => false, "message" => "Vehicle ID is required"]);
            return;
        }
        
        $query = "SELECT v.*, u.first_name, u.last_name, u.phone_number 
                  FROM vehicles v 
                  JOIN users u ON v.owner_id = u.id 
                  WHERE v.id = :vehicle_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([':vehicle_id' => $vehicle_id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($vehicle) {
            // Convert image path to full URL
            if (!empty($vehicle['image_path'])) {
                $vehicle['image_url'] = getBaseUrl() . $vehicle['image_path'];
            }
            
            echo json_encode(["success" => true, "vehicle" => $vehicle]);
        } else {
            echo json_encode(["success" => false, "message" => "Vehicle not found"]);
        }
        
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}

function updateVehicleStatus($db, $data) {
    $query = "UPDATE vehicles SET status = :status WHERE id = :id AND owner_id = :owner_id";
    $stmt = $db->prepare($query);
    
    try {
        $stmt->execute([
            ':status' => $data['status'],
            ':id' => $data['vehicle_id'],
            ':owner_id' => $data['owner_id']
        ]);
        
        echo json_encode(["success" => true, "message" => "Status updated"]);
    } catch(PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error updating status"]);
    }
}

function updateVehicle($db, $data) {
    try {
        $vehicle_id = $data['vehicle_id'] ?? '';
        $owner_id = $data['owner_id'] ?? '';

        if (empty($vehicle_id) || empty($owner_id)) {
            echo json_encode(["success" => false, "message" => "vehicle_id and owner_id are required"]);
            return;
        }

        // Build dynamic SET clause for provided fields
        $fields = [];
        $params = [':id' => $vehicle_id, ':owner_id' => $owner_id];

        $allowed = ['name', 'type', 'rate', 'hours', 'location', 'description', 'status', 'image_filename', 'image_path'];
        foreach ($allowed as $col) {
            if (isset($data[$col])) {
                $fields[] = "$col = :$col";
                // cast rate to float
                $params[":$col"] = ($col === 'rate') ? floatval($data[$col]) : $data[$col];
            }
        }

        if (empty($fields)) {
            echo json_encode(["success" => false, "message" => "No fields to update"]);
            return;
        }

        $setClause = implode(', ', $fields);
        $query = "UPDATE vehicles SET $setClause WHERE id = :id AND owner_id = :owner_id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        echo json_encode(["success" => true, "message" => "Vehicle updated successfully", "vehicle_id" => $vehicle_id]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error updating vehicle: " . $e->getMessage()]);
    }
}

function deleteVehicle($db, $data) {
    try {
        $vehicle_id = $data['vehicle_id'] ?? '';
        $owner_id = $data['owner_id'] ?? '';

        if (empty($vehicle_id) || empty($owner_id)) {
            echo json_encode(["success" => false, "message" => "vehicle_id and owner_id are required"]);
            return;
        }

        // Fetch existing vehicle to remove image file if exists
        $stmt = $db->prepare("SELECT image_path FROM vehicles WHERE id = :id AND owner_id = :owner_id");
        $stmt->execute([':id' => $vehicle_id, ':owner_id' => $owner_id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$vehicle) {
            echo json_encode(["success" => false, "message" => "Vehicle not found or unauthorized"]);
            return;
        }

    // Delete DB row
        $del = $db->prepare("DELETE FROM vehicles WHERE id = :id AND owner_id = :owner_id");
        $del->execute([':id' => $vehicle_id, ':owner_id' => $owner_id]);

    // Log deletion for debugging
    error_log("vehicles.php: delete_vehicle executed for id={$vehicle_id}, owner_id={$owner_id}, affected_rows=" . $del->rowCount());

        // Remove image file if present
        if (!empty($vehicle['image_path']) && file_exists($vehicle['image_path'])) {
            @unlink($vehicle['image_path']);
        }

        echo json_encode(["success" => true, "message" => "Vehicle deleted", "vehicle_id" => $vehicle_id]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error deleting vehicle: " . $e->getMessage()]);
    }
}

function getBaseUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $project_path = dirname($_SERVER['SCRIPT_NAME']);
    
    return $protocol . "://" . $host . $project_path . '/';
}
?>