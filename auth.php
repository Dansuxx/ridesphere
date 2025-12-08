<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';
require_once 'sendEmailViaPHPMailer.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Legacy GET-based email verification removed. Use OTP flow instead.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = file_get_contents("php://input");
    error_log("Received data: " . $input);
    $data = json_decode($input, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(["success" => false, "message" => "Invalid JSON data: " . json_last_error_msg()]);
        exit;
    }
    $action = $data['action'] ?? '';
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit;
    }

    switch ($action) {
        case 'signup':
            signup($db, $data);
            break;
        // Backwards-compatibility: map legacy 'send_verification' action to OTP flow
        case 'send_verification':
            // This used to send a verification link. We now send a 6-digit OTP instead.
            sendOTPToEmailAction($db, $data);
            break;
        case 'login':
            login($db, $data);
            break;
        case 'update_profile':
            updateProfile($db, $data);
            break;
        case 'send_otp_to_email':
            sendOTPToEmailAction($db, $data);
            break;
        case 'verify_otp_for_email':
            verifyOTPForEmailAction($db, $data);
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid action: " . $action]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}



// signup, updateProfile, login unchanged from previous implementation
function signup($db, $data) {
    try {
        $first_name = $data['firstName'] ?? '';
        $middle_name = $data['middleName'] ?? '';
        $last_name = $data['lastName'] ?? '';
        $phone_number = $data['phoneNumber'] ?? '';
        $address = $data['address'] ?? '';
        $email = isset($data['email']) ? trim($data['email']) : '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';
        $role = $data['role'] ?? 'renter';
        $email_verified = isset($data['email_verified']) && $data['email_verified'] ? 1 : 0;

        if(empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
            echo json_encode(["success" => false, "message" => "All required fields must be filled (first name, last name, email, password)"]);
            return;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Invalid email format"]);
            return;
        }

        $colCheckQ = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'";
        $colStmt = $db->query($colCheckQ);
        $colRow = $colStmt->fetch(PDO::FETCH_ASSOC);
        $hasEmailColumn = ($colRow && intval($colRow['cnt']) > 0);

        // Prepare username (use provided username or fall back to email)
        $usernameToUse = $username ?: $email;

        if ($hasEmailColumn) {
            // If email already exists, abort
            $checkEmailQ = "SELECT id FROM users WHERE email = :email";
            $checkEmailStmt = $db->prepare($checkEmailQ);
            $checkEmailStmt->execute([':email' => $email]);
            if($checkEmailStmt->rowCount() > 0) {
                echo json_encode(["success" => false, "message" => "An account with this email already exists"]);
                return;
            }

            // Also ensure username uniqueness to avoid duplicate-key on insert.
            $checkUserQ = "SELECT id FROM users WHERE username = :username LIMIT 1";
            $checkUserStmt = $db->prepare($checkUserQ);
            $checkUserStmt->execute([':username' => $usernameToUse]);
            $existing = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                // If the user supplied a username explicitly, return error.
                if (!empty($username)) {
                    echo json_encode(["success" => false, "message" => "Username already taken"]);
                    return;
                }

                // Otherwise, the fallback username (email) collides with an existing username.
                // Generate a unique username by appending a short suffix.
                $base = $usernameToUse;
                $attempt = 0;
                $candidate = null;
                do {
                    $suffix = bin2hex(random_bytes(2));
                    $candidate = $base . '+' . $suffix;
                    $checkUserStmt->execute([':username' => $candidate]);
                    $existing = $checkUserStmt->fetch(PDO::FETCH_ASSOC);
                    $attempt++;
                } while ($existing && $attempt < 10);

                if ($existing) {
                    // give up after several attempts
                    echo json_encode(["success" => false, "message" => "Unable to generate unique username, please specify one"]);
                    return;
                }

                $usernameToUse = $candidate;
            }
        } else {
            // Legacy DB with no email column: ensure username uniqueness
            $checkQuery = "SELECT id FROM users WHERE username = :username LIMIT 1";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->execute([':username' => $usernameToUse]);
            $existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
            if($existing) {
                echo json_encode(["success" => false, "message" => "An account with this email/username already exists"]);
                return;
            }
        }

        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        if ($hashed_password === false) {
            throw new Exception("Password hashing failed");
        }

        if ($hasEmailColumn) {
            // Use provided email_verified flag (set to true by OTP flow when applicable)
            $query = "INSERT INTO users (first_name, middle_name, last_name, phone_number, address, email, username, password, role, email_verified) VALUES (:first_name, :middle_name, :last_name, :phone_number, :address, :email, :username, :password, :role, :email_verified)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':phone_number' => $phone_number,
                ':address' => $address,
                ':email' => $email,
                ':username' => $usernameToUse,
                ':password' => $hashed_password,
                ':role' => $role,
                ':email_verified' => $email_verified
            ]);
        } else {
            $query = "INSERT INTO users (first_name, middle_name, last_name, phone_number, address, username, password, role) VALUES (:first_name, :middle_name, :last_name, :phone_number, :address, :username, :password, :role)";
            $stmt = $db->prepare($query);
            $result = $stmt->execute([
                ':first_name' => $first_name,
                ':middle_name' => $middle_name,
                ':last_name' => $last_name,
                ':phone_number' => $phone_number,
                ':address' => $address,
                ':username' => $usernameToUse,
                ':password' => $hashed_password,
                ':role' => $role
            ]);
        }

        if ($result) {
            // No legacy verification token logic — email verification is handled via OTP flow.
            echo json_encode(["success" => true, "message" => "Account created successfully!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Failed to create account"]);
        }
    } catch(PDOException $e) {
        error_log("Signup PDO error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    } catch(Exception $e) {
        error_log("Signup general error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}

function updateProfile($db, $data) {
    try {
        $user_id = $data['user_id'] ?? '';
        if (empty($user_id)) {
            echo json_encode(["success" => false, "message" => "user_id is required"]);
            return;
        }

        $fields = [];
        $params = [':id' => $user_id];

        $allowed = ['first_name','middle_name','last_name','phone_number','address'];
        foreach ($allowed as $col) {
            if (isset($data[$col])) {
                $fields[] = "$col = :$col";
                $params[":$col"] = $data[$col];
            }
        }

        if (empty($fields)) {
            echo json_encode(["success" => false, "message" => "No fields to update"]);
            return;
        }

        $set = implode(', ', $fields);
        $query = "UPDATE users SET $set WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        // Return updated user data
        $q = "SELECT id, first_name, middle_name, last_name, phone_number, address, username, role FROM users WHERE id = :id";
        $s = $db->prepare($q);
        $s->execute([':id' => $user_id]);
        $user = $s->fetch(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "user" => $user]);
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Error updating profile: " . $e->getMessage()]);
    }
}

function login($db, $data) {
    try {
        $email = isset($data['email']) ? trim($data['email']) : '';
        $username = $data['username'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($password) || (empty($email) && empty($username))) {
            echo json_encode(["success" => false, "message" => "Email (or username) and password are required"]);
            return;
        }

        // Determine if 'email' column exists in users table
        $colCheckQ = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'";
        $colStmt = $db->query($colCheckQ);
        $colRow = $colStmt->fetch(PDO::FETCH_ASSOC);
        $hasEmailColumn = ($colRow && intval($colRow['cnt']) > 0);

    // Determine if email_verified column exists (for verification enforcement)
    $colCheckQ2 = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified'";
    $colStmt2 = $db->query($colCheckQ2);
    $colRow2 = $colStmt2->fetch(PDO::FETCH_ASSOC);
    $hasEmailVerified = ($colRow2 && intval($colRow2['cnt']) > 0);

        // Build selectable columns based on schema availability
        $baseCols = ['id', 'first_name', 'middle_name', 'last_name', 'phone_number', 'address', 'username', 'password', 'role'];
        if ($hasEmailColumn) {
            // include email column only if it exists
            array_splice($baseCols, 6, 0, 'email'); // insert 'email' before 'username'
            // if email_verified exists, include it right after email
            if ($hasEmailVerified) {
                array_splice($baseCols, 7, 0, 'email_verified');
            }
        } else {
            // If there is an email_verified column but not email (edge case), include email_verified near address
            if ($hasEmailVerified) {
                array_splice($baseCols, 6, 0, 'email_verified');
            }
        }
        $selectCols = implode(', ', $baseCols);

        // Prefer lookup by email when provided and the column exists, else fallback to username
        if (!empty($email) && $hasEmailColumn) {
            $query = "SELECT $selectCols FROM users WHERE email = :email";
            $stmt = $db->prepare($query);
            $stmt->execute([':email' => $email]);
        } else {
            // Legacy: treat provided email as username if column missing
            $usernameToLookup = !empty($username) ? $username : $email;
            $query = "SELECT $selectCols FROM users WHERE username = :username";
            $stmt = $db->prepare($query);
            $stmt->execute([':username' => $usernameToLookup]);
        }
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if($user) {
            // If email verification is enabled, and the user hasn't verified, block login.
            if ($hasEmailVerified && isset($user['email_verified']) && intval($user['email_verified']) === 0) {
                echo json_encode(["success" => false, "message" => "Email not verified", "requires_verification" => true]);
                return;
            }

            $passwordValid = false;
            
            if (password_verify($password, $user['password'])) {
                $passwordValid = true;
            } else if ($user['password'] === $password) {
                $passwordValid = true;
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $updateQuery = "UPDATE users SET password = :password WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([':password' => $newHash, ':id' => $user['id']]);
            } else if (md5($password) === $user['password']) {
                $passwordValid = true;
                $newHash = password_hash($password, PASSWORD_BCRYPT);
                $updateQuery = "UPDATE users SET password = :password WHERE id = :id";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->execute([':password' => $newHash, ':id' => $user['id']]);
            }

            if ($passwordValid) {
                unset($user['password']);
                echo json_encode(["success" => true, "user" => $user]);
            } else {
                echo json_encode(["success" => false, "message" => "Invalid password"]);
            }
        } else {
            echo json_encode(["success" => false, "message" => "User not found"]);
        }
        
    } catch(PDOException $e) {
        error_log("Login PDO error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
}

function sendOTPToEmailAction($db, $data) {
    require_once 'sendEmailViaPHPMailer.php';
    
    try {
        // Log incoming data for debugging (local dev)
        error_log('sendOTPToEmailAction called with: ' . json_encode($data));
        // Also write a copy into project logs for easier inspection
        @file_put_contents(__DIR__ . '/logs/last_send_otp_request.json', json_encode(['timestamp' => date('c'), 'payload' => $data], JSON_PRETTY_PRINT));
        $email = isset($data['email']) ? trim($data['email']) : '';
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(["success" => false, "message" => "Valid email is required"]);
            return;
        }
        
        // Create otp_pending_emails table if it doesn't exist
        $db->exec("CREATE TABLE IF NOT EXISTS otp_pending_emails (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            otp_code VARCHAR(6) NOT NULL,
            otp_expires DATETIME NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Generate OTP
        $otp = generateOTP();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+10 minutes'));
        
        // Store OTP in database (upsert)
        $stmt = $db->prepare("INSERT INTO otp_pending_emails (email, otp_code, otp_expires) 
                             VALUES (:email, :otp, :expires)
                             ON DUPLICATE KEY UPDATE otp_code = :otp, otp_expires = :expires");
        $executed = $stmt->execute([
            ':email' => $email,
            ':otp' => $otp,
            ':expires' => $expiresAt
        ]);

        // Verify that the row was written; if not, attempt one retry and log diagnostics
        $verifyStmt = $db->prepare("SELECT otp_code, otp_expires FROM otp_pending_emails WHERE LOWER(email) = LOWER(:email) LIMIT 1");
        $verifyStmt->execute([':email' => $email]);
        $verifyRow = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        if (!$verifyRow) {
            error_log('OTP insert appeared to fail on first attempt for: ' . $email . ' - retrying once');
            // retry
            $stmt->execute([':email' => $email, ':otp' => $otp, ':expires' => $expiresAt]);
            $verifyStmt->execute([':email' => $email]);
            $verifyRow = $verifyStmt->fetch(PDO::FETCH_ASSOC);
        }

        if (!$verifyRow) {
            error_log('OTP insert failed after retry for email: ' . $email . ' | last OTP: ' . $otp);
            @file_put_contents(__DIR__ . '/logs/last_send_otp_error.json', json_encode(['timestamp' => date('c'), 'email' => $email, 'otp' => $otp], JSON_PRETTY_PRINT));
            echo json_encode(["success" => false, "message" => "Failed to store OTP in database"]);
            return;
        }
        
        // Send OTP email
        $result = sendOTPEmail($email, $otp);
        error_log('sendOTPToEmailAction: sendOTPEmail result for ' . $email . ' => ' . json_encode($result));
        
        if ($result['success']) {
            // Return success and the email so the frontend can reliably open the OTP modal
            $out = ["success" => true, "message" => "OTP sent to $email", "email" => $email];
            // write response snapshot
            @file_put_contents(__DIR__ . '/logs/last_send_otp_response.json', json_encode(['timestamp' => date('c'), 'response' => $out, 'mailer' => $result], JSON_PRETTY_PRINT));
            echo json_encode($out);
        } else {
            // Return a clean error message. Do not emit internal debug information to the client by default.
            $out = ["success" => false, "message" => $result['message'] ?? "Failed to send OTP"];
            @file_put_contents(__DIR__ . '/logs/last_send_otp_response.json', json_encode(['timestamp' => date('c'), 'response' => $out, 'mailer' => $result], JSON_PRETTY_PRINT));
            echo json_encode($out);
        }
        
    } catch(Exception $e) {
        error_log("sendOTPToEmailAction error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error sending OTP: " . $e->getMessage()]);
    }
}

function verifyOTPForEmailAction($db, $data) {
    try {
           error_log('verifyOTPForEmailAction called with: ' . json_encode($data));
           $email = isset($data['email']) ? trim($data['email']) : '';
        // also write payload to file for easier debugging from workspace
        @file_put_contents(__DIR__ . '/logs/last_verify_request.json', json_encode(['timestamp' => date('c'), 'payload' => $data], JSON_PRETTY_PRINT));
        // Normalize email to avoid accidental whitespace/casing issues
        $email = filter_var($email, FILTER_SANITIZE_EMAIL);
        $otp = isset($data['otp']) ? trim($data['otp']) : '';
        
        if (empty($email) || empty($otp)) {
            echo json_encode(["success" => false, "message" => "Email and OTP are required"]);
            return;
        }
        
        // Verify OTP from database
        // Use case-insensitive lookup to avoid failures due to email casing
        $stmt = $db->prepare("SELECT otp_code, otp_expires FROM otp_pending_emails WHERE LOWER(email) = LOWER(:email)");
        $stmt->execute([':email' => $email]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        error_log('verifyOTPForEmailAction: DB select result for ' . $email . ' => ' . json_encode($record));
        @file_put_contents(__DIR__ . '/logs/last_verify_db_select.json', json_encode(['timestamp' => date('c'), 'email' => $email, 'record' => $record], JSON_PRETTY_PRINT));
        
        if (!$record) {
            echo json_encode(["success" => false, "message" => "OTP not found for this email"]);
            return;
        }
        
        // Check if OTP is expired
        if (strtotime($record['otp_expires']) < time()) {
            // Delete expired OTP
            // Delete expired OTP (case-insensitive)
            $deleteStmt = $db->prepare("DELETE FROM otp_pending_emails WHERE LOWER(email) = LOWER(:email)");
            $deleteStmt->execute([':email' => $email]);
            echo json_encode(["success" => false, "message" => "OTP has expired"]);
            return;
        }
        
        // Verify OTP code
        if ($record['otp_code'] !== $otp) {
            echo json_encode(["success" => false, "message" => "Invalid OTP code"]);
            return;
        }
        
        // Delete the OTP after successful verification (single-use)
        // Delete the OTP after successful verification (single-use, case-insensitive)
        $deleteStmt = $db->prepare("DELETE FROM otp_pending_emails WHERE LOWER(email) = LOWER(:email)");
        $deleteStmt->execute([':email' => $email]);
            error_log('verifyOTPForEmailAction: Deleted OTP row for ' . $email);

        // If there is a users record for this email, mark email_verified = 1
        try {
            // Check if 'users' table has email_verified column
            $colCheckQ = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email_verified'";
            $colStmt = $db->query($colCheckQ);
            $colRow = $colStmt->fetch(PDO::FETCH_ASSOC);
            $hasEmailVerified = ($colRow && intval($colRow['cnt']) > 0);

            // Also check for otp_code and otp_expires columns in users table
            $colCheckQ2 = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME IN ('otp_code', 'otp_expires')";
            $cols = [];
            foreach ($db->query($colCheckQ2) as $r) {
                $cols[] = $r['COLUMN_NAME'];
            }

            if ($hasEmailVerified) {
                // Build an update statement depending on available columns
                $updateParts = ['email_verified = 1'];
                $params = [':email' => $email];
                if (in_array('otp_code', $cols)) {
                    $updateParts[] = 'otp_code = NULL';
                }
                if (in_array('otp_expires', $cols)) {
                    $updateParts[] = 'otp_expires = NULL';
                }
                $updateSql = 'UPDATE users SET ' . implode(', ', $updateParts) . ' WHERE LOWER(email) = LOWER(:email)';
                $updStmt = $db->prepare($updateSql);
                $updStmt->execute($params);
            }
        } catch (Exception $e) {
            // Non-fatal: log and continue
            error_log('Post-OTP user update failed: ' . $e->getMessage());
        }

        echo json_encode(["success" => true, "message" => "OTP verified successfully"]);
        
    } catch(Exception $e) {
        error_log("verifyOTPForEmailAction error: " . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error verifying OTP: " . $e->getMessage()]);
    }
}
?>