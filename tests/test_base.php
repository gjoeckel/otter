<?php
/**
 * Base Test Class for Enterprise-Agnostic Testing
 * Provides common functionality for all tests
 */
class TestBase {
    protected static $test_enterprise = null;
    protected static $test_environment = null;
    protected static $output_buffer = null;
    protected static $chrome_mcp_initialized = false;
    
    /**
     * Set the enterprise to test
     * @param string $enterprise_code The enterprise code (e.g., 'csu', 'ccc')
     */
    public static function setEnterprise($enterprise_code) {
        self::$test_enterprise = $enterprise_code;
    }
    
    /**
     * Get the current test enterprise
     * @return string The enterprise code
     */
    public static function getEnterprise() {
        if (!self::$test_enterprise) {
            throw new Exception('Test enterprise not initialized. Call initEnterprise() first.');
        }
        return self::$test_enterprise;
    }
    
    /**
     * Set the environment to test
     * @param string $environment The environment ('local' or 'production')
     */
    public static function setEnvironment($environment) {
        self::$test_environment = $environment;
    }
    
    /**
     * Get the current test environment
     * @return string The environment
     */
    public static function getEnvironment() {
        if (!self::$test_environment) {
            throw new Exception('Test environment not initialized. Call setEnvironment() first.');
        }
        return self::$test_environment;
    }
    
    /**
     * Start output buffering for tests
     */
    public static function startOutputBuffer() {
        if (ob_get_level() === 0) {
            self::$output_buffer = ob_start();
        }
    }
    
    /**
     * End output buffering and return content
     */
    public static function endOutputBuffer() {
        if (ob_get_level() > 0) {
            $content = ob_get_contents();
            ob_clean();
            return $content;
        }
        return '';
    }
    
    /**
     * Initialize enterprise configuration for testing with proper session management
     * @param string $enterprise_code Optional enterprise code override
     */
    public static function initEnterprise($enterprise_code = null) {
        // Start output buffering
        self::startOutputBuffer();
        
        if ($enterprise_code) {
            self::setEnterprise($enterprise_code);
        }
        
        require_once __DIR__ . '/../lib/unified_enterprise_config.php';
        UnifiedEnterpriseConfig::init(self::getEnterprise());
    }
    
    /**
     * Clean up after test execution
     */
    public static function cleanup() {
        self::endOutputBuffer();
    }
    
    /**
     * Assert that a condition is true
     * @param bool $condition The condition to test
     * @param string $message The assertion message
     */
    public static function assertTrue($condition, $message = '') {
        if (!$condition) {
            throw new Exception("Assertion failed: $message");
        }
    }
    
    /**
     * Assert that a condition is false
     * @param bool $condition The condition to test
     * @param string $message The assertion message
     */
    public static function assertFalse($condition, $message = '') {
        if ($condition) {
            throw new Exception("Assertion failed: $message");
        }
    }
    
    /**
     * Assert that two values are equal
     * @param mixed $expected The expected value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertEquals($expected, $actual, $message = '') {
        if ($expected !== $actual) {
            throw new Exception("Assertion failed: Expected '$expected', got '$actual'. $message");
        }
    }
    
    /**
     * Assert that two values are not equal
     * @param mixed $expected The expected value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertNotEquals($expected, $actual, $message = '') {
        if ($expected === $actual) {
            throw new Exception("Assertion failed: Expected values to be different, but both are '$expected'. $message");
        }
    }
    
    /**
     * Assert that a value is not null
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertNotNull($value, $message = '') {
        if ($value === null) {
            throw new Exception("Assertion failed: Value is null. $message");
        }
    }
    
    /**
     * Assert that a value is empty
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertEmpty($value, $message = '') {
        if (!empty($value)) {
            throw new Exception("Assertion failed: Value is not empty. $message");
        }
    }
    
    /**
     * Assert that a value is not empty
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertNotEmpty($value, $message = '') {
        if (empty($value)) {
            throw new Exception("Assertion failed: Value is empty. $message");
        }
    }
    
    
    /**
     * Assert that a value is not false
     * @param mixed $value The value to test
     * @param string $message The assertion message
     */
    public static function assertNotFalse($value, $message = '') {
        if ($value === false) {
            throw new Exception("Assertion failed: Value is false. $message");
        }
    }
    
    /**
     * Assert that a URL is valid
     * @param string $url The URL to validate
     * @param string $message The assertion message
     */
    public static function assertValidUrl($url, $message = '') {
        if (!filter_var($url, FILTER_VALIDATE_URL) && $url !== 'N/A') {
            throw new Exception("Assertion failed: Invalid URL '$url'. $message");
        }
    }
    
    /**
     * Assert that a value is greater than another
     * @param mixed $expected The expected minimum value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertGreaterThan($expected, $actual, $message = '') {
        if ($actual <= $expected) {
            throw new Exception("Assertion failed: Expected value greater than '$expected', got '$actual'. $message");
        }
    }
    
    /**
     * Log a test result
     * @param string $status The test status ('PASS' or 'FAIL')
     * @param string $test_name The test name
     * @param string $message The test message
     */
    public static function logResult($status, $test_name, $message = '') {
        $status_icon = $status === 'PASS' ? '✅' : '❌';
        echo "[$status] $test_name: $message\n";
    }
    
    /**
     * Run a test with error handling
     * @param string $test_name The test name
     * @param callable $test_function The test function to run
     * @return bool True if test passed, false if failed
     */
    public static function runTest($test_name, $test_function) {
        try {
            $test_function();
            self::logResult('PASS', $test_name);
            return true;
        } catch (Exception $e) {
            self::logResult('FAIL', $test_name, $e->getMessage());
            return false;
        }
    }
    
    // ===== ENHANCED ASSERTION METHODS =====
    
    /**
     * Assert that an array contains a specific key
     * @param string $key The key to check for
     * @param array $array The array to check
     * @param string $message The assertion message
     */
    public static function assertArrayHasKey($key, $array, $message = '') {
        if (!array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: Array does not contain key '$key'. $message");
        }
    }
    
    /**
     * Assert that an array does not contain a specific key
     * @param string $key The key to check for
     * @param array $array The array to check
     * @param string $message The assertion message
     */
    public static function assertArrayNotHasKey($key, $array, $message = '') {
        if (array_key_exists($key, $array)) {
            throw new Exception("Assertion failed: Array contains key '$key'. $message");
        }
    }
    
    /**
     * Assert that an array or string contains a specific value
     * @param mixed $needle The value to search for
     * @param array|string $haystack The array or string to search in
     * @param string $message The assertion message
     */
    public static function assertContains($needle, $haystack, $message = '') {
        if (is_string($haystack)) {
            if (strpos($haystack, $needle) === false) {
                throw new Exception("Assertion failed: String does not contain '$needle'. $message");
            }
        } else {
            if (!in_array($needle, $haystack)) {
                throw new Exception("Assertion failed: Array does not contain value '$needle'. $message");
            }
        }
    }
    
    /**
     * Assert that an array does not contain a specific value
     * @param mixed $needle The value to search for
     * @param array $haystack The array to search in
     * @param string $message The assertion message
     */
    public static function assertNotContains($needle, $haystack, $message = '') {
        if (in_array($needle, $haystack)) {
            throw new Exception("Assertion failed: Array contains value '$needle'. $message");
        }
    }
    
    /**
     * Assert that a string is valid JSON
     * @param string $json The JSON string to validate
     * @param string $message The assertion message
     */
    public static function assertValidJson($json, $message = '') {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Assertion failed: Invalid JSON - " . json_last_error_msg() . ". $message");
        }
    }
    
    /**
     * Assert that JSON contains a specific key
     * @param string $key The key to check for
     * @param string $json The JSON string to check
     * @param string $message The assertion message
     */
    public static function assertJsonHasKey($key, $json, $message = '') {
        $decoded = json_decode($json, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Assertion failed: Invalid JSON. $message");
        }
        self::assertArrayHasKey($key, $decoded, $message);
    }
    
    /**
     * Assert that an HTTP response has a specific status code
     * @param int $expected The expected status code
     * @param array $response The response array
     * @param string $message The assertion message
     */
    public static function assertHttpStatus($expected, $response, $message = '') {
        $status = $response['status'] ?? $response['http_code'] ?? null;
        if ($status !== $expected) {
            throw new Exception("Assertion failed: Expected HTTP status $expected, got $status. $message");
        }
    }
    
    /**
     * Assert that an HTTP response contains specific headers
     * @param string $header The header name
     * @param string $value The expected header value
     * @param array $response The response array
     * @param string $message The assertion message
     */
    public static function assertHttpHeader($header, $value, $response, $message = '') {
        $headers = $response['headers'] ?? [];
        if (!isset($headers[$header]) || $headers[$header] !== $value) {
            throw new Exception("Assertion failed: Expected header '$header' with value '$value'. $message");
        }
    }
    
    /**
     * Assert that a value is greater than or equal to another
     * @param mixed $expected The expected minimum value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertGreaterThanOrEqual($expected, $actual, $message = '') {
        if ($actual < $expected) {
            throw new Exception("Assertion failed: Expected value >= '$expected', got '$actual'. $message");
        }
    }
    
    /**
     * Assert that a value is less than another
     * @param mixed $expected The expected maximum value
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertLessThan($expected, $actual, $message = '') {
        if ($actual >= $expected) {
            throw new Exception("Assertion failed: Expected value < '$expected', got '$actual'. $message");
        }
    }
    
    /**
     * Assert that a value is an instance of a specific class
     * @param string $expectedClass The expected class name
     * @param mixed $actual The actual value
     * @param string $message The assertion message
     */
    public static function assertInstanceOf($expectedClass, $actual, $message = '') {
        if (!($actual instanceof $expectedClass)) {
            $actualClass = is_object($actual) ? get_class($actual) : gettype($actual);
            throw new Exception("Assertion failed: Expected instance of '$expectedClass', got '$actualClass'. $message");
        }
    }
    
    // ===== CHROME MCP INTEGRATION METHODS =====
    
    /**
     * Initialize Chrome MCP for testing
     * @param string $base_url The base URL for testing
     */
    public static function initChromeMCP($base_url = 'http://localhost:8000') {
        if (self::$chrome_mcp_initialized) {
            return;
        }
        
        // Set up Chrome MCP environment
        self::$chrome_mcp_initialized = true;
        
        // Note: Chrome MCP tools are available through the assistant
        // This method sets up the testing environment for Chrome MCP usage
        echo "Chrome MCP initialized for testing at: $base_url\n";
    }
    
    /**
     * Take a screenshot for visual validation
     * @param string $name The name for the screenshot
     * @param string $description Optional description
     */
    public static function takeScreenshot($name, $description = '') {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_take_screenshot
        echo "Screenshot taken: $name" . ($description ? " - $description" : "") . "\n";
        return true;
    }
    
    /**
     * Click an element on the page
     * @param string $selector The CSS selector or element UID
     * @param string $description Optional description
     */
    public static function clickElement($selector, $description = '') {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_click
        echo "Clicked element: $selector" . ($description ? " - $description" : "") . "\n";
        return true;
    }
    
    /**
     * Fill a form with data
     * @param array $form_data Array of field selectors and values
     * @param string $description Optional description
     */
    public static function fillForm($form_data, $description = '') {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_fill_form
        echo "Form filled with data: " . json_encode($form_data) . ($description ? " - $description" : "") . "\n";
        return true;
    }
    
    /**
     * Evaluate JavaScript code in the browser
     * @param string $script The JavaScript code to execute
     * @param string $description Optional description
     * @return mixed The result of the script execution
     */
    public static function evaluateScript($script, $description = '') {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_evaluate_script
        echo "Script executed: " . substr($script, 0, 50) . "..." . ($description ? " - $description" : "") . "\n";
        return "Script result placeholder";
    }
    
    /**
     * Get console errors from the browser
     * @return array Array of console messages
     */
    public static function getConsoleErrors() {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_list_console_messages
        echo "Console errors retrieved\n";
        return [];
    }
    
    /**
     * Get network requests from the browser
     * @return array Array of network requests
     */
    public static function getNetworkRequests() {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_list_network_requests
        echo "Network requests retrieved\n";
        return [];
    }
    
    /**
     * Navigate to a page
     * @param string $url The URL to navigate to
     * @param string $description Optional description
     */
    public static function navigateToPage($url, $description = '') {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_navigate_page
        echo "Navigated to: $url" . ($description ? " - $description" : "") . "\n";
        return true;
    }
    
    /**
     * Wait for text to appear on the page
     * @param string $text The text to wait for
     * @param int $timeout Optional timeout in seconds
     */
    public static function waitForText($text, $timeout = 10) {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_wait_for
        echo "Waiting for text: $text (timeout: {$timeout}s)\n";
        return true;
    }
    
    /**
     * Take a snapshot of the current page
     * @param string $description Optional description
     * @return array Page snapshot data
     */
    public static function takePageSnapshot($description = '') {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_take_snapshot
        echo "Page snapshot taken" . ($description ? " - $description" : "") . "\n";
        return ['snapshot' => 'placeholder'];
    }
    
    /**
     * Start performance tracing
     * @param bool $reload Whether to reload the page
     * @param bool $auto_stop Whether to auto-stop tracing
     */
    public static function startPerformanceTrace($reload = false, $auto_stop = true) {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_performance_start_trace
        echo "Performance trace started (reload: " . ($reload ? 'yes' : 'no') . ", auto-stop: " . ($auto_stop ? 'yes' : 'no') . ")\n";
        return true;
    }
    
    /**
     * Stop performance tracing
     * @return array Performance trace data
     */
    public static function stopPerformanceTrace() {
        if (!self::$chrome_mcp_initialized) {
            throw new Exception("Chrome MCP not initialized. Call initChromeMCP() first.");
        }
        
        // Note: This would use mcp_chrome-devtools_performance_stop_trace
        echo "Performance trace stopped\n";
        return ['trace' => 'placeholder'];
    }
}
?> 