<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../brevo_config.php';

$domain = $argv[1] ?? null;
if (!$domain) {
    echo "Usage: php brevo_get_domain_records.php example.com\n";
    exit(1);
}

if (empty($BREVO_CONFIG) || empty($BREVO_CONFIG['enabled'])) {
    echo "BREVO_CONFIG not enabled.\n";
    exit(2);
}

$cfg = $BREVO_CONFIG;

try {
    $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $cfg['api_key']);
    $apiInstance = new \Brevo\Client\Api\DomainsApi(new \GuzzleHttp\Client(), $configSB);

    // Try to get existing domain configuration first.
    try {
        $result = $apiInstance->getDomainConfiguration($domain);
        echo "Domain configuration found (existing).\n";
    } catch (\Brevo\Client\ApiException $e) {
        // If not found (404) then create the domain which will return DNS records to add.
        $code = $e->getCode();
        if ($code == 404) {
            echo "Domain not found in account, creating domain to obtain DNS records...\n";
            $createModel = new \Brevo\Client\Model\CreateDomain(['name' => $domain]);
            $result = $apiInstance->createDomain($createModel);
        } else {
            throw $e;
        }
    }

    // Print structured DNS records
    // The result should be a GetDomainConfigurationModel or CreateDomainModel which contains dns records.
    $out = [];
    if (isset($result->getDnsRecords)) {
        // Defensive: sometimes the SDK returns arrays differently.
    }

    // Try to read verification/authentication flags and dns records using SDK getters
    $dnsRecords = null;
    if (is_object($result)) {
        if (method_exists($result, 'getVerified')) {
            $ver = $result->getVerified();
            echo "Verified: " . ($ver ? 'true' : 'false') . "\n";
        }
        if (method_exists($result, 'getAuthenticated')) {
            $auth = $result->getAuthenticated();
            echo "Authenticated: " . ($auth ? 'true' : 'false') . "\n";
        }
        if (method_exists($result, 'getDnsRecords')) {
            $dnsRecords = $result->getDnsRecords();
        }
    }

    // Fallback: convert object to associative array via json encode/decode for easy printing
    $json = json_encode($result, JSON_PRETTY_PRINT);
    echo "API response:\n" . $json . "\n";

    if (empty($dnsRecords)) {
        $arr = json_decode($json, true);
        if (isset($arr['dnsRecords']) && is_array($arr['dnsRecords'])) {
            $dnsRecords = $arr['dnsRecords'];
        }
    }

    if (!empty($dnsRecords) && is_array($dnsRecords)) {
        echo "\nDNS records to add:\n";
        foreach ($dnsRecords as $rec) {
            // rec may be an associative array or an SDK model object
            if (is_object($rec)) {
                // try common getters
                $type = (method_exists($rec, 'getType') ? $rec->getType() : null) ?: 'TXT';
                $name = (method_exists($rec, 'getName') ? $rec->getName() : null);
                $value = (method_exists($rec, 'getValue') ? $rec->getValue() : null);
                if ($name && $value) {
                    echo sprintf("- Type: %s\n  Name: %s\n  Value: %s\n\n", $type, $name, $value);
                    continue;
                }
                // last resort: var_export
                echo "- " . var_export($rec, true) . "\n";
            } elseif (is_array($rec)) {
                if (isset($rec['name']) && isset($rec['value'])) {
                    echo sprintf("- Type: %s\n  Name: %s\n  Value: %s\n\n", $rec['type'] ?? 'TXT', $rec['name'], $rec['value']);
                } else {
                    echo "- " . json_encode($rec) . "\n";
                }
            } else {
                echo "- " . json_encode($rec) . "\n";
            }
        }
    } else {
        // If no dnsRecords found, attempt to create the domain (Brevo may return DNS records on creation)
        echo "\nNo dnsRecords property found in API response. Attempting to create domain to fetch DNS records...\n";
        try {
            $createModel = new \Brevo\Client\Model\CreateDomain(['name' => $domain]);
            $created = $apiInstance->createDomain($createModel);
            echo "CreateDomain response:\n" . json_encode($created, JSON_PRETTY_PRINT) . "\n";
            if (is_object($created) && method_exists($created, 'getDnsRecords')) {
                $dnsRecords = $created->getDnsRecords();
            } else {
                $arr2 = json_decode(json_encode($created), true);
                if (isset($arr2['dnsRecords'])) {
                    $dnsRecords = $arr2['dnsRecords'];
                }
            }

            if (!empty($dnsRecords)) {
                echo "\nDNS records to add (from createDomain):\n";
                foreach ($dnsRecords as $rec) {
                    if (is_array($rec) && isset($rec['name']) && isset($rec['value'])) {
                        echo sprintf("- Type: %s\n  Name: %s\n  Value: %s\n\n", $rec['type'] ?? 'TXT', $rec['name'], $rec['value']);
                    } elseif (is_object($rec) && method_exists($rec, 'getName')) {
                        echo sprintf("- Name: %s\n  Value: %s\n\n", $rec->getName(), $rec->getValue());
                    } else {
                        echo "- " . json_encode($rec) . "\n";
                    }
                }
            } else {
                echo "\nStill no dnsRecords returned. Full create response printed above.\n";
            }
        } catch (\Brevo\Client\ApiException $e) {
            echo "CreateDomain API Exception: " . $e->getMessage() . "\n";
            try { echo $e->getResponseBody() . "\n"; } catch (Exception $ex) {}
        } catch (Exception $e) {
            echo "CreateDomain Exception: " . $e->getMessage() . "\n";
        }
    }

} catch (\Brevo\Client\ApiException $e) {
    echo "API Exception: \n";
    echo $e->getMessage() . "\n";
    echo "Response body:\n";
    try { echo $e->getResponseBody() . "\n"; } catch (Exception $ex) {}
    exit(3);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
    exit(4);
}
