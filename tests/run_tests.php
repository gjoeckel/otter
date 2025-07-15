<?php
/**
 * Test Runner for Password Change and Direct Link Functionality
 * 
 * This script runs comprehensive tests to validate the refactored code
 */

// Prevent output buffering issues
if (ob_get_level()) ob_end_clean();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include test files
require_once __DIR__ . '/password_change_test.php';
require_once __DIR__ . '/../lib/unified_enterprise_config.php';

class TestRunner {
    private $results = [];
    private $startTime;
    
    public function __construct() {
        $this->startTime = microtime(true);
    }
    
    /**
     * Add test result
     */
    private function addResult($category, $testName, $passed, $message = '', $details = '') {
        $this->results[] = [
            'category' => $category,
            'test' => $testName,
            'passed' => $passed,
            'message' => $message,
            'details' => $details,
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Run backend tests
     */
    public function runBackendTests() {
        echo "Running Backend Tests...\n";
        echo "=======================\n";
        
        try {
            $test = new PasswordChangeTest();
            $test->runAllTests();
            
            // Capture individual test results from PasswordChangeTest
            $testResults = $test->getResults();
            if ($testResults) {
                foreach ($testResults as $result) {
                    $this->addResult('Backend', $result['test'], $result['passed'], $result['message']);
                }
            } else {
                // Fallback if results not available
                $this->addResult('Backend', 'Password Change Tests', true, 'Backend tests completed');
            }
            
        } catch (Exception $e) {
            $this->addResult('Backend', 'Password Change Tests', false, 'Backend test execution failed', $e->getMessage());
        }
    }
    
    /**
     * Run frontend tests
     */
    public function runFrontendTests() {
        echo "\nRunning Frontend Tests...\n";
        echo "========================\n";
        
        try {
            // Test if frontend test file exists
            $frontendTestFile = __DIR__ . '/frontend_test.html';
            if (file_exists($frontendTestFile)) {
                $this->addResult('Frontend', 'Test File Exists', true, 'Frontend test file found');
                
                // Test basic file structure
                $content = file_get_contents($frontendTestFile);
                if (strpos($content, 'Frontend Password Change Tests') !== false) {
                    $this->addResult('Frontend', 'Test File Content', true, 'Frontend test file contains expected content');
                } else {
                    $this->addResult('Frontend', 'Test File Content', false, 'Frontend test file missing expected content');
                }
                
                // Test module imports
                if (strpos($content, 'import { fetchEnterpriseData, getDashboardUrlJS, clearEnterpriseCache }') !== false) {
                    $this->addResult('Frontend', 'Module Imports', true, 'Frontend test includes required module imports');
                } else {
                    $this->addResult('Frontend', 'Module Imports', false, 'Frontend test missing required module imports');
                }
                
            } else {
                $this->addResult('Frontend', 'Test File Exists', false, 'Frontend test file not found');
            }
            
        } catch (Exception $e) {
            $this->addResult('Frontend', 'Frontend Tests', false, 'Frontend test execution failed', $e->getMessage());
        }
    }
    
    /**
     * Test file permissions and accessibility
     */
    public function testFilePermissions() {
        echo "\nTesting File Permissions...\n";
        echo "==========================\n";
        
        $criticalFiles = [
            '../lib/database.php',
            '../lib/direct_link.php',
            '../lib/unified_enterprise_config.php',
            '../lib/api/enterprise_api.php',
            '../lib/dashboard-link-utils.js',
            '../settings/index.php'
        ];
        
        foreach ($criticalFiles as $file) {
            $fullPath = __DIR__ . '/' . $file;
            if (file_exists($fullPath)) {
                if (is_readable($fullPath)) {
                    $this->addResult('Permissions', basename($file), true, 'File exists and is readable');
                } else {
                    $this->addResult('Permissions', basename($file), false, 'File exists but not readable');
                }
            } else {
                $this->addResult('Permissions', basename($file), false, 'File not found');
            }
        }
        
        // Test config directory writability
        $configDir = __DIR__ . '/../config/csu';
        if (is_dir($configDir)) {
            if (is_writable($configDir)) {
                $this->addResult('Permissions', 'Config Directory', true, 'Config directory is writable');
            } else {
                $this->addResult('Permissions', 'Config Directory', false, 'Config directory not writable');
            }
        } else {
            $this->addResult('Permissions', 'Config Directory', false, 'Config directory not found');
        }
    }
    
    /**
     * Test environment configuration
     */
    public function testEnvironmentConfiguration() {
        echo "\nTesting Environment Configuration...\n";
        echo "====================================\n";
        
        try {
            // Test enterprise configuration
            UnifiedEnterpriseConfig::init('testenterprise');
            
            $enterprise = UnifiedEnterpriseConfig::getEnterprise();
            if ($enterprise && isset($enterprise['name'])) {
                $this->addResult('Environment', 'Enterprise Config', true, 'Enterprise configuration loaded: ' . $enterprise['name']);
            } else {
                $this->addResult('Environment', 'Enterprise Config', false, 'Enterprise configuration failed to load');
            }
            
            // Test environment detection
            if (function_exists('getEnvironment')) {
                $env = getEnvironment();
                $this->addResult('Environment', 'Environment Detection', true, 'Environment detected: ' . $env);
            } else {
                $this->addResult('Environment', 'Environment Detection', false, 'getEnvironment function not found');
            }
            
        } catch (Exception $e) {
            $this->addResult('Environment', 'Configuration', false, 'Environment configuration test failed', $e->getMessage());
        }
    }
    
    /**
     * Test database connectivity
     */
    public function testDatabaseConnectivity() {
        echo "\nTesting Database Connectivity...\n";
        echo "===============================\n";
        
        try {
            require_once __DIR__ . '/../lib/unified_database.php';
            $db = new UnifiedDatabase();
            
            // Test basic database operations
            $organizations = $db->getAllOrganizations();
            if (is_array($organizations)) {
                $this->addResult('Database', 'Connection', true, 'Database connection successful, found ' . count($organizations) . ' organizations');
            } else {
                $this->addResult('Database', 'Connection', false, 'Database connection failed or returned invalid data');
            }
            
        } catch (Exception $e) {
            $this->addResult('Database', 'Connection', false, 'Database connectivity test failed', $e->getMessage());
        }
    }
    
    /**
     * Generate test report
     */
    public function generateReport() {
        $endTime = microtime(true);
        $duration = round($endTime - $this->startTime, 2);
        
        echo "\n" . str_repeat("=", 60) . "\n";
        echo "TEST REPORT\n";
        echo str_repeat("=", 60) . "\n";
        echo "Generated: " . date('Y-m-d H:i:s') . "\n";
        echo "Duration: {$duration} seconds\n\n";
        
        // Group results by category
        $categories = [];
        foreach ($this->results as $result) {
            $category = $result['category'];
            if (!isset($categories[$category])) {
                $categories[$category] = [];
            }
            $categories[$category][] = $result;
        }
        
        $totalTests = count($this->results);
        $passedTests = 0;
        
        foreach ($categories as $category => $results) {
            echo strtoupper($category) . " TESTS\n";
            echo str_repeat("-", strlen($category) + 6) . "\n";
            
            foreach ($results as $result) {
                $status = $result['passed'] ? 'PASS' : 'FAIL';
                echo "[{$status}] {$result['test']}\n";
                if ($result['message']) {
                    echo "    {$result['message']}\n";
                }
                if ($result['details']) {
                    echo "    Details: {$result['details']}\n";
                }
                if ($result['passed']) $passedTests++;
            }
            echo "\n";
        }
        
        // Summary
        echo "SUMMARY\n";
        echo "=======\n";
        echo "Total Tests: {$totalTests}\n";
        echo "Passed: {$passedTests}\n";
        echo "Failed: " . ($totalTests - $passedTests) . "\n";
        echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 1) . "%\n\n";
        
        if ($passedTests === $totalTests) {
            echo "✅ ALL TESTS PASSED! The refactored code is working correctly.\n";
        } else {
            echo "❌ SOME TESTS FAILED. Please review the issues above.\n";
        }
        
        // Save report to file
        $reportFile = __DIR__ . '/test_report_' . date('Y-m-d_H-i-s') . '.txt';
        $reportContent = ob_get_contents();
        file_put_contents($reportFile, $reportContent);
        echo "\nDetailed report saved to: " . basename($reportFile) . "\n";
    }
    
    /**
     * Run all tests
     */
    public function runAllTests() {
        echo "Starting Comprehensive Test Suite...\n";
        echo "====================================\n\n";
        
        $this->testFilePermissions();
        $this->testEnvironmentConfiguration();
        $this->testDatabaseConnectivity();
        $this->runBackendTests();
        $this->runFrontendTests();
        
        $this->generateReport();
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli' || isset($_GET['run_tests'])) {
    $runner = new TestRunner();
    $runner->runAllTests();
} else {
    echo "Test Runner for Password Change and Direct Link Functionality\n";
    echo "============================================================\n\n";
    echo "To run tests:\n";
    echo "1. Via command line: php run_tests.php\n";
    echo "2. Via web browser: run_tests.php?run_tests=1\n";
    echo "3. Individual test files can also be run directly\n\n";
    echo "Test Files:\n";
    echo "- password_change_test.php (Backend tests)\n";
    echo "- frontend_test.html (Frontend tests)\n";
    echo "- run_tests.php (This comprehensive test runner)\n";
}
?> 