<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../brevo_config.php';

if (empty($BREVO_CONFIG) || empty($BREVO_CONFIG['enabled'])) {
    echo "BREVO_CONFIG not enabled.\n";
    exit(1);
}
$cfg = $BREVO_CONFIG;

try {
    $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
    $apiInstance = new \Brevo\Client\Api\DomainsApi(new \GuzzleHttp\Client(), $configSB);

    $result = $apiInstance->getDomains();
    echo "Domains list:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";

    // If domains present, try to print detailed configuration for each
    $arr = json_decode(json_encode($result), true);
    if (isset($arr['domains']) && is_array($arr['domains'])) {
        foreach ($arr['domains'] as $d) {
            $name = $d['name'] ?? null;
            echo "\n--- Details for: " . ($name ?: 'unknown') . " ---\n";
            try {
                $conf = $apiInstance->getDomainConfiguration($name);
                echo json_encode($conf, JSON_PRETTY_PRINT) . "\n";
            } catch (\Brevo\Client\ApiException $e) {
                echo "Could not get configuration for $name: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "No domains found in account.\n";
    }

} catch (\Brevo\Client\ApiException $e) {
    echo "API Exception: \n" . $e->getMessage() . "\n";
    try { echo $e->getResponseBody() . "\n"; } catch (Exception $ex) {}
    exit(2);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(3);
}
