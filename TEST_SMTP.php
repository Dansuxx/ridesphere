<?php
/**
 * TEST_SMTP.php
 * Quick SMTP connectivity diagnostic using fsockopen and PHPMailer::smtpConnect().
 * Run from the server (PHP CLI) to verify outbound SMTP reachability and PHPMailer setup.
 */

require __DIR__ . '/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

header('Content-Type: application/json');

// Load .env (if present) so CLI tests pick up local SMTP_USER/SMTP_PASS values
if (file_exists(__DIR__ . '/sendEmailViaPHPMailer.php')) {
    // that file defines a lightweight load_dotenv() and calls it; include it to load env
    require_once __DIR__ . '/sendEmailViaPHPMailer.php';
    if (function_exists('load_dotenv')) {
        // ensure .env is loaded into process environment
        load_dotenv(__DIR__ . '/.env');
    }
}

$host = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
$port = getenv('SMTP_PORT') ?: 587;
$timeout = 5;

$out = [];

// 1) Low-level TCP connect test
@$fp = fsockopen($host, $port, $errno, $errstr, $timeout);
if ($fp) {
    stream_set_timeout($fp, $timeout);
    $banner = '';
    // try to read banner (may or may not be available)
    $banner = @fgets($fp, 512);
    fclose($fp);
    $out['socket'] = [
        'success' => true,
        'message' => "TCP connect to $host:$port succeeded",
        'banner' => $banner
    ];
} else {
    $out['socket'] = [
        'success' => false,
        'message' => "TCP connect to $host:$port failed: ($errno) $errstr"
    ];
}

// 2) PHPMailer smtpConnect test
$mail = new PHPMailer(true);
$debug = '';
$mail->SMTPDebug = 2;
$mail->Debugoutput = function($str, $level) use (&$debug) {
    $debug .= "[level:$level] $str\n";
};

try {
    $mail->isSMTP();
    $mail->Host = $host;
    $mailUser = getenv('SMTP_USER');
    $mailPass = getenv('SMTP_PASS');
    if (empty($mailUser) || empty($mailPass)) {
        $out['phpmailer'] = [
            'success' => false,
            'message' => 'SMTP_USER or SMTP_PASS not set in environment. Please create a .env file or export variables and retry.',
            'debug' => $debug
        ];
        echo json_encode($out, JSON_PRETTY_PRINT);
        exit(0);
    }
    $mail->SMTPAuth = true;
    $mail->Username = $mailUser;
    $mail->Password = $mailPass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int)$port;
    $mail->Timeout = 10;

    $connected = $mail->smtpConnect();
    if ($connected) {
        $mail->smtpClose();
        $out['phpmailer'] = [
            'success' => true,
            'message' => 'PHPMailer smtpConnect() succeeded',
            'debug' => $debug
        ];
    } else {
        $out['phpmailer'] = [
            'success' => false,
            'message' => 'PHPMailer smtpConnect() returned false',
            'debug' => $debug
        ];
    }
} catch (Exception $e) {
    $out['phpmailer'] = [
        'success' => false,
        'message' => 'PHPMailer Exception: ' . $e->getMessage(),
        'debug' => $debug
    ];
}

echo json_encode($out, JSON_PRETTY_PRINT);

?>
