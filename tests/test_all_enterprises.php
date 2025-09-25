<?php
/**
 * Test All Enterprise Configurations
 * Validates that all enterprise configs (csu, ccc, demo) load properly
 */

require_once __DIR__ . '/test_base.php';

echo "=== Testing All Enterprise Configurations ===\n\n";

$enterprises = ['csu', 'ccc', 'demo'];
$results = [];

foreach ($enterprises as $enterprise) {
    echo "Testing Enterprise: " . strtoupper($enterprise) . "\n";
    echo str_repeat('-', 40) . "\n";
    
    try {
        // Initialize enterprise configuration
        TestBase::initEnterprise($enterprise);
        
        // Test enterprise information
        $enterprise_info = UnifiedEnterpriseConfig::getEnterprise();
        echo "✅ Enterprise: " . $enterprise_info['name'] . " (" . $enterprise_info['code'] . ")\n";
        
        // Test organizations
        $organizations = UnifiedEnterpriseConfig::getOrganizations();
        echo "✅ Organizations count: " . count($organizations) . "\n";
        
        // Test admin organization
        $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
        if ($admin_org) {
            echo "✅ Admin organization: " . $admin_org['name'] . " (password: " . $admin_org['password'] . ")\n";
        } else {
            echo "❌ No admin organization found\n";
        }
        
        // Test settings
        $settings = UnifiedEnterpriseConfig::getSettings();
        echo "✅ Start date: " . $settings['start_date'] . "\n";
        echo "✅ Cache TTL: " . $settings['cache_ttl'] . " seconds\n";
        
        $results[$enterprise] = 'PASS';
        echo "✅ " . strtoupper($enterprise) . " configuration test PASSED\n";
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
        $results[$enterprise] = 'FAIL';
        echo "❌ " . strtoupper($enterprise) . " configuration test FAILED\n";
    }
    
    echo "\n";
}

// Summary
echo "=== SUMMARY ===\n";
foreach ($results as $enterprise => $status) {
    $icon = $status === 'PASS' ? '✅' : '❌';
    echo "$icon " . strtoupper($enterprise) . ": $status\n";
}

$passed = count(array_filter($results, function($status) { return $status === 'PASS'; }));
$total = count($results);

echo "\nOverall: $passed/$total enterprises passed\n";

if ($passed === $total) {
    echo "🎉 All enterprise configurations are working correctly!\n";
} else {
    echo "⚠️  Some enterprise configurations have issues.\n";
}
?>
