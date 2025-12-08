<?php
/**
 * PHPMailer Email Sending Module for Ridesphere OTP
 * Sends OTP verification emails via Gmail SMTP
 */

require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Lightweight .env loader (no external dependency). Reads KEY=VALUE lines.
function load_dotenv($path = __DIR__ . '/.env') {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!strpos($line, '=')) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        // remove surrounding quotes
        if ((substr($value,0,1) === '"' && substr($value,-1) === '"') || (substr($value,0,1) === "'" && substr($value,-1) === "'")) {
            $value = substr($value,1,-1);
        }
        if (getenv($name) === false) {
            putenv("$name=$value");
            $_ENV[$name] = $value;
        }
    }
}

// Load .env if present
load_dotenv();

/**
 * Send OTP email using PHPMailer
 * 
 * @param string $toEmail Recipient email address
 * @param string $otp 6-digit OTP code
 * @return array Status array with 'success' and 'message' keys
 */
function sendOTPEmail($toEmail, $otp) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings (read from environment variables or defaults)
        $mail->isSMTP();
        $mailHost = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mailPort = getenv('SMTP_PORT') ?: 587;
        $mailUser = getenv('SMTP_USER') ?: null;
        $mailPass = getenv('SMTP_PASS') ?: null;
        $mailSecure = getenv('SMTP_SECURE') ?: 'tls';

        $mail->Host = $mailHost;
        $mail->SMTPAuth = !empty($mailUser);
        if ($mail->SMTPAuth) {
            $mail->Username = $mailUser;
            $mail->Password = $mailPass;
        }
        // Map common secure values
        if (strtolower($mailSecure) === 'ssl') {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        }
        $mail->Port = (int)$mailPort;
        
        // Recipients
        $fromEmail = getenv('FROM_EMAIL') ?: ($mailUser ?: 'ridesphererentcar@gmail.com');
        $fromName = getenv('FROM_NAME') ?: 'Ridesphere';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($toEmail);
        
        // Content - HTML email with styling
        $mail->isHTML(true);
        // If $otp is a 6-digit code, include it in the subject to improve visibility in clients
        if (preg_match('/^\d{6}$/', $otp)) {
            $mail->Subject = 'Your Ridesphere OTP: ' . $otp;
        } else {
            $mail->Subject = 'Your Ridesphere Notification';
        }

        // Build a plain-text fallback that always contains the OTP clearly.
        if (preg_match('/^\d{6}$/', $otp)) {
            $plainText = "Your Ridesphere OTP verification code is: $otp\nThis code will expire in 10 minutes.";
        } else {
            // If a non-OTP message is passed (e.g., verification link text), include it plainly
            $plainText = "Message: $otp";
        }

        // Beautiful HTML email template (keeps the OTP visible)
        $htmlBody = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
                .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 8px 8px 0 0; margin: -40px -40px 30px -40px; }
                .header h1 { margin: 0; font-size: 28px; }
                .content { text-align: center; }
                .otp-code { font-size: 48px; font-weight: bold; letter-spacing: 10px; color: #667eea; margin: 30px 0; font-family: 'Courier New', monospace; }
                .expiry { color: #888; font-size: 14px; margin-top: 20px; }
                .footer { color: #999; font-size: 12px; text-align: center; margin-top: 30px; border-top: 1px solid #eee; padding-top: 20px; }
                .warning { color: #d9534f; font-size: 13px; margin-top: 20px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Ridesphere</h1>
                    <p style='margin: 10px 0 0 0;'>Email Verification</p>
                </div>
                <div class='content'>
                    <h2 style='color: #333;'>Verify Your Email</h2>
                    <p style='color: #666; font-size: 16px;'>Your OTP code is:</p>
                    <div class='otp-code'>" . htmlspecialchars($otp) . "</div>
                    <p style='color: #666;'>Enter this code to verify your email and complete your registration.</p>
                    <p class='expiry'>This code will expire in <strong>10 minutes</strong></p>
                    <p class='warning'>⚠️ If you did not request this code, please ignore this email.</p>
                </div>
                <div class='footer'>
                    <p>© 2024 Ridesphere. All rights reserved.</p>
                    <p>This is an automated message, please do not reply to this email.</p>
                </div>
            </div>
        </body>
        </html>
        ";

        $mail->Body = $htmlBody;
        $mail->AltBody = $plainText;
        
        // Send email
        $mail->send();
        
        return [
            'success' => true,
            'message' => 'OTP sent successfully to ' . htmlspecialchars($toEmail)
        ];
        
    } catch (Exception $e) {
        // Log internal error details for server-side debugging
        error_log("PHPMailer Error: " . $mail->ErrorInfo . " | Exception: " . $e->getMessage());

        // Return a friendly message with guidance to the caller. Include a 'debug' field
        // that contains the internal PHPMailer ErrorInfo for developers, but keep the
        // main user-facing message concise.
        return [
            'success' => false,
            'message' => 'Failed to send OTP email: Unable to connect to SMTP server. Check SMTP credentials (Gmail app password), network/firewall, and smtp.gmail.com:587.',
            'debug' => $mail->ErrorInfo
        ];
    }
}

/**
 * Generate a random 6-digit OTP code
 * 
 * @return string 6-digit OTP code
 */
function generateOTP() {
    return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

?>
