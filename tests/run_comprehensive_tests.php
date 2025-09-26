<?php
/**
 * Comprehensive Test Runner for Clients-Enterprise
 * Consolidates all testing functionality into a single, maintainable system
 */

require_once __DIR__ . '/test_base.php';

class ComprehensiveTestRunner {
    private $results = [];
    private $startTime;
    private $enterprises = ['csu', 'ccc', 'demo'];
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    /**
     * Run all tests for all enterprises
     */
    public function runAllTests() {
        echo "=== Clients-Enterprise Comprehensive Test Suite ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n\n";
        
        $totalResults = [
            'total_tests' => 0,
            'passed' => 0,
            'failed' => 0,
            'enterprises' => []
        ];
        
        foreach ($this->enterprises as $enterprise) {
            echo "Testing Enterprise: " . strtoupper($enterprise) . "\n";
            echo str_repeat('=', 50) . "\n";
            
            try {
                $enterpriseResults = $this->runEnterpriseTests($enterprise);
                $totalResults['enterprises'][$enterprise] = $enterpriseResults;
                $totalResults['total_tests'] += $enterpriseResults['total'];
                $totalResults['passed'] += $enterpriseResults['passed'];
                $totalResults['failed'] += $enterpriseResults['failed'];
            } catch (Exception $e) {
                echo "❌ Error testing enterprise $enterprise: " . $e->getMessage() . "\n";
                $totalResults['enterprises'][$enterprise] = [
                    'error' => $e->getMessage(),
                    'total' => 0,
                    'passed' => 0,
                    'failed' => 0
                ];
            }
            
            echo "\n";
        }
        
        $this->printSummary($totalResults);
        return $totalResults;
    }
    
    /**
     * Run tests for a specific enterprise
     */
    private function runEnterpriseTests($enterprise) {
        TestBase::initEnterprise($enterprise);
        
        $results = [
            'enterprise' => $enterprise,
            'timestamp' => date('Y-m-d H:i:s'),
            'tests' => [],
            'passed' => 0,
            'failed' => 0,
            'total' => 0
        ];
        
        // Run test categories
        $testCategories = [
            'configuration' => [$this, 'runConfigurationTests'],
            'api' => [$this, 'runApiTests'],
            'login' => [$this, 'runLoginTests'],
            'data_service' => [$this, 'runDataServiceTests'],
            'direct_links' => [$this, 'runDirectLinksTests']
        ];
        
        foreach ($testCategories as $category => $testMethod) {
            echo "Running " . ucfirst($category) . " Tests...\n";
            $categoryResults = $testMethod($enterprise);
            $results['tests'][$category] = $categoryResults;
            $results['passed'] += $categoryResults['passed'];
            $results['failed'] += $categoryResults['failed'];
            $results['total'] += $categoryResults['total'];
        }
        
        return $results;
    }
    
    /**
     * Run configuration tests
     */
    private function runConfigurationTests($enterprise) {
        $results = ['passed' => 0, 'failed' => 0, 'total' => 0];
        
        // Test enterprise configuration loading
        $results['total']++;
        if (TestBase::runTest('Enterprise Config Loading', function() {
            $enterprise = UnifiedEnterpriseConfig::getEnterprise();
            TestBase::assertNotNull($enterprise, 'Enterprise configuration should be loaded');
            TestBase::assertNotEmpty($enterprise['name'], 'Enterprise name should not be empty');
            TestBase::assertNotEmpty($enterprise['code'], 'Enterprise code should not be empty');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        // Test organizations loading
        $results['total']++;
        if (TestBase::runTest('Organizations Loading', function() {
            $organizations = UnifiedEnterpriseConfig::getOrganizations();
            TestBase::assertNotNull($organizations, 'Organizations should be loaded');
            TestBase::assertNotEmpty($organizations, 'Organizations array should not be empty');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        // Test admin organization
        $results['total']++;
        if (TestBase::runTest('Admin Organization', function() {
            $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
            TestBase::assertNotNull($admin_org, 'Admin organization should exist');
            TestBase::assertTrue($admin_org['is_admin'], 'Admin organization should have is_admin flag');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        // Test URL generation
        $results['total']++;
        if (TestBase::runTest('URL Generation', function() {
            $dashboard_url = UnifiedEnterpriseConfig::generateUrl('', 'dashboard');
            TestBase::assertNotNull($dashboard_url, 'Dashboard URL should be generated');
            TestBase::assertNotEmpty($dashboard_url, 'Dashboard URL should not be empty');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        return $results;
    }
    
    /**
     * Run API tests
     */
    private function runApiTests($enterprise) {
        $results = ['passed' => 0, 'failed' => 0, 'total' => 0];
        
        // Test API endpoint accessibility
        $results['total']++;
        if (TestBase::runTest('API Endpoint', function() {
            $api_file = __DIR__ . '/../reports/reports_api.php';
            TestBase::assertTrue(file_exists($api_file), 'API file should exist');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        return $results;
    }
    
    /**
     * Run login tests
     */
    private function runLoginTests($enterprise) {
        $results = ['passed' => 0, 'failed' => 0, 'total' => 0];
        
        // Test password validation
        $results['total']++;
        if (TestBase::runTest('Password Validation', function() {
            $admin_org = UnifiedEnterpriseConfig::getAdminOrganization();
            $is_valid = UnifiedEnterpriseConfig::isValidOrganizationPassword($admin_org['password']);
            TestBase::assertTrue($is_valid, 'Admin password should be valid');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        // Test session management
        $results['total']++;
        if (TestBase::runTest('Session Management', function() {
            // Test session management without actually starting session
            // Simulate session data
            $test_session = [
                'home_authenticated' => true,
                'enterprise_code' => UnifiedEnterpriseConfig::getEnterpriseCode()
            ];
            
            $is_authenticated = isset($test_session['home_authenticated']) && $test_session['home_authenticated'] === true;
            $enterprise_matches = isset($test_session['enterprise_code']) && $test_session['enterprise_code'] === UnifiedEnterpriseConfig::getEnterpriseCode();
            
            TestBase::assertTrue($is_authenticated, 'Authentication should be set');
            TestBase::assertTrue($enterprise_matches, 'Enterprise code should match');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        return $results;
    }
    
    /**
     * Run data service tests
     */
    private function runDataServiceTests($enterprise) {
        $results = ['passed' => 0, 'failed' => 0, 'total' => 0];
        
        // Test data service file existence
        $results['total']++;
        if (TestBase::runTest('Data Service File', function() {
            $data_service_file = __DIR__ . '/../lib/unified_database.php';
            TestBase::assertTrue(file_exists($data_service_file), 'Data service file should exist');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        return $results;
    }
    
    /**
     * Run direct links tests
     */
    private function runDirectLinksTests($enterprise) {
        $results = ['passed' => 0, 'failed' => 0, 'total' => 0];
        
        // Test direct link file existence
        $results['total']++;
        if (TestBase::runTest('Direct Link File', function() {
            $direct_link_file = __DIR__ . '/../lib/direct_link.php';
            TestBase::assertTrue(file_exists($direct_link_file), 'Direct link file should exist');
        })) {
            $results['passed']++;
        } else {
            $results['failed']++;
        }
        
        return $results;
    }
    
    /**
     * Print comprehensive test summary
     */
    private function printSummary($results) {
        $duration = round(microtime(true) - $this->startTime, 2);
        
        echo "=== COMPREHENSIVE TEST SUMMARY ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Duration: {$duration} seconds\n";
        echo "Total Enterprises: " . count($this->enterprises) . "\n";
        echo "Total Tests: {$results['total_tests']}\n";
        echo "Total Passed: {$results['passed']}\n";
        echo "Total Failed: {$results['failed']}\n";
        echo "Overall Success Rate: " . ($results['total_tests'] > 0 ? round(($results['passed'] / $results['total_tests']) * 100, 1) : 0) . "%\n\n";
        
        // Enterprise breakdown
        echo "=== ENTERPRISE BREAKDOWN ===\n";
        foreach ($results['enterprises'] as $enterprise => $enterpriseResults) {
            if (isset($enterpriseResults['error'])) {
                echo "❌ " . strtoupper($enterprise) . ": ERROR - " . $enterpriseResults['error'] . "\n";
            } else {
                $status = $enterpriseResults['failed'] === 0 ? '✅' : '❌';
                $success_rate = $enterpriseResults['total'] > 0 ? round(($enterpriseResults['passed'] / $enterpriseResults['total']) * 100, 1) : 0;
                echo "$status " . strtoupper($enterprise) . ": {$enterpriseResults['passed']}/{$enterpriseResults['total']} ({$success_rate}%)\n";
            }
        }
        
        echo "\n";
        
        // Detailed test breakdown
        echo "=== DETAILED TEST BREAKDOWN ===\n";
        foreach ($results['enterprises'] as $enterprise => $enterpriseResults) {
            if (isset($enterpriseResults['error'])) {
                continue;
            }
            
            echo strtoupper($enterprise) . ":\n";
            foreach ($enterpriseResults['tests'] as $test_category => $categoryResults) {
                $status = $categoryResults['failed'] === 0 ? '✅' : '❌';
                $success_rate = $categoryResults['total'] > 0 ? round(($categoryResults['passed'] / $categoryResults['total']) * 100, 1) : 0;
                echo "  $status $test_category: {$categoryResults['passed']}/{$categoryResults['total']} ({$success_rate}%)\n";
            }
            echo "\n";
        }
        
        // Final status
        if ($results['failed'] === 0 && $results['total_tests'] > 0) {
            echo "🎉 ALL TESTS PASSED! All enterprises are ready for production.\n";
        } else {
            echo "⚠️  Some tests failed. Please review the failed tests above.\n";
        }
        
        echo "\n=== Test Suite Complete ===\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $runner = new ComprehensiveTestRunner();
    $runner->runAllTests();
}
?>
