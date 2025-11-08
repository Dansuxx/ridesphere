<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

require_once 'db.php';
// Composer autoload for SendinBlue SDK and dependencies
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Helper: ensure a verified sender exists for the given fromEmail. If a verified sender exists, return ['sender'=>...].
// If not, create a sender via Senders API which will trigger a verification email to that address and return ['requires_verification'=>true, 'sender_id'=>...]
function ensureVerifiedSenderOrCreate($cfg, $fromEmail, $fromName) {
    try {
        if (!class_exists('\Brevo\\Client\\Configuration')) {
            return ['error' => 'Brevo SDK not available'];
        }
        $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
        $sendersApi = new \Brevo\Client\Api\SendersApi(new \GuzzleHttp\Client(), $configSB);

        // Attempt to find an active sender that matches the fromEmail
        $domain = substr(strrchr($fromEmail, '@'), 1);
        try {
            $list = $sendersApi->getSenders(null, $domain);
        } catch (Exception $e) {
            // fallback: try without domain filter
            try { $list = $sendersApi->getSenders(); } catch (Exception $e2) { throw $e2; }
        }

        $arr = json_decode(json_encode($list), true);
        if (isset($arr['senders']) && is_array($arr['senders'])) {
            foreach ($arr['senders'] as $s) {
                if (isset($s['email']) && strcasecmp($s['email'], $fromEmail) === 0 && (!isset($s['active']) || $s['active'] === true)) {
                    return ['sender' => $s];
                }
            }
        }

        // If we get here, no active sender found. Create one which triggers verification email to the address.
        $create = new \Brevo\Client\Model\CreateSender(['name' => $fromName, 'email' => $fromEmail]);
        $created = $sendersApi->createSender($create);
        $createdArr = json_decode(json_encode($created), true);
        $senderId = $createdArr['id'] ?? null;
        return ['requires_verification' => true, 'sender_id' => $senderId, 'create_response' => $createdArr];
    } catch (\Brevo\Client\ApiException $e) {
        error_log('ensureVerifiedSenderOrCreate ApiException: ' . $e->getMessage());
        return ['error' => 'api_exception', 'message' => $e->getMessage(), 'body' => $e->getResponseBody()];
    } catch (Exception $e) {
        error_log('ensureVerifiedSenderOrCreate error: ' . $e->getMessage());
        return ['error' => 'exception', 'message' => $e->getMessage()];
    }
}

// Action: list existing senders via API and return them
function listSendersAction($db, $data) {
    try {
        if (empty($GLOBALS['BREVO_CONFIG']) || empty($GLOBALS['BREVO_CONFIG']['enabled'])) {
            echo json_encode(['success' => false, 'message' => 'Brevo not enabled']);
            return;
        }
        $cfg = $GLOBALS['BREVO_CONFIG'];
        $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
        $sendersApi = new \Brevo\Client\Api\SendersApi(new \GuzzleHttp\Client(), $configSB);
        $domain = $data['domain'] ?? null;
        $result = $sendersApi->getSenders(null, $domain);
        echo json_encode(['success' => true, 'senders' => json_decode(json_encode($result), true)]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Action: create a sender (triggers verification email to the provided address)
function createSenderAction($db, $data) {
    try {
        if (empty($GLOBALS['BREVO_CONFIG']) || empty($GLOBALS['BREVO_CONFIG']['enabled'])) {
            echo json_encode(['success' => false, 'message' => 'Brevo not enabled']);
            return;
        }
        $fromEmail = $data['email'] ?? ($GLOBALS['BREVO_CONFIG']['from_email'] ?? null);
        $fromName = $data['name'] ?? ($GLOBALS['BREVO_CONFIG']['from_name'] ?? 'Ridesphere');
        if (empty($fromEmail)) { echo json_encode(['success'=>false,'message'=>'email required']); return; }
        $cfg = $GLOBALS['BREVO_CONFIG'];
        $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
        $sendersApi = new \Brevo\Client\Api\SendersApi(new \GuzzleHttp\Client(), $configSB);
        $create = new \Brevo\Client\Model\CreateSender(['name' => $fromName, 'email' => $fromEmail]);
        $created = $sendersApi->createSender($create);
        echo json_encode(['success' => true, 'created' => json_decode(json_encode($created), true)]);
    } catch (\Brevo\Client\ApiException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'body' => $e->getResponseBody()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Action: validate sender by OTP (user supplies sender_id and otp)
function validateSenderOtpAction($db, $data) {
    try {
        if (empty($GLOBALS['BREVO_CONFIG']) || empty($GLOBALS['BREVO_CONFIG']['enabled'])) {
            echo json_encode(['success' => false, 'message' => 'Brevo not enabled']);
            return;
        }
        $senderId = $data['sender_id'] ?? null;
        $otp = $data['otp'] ?? null;
        if (empty($senderId) || empty($otp)) { echo json_encode(['success'=>false,'message'=>'sender_id and otp required']); return; }
        $cfg = $GLOBALS['BREVO_CONFIG'];
        $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
        $sendersApi = new \Brevo\Client\Api\SendersApi(new \GuzzleHttp\Client(), $configSB);
        $otpModel = new \Brevo\Client\Model\Otp(['otp' => $otp]);
        $result = $sendersApi->validateSenderByOTP($senderId, $otpModel);
        echo json_encode(['success' => true, 'result' => json_decode(json_encode($result), true)]);
    } catch (\Brevo\Client\ApiException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage(), 'body' => $e->getResponseBody()]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

// Brevo (SendinBlue) config
if (file_exists(__DIR__ . '/brevo_config.php')) {
    require_once __DIR__ . '/brevo_config.php';
} else {
    $BREVO_CONFIG = ['enabled' => false];
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Support GET verification link clicks
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'verify_email') {
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) {
        echo json_encode(["success" => false, "message" => "Database connection failed"]);
        exit;
    }
    verifyEmail($db, $_GET);
    exit(0);
}

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
        case 'list_senders':
            listSendersAction($db, $data);
            break;
        case 'create_sender':
            createSenderAction($db, $data);
            break;
        case 'validate_sender_otp':
            validateSenderOtpAction($db, $data);
            break;
        case 'send_verification':
            sendVerification($db, $data);
            break;
        case 'verify_email':
            verifyEmail($db, $data);
            break;
        case 'login':
            login($db, $data);
            break;
        case 'update_profile':
            updateProfile($db, $data);
            break;
        default:
            echo json_encode(["success" => false, "message" => "Invalid action: " . $action]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}

function sendVerification($db, $data) {
    try {
        $email = isset($data['email']) ? trim($data['email']) : '';
        if (empty($email)) {
            echo json_encode(["success" => false, "message" => "email is required"]);
            return;
        }

        // ensure users table has email column
        $colCheckQ = "SELECT COUNT(*) AS cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'email'";
        $colRow = $db->query($colCheckQ)->fetch(PDO::FETCH_ASSOC);
        $hasEmailColumn = ($colRow && intval($colRow['cnt']) > 0);
        if (!$hasEmailColumn) {
            echo json_encode(["success" => false, "message" => "Email verification not supported on legacy DB"]);
            return;
        }

        $q = "SELECT id, email_verified FROM users WHERE email = :email";
        $s = $db->prepare($q);
        $s->execute([':email' => $email]);
        $user = $s->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(["success" => false, "message" => "User not found"]);
            return;
        }

        // generate token and expiry
        $token = bin2hex(random_bytes(16));
        $expires = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
        $updQ = "UPDATE users SET verification_token = :token, verification_expires = :expires, email_verified = 0 WHERE id = :id";
        $upd = $db->prepare($updQ);
        $upd->execute([':token' => $token, ':expires' => $expires, ':id' => $user['id']]);

        $base = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . dirname($_SERVER['REQUEST_URI']);
        $link = rtrim($base, '/') . '/auth.php?action=verify_email&token=' . $token;

        $email_content = "Please verify your email by clicking the link: " . $link;

        $sent = false;
            if (!empty($GLOBALS['BREVO_CONFIG']) && !empty($GLOBALS['BREVO_CONFIG']['enabled'])) {
                $cfg = $GLOBALS['BREVO_CONFIG'];
                try {
                    $fromEmail = $cfg['from_email'] ?? 'no-reply@ridesphere.local';
                    $fromName = $cfg['from_name'] ?? 'Ridesphere';
                    $subject = $cfg['subject'] ?? 'Verify your Ridesphere email';
                
                    // Prefer the maintained Brevo SDK; otherwise fall back to PHP mail
                    if (class_exists('\Brevo\\Client\\Configuration')) {
                        // Ensure we have a verified sender available in Brevo. If not, create one and instruct the caller to verify it.
                        $senderCheck = ensureVerifiedSenderOrCreate($cfg, $fromEmail, $fromName);
                        if (isset($senderCheck['requires_verification']) && $senderCheck['requires_verification']) {
                            // Do not attempt to send until sender is verified; return instructions to the caller.
                            echo json_encode(["success" => false, "message" => "Sender email requires verification", "sender_info" => $senderCheck]);
                            return;
                        }

                        $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
                        $apiInstance = new \Brevo\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $configSB);
                        $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail([
                            'subject' => $subject,
                            'textContent' => $email_content,
                            'sender' => ['name' => $fromName, 'email' => $fromEmail],
                            'to' => [[ 'email' => $email ]]
                        ]);
                        try {
                            $resp = $apiInstance->sendTransacEmail($sendSmtpEmail);
                            // Log response for debugging — Brevo may return details useful for delivery troubleshooting
                            error_log('Brevo send response: ' . print_r($resp, true));
                            $sent = true;
                        } catch (\Brevo\Client\ApiException $ae) {
                            // ApiException contains response body with details
                            $body = $ae->getResponseBody();
                            error_log('Brevo ApiException: ' . $ae->getMessage() . ' body: ' . print_r($body, true));
                            $sent = false;
                        } catch (Exception $e) {
                            error_log('Brevo send exception: ' . $e->getMessage());
                            $sent = false;
                        }
                    } else {
                        error_log('Brevo SDK not installed; falling back to mail() and returning the verification link in JSON for manual testing.');
                        $sent = false;
                    }
            } catch (Exception $e) {
                error_log('Brevo/Send error: ' . $e->getMessage());
                $sent = false;
            }
        } else {
            // fallback to PHP mail for local/dev
            $fromEmail = $GLOBALS['BREVO_CONFIG']['from_email'] ?? 'no-reply@ridesphere.local';
            $headers = 'From: ' . $fromEmail . "\r\n" . 'Reply-To: ' . $fromEmail . "\r\n";
            $subject = $GLOBALS['BREVO_CONFIG']['subject'] ?? 'Verify your Ridesphere email';
            if (@mail($email, $subject, $email_content, $headers)) {
                $sent = true;
            }
        }

        // Optionally include Brevo SDK debug output in the response when configured (local testing only)
        $brevo_debug = null;
        $response = ["success" => true, "message" => "Verification sent", "verification_link" => $link];
        if ($sent && !empty($GLOBALS['BREVO_CONFIG']['enabled'])) {
            unset($response['verification_link']);
        }
        if (!empty($GLOBALS['BREVO_CONFIG']['debug'])) {
            // Attempt to include any SDK response captured earlier
            if (isset($resp)) {
                $response['brevo_response'] = print_r($resp, true);
            }
        }

        echo json_encode($response);
    } catch (Exception $e) {
        error_log('sendVerification error: ' . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
}

function verifyEmail($db, $data) {
    try {
        $token = '';
        if (isset($data['token'])) $token = $data['token'];
        else if (isset($_GET['token'])) $token = $_GET['token'];
        $token = trim($token);
        if (empty($token)) {
            echo json_encode(["success" => false, "message" => "token is required"]);
            return;
        }

        $q = "SELECT id, verification_expires FROM users WHERE verification_token = :token";
        $s = $db->prepare($q);
        $s->execute([':token' => $token]);
        $user = $s->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            echo json_encode(["success" => false, "message" => "Invalid token"]);
            return;
        }

        if (!empty($user['verification_expires']) && strtotime($user['verification_expires']) < time()) {
            echo json_encode(["success" => false, "message" => "Token expired"]);
            return;
        }

        $updQ = "UPDATE users SET email_verified = 1, verification_token = NULL, verification_expires = NULL WHERE id = :id";
        $upd = $db->prepare($updQ);
        $upd->execute([':id' => $user['id']]);

        echo json_encode(["success" => true, "message" => "Email verified"]);
    } catch (Exception $e) {
        error_log('verifyEmail error: ' . $e->getMessage());
        echo json_encode(["success" => false, "message" => "Error: " . $e->getMessage()]);
    }
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

        $verification_token = null;
        $verification_expires = null;
        if ($hasEmailColumn) {
            $verification_token = bin2hex(random_bytes(16));
            $verification_expires = date('Y-m-d H:i:s', time() + 24 * 60 * 60);
            $query = "INSERT INTO users (first_name, middle_name, last_name, phone_number, address, email, username, password, role) VALUES (:first_name, :middle_name, :last_name, :phone_number, :address, :email, :username, :password, :role)";
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
                ':role' => $role
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
            if ($hasEmailColumn) {
                try {
                    $userId = $db->lastInsertId();
                    $updQ = "UPDATE users SET verification_token = :token, verification_expires = :expires, email_verified = 0 WHERE id = :id";
                    $upd = $db->prepare($updQ);
                    $upd->execute([':token' => $verification_token, ':expires' => $verification_expires, ':id' => $userId]);
                } catch (Exception $e) {
                    error_log('Failed to store verification token: ' . $e->getMessage());
                }
            }
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
?>