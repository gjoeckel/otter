<?php
/**
 * Test the integration of MVP APIs with original system
 */
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/mvp_config.php';
require_once __DIR__ . '/lib/mvp_reports_data_service.php';
require_once __DIR__ . '/lib/unified_enterprise_config.php';

// Initialize session (simulate browser session)
initializeSession();
$_SESSION['admin_authenticated'] = true;
$_SESSION['enterprise_code'] = 'ccc';

// Initialize enterprise context
UnifiedEnterpriseConfig::initializeFromRequest();

echo "=== Testing MVP Integration ===\n";

// Test 1: Original reports_api.php (now using MVP implementation)
echo "\n1. Testing original reports_api.php (now MVP):\n";
try {
    $_REQUEST = [
        'start_date' => '01-01-20',
        'end_date' => '12-31-25',
        'enrollment_mode' => 'by-tou',
        'all_tables' => '1'
    ];
    
    ob_start();
    include 'reports/reports_api.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    $response = json_decode($output, true);
    if ($response) {
        echo "✅ Original reports_api.php now working with MVP implementation\n";
        echo "Systemwide: " . json_encode($response['systemwide']) . "\n";
        echo "Organizations: " . count($response['organizations'] ?? []) . " organizations\n";
        echo "Groups: " . count($response['groups'] ?? []) . " groups\n";
    } else {
        echo "❌ Original API failed to return JSON\n";
        echo "Raw output: " . substr($output, 0, 200) . "...\n";
    }
} catch (Exception $e) {
    echo "❌ Original API Error: " . $e->getMessage() . "\n";
}

// Test 2: Original reports_api_internal.php (now using MVP implementation)
echo "\n2. Testing original reports_api_internal.php (now MVP):\n";
try {
    $_REQUEST = [
        'start_date' => '01-01-20',
        'end_date' => '12-31-25',
        'enrollment_mode' => 'by-tou'
    ];
    
    ob_start();
    include 'reports/reports_api_internal.php';
    $output = ob_get_contents();
    ob_end_clean();
    
    $response = json_decode($output, true);
    if ($response) {
        echo "✅ Original reports_api_internal.php now working with MVP implementation\n";
        echo "Systemwide: " . json_encode($response['systemwide']) . "\n";
    } else {
        echo "❌ Original Internal API failed to return JSON\n";
        echo "Raw output: " . substr($output, 0, 200) . "...\n";
    }
} catch (Exception $e) {
    echo "❌ Original Internal API Error: " . $e->getMessage() . "\n";
}

echo "\n=== Integration Test Complete ===\n";
?>
