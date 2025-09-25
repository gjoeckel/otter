<?php
require_once 'lib/session.php';
require_once 'lib/unified_enterprise_config.php';

initializeSession();
$_SESSION['organization_authenticated'] = true;
$_SESSION['organization_name'] = 'Test Org';
$_SESSION['enterprise_code'] = 'ccc';

UnifiedEnterpriseConfig::initializeFromRequest();

$_REQUEST = [
    'start_date' => '08-06-22',
    'end_date' => '09-24-25',
    'enrollment_mode' => 'by-tou',
    'all_tables' => '1'
];

try {
    $config = MvpConfig::load();
    $service = new MvpReportsDataService($config);
    $result = $service->getArrayResponse($_REQUEST);
    
    echo "API Test Results:\n";
    echo "Registrations: " . $result['systemwide']['registrations_count'] . "\n";
    echo "Enrollments: " . $result['systemwide']['enrollments_count'] . "\n";
    echo "Certificates: " . $result['systemwide']['certificates_count'] . "\n";
    echo "Organizations: " . count($result['organizations']) . "\n";
    echo "Groups: " . count($result['groups']) . "\n";
} catch (Exception $e) {
    echo "API Error: " . $e->getMessage() . "\n";
}
?>