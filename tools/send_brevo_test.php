<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../brevo_config.php';

$to = $argv[1] ?? 'danrylboncales@gmail.com';

if (empty($BREVO_CONFIG) || empty($BREVO_CONFIG['enabled'])) {
    echo "BREVO_CONFIG not enabled.\n";
    exit(1);
}

$cfg = $BREVO_CONFIG;

try {
    $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
    $apiInstance = new \Brevo\Client\Api\TransactionalEmailsApi(new \GuzzleHttp\Client(), $configSB);

    $subject = $cfg['subject'] ?? 'Test Email';
    $fromEmail = $cfg['from_email'] ?? 'no-reply@ridesphere.local';
    $fromName = $cfg['from_name'] ?? 'Ridesphere';

    $email_content = "This is a test email from Ridesphere. If you receive this, Brevo send works.";

    $sendSmtpEmail = new \Brevo\Client\Model\SendSmtpEmail([
        'subject' => $subject,
        'textContent' => $email_content,
        'sender' => ['name' => $fromName, 'email' => $fromEmail],
        'to' => [[ 'email' => $to ]]
    ]);

    $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
    echo "Send result:\n";
    var_export($result);
    echo "\n";
} catch (\Brevo\Client\ApiException $e) {
    echo "API Exception: \n";
    echo $e->getMessage() . "\n";
    echo "Response body:\n";
    echo $e->getResponseBody() . "\n";
    exit(2);
} catch (Exception $e) {
    echo "General Exception: " . $e->getMessage() . "\n";
    exit(3);
}
