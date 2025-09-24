---
alwaysApply: false
description: "Chrome MCP testing integration rules and patterns"
---

# Chrome MCP Testing Integration Rules

## Chrome MCP Overview

Chrome MCP (Managed Chrome Protocol) provides browser automation capabilities for comprehensive frontend testing, including screenshot capture, element interaction, performance monitoring, and error detection.

## TestBase Class Integration

The TestBase class has been enhanced with Chrome MCP methods for browser automation:

### Core Methods
- **`initChromeMCP($base_url)`** - Initialize Chrome MCP environment
- **`takeScreenshot($name, $description)`** - Capture screenshots for visual validation
- **`clickElement($selector, $description)`** - Click elements on the page
- **`fillForm($form_data, $description)`** - Fill forms with data
- **`evaluateScript($script, $description)`** - Execute JavaScript in browser
- **`getConsoleErrors()`** - Retrieve console errors
- **`getNetworkRequests()`** - Monitor network requests
- **`navigateToPage($url, $description)`** - Navigate to pages
- **`waitForText($text, $timeout)`** - Wait for text to appear
- **`takePageSnapshot($description)`** - Capture page state
- **`startPerformanceTrace($reload, $auto_stop)`** - Start performance monitoring
- **`stopPerformanceTrace()`** - Stop and analyze performance

## Test Categories

### 1. Frontend Integration Tests
**Location:** `tests/chrome-mcp/mvp_frontend_integration_test.php`

**Purpose:** Test actual browser functionality, UI interactions, and JavaScript execution

**Key Tests:**
- Page navigation and loading
- Date range picker functionality
- Apply button functionality
- MVP bundle loading verification
- JavaScript execution validation
- Console error detection
- Network request monitoring
- Visual validation with screenshots

### 2. Performance Tests
**Location:** `tests/performance/mvp_bundle_performance_test.php`

**Purpose:** Measure and validate performance metrics for the MVP system

**Key Metrics:**
- Bundle loading performance
- API response times
- UI interaction performance
- Memory usage monitoring
- Network performance
- Overall page load performance

### 3. User Journey Tests
**Location:** `tests/e2e/mvp_user_journey_test.php`

**Purpose:** Test complete user workflows end-to-end

**Key Workflows:**
- Complete reports workflow
- Date range selection workflow
- Error handling workflow
- Enterprise switching workflow
- MVP functionality workflow

## Chrome MCP Test Patterns

### Basic Test Structure
```php
class ChromeMCPTest extends TestBase {
    private $base_url = 'http://localhost:8000';
    
    public function runAllTests($enterprise = 'csu') {
        // Initialize enterprise
        self::initEnterprise($enterprise);
        
        // Initialize Chrome MCP
        self::initChromeMCP($this->base_url);
        
        // Run tests
        $this->testPageNavigation();
        $this->testUIInteractions();
        $this->testPerformance();
    }
    
    private function testPageNavigation() {
        $this->runChromeTest('Navigate to Reports Page', function() {
            TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
            TestBase::waitForText('Systemwide Data', 10);
            TestBase::takeScreenshot('reports_page_loaded', 'Reports page loaded successfully');
        });
    }
}
```

### Element Interaction Pattern
```php
private function testElementInteraction() {
    $this->runChromeTest('Test Button Click', function() {
        TestBase::clickElement('#apply-button', 'Click Apply button');
        TestBase::waitForText('Loading', 5);
        TestBase::takeScreenshot('button_clicked', 'Button clicked successfully');
    });
}
```

### JavaScript Execution Pattern
```php
private function testJavaScriptExecution() {
    $this->runChromeTest('Test JavaScript Function', function() {
        $result = TestBase::evaluateScript('typeof window.reportsDataService !== "undefined"', 'Check if service is loaded');
        TestBase::assertTrue($result === true || $result === 'true', 'Service should be loaded');
    });
}
```

### Performance Monitoring Pattern
```php
private function testPerformance() {
    $this->runChromeTest('Measure Performance', function() {
        TestBase::startPerformanceTrace(true, false);
        
        TestBase::navigateToPage($this->base_url . '/reports/index.php', 'Navigate to reports page');
        TestBase::waitForText('Systemwide Data', 15);
        
        $trace_data = TestBase::stopPerformanceTrace();
        $this->performance_metrics['load_time'] = $trace_data;
    });
}
```

## Chrome MCP Configuration

### Global Configuration
The Chrome MCP server should be configured in the global Cursor settings:

**File:** `cline_mcp_settings.json`
```json
{
  "mcpServers": {
    "chrome-devtools": {
      "command": "npx",
      "args": ["chrome-devtools-mcp@latest"],
      "cwd": "C:\\Users\\George\\Projects\\otter\\browsertools-mcp"
    }
  }
}
```

### Local Configuration
**File:** `browsertools-mcp/cursor-mcp-config.json`
```json
{
  "mcpServers": {
    "chrome-devtools": {
      "command": "node",
      "args": ["server-simple.js"],
      "cwd": "C:\\Users\\George\\Projects\\otter\\browsertools-mcp"
    }
  }
}
```

## Chrome MCP Best Practices

### 1. Test Organization
- **Separate test categories** - Frontend, performance, E2E tests
- **Use descriptive test names** - Clear, actionable test descriptions
- **Include screenshots** - Visual validation for UI changes
- **Monitor performance** - Track bundle size, response times, memory usage

### 2. Error Handling
- **Console error detection** - Automatically capture JavaScript errors
- **Network monitoring** - Track API requests and responses
- **Timeout handling** - Use appropriate timeouts for different operations
- **Graceful failures** - Handle test failures without breaking the suite

### 3. Performance Monitoring
- **Bundle size validation** - Ensure bundle size stays within limits
- **Response time tracking** - Monitor API and UI response times
- **Memory usage monitoring** - Track memory consumption
- **Network request counting** - Monitor number of network requests

### 4. Visual Validation
- **Screenshot capture** - Take screenshots at key points
- **Element visibility** - Verify elements are visible and interactive
- **Layout validation** - Ensure UI layout is correct
- **Responsive testing** - Test different screen sizes

## Chrome MCP Troubleshooting

### Common Issues
1. **Chrome MCP not available** - Restart Cursor and verify MCP configuration
2. **Chrome remote debugging not working** - Restart Chrome with debugging flags
3. **Tests failing with timeouts** - Increase timeout values or check page loading
4. **Screenshots not capturing** - Verify Chrome MCP server is running
5. **JavaScript execution failing** - Check if page is fully loaded

### Debug Commands
```bash
# Check Chrome MCP server status
cd browsertools-mcp
node server-simple.js

# Test Chrome MCP integration
php tests/chrome-mcp/run_chrome_mcp_tests.php

# Run specific Chrome MCP tests
php tests/chrome-mcp/mvp_frontend_integration_test.php
php tests/performance/mvp_bundle_performance_test.php
php tests/e2e/mvp_user_journey_test.php
```

### Chrome Startup Flags
```bash
# Start Chrome with remote debugging
chrome.exe --remote-debugging-port=9222 --user-data-dir=C:\temp\chrome-debug --no-first-run --no-default-browser-check
```

## Chrome MCP Test Execution

### Running All Chrome MCP Tests
```bash
php tests/chrome-mcp/run_chrome_mcp_tests.php
```

### Running Specific Test Categories
```bash
# Frontend integration tests
php tests/chrome-mcp/mvp_frontend_integration_test.php

# Performance tests
php tests/performance/mvp_bundle_performance_test.php

# User journey tests
php tests/e2e/mvp_user_journey_test.php
```

### Running with Specific Enterprise
```bash
php tests/chrome-mcp/mvp_frontend_integration_test.php ccc
```

## Chrome MCP Integration Benefits

1. **Real Browser Testing** - Test actual browser functionality
2. **Visual Validation** - Screenshot-based UI testing
3. **Performance Monitoring** - Measurable performance metrics
4. **Error Detection** - Automatic frontend error capture
5. **User Journey Testing** - Complete workflow validation
6. **Cross-Browser Compatibility** - Test on different browsers
7. **Automated Testing** - Reduce manual testing effort
8. **CI/CD Integration** - Automated test execution in pipelines
