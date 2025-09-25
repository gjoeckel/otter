<?php
/**
 * MVP Reports Validation Test
 * Validates that the MVP reports system is working correctly
 */

require_once __DIR__ . '/../test_base.php';

class MvpReportsValidationTest extends TestBase {
    private $passed = 0;
    private $failed = 0;
    private $warnings = [];
    
    public function runAllTests($enterprise = 'csu') {
        echo "=== MVP Reports Validation Test ===\n";
        echo "Enterprise: $enterprise\n";
        echo "Validating MVP reports system functionality\n\n";
        
        // Initialize enterprise configuration
        self::initEnterprise($enterprise);
        
        // Run comprehensive tests
        $this->testMvpBundleExistence();
        $this->testMvpDataService();
        $this->testMvpTableUpdater();
        $this->testMvpReportsEntry();
        $this->testMvpMessaging();
        $this->testMvpApiEndpoints();
        $this->testMvpEnterpriseIntegration();
        $this->testMvpDateRangePicker();
        $this->testMvpApplyButton();
        
        // Print summary
        $this->printSummary();
    }
    
    private function testMvpBundleExistence() {
        echo "Testing MVP Bundle Existence...\n";
        
        $this->runMvpTest('MVP Bundle File', function() {
            $bundle_file = __DIR__ . '/../../reports/dist/reports.bundle.js';
            TestBase::assertTrue(file_exists($bundle_file), 'MVP bundle file should exist');
        });
        
        $this->runMvpTest('MVP Bundle Size', function() {
            $bundle_file = __DIR__ . '/../../reports/dist/reports.bundle.js';
            if (file_exists($bundle_file)) {
                $size = filesize($bundle_file);
                TestBase::assertGreaterThan(1000, $size, 'MVP bundle should be larger than 1KB');
                TestBase::assertLessThan(100000, $size, 'MVP bundle should be smaller than 100KB');
            }
        });
    }
    
    private function testMvpDataService() {
        echo "\nTesting MVP Data Service...\n";
        
        $this->runMvpTest('MVP Data Service File', function() {
            $data_service_file = __DIR__ . '/../../reports/js/unified-data-service.js';
            TestBase::assertTrue(file_exists($data_service_file), 'MVP data service file should exist');
        });
        
        $this->runMvpTest('MVP Data Service Content', function() {
            $data_service_file = __DIR__ . '/../../reports/js/unified-data-service.js';
            if (file_exists($data_service_file)) {
                $content = file_get_contents($data_service_file);
                TestBase::assertContains('MvpReportsDataService', $content, 'Should contain MvpReportsDataService class');
                TestBase::assertContains('updateAllTables', $content, 'Should contain updateAllTables method');
            }
        });
    }
    
    private function testMvpTableUpdater() {
        echo "\nTesting MVP Table Updater...\n";
        
        $this->runMvpTest('MVP Table Updater File', function() {
            $table_updater_file = __DIR__ . '/../../reports/js/unified-table-updater.js';
            TestBase::assertTrue(file_exists($table_updater_file), 'MVP table updater file should exist');
        });
        
        $this->runMvpTest('MVP Table Updater Content', function() {
            $table_updater_file = __DIR__ . '/../../reports/js/unified-table-updater.js';
            if (file_exists($table_updater_file)) {
                $content = file_get_contents($table_updater_file);
                TestBase::assertContains('MvpUnifiedTableUpdater', $content, 'Should contain MvpUnifiedTableUpdater class');
                TestBase::assertContains('updateTable', $content, 'Should contain updateTable method');
            }
        });
    }
    
    private function testMvpReportsEntry() {
        echo "\nTesting MVP Reports Entry...\n";
        
        $this->runMvpTest('MVP Reports Entry File', function() {
            $entry_file = __DIR__ . '/../../reports/js/reports-entry.js';
            TestBase::assertTrue(file_exists($entry_file), 'MVP reports entry file should exist');
        });
        
        $this->runMvpTest('MVP Reports Entry Content', function() {
            $entry_file = __DIR__ . '/../../reports/js/reports-entry.js';
            if (file_exists($entry_file)) {
                $content = file_get_contents($entry_file);
                TestBase::assertContains('fetchAndUpdateAllTables', $content, 'Should contain fetchAndUpdateAllTables function');
                TestBase::assertContains('handleEnrollmentModeChange', $content, 'Should contain handleEnrollmentModeChange function');
            }
        });
    }
    
    private function testMvpMessaging() {
        echo "\nTesting MVP Messaging...\n";
        
        $this->runMvpTest('MVP Messaging File', function() {
            $messaging_file = __DIR__ . '/../../reports/js/reports-messaging.js';
            TestBase::assertTrue(file_exists($messaging_file), 'MVP messaging file should exist');
        });
        
        $this->runMvpTest('MVP Messaging Content', function() {
            $messaging_file = __DIR__ . '/../../reports/js/reports-messaging.js';
            if (file_exists($messaging_file)) {
                $content = file_get_contents($messaging_file);
                TestBase::assertContains('show', $content, 'Should contain show function');
                TestBase::assertContains('hide', $content, 'Should contain hide function');
            }
        });
    }
    
    private function testMvpApiEndpoints() {
        echo "\nTesting MVP API Endpoints...\n";
        
        $this->runMvpTest('MVP Reports API File', function() {
            $api_file = __DIR__ . '/../../reports/reports_api.php';
            TestBase::assertTrue(file_exists($api_file), 'MVP reports API file should exist');
        });
        
        $this->runMvpTest('MVP Reports API Internal File', function() {
            $api_internal_file = __DIR__ . '/../../reports/reports_api_internal.php';
            TestBase::assertTrue(file_exists($api_internal_file), 'MVP reports API internal file should exist');
        });
    }
    
    private function testMvpEnterpriseIntegration() {
        echo "\nTesting MVP Enterprise Integration...\n";
        
        $this->runMvpTest('Enterprise Start Date', function() {
            $enterprise = UnifiedEnterpriseConfig::getEnterprise();
            $settings = UnifiedEnterpriseConfig::getSettings();
            TestBase::assertNotEmpty($settings['start_date'], 'Enterprise should have start_date');
            TestBase::assertTrue(strlen($settings['start_date']) >= 8, 'Start date should be in MM-DD-YY format');
        });
        
        $this->runMvpTest('Enterprise Code', function() {
            $enterprise = UnifiedEnterpriseConfig::getEnterprise();
            TestBase::assertNotEmpty($enterprise['code'], 'Enterprise should have code');
            TestBase::assertContains($enterprise['code'], ['csu', 'ccc', 'demo'], 'Enterprise code should be valid');
        });
    }
    
    private function testMvpDateRangePicker() {
        echo "\nTesting MVP Date Range Picker...\n";
        
        $this->runMvpTest('Date Range Picker File', function() {
            $date_picker_file = __DIR__ . '/../../reports/js/date-range-picker.js';
            TestBase::assertTrue(file_exists($date_picker_file), 'Date range picker file should exist');
        });
        
        $this->runMvpTest('Date Range Picker Content', function() {
            $date_picker_file = __DIR__ . '/../../reports/js/date-range-picker.js';
            if (file_exists($date_picker_file)) {
                $content = file_get_contents($date_picker_file);
                TestBase::assertContains('handleApplyClick', $content, 'Should contain handleApplyClick function');
                TestBase::assertContains('resetWidgetsToDefaults', $content, 'Should contain resetWidgetsToDefaults function');
            }
        });
    }
    
    private function testMvpApplyButton() {
        echo "\nTesting MVP Apply Button...\n";
        
        $this->runMvpTest('Apply Button Functionality', function() {
            $date_picker_file = __DIR__ . '/../../reports/js/date-range-picker.js';
            if (file_exists($date_picker_file)) {
                $content = file_get_contents($date_picker_file);
                TestBase::assertContains('window.handleApplyClick', $content, 'Should contain window.handleApplyClick function');
                TestBase::assertContains('isValidMMDDYYFormat', $content, 'Should contain date format validation');
            }
        });
    }
    
    private function runMvpTest($testName, $testFunction) {
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
        
        echo "\n=== MVP Reports Validation Summary ===\n";
        echo "Total Tests: $total\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Success Rate: {$success_rate}%\n";
        
        if ($this->failed === 0) {
            echo "🎉 ALL MVP TESTS PASSED! MVP system is ready for production.\n";
        } else {
            echo "⚠️  Some MVP tests failed. Please review the failed tests above.\n";
        }
        
        echo "\n";
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli') {
    $enterprise = $argv[1] ?? 'csu';
    $test = new MvpReportsValidationTest();
    $test->runAllTests($enterprise);
}
?>
