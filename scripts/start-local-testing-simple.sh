#!/bin/bash
# Simple Local Testing Environment Startup Script
# Usage: ./scripts/start-local-testing-simple.sh

# Default parameters
SKIP_BUILD=false
SKIP_WEBSOCKET=false
PHP_PORT=8000

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-build)
            SKIP_BUILD=true
            shift
            ;;
        --skip-websocket)
            SKIP_WEBSOCKET=true
            shift
            ;;
        --php-port)
            PHP_PORT="$2"
            shift 2
            ;;
        *)
            echo "Unknown option $1"
            exit 1
            ;;
    esac
done

echo -e "\033[32mStarting Simple Local Testing Environment...\033[0m"
echo -e "\033[32m=============================================\033[0m"

START_TIME=$(date +%s)
ERRORS=()

# Color functions
print_success() {
    echo -e "\033[32m✅ $1\033[0m"
}

print_error() {
    echo -e "\033[31m❌ $1\033[0m"
    ERRORS+=("$1")
}

print_warning() {
    echo -e "\033[33m⚠️  $1\033[0m"
}

print_info() {
    echo -e "\033[36m$1\033[0m"
}

print_step() {
    echo -e "\n\033[36m$1\033[0m"
    echo -e "\033[37m   $2\033[0m"
}

# Phase 1: Environment Validation
print_step "1. Validating environment..." "Checking basic dependencies"

# Check PHP
if command -v php &> /dev/null; then
    PHP_VERSION=$(php --version | grep -oP 'PHP \K\d+\.\d+\.\d+' | head -1)
    print_success "PHP $PHP_VERSION detected"
else
    print_error "PHP not found"
fi

# Check Node.js
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    print_success "Node.js $NODE_VERSION detected"
else
    print_error "Node.js not found"
fi

# Check package.json
if [ -f "package.json" ]; then
    print_success "package.json found"
else
    print_error "package.json not found"
fi

# Phase 2: Server Management
print_step "2. Managing servers..." "Starting PHP server"

# Kill existing PHP processes on the port
PHP_PROCESSES=$(pgrep -f "php.*-S.*localhost:$PHP_PORT" 2>/dev/null || true)
if [ -n "$PHP_PROCESSES" ]; then
    echo "$PHP_PROCESSES" | xargs kill -9 2>/dev/null || true
    print_success "Stopped existing PHP processes"
    sleep 2
fi

# Create error log file if it doesn't exist
if [ ! -f "php_errors.log" ]; then
    touch "php_errors.log"
fi

# Start PHP server
echo -e "\033[37m   Starting PHP server on port $PHP_PORT...\033[0m"
php -S "localhost:$PHP_PORT" \
    -d "error_reporting=E_ALL" \
    -d "log_errors=1" \
    -d "error_log=php_errors.log" \
    -d "display_errors=1" &
PHP_PID=$!
sleep 3

# Test PHP server
if curl -s -f "http://localhost:$PHP_PORT/health_check.php" > /dev/null 2>&1; then
    print_success "PHP server started successfully"
else
    print_warning "PHP server started but health check failed"
fi

# Phase 3: Build Process (if not skipped)
if [ "$SKIP_BUILD" = false ]; then
    print_step "3. Building reports..." "Installing dependencies and building"
    
    # Install dependencies
    echo -e "\033[37m   Installing npm dependencies...\033[0m"
    if npm ci > /dev/null 2>&1; then
        print_success "npm dependencies installed"
    else
        print_warning "npm ci failed, trying npm install..."
        if npm install > /dev/null 2>&1; then
            print_success "npm dependencies installed with npm install"
        else
            print_error "npm dependency installation failed"
        fi
    fi
    
    # Build reports bundle
    echo -e "\033[37m   Building reports bundle...\033[0m"
    if npm run build:reports > /dev/null 2>&1; then
        print_success "Reports bundle built successfully"
    else
        print_error "Reports build failed"
    fi
else
    echo -e "\n\033[33m3. Skipping build process\033[0m"
fi

# Phase 4: Health Checks
print_step "4. Running health checks..." "Testing endpoints"

# Basic health checks
HEALTH_CHECKS=(
    "http://localhost:$PHP_PORT/health_check.php"
    "http://localhost:$PHP_PORT/login.php"
    "http://localhost:$PHP_PORT/reports/index.php"
)

for url in "${HEALTH_CHECKS[@]}"; do
    if curl -s -f "$url" > /dev/null 2>&1; then
        print_success "Health check passed: $url"
    else
        print_warning "Health check failed: $url"
    fi
done

# Final Status
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo -e "\n\033[32mSimple Local Testing Environment Ready!\033[0m"
echo -e "\033[32m=======================================\033[0m"
echo -e "\033[37mSetup completed in $DURATION seconds\033[0m"

echo -e "\n\033[36mAccess Points:\033[0m"
echo -e "\033[37m   Main Application: http://localhost:$PHP_PORT\033[0m"
echo -e "\033[37m   Login Page: http://localhost:$PHP_PORT/login.php\033[0m"
echo -e "\033[37m   Reports: http://localhost:$PHP_PORT/reports/index.php\033[0m"
echo -e "\033[37m   Health Check: http://localhost:$PHP_PORT/health_check.php\033[0m"

echo -e "\n\033[36mTesting Commands:\033[0m"
echo -e "\033[37m   Run all tests: php run_tests.php\033[0m"
echo -e "\033[37m   Test specific: php run_tests.php csu\033[0m"

echo -e "\n\033[36mLogging Commands:\033[0m"
echo -e "\033[37m   View recent errors: tail -10 php_errors.log\033[0m"
echo -e "\033[37m   Monitor logs live: tail -f php_errors.log\033[0m"

if [ ${#ERRORS[@]} -gt 0 ]; then
    echo -e "\n\033[33mIssues encountered:\033[0m"
    for error in "${ERRORS[@]}"; do
        echo -e "\033[33m   - $error\033[0m"
    done
fi

echo -e "\n\033[31mTo stop server: kill $PHP_PID\033[0m"
echo -e "\n\033[32mSimple local testing environment is ready!\033[0m"
