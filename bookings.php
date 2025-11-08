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
        case 'create_booking':
            createBooking($db, $data);
            break;
        case 'get_renter_bookings':
            getRenterBookings($db, $data);
            break;
        case 'get_owner_bookings':
            getOwnerBookings($db, $data);
            break;
        case 'update_booking_status':
            updateBookingStatus($db, $data);
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid action"]);
    }
}

function createBooking($db, $data) {
    try {
        $vehicle_id = $data['vehicle_id'] ?? '';
        $renter_id = $data['renter_id'] ?? '';
        $start_date = $data['start_date'] ?? '';
        $end_date = $data['end_date'] ?? '';
        $total_amount = $data['total_amount'] ?? '';

        if(empty($vehicle_id) || empty($renter_id) || empty($start_date) || empty($end_date) || empty($total_amount)) {
            echo json_encode(["success" => false, "message" => "All booking details are required"]);
            return;
        }

        // Check if vehicle is available
        $checkVehicle = "SELECT status, owner_id FROM vehicles WHERE id = :vehicle_id";
        $stmt = $db->prepare($checkVehicle);
        $stmt->execute([':vehicle_id' => $vehicle_id]);
        $vehicle = $stmt->fetch(PDO::FETCH_ASSOC);

        if(!$vehicle) {
            echo json_encode(["success" => false, "message" => "Vehicle not found"]);
            return;
        }

        if($vehicle['status'] !== 'available') {
            echo json_encode(["success" => false, "message" => "Vehicle is not available for booking"]);
            return;
        }

        // Create booking
        $query = "INSERT INTO bookings (vehicle_id, renter_id, start_date, end_date, total_amount, status) 
                  VALUES (:vehicle_id, :renter_id, :start_date, :end_date, :total_amount, 'pending')";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':vehicle_id' => $vehicle_id,
            ':renter_id' => $renter_id,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':total_amount' => $total_amount
        ]);

        $booking_id = $db->lastInsertId();

        // Update vehicle status to pending
        $updateVehicle = "UPDATE vehicles SET status = 'pending' WHERE id = :vehicle_id";
        $stmt = $db->prepare($updateVehicle);
        $stmt->execute([':vehicle_id' => $vehicle_id]);

        echo json_encode([
            "success" => true, 
            "message" => "Booking created successfully! Waiting for owner confirmation.",
            "booking_id" => $booking_id
        ]);

    } catch(PDOException $e) {
        error_log("Booking creation error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}

function getRenterBookings($db, $data) {
    try {
        $renter_id = $data['renter_id'] ?? '';

        if(empty($renter_id)) {
            echo json_encode(["success" => false, "message" => "Renter ID is required"]);
            return;
        }

        $query = "SELECT b.*, 
                         v.name as vehicle_name, 
                         v.type as vehicle_type, 
                         v.rate as daily_rate,
                         v.location as vehicle_location,
                         owner.first_name as owner_first_name, 
                         owner.last_name as owner_last_name,
                         owner.phone_number as owner_phone
                  FROM bookings b
                  JOIN vehicles v ON b.vehicle_id = v.id
                  JOIN users owner ON v.owner_id = owner.id
                  WHERE b.renter_id = :renter_id
                  ORDER BY b.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([':renter_id' => $renter_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "bookings" => $bookings]);

    } catch(PDOException $e) {
        error_log("Get renter bookings error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}

function getOwnerBookings($db, $data) {
    try {
        $owner_id = $data['owner_id'] ?? '';

        if(empty($owner_id)) {
            echo json_encode(["success" => false, "message" => "Owner ID is required"]);
            return;
        }

        $query = "SELECT b.*, 
                         v.name as vehicle_name, 
                         v.type as vehicle_type,
                         renter.first_name as renter_first_name, 
                         renter.last_name as renter_last_name,
                         renter.phone_number as renter_phone
                  FROM bookings b
                  JOIN vehicles v ON b.vehicle_id = v.id
                  JOIN users renter ON b.renter_id = renter.id
                  WHERE v.owner_id = :owner_id
                  ORDER BY b.created_at DESC";

        $stmt = $db->prepare($query);
        $stmt->execute([':owner_id' => $owner_id]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "bookings" => $bookings]);

    } catch(PDOException $e) {
        error_log("Get owner bookings error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}

function updateBookingStatus($db, $data) {
    try {
        $booking_id = $data['booking_id'] ?? '';
        $status = $data['status'] ?? '';
        $owner_id = $data['owner_id'] ?? '';

        if(empty($booking_id) || empty($status)) {
            echo json_encode(["success" => false, "message" => "Booking ID and status are required"]);
            return;
        }

        // Verify that the owner owns the vehicle in this booking
        $verifyQuery = "SELECT v.id 
                        FROM bookings b 
                        JOIN vehicles v ON b.vehicle_id = v.id 
                        WHERE b.id = :booking_id AND v.owner_id = :owner_id";
        $stmt = $db->prepare($verifyQuery);
        $stmt->execute([':booking_id' => $booking_id, ':owner_id' => $owner_id]);
        
        if($stmt->rowCount() === 0) {
            echo json_encode(["success" => false, "message" => "Unauthorized to update this booking"]);
            return;
        }

        // Update booking status
        $query = "UPDATE bookings SET status = :status WHERE id = :booking_id";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':status' => $status,
            ':booking_id' => $booking_id
        ]);

        // Update vehicle status based on booking status
        if($status === 'confirmed') {
            $vehicleStatus = 'booked';
        } else if($status === 'cancelled') {
            $vehicleStatus = 'available';
        } else if($status === 'completed') {
            // When booking is completed by the owner, make the vehicle available again
            $vehicleStatus = 'available';
        } else {
            $vehicleStatus = 'pending';
        }

        $updateVehicle = "UPDATE vehicles SET status = :status WHERE id = (SELECT vehicle_id FROM bookings WHERE id = :booking_id)";
        $stmt = $db->prepare($updateVehicle);
        $stmt->execute([':status' => $vehicleStatus, ':booking_id' => $booking_id]);

        echo json_encode(["success" => true, "message" => "Booking status updated successfully"]);

    } catch(PDOException $e) {
        error_log("Update booking status error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}
?>