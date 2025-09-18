<?php
// Simple test to debug organization data processing
echo "Testing organization data processing...\n";

$url = 'http://localhost:8000/reports/reports_api.php?start_date=08-06-22&end_date=09-17-25&organization_data=1';
$response = file_get_contents($url);

if ($response === false) {
    echo "Failed to fetch API response\n";
    exit(1);
}

$data = json_decode($response, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo "JSON decode error: " . json_last_error_msg() . "\n";
    echo "Raw response: " . substr($response, 0, 500) . "...\n";
    exit(1);
}

echo "Response keys: " . implode(', ', array_keys($data)) . "\n";

if (isset($data['error'])) {
    echo "ERROR: " . $data['error'] . "\n";
} else {
    echo "Organization data count: " . (isset($data['organization_data']) ? count($data['organization_data']) : 'NOT FOUND') . "\n";
}
?>