<?php
/**
 * Chrome MCP Test Runner
 * Runs all Chrome MCP-based tests for comprehensive frontend validation
 */

require_once __DIR__ . '/../test_base.php';

class ChromeMCPTestRunner {
    private $results = [];
    private $startTime;
    private $enterprises = ['csu', 'ccc', 'demo'];
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    /**
     * Run all Chrome MCP tests for all enterprises
     */
    public function runAllTests() {
        echo "=== Chrome MCP Test Suite ===\n";
        echo "Date: " . date('Y-m-d H:i:s') . "\n";
        echo "Testing frontend functionality with Chrome MCP integration\n\n";
        
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
     * Run Chrome MCP tests for a specific enterprise
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
        
        // Run Chrome MCP test categories
        $testCategories = [
            'frontend_integration' => [$this, 'runFrontendIntegrationTests'],
            'user_journey' => [$this, 'runUserJourneyTests']
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
     * Run frontend integration tests
     */
    private function runFrontendIntegrationTests($enterprise) {
        $results = ['passed' => 0, 'failed' => 0, 'total' => 0];
        
        try {
            require_once __DIR__ . '/srd_frontend_integration_test.php';
            $test = new SrdFrontendIntegrationTest();
            
            // Capture output
            ob_start();
            $test->runAllTests($enterprise);
            $output = ob_get_clean();
            
            // Parse results from output
            $passed = substr_count($output, '✅');
            $failed = substr_count($output, '❌');
            
            $results['passed'] = $passed;
            $results['failed'] = $failed;
            $results['total'] = $passed + $failed;
            
            echo $output;
            
        } catch (Exception $e) {
            echo "❌ Frontend Integration Tests Error: " . $e->getMessage() . "\n";
            $results['failed'] = 1;
            $results['total'] = 1;
        }
        
        return $results;
    }
    
    /**
     * Performance tests removed with bundle system - using direct ES6 modules
     */
    
    /**
     * Run user journey tests
     */
    private function runUserJourneyTests($enterprise) {
        $results = ['passed' => 0, 'failed' => 0, 'total' => 0];
        
        try {
            require_once __DIR__ . '/../e2e/mvp_user_journey_test.php';
            $test = new MvpUserJourneyTest();
            
            // Capture output
            ob_start();
            $test->runAllTests($enterprise);
            $output = ob_get_clean();
            
            // Parse results from output
            $passed = substr_count($output, '✅');
            $failed = substr_count($output, '❌');
            
            $results['passed'] = $passed;
            $results['failed'] = $failed;
            $results['total'] = $passed + $failed;
            
            echo $output;
            
        } catch (Exception $e) {
            echo "❌ User Journey Tests Error: " . $e->getMessage() . "\n";
            $results['failed'] = 1;
            $results['total'] = 1;
        }
        
        return $results;
    }
    
    /**
     * Print comprehensive test summary
     */
    private function printSummary($results) {
        $duration = round(microtime(true) - $this->startTime, 2);
        
        echo "=== CHROME MCP TEST SUMMARY ===\n";
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
            echo "🎉 ALL CHROME MCP TESTS PASSED! Frontend functionality is working perfectly.\n";
        } else {
            echo "⚠️  Some Chrome MCP tests failed. Please review the failed tests above.\n";
        }
        
        echo "\n=== Chrome MCP Test Suite Complete ===\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $runner = new ChromeMCPTestRunner();
    $runner->runAllTests();
}
?>
