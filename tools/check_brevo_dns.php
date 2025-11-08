<?php
$domain = $argv[1] ?? 'ridesphere.com';

echo "Checking DNS records for $domain...\n\n";

// Records to check (name => type)
$records = [
    '@' => 'TXT',                    // Brevo verification code
    'brevo1._domainkey' => 'CNAME',  // DKIM 1
    'brevo2._domainkey' => 'CNAME',  // DKIM 2
    '_dmarc' => 'TXT'                // DMARC policy
];

// Expected values (for verification)
$expected = [
    '@' => 'brevo-code:a6a0645a6087d40452bd69097d5b6d09',
    'brevo1._domainkey' => 'b1.ridesphere-com.dkim.brevo.com',
    'brevo2._domainkey' => 'b2.ridesphere-com.dkim.brevo.com',
    '_dmarc' => 'v=DMARC1; p=none; rua=mailto:rua@dmarc.brevo.com'
];

// Check each record
foreach ($records as $name => $type) {
    $lookupName = ($name === '@') ? $domain : "$name.$domain";
    echo "Checking $type record for: $lookupName\n";
    
    try {
        if ($type === 'TXT') {
            $result = dns_get_record($lookupName, DNS_TXT);
        } else if ($type === 'CNAME') {
            $result = dns_get_record($lookupName, DNS_CNAME);
        } else {
            echo "Unsupported record type: $type\n";
            continue;
        }

        if (empty($result)) {
            echo "- Not found (no $type record)\n";
        } else {
            foreach ($result as $record) {
                if ($type === 'TXT' && isset($record['txt'])) {
                    $value = $record['txt'];
                    echo "- Found TXT: $value\n";
                    // Check if it matches expected
                    if (isset($expected[$name])) {
                        if (strcasecmp($value, $expected[$name]) === 0) {
                            echo "  ✓ Matches expected value\n";
                        } else {
                            echo "  × Does not match expected value\n";
                            echo "  Expected: " . $expected[$name] . "\n";
                        }
                    }
                } else if ($type === 'CNAME' && isset($record['target'])) {
                    $value = $record['target'];
                    echo "- Found CNAME: $value\n";
                    // Check if it matches expected
                    if (isset($expected[$name])) {
                        if (strcasecmp($value, $expected[$name]) === 0) {
                            echo "  ✓ Matches expected value\n";
                        } else {
                            echo "  × Does not match expected value\n";
                            echo "  Expected: " . $expected[$name] . "\n";
                        }
                    }
                } else {
                    echo "- Found record but missing expected field\n";
                    echo "Record data: " . print_r($record, true) . "\n";
                }
            }
        }
    } catch (Exception $e) {
        echo "Error looking up $type record: " . $e->getMessage() . "\n";
    }
    echo "\n";
}

// Also check our Brevo domain verification status
require_once __DIR__ . '/../brevo_config.php';
if (!empty($BREVO_CONFIG) && !empty($BREVO_CONFIG['enabled']) && !empty($BREVO_CONFIG['api_key'])) {
    echo "Checking Brevo API domain status...\n";
    try {
        $configSB = \Brevo\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $BREVO_CONFIG['api_key']);
        $sendersApi = new \Brevo\Client\Api\DomainsApi(new \GuzzleHttp\Client(), $configSB);
        $result = $sendersApi->getDomainConfiguration($domain);
        echo "Domain status from Brevo API:\n";
        echo json_encode(json_decode(json_encode($result), true), JSON_PRETTY_PRINT) . "\n";
    } catch (Exception $e) {
        echo "Error checking Brevo API: " . $e->getMessage() . "\n";
    }
} else {
    echo "Brevo API check skipped (config not available)\n";
}