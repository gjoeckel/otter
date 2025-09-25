#!/bin/bash
# Test Local Testing Environment Script
# Usage: ./scripts/test-local-testing.sh

echo -e "\033[32mTesting Local Testing Environment...\033[0m"
echo -e "\033[32m=====================================\033[0m"

# Test parameters
PHP_PORT=8000
TEST_URLS=(
    "http://localhost:$PHP_PORT/health_check.php"
    "http://localhost:$PHP_PORT/login.php"
    "http://localhost:$PHP_PORT/reports/index.php"
)

# Color functions
print_success() {
    echo -e "\033[32m✅ $1\033[0m"
}

print_error() {
    echo -e "\033[31m❌ $1\033[0m"
}

print_warning() {
    echo -e "\033[33m⚠️  $1\033[0m"
}

print_info() {
    echo -e "\033[36m$1\033[0m"
}

# Test 1: Check if PHP server is running
print_info "Test 1: Checking PHP server status"
if pgrep -f "php.*-S.*localhost:$PHP_PORT" > /dev/null; then
    print_success "PHP server is running on port $PHP_PORT"
else
    print_error "PHP server is not running on port $PHP_PORT"
    echo -e "\033[37m   Start server with: ./scripts/start-local-testing.sh\033[0m"
    exit 1
fi

# Test 2: Check if curl is available
print_info "Test 2: Checking curl availability"
if command -v curl &> /dev/null; then
    print_success "curl is available"
else
    print_error "curl is not available"
    echo -e "\033[37m   Install curl to run HTTP tests\033[0m"
    exit 1
fi

# Test 3: Test HTTP endpoints
print_info "Test 3: Testing HTTP endpoints"
for url in "${TEST_URLS[@]}"; do
    echo -e "\033[37m   Testing: $url\033[0m"
    if curl -s -f "$url" > /dev/null 2>&1; then
        HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$url")
        print_success "HTTP $HTTP_CODE - $url"
    else
        print_error "Failed to connect to $url"
    fi
done

# Test 4: Check error log
print_info "Test 4: Checking error log"
if [ -f "php_errors.log" ]; then
    LOG_SIZE=$(stat -c%s "php_errors.log" 2>/dev/null || stat -f%z "php_errors.log" 2>/dev/null || echo "0")
    if [ "$LOG_SIZE" -gt 0 ]; then
        print_warning "PHP error log contains $LOG_SIZE bytes of data"
        echo -e "\033[37m   Recent errors:\033[0m"
        tail -3 "php_errors.log" | while read -r line; do
            echo -e "\033[37m     $line\033[0m"
        done
    else
        print_success "PHP error log is clean"
    fi
else
    print_warning "PHP error log file not found"
fi

# Test 5: Check build output
print_info "Test 5: Checking build output"
if [ -f "reports/dist/reports.bundle.js" ]; then
    BUNDLE_SIZE=$(stat -c%s "reports/dist/reports.bundle.js" 2>/dev/null || stat -f%z "reports/dist/reports.bundle.js" 2>/dev/null || echo "unknown")
    print_success "Reports bundle exists ($BUNDLE_SIZE bytes)"
else
    print_warning "Reports bundle not found"
    echo -e "\033[37m   Build with: npm run build:reports\033[0m"
fi

# Test 6: Check config files
print_info "Test 6: Checking config files"
CONFIG_FILES=("config/csu.config" "config/ccc.config" "config/demo.config")
for config in "${CONFIG_FILES[@]}"; do
    if [ -f "$config" ]; then
        print_success "Config file found: $config"
    else
        print_warning "Config file missing: $config"
    fi
done

# Test 7: Check cache directories
print_info "Test 7: Checking cache directories"
CACHE_DIRS=("cache/ccc" "cache/csu" "cache/demo")
for dir in "${CACHE_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        CACHE_COUNT=$(find "$dir" -type f 2>/dev/null | wc -l)
        print_success "Cache directory exists: $dir ($CACHE_COUNT files)"
    else
        print_warning "Cache directory missing: $dir"
    fi
done

echo -e "\n\033[32mLocal Testing Environment Test Complete!\033[0m"
echo -e "\033[32m========================================\033[0m"

echo -e "\n\033[36mSummary:\033[0m"
echo -e "\033[37m   PHP Server: $(pgrep -f "php.*-S.*localhost:$PHP_PORT" > /dev/null && echo "Running" || echo "Not Running")\033[0m"
echo -e "\033[37m   Error Log: $([ -f "php_errors.log" ] && echo "Exists" || echo "Missing")\033[0m"
echo -e "\033[37m   Build Output: $([ -f "reports/dist/reports.bundle.js" ] && echo "Exists" || echo "Missing")\033[0m"

echo -e "\n\033[36mNext Steps:\033[0m"
echo -e "\033[37m   Run tests: php run_tests.php\033[0m"
echo -e "\033[37m   View logs: tail -f php_errors.log\033[0m"
echo -e "\033[37m   Stop server: kill \$(pgrep -f 'php.*-S.*localhost:$PHP_PORT')\033[0m"
