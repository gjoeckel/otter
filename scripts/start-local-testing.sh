#!/bin/bash
# Enhanced Local Testing Environment Startup Script
# Provides comprehensive environment preparation for local testing
# Usage: "start local testing" or ./scripts/start-local-testing.sh

# Default parameters
SKIP_BUILD=false
SKIP_WEBSOCKET=false
SKIP_VALIDATION=false
PHP_PORT=8000
WEBSOCKET_PORT=8080
VERBOSE=false

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
        --skip-validation)
            SKIP_VALIDATION=true
            shift
            ;;
        --php-port)
            PHP_PORT="$2"
            shift 2
            ;;
        --websocket-port)
            WEBSOCKET_PORT="$2"
            shift 2
            ;;
        --verbose)
            VERBOSE=true
            shift
            ;;
        *)
            echo "Unknown option $1"
            exit 1
            ;;
    esac
done

# Color functions for better output
print_color_output() {
    local message="$1"
    local color="${2:-White}"
    
    case $color in
        "Red") echo -e "\033[31m$message\033[0m" ;;
        "Green") echo -e "\033[32m$message\033[0m" ;;
        "Yellow") echo -e "\033[33m$message\033[0m" ;;
        "Blue") echo -e "\033[34m$message\033[0m" ;;
        "Magenta") echo -e "\033[35m$message\033[0m" ;;
        "Cyan") echo -e "\033[36m$message\033[0m" ;;
        "White") echo -e "\033[37m$message\033[0m" ;;
        "Gray") echo -e "\033[90m$message\033[0m" ;;
        *) echo "$message" ;;
    esac
}

print_step() {
    local step="$1"
    local message="$2"
    print_color_output "🔧 $step" "Cyan"
    print_color_output "   $message" "Gray"
}

print_success() {
    print_color_output "✅ $1" "Green"
}

print_warning() {
    print_color_output "⚠️  $1" "Yellow"
}

print_error() {
    print_color_output "❌ $1" "Red"
}

# Main execution starts here
print_color_output "🚀 STARTING LOCAL TESTING ENVIRONMENT" "Green"
print_color_output "===========================================" "Green"
print_color_output "Time: $(date '+%Y-%m-%d %H:%M:%S')" "Gray"
print_color_output "PHP Port: $PHP_PORT | WebSocket Port: $WEBSOCKET_PORT" "Gray"

START_TIME=$(date +%s)
ERRORS=()

# PHASE 1: Environment Validation
if [ "$SKIP_VALIDATION" = false ]; then
    print_step "Environment Validation" "Checking dependencies and configuration"
    
    # Check PHP version
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php --version | grep -oP 'PHP \K\d+\.\d+\.\d+' | head -1)
        if [ -n "$PHP_VERSION" ]; then
            # Check if version is 8.4.6 or higher
            if [ "$(printf '%s\n' "8.4.6" "$PHP_VERSION" | sort -V | head -n1)" = "8.4.6" ]; then
                print_success "PHP $PHP_VERSION detected (8.4.6+ required)"
            else
                print_error "PHP 8.4.6+ required, found: $PHP_VERSION"
                ERRORS+=("PHP version check failed")
            fi
        else
            print_error "PHP version check failed"
            ERRORS+=("PHP version check failed")
        fi
    else
        print_error "PHP not found or not accessible"
        ERRORS+=("PHP not found")
    fi
    
    # Check Node.js and npm
    if command -v node &> /dev/null && command -v npm &> /dev/null; then
        NODE_VERSION=$(node --version)
        NPM_VERSION=$(npm --version)
        print_success "Node.js $NODE_VERSION and npm $NPM_VERSION detected"
    else
        print_error "Node.js or npm not found"
        ERRORS+=("Node.js/npm not found")
    fi
    
    # Validate package.json exists
    if [ -f "package.json" ]; then
        print_success "package.json found"
    else
        print_error "package.json not found"
        ERRORS+=("package.json missing")
    fi
    
    # Check critical config files
    REQUIRED_CONFIGS=("config/csu.config" "config/ccc.config" "config/demo.config")
    for config in "${REQUIRED_CONFIGS[@]}"; do
        if [ -f "$config" ]; then
            print_success "Config file found: $config"
        else
            print_warning "Config file missing: $config"
        fi
    done
    
    # Clean cache directories
    print_step "Cache Cleanup" "Clearing stale cache data"
    CACHE_DIRS=("cache/ccc" "cache/csu" "cache/demo")
    for dir in "${CACHE_DIRS[@]}"; do
        if [ -d "$dir" ]; then
            rm -rf "$dir"/* 2>/dev/null || true
            print_success "Cleared cache: $dir"
        fi
    done
    
    if [ ${#ERRORS[@]} -gt 0 ]; then
        print_color_output "❌ Validation failed with ${#ERRORS[@]} error(s):" "Red"
        for error in "${ERRORS[@]}"; do
            print_color_output "   - $error" "Red"
        done
        print_color_output "Continue anyway? (y/N): " "Yellow"
        read -r continue_choice
        if [ "$continue_choice" != "y" ] && [ "$continue_choice" != "Y" ]; then
            exit 1
        fi
    else
        print_success "Environment validation completed successfully"
    fi
fi

# PHASE 2: Server Management
print_step "Server Management" "Stopping existing processes and starting servers"

# Kill existing PHP processes
print_color_output "   Stopping existing PHP processes..." "Gray"
PHP_PROCESSES=$(pgrep -f "php.*-S.*localhost:$PHP_PORT" 2>/dev/null || true)
if [ -n "$PHP_PROCESSES" ]; then
    echo "$PHP_PROCESSES" | xargs kill -9 2>/dev/null || true
    print_success "Stopped $(echo "$PHP_PROCESSES" | wc -l) PHP process(es)"
    sleep 2
else
    print_success "No existing PHP processes found"
fi

# Check port availability
test_port() {
    local port="$1"
    if command -v netstat &> /dev/null; then
        ! netstat -an 2>/dev/null | grep -q ":$port "
    elif command -v ss &> /dev/null; then
        ! ss -an 2>/dev/null | grep -q ":$port "
    else
        # Fallback: try to connect to the port
        ! timeout 1 bash -c "</dev/tcp/localhost/$port" 2>/dev/null
    fi
}

# Start PHP server
print_color_output "   Starting PHP server on port $PHP_PORT..." "Gray"
if ! test_port "$PHP_PORT"; then
    print_warning "Port $PHP_PORT is in use, attempting to free it..."
    # Try to find and kill process using the port
    if command -v lsof &> /dev/null; then
        PID=$(lsof -ti:$PHP_PORT 2>/dev/null || true)
        if [ -n "$PID" ]; then
            kill -9 "$PID" 2>/dev/null || true
        fi
    fi
    sleep 3
fi

# Create error log file if it doesn't exist
if [ ! -f "php_errors.log" ]; then
    touch "php_errors.log"
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

if [ "$VERBOSE" = true ]; then
    PHP_ARGS+=("-d" "display_errors=1")
    print_color_output "   Starting with verbose logging..." "Cyan"
fi

# Start PHP server in background
php "${PHP_ARGS[@]}" &
PHP_PID=$!
sleep 3

# Test PHP server
if curl -s -f "http://localhost:$PHP_PORT/health_check.php" > /dev/null 2>&1; then
    print_success "PHP server started successfully on http://localhost:$PHP_PORT"
else
    print_warning "PHP server started but health check failed"
fi

# Start WebSocket server (optional)
if [ "$SKIP_WEBSOCKET" = false ]; then
    print_color_output "   Starting WebSocket server on port $WEBSOCKET_PORT..." "Gray"
    if test_port "$WEBSOCKET_PORT"; then
        php "lib/websocket/websocket-server.php" &
        WS_PID=$!
        sleep 2
        print_success "WebSocket server started on port $WEBSOCKET_PORT"
    else
        print_warning "WebSocket port $WEBSOCKET_PORT is in use, skipping WebSocket server"
    fi
fi

# PHASE 3: Build Process
if [ "$SKIP_BUILD" = false ]; then
    print_step "Build Process" "Installing dependencies and building reports bundle"
    
    # Install/update npm dependencies
    print_color_output "   Installing npm dependencies..." "Gray"
    if npm ci > /dev/null 2>&1; then
        print_success "npm dependencies installed successfully"
    else
        print_warning "npm ci failed, trying npm install..."
        if npm install > /dev/null 2>&1; then
            print_success "npm dependencies installed with npm install"
        else
            print_error "npm dependency installation failed"
            ERRORS+=("npm dependency installation failed")
        fi
    fi
    
    # Build reports bundle
    print_color_output "   Building reports bundle..." "Gray"
    if npm run build:reports > /dev/null 2>&1; then
        print_success "Reports bundle built successfully"
        
        # Verify build output
        if [ -f "reports/dist/reports.bundle.js" ]; then
            FILE_SIZE=$(stat -c%s "reports/dist/reports.bundle.js" 2>/dev/null || stat -f%z "reports/dist/reports.bundle.js" 2>/dev/null || echo "0")
            FILE_SIZE_KB=$((FILE_SIZE / 1024))
            print_success "Build output verified: reports.bundle.js ($FILE_SIZE_KB KB)"
        else
            print_warning "Build completed but reports.bundle.js not found"
        fi
    else
        print_error "Reports build failed"
        ERRORS+=("Reports build failed")
    fi
else
    print_color_output "⏭️  Skipping build process" "Yellow"
fi

# PHASE 4: Testing Preparation
print_step "Testing Preparation" "Running health checks and validation"

# Run comprehensive health checks
declare -A HEALTH_CHECKS=(
    ["PHP Server"]="http://localhost:$PHP_PORT/health_check.php"
    ["Login Page"]="http://localhost:$PHP_PORT/login.php"
    ["Reports Page"]="http://localhost:$PHP_PORT/reports/index.php"
)

HEALTH_RESULTS=()
for check_name in "${!HEALTH_CHECKS[@]}"; do
    check_url="${HEALTH_CHECKS[$check_name]}"
    if curl -s -f "$check_url" > /dev/null 2>&1; then
        print_success "$check_name: OK"
        HEALTH_RESULTS+=("$check_name:OK")
    else
        print_error "$check_name: ERROR"
        HEALTH_RESULTS+=("$check_name:ERROR")
    fi
done

# Check error logs
if [ -f "php_errors.log" ]; then
    LOG_SIZE=$(stat -c%s "php_errors.log" 2>/dev/null || stat -f%z "php_errors.log" 2>/dev/null || echo "0")
    if [ "$LOG_SIZE" -gt 0 ]; then
        print_warning "PHP error log contains $LOG_SIZE bytes of data"
        if [ "$VERBOSE" = true ]; then
            print_color_output "Recent errors:" "Yellow"
            tail -5 "php_errors.log" | while read -r line; do
                print_color_output "   $line" "Red"
            done
        fi
    else
        print_success "PHP error log is clean"
    fi
fi

# PHASE 5: Final Status and Instructions
END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

print_color_output "🎉 LOCAL TESTING ENVIRONMENT READY!" "Green"
print_color_output "===========================================" "Green"
print_color_output "Setup completed in $DURATION seconds" "Gray"

# Display access information
print_color_output "📱 ACCESS POINTS:" "Cyan"
print_color_output "   🌐 Main Application: http://localhost:$PHP_PORT" "White"
print_color_output "   🔐 Login Page: http://localhost:$PHP_PORT/login.php" "White"
print_color_output "   📊 Reports: http://localhost:$PHP_PORT/reports/index.php" "White"
print_color_output "   ❤️  Health Check: http://localhost:$PHP_PORT/health_check.php" "White"

if [ "$SKIP_WEBSOCKET" = false ] && [ -n "$WS_PID" ]; then
    print_color_output "   🔌 WebSocket Console: ws://localhost:$WEBSOCKET_PORT/console-monitor" "White"
fi

print_color_output "📁 IMPORTANT FILES:" "Cyan"
print_color_output "   📝 Error Log: php_errors.log" "White"
print_color_output "   📦 Build Output: reports/dist/reports.bundle.js" "White"
print_color_output "   ⚙️  Config Files: config/*.config" "White"

print_color_output "🧪 TESTING COMMANDS:" "Cyan"
print_color_output "   Run all tests: php run_tests.php" "White"
print_color_output "   Test specific enterprise: php run_tests.php csu" "White"
print_color_output "   View logs: tail -10 php_errors.log" "White"

# Display any errors or warnings
if [ ${#ERRORS[@]} -gt 0 ]; then
    print_color_output "⚠️  ISSUES ENCOUNTERED:" "Yellow"
    for error in "${ERRORS[@]}"; do
        print_color_output "   - $error" "Yellow"
    done
    print_color_output "Environment is ready but some issues were noted above." "Yellow"
fi

print_color_output "🛑 TO STOP SERVERS:" "Red"
print_color_output "   Stop PHP server: kill $PHP_PID" "White"
if [ -n "$WS_PID" ]; then
    print_color_output "   Stop WebSocket server: kill $WS_PID" "White"
fi
print_color_output "   Or use Ctrl+C if running in foreground" "White"

print_color_output "Press any key to continue..." "Gray"
read -n 1 -s

print_color_output "✅ Local testing environment is ready for use!" "Green"
