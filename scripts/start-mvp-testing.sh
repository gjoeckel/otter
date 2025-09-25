#!/bin/bash
# MVP Local Testing Environment Startup Script
# Usage: ./scripts/start-mvp-testing.sh
# Token: "mvp local"

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

echo -e "\033[32mStarting MVP Local Testing Environment...\033[0m"
echo -e "\033[32m=========================================\033[0m"
echo -e "\033[36mMode: MVP (simplified, no count options complexity)\033[0m"

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
print_step "1. Validating environment..." "Checking dependencies and configuration"

# Check PHP version
if command -v php &> /dev/null; then
    PHP_VERSION=$(php --version | grep -oP 'PHP \K\d+\.\d+\.\d+' | head -1)
    if [ -n "$PHP_VERSION" ]; then
        print_success "PHP $PHP_VERSION detected"
    else
        print_error "PHP version check failed"
    fi
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

# Check MVP files exist
echo -e "\033[37m   Checking MVP files...\033[0m"
MVP_FILES=(
    "reports/js/reports-data.js"
    "reports/js/unified-data-service.js"
    "reports/js/unified-table-updater.js"
    "reports/js/reports-entry.js"
    "reports/js/reports-messaging.js"
)

MVP_FILES_EXIST=true
for file in "${MVP_FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "\033[32m     ✅ $file\033[0m"
    else
        echo -e "\033[31m     ❌ $file (missing)\033[0m"
        MVP_FILES_EXIST=false
        ERRORS+=("MVP file missing: $file")
    fi
done

if [ "$MVP_FILES_EXIST" = true ]; then
    print_success "All MVP files present"
else
    print_error "Some MVP files missing"
fi

# Phase 2: Server Management
print_step "2. Managing servers..." "Stopping existing processes and starting servers"

# Kill existing PHP processes
PHP_PROCESSES=$(pgrep -f "php.*-S.*localhost:$PHP_PORT" 2>/dev/null || true)
if [ -n "$PHP_PROCESSES" ]; then
    echo "$PHP_PROCESSES" | xargs kill -9 2>/dev/null || true
    print_success "Stopped existing PHP processes"
    sleep 2
fi

# Start PHP server with enhanced logging
echo -e "\033[37m   Starting PHP server on port $PHP_PORT with logging...\033[0m"

# Create error log file if it doesn't exist
if [ ! -f "php_errors.log" ]; then
    touch "php_errors.log"
    print_success "Created php_errors.log file"
fi

# Start PHP server with enhanced configuration
PHP_ARGS=(
    "-S" "localhost:$PHP_PORT"
    "-d" "error_reporting=E_ALL"
    "-d" "log_errors=1"
    "-d" "error_log=php_errors.log"
    "-d" "display_errors=1"
    "-d" "display_startup_errors=1"
)

# Start PHP server in background
php "${PHP_ARGS[@]}" &
PHP_PID=$!
sleep 3

# Test PHP server
if curl -s -f "http://localhost:$PHP_PORT/health_check.php" > /dev/null 2>&1; then
    print_success "PHP server started successfully with logging enabled"
    
    # Verify logging is working
    if [ -f "php_errors.log" ]; then
        print_success "PHP error log file created: php_errors.log"
    else
        print_warning "PHP error log file not found"
    fi
else
    print_warning "PHP server started but health check failed"
fi

# Phase 3: Logging Verification
print_step "3. Verifying logging setup..." "Testing logging functionality"

# Test logging by making a request that should generate logs
if curl -s -f "http://localhost:$PHP_PORT/health_check.php" > /dev/null 2>&1; then
    if [ -f "php_errors.log" ]; then
        LOG_SIZE=$(stat -c%s "php_errors.log" 2>/dev/null || stat -f%z "php_errors.log" 2>/dev/null || echo "0")
        print_success "PHP error log file exists (size: $LOG_SIZE bytes)"
        
        # Show recent log entries if any
        if [ "$LOG_SIZE" -gt 0 ]; then
            echo -e "\033[36m   Recent log entries:\033[0m"
            tail -3 "php_errors.log" | while read -r line; do
                echo -e "\033[37m     $line\033[0m"
            done
        else
            echo -e "\033[37m   No log entries yet (this is normal for successful requests)\033[0m"
        fi
    else
        print_warning "PHP error log file not found"
    fi
else
    print_warning "Could not verify logging setup"
fi

# Phase 4: MVP Build Process
if [ "$SKIP_BUILD" = false ]; then
    print_step "4. Building MVP reports..." "Installing dependencies and building bundle"
    
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
    
    # Build MVP reports bundle
    echo -e "\033[37m   Building MVP reports bundle...\033[0m"
    if npm run build:mvp > /dev/null 2>&1; then
        if [ -f "reports/dist/reports.bundle.js" ]; then
            BUNDLE_SIZE=$(stat -c%s "reports/dist/reports.bundle.js" 2>/dev/null || stat -f%z "reports/dist/reports.bundle.js" 2>/dev/null || echo "unknown")
            print_success "MVP reports bundle built successfully ($BUNDLE_SIZE bytes)"
        else
            print_warning "Build completed but MVP bundle not found"
        fi
    else
        print_error "MVP build failed"
    fi
else
    echo -e "\n\033[33m4. Skipping MVP build process\033[0m"
fi

# Phase 5: Cache Busting
print_step "5. Applying cache busting..." "Updating cache busting parameters"

# Generate multiple cache busting timestamps
CACHE_BUST_TIMESTAMP=$(date +%s)
CACHE_BUST_DATE=$(date +%Y%m%d%H%M%S)
CACHE_BUST_RANDOM=$((RANDOM % 9000 + 1000))
echo -e "\033[37m   Cache bust timestamps: $CACHE_BUST_TIMESTAMP, $CACHE_BUST_DATE, $CACHE_BUST_RANDOM\033[0m"

# Function to update cache busting in files
update_cache_busting() {
    local file_path="$1"
    local timestamp="$2"
    
    if [ -f "$file_path" ]; then
        # Pattern 1: PHP time() function
        if grep -q 'v=<?php echo time(); ?>' "$file_path"; then
            sed -i "s/v=<?php echo time(); ?>/v=$timestamp/g" "$file_path"
            print_success "Updated cache busting in $file_path"
        # Pattern 2: Static version numbers
        elif grep -q 'v=[0-9]\+' "$file_path"; then
            sed -i "s/v=[0-9]\+/v=$timestamp/g" "$file_path"
            print_success "Updated cache busting in $file_path"
        # Pattern 3: Date-based versions
        elif grep -q 'v=[0-9]\{14\}' "$file_path"; then
            sed -i "s/v=[0-9]\{14\}/v=$CACHE_BUST_DATE/g" "$file_path"
            print_success "Updated cache busting in $file_path"
        else
            echo -e "\033[37m   No cache busting patterns found in $file_path\033[0m"
        fi
    fi
}

# Update MVP PHP files with cache busting parameters
MVP_PHP_FILES=("reports/index.php")

for php_file in "${MVP_PHP_FILES[@]}"; do
    update_cache_busting "$php_file" "$CACHE_BUST_TIMESTAMP"
done

# Update CSS files with cache busting
if [ -d "css" ]; then
    for css_file in css/*.css; do
        if [ -f "$css_file" ]; then
            update_cache_busting "$css_file" "$CACHE_BUST_RANDOM"
        fi
    done
fi

# Update MVP JavaScript files with cache busting
MVP_JS_FILES=("reports/dist/reports.bundle.js")

for js_file in "${MVP_JS_FILES[@]}"; do
    if [ -f "$js_file" ]; then
        # Touch the file to update its modification time
        touch "$js_file"
        print_success "Updated timestamp for $js_file"
    fi
done

# Create MVP cache-busting manifest file
cat > "cache-bust-manifest.json" << EOF
{
    "mode": "MVP",
    "timestamp": $CACHE_BUST_TIMESTAMP,
    "date": "$CACHE_BUST_DATE",
    "random": $CACHE_BUST_RANDOM,
    "generated": "$(date '+%Y-%m-%d %H:%M:%S')",
    "description": "MVP (simplified, no count options complexity)"
}
EOF

print_success "Created cache-bust-manifest.json"
print_success "MVP cache busting completed successfully"

# Phase 6: MVP Testing Preparation
print_step "6. Preparing for MVP testing..." "Running health checks"

# Health checks for MVP
declare -A HEALTH_CHECKS=(
    ["PHP Server"]="http://localhost:$PHP_PORT/health_check.php"
    ["Login Page"]="http://localhost:$PHP_PORT/login.php"
    ["MVP Reports Page"]="http://localhost:$PHP_PORT/reports/index.php"
)

for check_name in "${!HEALTH_CHECKS[@]}"; do
    check_url="${HEALTH_CHECKS[$check_name]}"
    if curl -s -f "$check_url" > /dev/null 2>&1; then
        print_success "$check_name: OK"
    else
        print_error "$check_name: ERROR"
    fi
done

# Final Status
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo -e "\n\033[32mMVP Local Testing Environment Setup Complete!\033[0m"
echo -e "\033[32m=============================================\033[0m"
echo -e "\033[37mSetup completed in $DURATION seconds\033[0m"

echo -e "\n\033[36mMVP Access Points:\033[0m"
echo -e "\033[37m   Main Application: http://localhost:$PHP_PORT\033[0m"
echo -e "\033[37m   Login Page: http://localhost:$PHP_PORT/login.php\033[0m"
echo -e "\033[37m   MVP Reports: http://localhost:$PHP_PORT/reports/index.php\033[0m"
echo -e "\033[37m   Original Reports: http://localhost:$PHP_PORT/reports/index.php\033[0m"
echo -e "\033[37m   Health Check: http://localhost:$PHP_PORT/health_check.php\033[0m"

echo -e "\n\033[36mMVP Features:\033[0m"
echo -e "\033[32m   ✅ Simplified interface (no count options complexity)\033[0m"
echo -e "\033[32m   ✅ Hardcoded modes (by-date registrations, by-tou enrollments)\033[0m"
echo -e "\033[32m   ✅ No radio buttons or mode switching\033[0m"
echo -e "\033[32m   ✅ Reliable data display\033[0m"
echo -e "\033[32m   ✅ Smaller bundle size (10KB vs 37KB)\033[0m"

echo -e "\n\033[36mTesting Commands:\033[0m"
echo -e "\033[37m   Run all tests: php run_tests.php\033[0m"
echo -e "\033[37m   Test specific: php run_tests.php csu\033[0m"

echo -e "\n\033[36mLogging Commands:\033[0m"
echo -e "\033[37m   View recent errors: tail -10 php_errors.log\033[0m"
echo -e "\033[37m   Monitor logs live: tail -f php_errors.log\033[0m"
echo -e "\033[37m   Check log size: stat -c%s php_errors.log\033[0m"

echo -e "\n\033[36mMVP Cache Busting Commands:\033[0m"
echo -e "\033[37m   View cache manifest: cat cache-bust-manifest.json\033[0m"
echo -e "\033[37m   Force cache bust: rm cache-bust-manifest.json; ./scripts/start-mvp-testing.sh\033[0m"

if [ ${#ERRORS[@]} -gt 0 ]; then
    echo -e "\n\033[33mIssues encountered:\033[0m"
    for error in "${ERRORS[@]}"; do
        echo -e "\033[33m   - $error\033[0m"
    done
fi

echo -e "\n\033[31mTo stop servers: kill $PHP_PID\033[0m"
echo -e "\n\033[32mMVP local testing environment is ready!\033[0m"
