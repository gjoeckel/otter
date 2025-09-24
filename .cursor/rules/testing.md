---
alwaysApply: false
description: "Testing framework rules and patterns"
---

# Testing Framework Rules

## Testing Overview

The Otter project uses a comprehensive testing framework with Chrome MCP integration, providing unit, integration, performance, and end-to-end testing capabilities.

## Test Structure

### Directory Organization
```
tests/
├── test_base.php                 # Enhanced base class with Chrome MCP
├── run_comprehensive_tests.php   # Master test runner
├── test_all_enterprises.php      # Enterprise configuration validation
├── chrome-mcp/                   # Chrome MCP integration tests
│   ├── mvp_frontend_integration_test.php
│   └── run_chrome_mcp_tests.php
├── performance/                  # Performance testing
│   └── mvp_bundle_performance_test.php
├── e2e/                         # End-to-end tests
│   └── mvp_user_journey_test.php
├── mvp/                         # MVP-specific tests
│   └── mvp_reports_validation_test.php
├── unit/                        # Unit tests
│   └── config_test.php
├── integration/                 # Integration tests
│   ├── reports_tables_validation_test.php
│   └── login_test.php
├── enterprise/                  # Enterprise-specific tests
│   └── csu_test.php
└── archive/                     # Obsolete test files
```

## TestBase Class

### Core Functionality
The TestBase class provides common functionality for all tests:

```php
class TestBase {
    // Enterprise management
    public static function initEnterprise($enterprise_code = null);
    public static function getEnterprise();
    public static function setEnterprise($enterprise_code);
    
    // Output buffering for session management
    public static function startOutputBuffer();
    public static function endOutputBuffer();
    public static function cleanup();
    
    // Test execution
    public static function runTest($testName, $testFunction);
    
    // Assertion methods
    public static function assertTrue($condition, $message = '');
    public static function assertFalse($condition, $message = '');
    public static function assertEquals($expected, $actual, $message = '');
    public static function assertNotNull($value, $message = '');
    public static function assertNotEmpty($value, $message = '');
    public static function assertArrayHasKey($key, $array, $message = '');
    public static function assertArrayNotHasKey($key, $array, $message = '');
    public static function assertContains($needle, $haystack, $message = '');
    public static function assertNotContains($needle, $haystack, $message = '');
    public static function assertValidJson($json, $message = '');
    public static function assertJsonHasKey($key, $json, $message = '');
    public static function assertHttpStatus($expected, $response, $message = '');
    public static function assertHttpHeader($header, $value, $response, $message = '');
    public static function assertGreaterThanOrEqual($expected, $actual, $message = '');
    public static function assertLessThan($expected, $actual, $message = '');
    public static function assertInstanceOf($expectedClass, $actual, $message = '');
    
    // Chrome MCP integration
    public static function initChromeMCP($base_url = 'http://localhost:8000');
    public static function takeScreenshot($name, $description = '');
    public static function clickElement($selector, $description = '');
    public static function fillForm($form_data, $description = '');
    public static function evaluateScript($script, $description = '');
    public static function getConsoleErrors();
    public static function getNetworkRequests();
    public static function navigateToPage($url, $description = '');
    public static function waitForText($text, $timeout = 10);
    public static function takePageSnapshot($description = '');
    public static function startPerformanceTrace($reload = false, $auto_stop = true);
    public static function stopPerformanceTrace();
}
```

## Test Categories

### 1. Unit Tests
**Purpose:** Test individual components in isolation

**Location:** `tests/unit/`

**Pattern:**
```php
class ComponentTest extends TestBase {
    public function runAllTests($enterprise = 'csu') {
        self::initEnterprise($enterprise);
        
        $this->testComponentFunctionality();
        $this->testComponentIntegration();
    }
    
    private function testComponentFunctionality() {
        $this->runTest('Component Functionality', function() {
            // Test implementation
        });
    }
}
```

### 2. Integration Tests
**Purpose:** Test component interactions and API endpoints

**Location:** `tests/integration/`

**Pattern:**
```php
class IntegrationTest extends TestBase {
    public function runAllTests($enterprise = 'csu') {
        self::initEnterprise($enterprise);
        
        $this->testAPIIntegration();
        $this->testComponentInteraction();
    }
}
```

### 3. Chrome MCP Tests
**Purpose:** Browser automation and frontend testing

**Location:** `tests/chrome-mcp/`

**Pattern:**
```php
class ChromeMCPTest extends TestBase {
    private $base_url = 'http://localhost:8000';
    
    public function runAllTests($enterprise = 'csu') {
        self::initEnterprise($enterprise);
        self::initChromeMCP($this->base_url);
        
        $this->testFrontendIntegration();
        $this->testBrowserAutomation();
    }
}
```

### 4. Performance Tests
**Purpose:** Measure and validate performance metrics

**Location:** `tests/performance/`

**Pattern:**
```php
class PerformanceTest extends TestBase {
    private $performance_metrics = [];
    
    public function runAllTests($enterprise = 'csu') {
        self::initEnterprise($enterprise);
        self::initChromeMCP('http://localhost:8000');
        
        $this->testBundlePerformance();
        $this->testAPIPerformance();
    }
}
```

### 5. End-to-End Tests
**Purpose:** Test complete user workflows

**Location:** `tests/e2e/`

**Pattern:**
```php
class E2ETest extends TestBase {
    public function runAllTests($enterprise = 'csu') {
        self::initEnterprise($enterprise);
        self::initChromeMCP('http://localhost:8000');
        
        $this->testCompleteWorkflow();
        $this->testUserJourney();
    }
}
```

### 6. MVP Tests
**Purpose:** MVP-specific validation and testing

**Location:** `tests/mvp/`

**Pattern:**
```php
class MvpTest extends TestBase {
    public function runAllTests($enterprise = 'csu') {
        self::initEnterprise($enterprise);
        
        $this->testMvpComponents();
        $this->testMvpIntegration();
    }
}
```

## Test Execution

### Master Test Runner
**File:** `tests/run_comprehensive_tests.php`

**Usage:**
```bash
php tests/run_comprehensive_tests.php
```

**Features:**
- Runs tests for all enterprises (CSU, CCC, DEMO)
- Comprehensive test categories
- Detailed reporting and summaries
- Session management to avoid warnings

### Chrome MCP Test Runner
**File:** `tests/chrome-mcp/run_chrome_mcp_tests.php`

**Usage:**
```bash
php tests/chrome-mcp/run_chrome_mcp_tests.php
```

**Features:**
- Frontend integration tests
- Performance testing
- User journey testing
- Browser automation validation

### Enterprise Configuration Tests
**File:** `tests/test_all_enterprises.php`

**Usage:**
```bash
php tests/test_all_enterprises.php
```

**Features:**
- Validates all enterprise configurations
- Tests configuration loading
- Verifies enterprise-specific settings

## Testing Best Practices

### 1. Test Organization
- **Use descriptive test names** - Clear, actionable test descriptions
- **Group related tests** - Organize tests by functionality
- **Use consistent patterns** - Follow established test patterns
- **Include setup and teardown** - Proper test initialization and cleanup

### 2. Assertion Usage
- **Use appropriate assertions** - Choose the right assertion for the test
- **Include meaningful messages** - Provide clear failure messages
- **Test both positive and negative cases** - Verify success and failure scenarios
- **Validate edge cases** - Test boundary conditions

### 3. Chrome MCP Integration
- **Take screenshots** - Visual validation for UI changes
- **Monitor performance** - Track performance metrics
- **Detect errors** - Automatic error capture and reporting
- **Test user journeys** - Complete workflow validation

### 4. Enterprise Testing
- **Test all enterprises** - Ensure compatibility across CSU, CCC, DEMO
- **Validate configurations** - Test enterprise-specific settings
- **Handle differences** - Account for enterprise-specific variations
- **Maintain consistency** - Ensure consistent behavior across enterprises

## Test Data Management

### Enterprise Configuration
```php
// Initialize enterprise for testing
TestBase::initEnterprise('csu');

// Get enterprise configuration
$config = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate();
$organizations = UnifiedEnterpriseConfig::getOrganizations();
```

### Test Data Isolation
- **Use test-specific data** - Avoid conflicts with production data
- **Clean up after tests** - Remove test data after completion
- **Use mock data** - Create test-specific mock data
- **Validate data integrity** - Ensure test data is consistent

## Test Reporting

### Test Results Format
```
=== Test Suite Summary ===
Date: 2025-01-24 16:30:00
Duration: 45.2 seconds
Total Enterprises: 3
Total Tests: 150
Total Passed: 147
Total Failed: 3
Overall Success Rate: 98.0%

=== Enterprise Breakdown ===
✅ CSU: 50/50 (100%)
✅ CCC: 49/50 (98%)
❌ DEMO: 48/50 (96%)

=== Detailed Test Breakdown ===
CSU:
  ✅ unit: 10/10 (100%)
  ✅ integration: 15/15 (100%)
  ✅ chrome-mcp: 15/15 (100%)
  ✅ performance: 10/10 (100%)
```

### Test Failure Analysis
- **Clear error messages** - Detailed failure information
- **Screenshot capture** - Visual evidence of failures
- **Performance metrics** - Performance data for analysis
- **Console errors** - JavaScript error capture

## Testing Troubleshooting

### Common Issues
1. **Session warnings** - Use output buffering in TestBase
2. **Enterprise config errors** - Verify configuration files exist
3. **Chrome MCP not available** - Restart Cursor and verify MCP config
4. **Test timeouts** - Increase timeout values for slow operations
5. **Memory issues** - Clean up test data and resources

### Debug Commands
```bash
# Run specific test categories
php tests/unit/config_test.php
php tests/integration/reports_tables_validation_test.php
php tests/chrome-mcp/mvp_frontend_integration_test.php
php tests/performance/mvp_bundle_performance_test.php
php tests/e2e/mvp_user_journey_test.php
php tests/mvp/mvp_reports_validation_test.php

# Run with specific enterprise
php tests/run_comprehensive_tests.php csu
php tests/chrome-mcp/run_chrome_mcp_tests.php ccc
```

## Testing Integration

### CI/CD Integration
- **Automated test execution** - Run tests in CI/CD pipelines
- **Test result reporting** - Integrate with build systems
- **Performance monitoring** - Track performance over time
- **Visual regression testing** - Screenshot comparison

### Development Workflow
- **Pre-commit testing** - Run tests before commits
- **Feature branch testing** - Test feature branches
- **Release validation** - Comprehensive testing before releases
- **Hotfix testing** - Quick validation for hotfixes
