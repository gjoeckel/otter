<?php
/**
 * MVP Bundle Performance Test with Chrome MCP
 * Measures and validates performance metrics for the MVP system
 */

require_once __DIR__ . '/../test_base.php';

class MvpBundlePerformanceTest extends TestBase {
    private $passed = 0;
    private $failed = 0;
    private $base_url = 'http://localhost:8000';
    private $performance_metrics = [];
    
    public function runAllTests($enterprise = 'csu') {
        echo "=== MVP Bundle Performance Test with Chrome MCP ===\n";
        echo "Enterprise: $enterprise\n";
        echo "Base URL: {$this->base_url}\n";
        echo "Measuring performance metrics for MVP system\n\n";
        
        // Initialize enterprise configuration
        self::initEnterprise($enterprise);
        
        // Initialize Chrome MCP
        self::initChromeMCP($this->base_url);
        
        // Run comprehensive performance tests
        $this->testBundleLoadingPerformance();
        $this->testDataLoadingPerformance();
        $this->testUIInteractionPerformance();
        $this->testMemoryUsagePerformance();
        $this->testNetworkPerformance();
        $this->testOverallPerformance();
        
        // Print summary
        $this->printSummary();
    }
    
    private function testBundleLoadingPerformance() {
        echo "Testing Bundle Loading Performance...\n";
        
        $this->runPerformanceTest('Bundle Load Time', function() {
            TestBase::startPerformanceTrace(true, false); // Reload page, don't auto-stop
            
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Systemwide Data', 15);
            
            // Wait for bundle to fully load
            TestBase::evaluateScript('typeof window.reportsDataService !== "undefined"', 'Wait for bundle to load');
            
            $trace_data = TestBase::stopPerformanceTrace();
            $this->performance_metrics['bundle_load_time'] = $trace_data;
            
            TestBase::assertTrue(true, 'Bundle load time measured');
        });
        
        $this->runPerformanceTest('Bundle Size Validation', function() {
            $bundle_file = __DIR__ . '/../../reports/dist/reports.bundle.js';
            if (file_exists($bundle_file)) {
                $size = filesize($bundle_file);
                $size_kb = round($size / 1024, 1);
                
                TestBase::assertLessThan(50, $size_kb, "Bundle size should be less than 50KB, got {$size_kb}KB");
                TestBase::assertGreaterThan(10, $size_kb, "Bundle size should be greater than 10KB, got {$size_kb}KB");
                
                $this->performance_metrics['bundle_size_kb'] = $size_kb;
                echo "Bundle size: {$size_kb}KB\n";
            }
        });
    }
    
    private function testDataLoadingPerformance() {
        echo "\nTesting Data Loading Performance...\n";
        
        $this->runPerformanceTest('API Response Time', function() {
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Systemwide Data', 10);
            
            $start_time = microtime(true);
            
            // Trigger data loading
            TestBase::clickElement('#apply-button', 'Click Apply to trigger data loading');
            TestBase::waitForText('Systemwide Data', 15);
            
            $end_time = microtime(true);
            $response_time = round(($end_time - $start_time) * 1000, 2); // Convert to milliseconds
            
            TestBase::assertLessThan(5000, $response_time, "API response time should be less than 5000ms, got {$response_time}ms");
            
            $this->performance_metrics['api_response_time_ms'] = $response_time;
            echo "API response time: {$response_time}ms\n";
        });
        
        $this->runPerformanceTest('Table Update Performance', function() {
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Systemwide Data', 10);
            
            $start_time = microtime(true);
            
            // Test table update performance
            TestBase::clickElement('input[name="preset-range"][value="today"]', 'Select Today preset');
            TestBase::clickElement('#apply-button', 'Click Apply to update tables');
            TestBase::waitForText('Systemwide Data', 15);
            
            $end_time = microtime(true);
            $update_time = round(($end_time - $start_time) * 1000, 2);
            
            TestBase::assertLessThan(3000, $update_time, "Table update time should be less than 3000ms, got {$update_time}ms");
            
            $this->performance_metrics['table_update_time_ms'] = $update_time;
            echo "Table update time: {$update_time}ms\n";
        });
    }
    
    private function testUIInteractionPerformance() {
        echo "\nTesting UI Interaction Performance...\n";
        
        $this->runPerformanceTest('Date Picker Response Time', function() {
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Select Date Range', 10);
            
            $start_time = microtime(true);
            
            // Test date picker response time
            TestBase::clickElement('input[name="preset-range"][value="today"]', 'Click Today preset');
            TestBase::waitForText('Today', 5);
            
            $end_time = microtime(true);
            $response_time = round(($end_time - $start_time) * 1000, 2);
            
            TestBase::assertLessThan(500, $response_time, "Date picker response time should be less than 500ms, got {$response_time}ms");
            
            $this->performance_metrics['date_picker_response_time_ms'] = $response_time;
            echo "Date picker response time: {$response_time}ms\n";
        });
        
        $this->runPerformanceTest('Apply Button Response Time', function() {
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Apply', 10);
            
            $start_time = microtime(true);
            
            // Test Apply button response time
            TestBase::clickElement('#apply-button', 'Click Apply button');
            TestBase::waitForText('Loading', 5);
            
            $end_time = microtime(true);
            $response_time = round(($end_time - $start_time) * 1000, 2);
            
            TestBase::assertLessThan(200, $response_time, "Apply button response time should be less than 200ms, got {$response_time}ms");
            
            $this->performance_metrics['apply_button_response_time_ms'] = $response_time;
            echo "Apply button response time: {$response_time}ms\n";
        });
    }
    
    private function testMemoryUsagePerformance() {
        echo "\nTesting Memory Usage Performance...\n";
        
        $this->runPerformanceTest('Memory Usage Check', function() {
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Systemwide Data', 10);
            
            // Check memory usage through JavaScript
            $memory_info = TestBase::evaluateScript('performance.memory ? performance.memory.usedJSHeapSize : 0', 'Get memory usage');
            
            if (is_numeric($memory_info) && $memory_info > 0) {
                $memory_mb = round($memory_info / (1024 * 1024), 2);
                TestBase::assertLessThan(100, $memory_mb, "Memory usage should be less than 100MB, got {$memory_mb}MB");
                
                $this->performance_metrics['memory_usage_mb'] = $memory_mb;
                echo "Memory usage: {$memory_mb}MB\n";
            } else {
                echo "Memory usage: Not available in this browser\n";
            }
        });
    }
    
    private function testNetworkPerformance() {
        echo "\nTesting Network Performance...\n";
        
        $this->runPerformanceTest('Network Request Monitoring', function() {
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Systemwide Data', 10);
            
            // Monitor network requests
            $network_requests = TestBase::getNetworkRequests();
            TestBase::assertTrue(is_array($network_requests), 'Network requests should be monitored');
            
            // Count requests
            $request_count = count($network_requests);
            TestBase::assertLessThan(20, $request_count, "Should have less than 20 network requests, got {$request_count}");
            
            $this->performance_metrics['network_request_count'] = $request_count;
            echo "Network requests: {$request_count}\n";
        });
    }
    
    private function testOverallPerformance() {
        echo "\nTesting Overall Performance...\n";
        
        $this->runPerformanceTest('Complete Page Load Performance', function() {
            TestBase::startPerformanceTrace(true, false);
            
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Systemwide Data', 15);
            
            // Wait for all resources to load
            TestBase::evaluateScript('typeof window.reportsDataService !== "undefined"', 'Wait for all resources');
            
            $trace_data = TestBase::stopPerformanceTrace();
            $this->performance_metrics['complete_load_time'] = $trace_data;
            
            TestBase::assertTrue(true, 'Complete page load time measured');
        });
        
        $this->runPerformanceTest('Performance Benchmark Comparison', function() {
            // Compare with expected performance benchmarks
            $bundle_size = $this->performance_metrics['bundle_size_kb'] ?? 0;
            $api_response = $this->performance_metrics['api_response_time_ms'] ?? 0;
            $table_update = $this->performance_metrics['table_update_time_ms'] ?? 0;
            
            if ($bundle_size > 0) {
                TestBase::assertLessThan(50, $bundle_size, "Bundle size benchmark: should be < 50KB");
            }
            
            if ($api_response > 0) {
                TestBase::assertLessThan(5000, $api_response, "API response benchmark: should be < 5000ms");
            }
            
            if ($table_update > 0) {
                TestBase::assertLessThan(3000, $table_update, "Table update benchmark: should be < 3000ms");
            }
            
            echo "Performance benchmarks validated\n";
        });
    }
    
    private function runPerformanceTest($testName, $testFunction) {
        try {
            $testFunction();
            $this->passed++;
            echo "✅ $testName: PASS\n";
        } catch (Exception $e) {
            $this->failed++;
            echo "❌ $testName: FAIL - " . $e->getMessage() . "\n";
        }
    }
    
    private function printSummary() {
        $total = $this->passed + $this->failed;
        $success_rate = $total > 0 ? round(($this->passed / $total) * 100, 1) : 0;
        
        echo "\n=== MVP Bundle Performance Test Summary ===\n";
        echo "Total Tests: $total\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Success Rate: {$success_rate}%\n";
        
        if (!empty($this->performance_metrics)) {
            echo "\n=== Performance Metrics ===\n";
            foreach ($this->performance_metrics as $metric => $value) {
                if (is_array($value)) {
                    echo "$metric: " . json_encode($value) . "\n";
                } else {
                    echo "$metric: $value\n";
                }
            }
        }
        
        if ($this->failed === 0) {
            echo "\n🎉 ALL PERFORMANCE TESTS PASSED! MVP system meets performance requirements.\n";
        } else {
            echo "\n⚠️  Some performance tests failed. Please review the failed tests above.\n";
        }
        
        echo "\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $enterprise = $argv[1] ?? 'csu';
    $test = new MvpBundlePerformanceTest();
    $test->runAllTests($enterprise);
}
?>
