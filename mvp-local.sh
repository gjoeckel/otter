#!/bin/bash
# MVP Local Command - Git Bash Script
# Usage: mvp local
# This script provides the "mvp local" command functionality

# Color functions for better output
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

print_header() {
    echo -e "\033[32m$1\033[0m"
    echo -e "\033[32m$(printf '=%.0s' {1..50})\033[0m"
}

# Function to check if we're in the correct directory
check_directory() {
    if [[ ! -f "package.json" ]] || [[ ! -d "reports" ]] || [[ ! -d "config" ]]; then
        print_error "Not in the otter project directory"
        print_info "Please run this script from the project root directory"
        print_info "Expected files: package.json, reports/, config/"
        exit 1
    fi
    print_success "Project directory verified"
}

# Function to check dependencies
check_dependencies() {
    print_info "Checking dependencies..."
    
    # Check PHP
    if command -v php &> /dev/null; then
        PHP_VERSION=$(php --version | grep -oP 'PHP \K\d+\.\d+\.\d+' | head -1)
        print_success "PHP $PHP_VERSION detected"
    else
        print_error "PHP not found"
        exit 1
    fi
    
    # Check Node.js
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node --version)
        print_success "Node.js $NODE_VERSION detected"
    else
        print_error "Node.js not found"
        exit 1
    fi
    
    # Check npm
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm --version)
        print_success "npm $NPM_VERSION detected"
    else
        print_error "npm not found"
        exit 1
    fi
}

# Function to stop existing PHP processes
stop_existing_php() {
    print_info "Checking for existing PHP processes..."
    
    PHP_PROCESSES=$(pgrep -f "php.*-S.*localhost:8000" 2>/dev/null || true)
    if [ -n "$PHP_PROCESSES" ]; then
        print_warning "Found existing PHP server processes"
        echo "$PHP_PROCESSES" | xargs kill -9 2>/dev/null || true
        print_success "Stopped existing PHP processes"
        sleep 2
    else
        print_success "No existing PHP processes found"
    fi
}

# Function to start PHP server
start_php_server() {
    print_info "Starting PHP development server..."
    
    # Create error log if it doesn't exist
    if [ ! -f "php_errors.log" ]; then
        touch "php_errors.log"
        print_success "Created php_errors.log file"
    fi
    
    # Start PHP server in background
    php -S localhost:8000 \
        -d "error_reporting=E_ALL" \
        -d "log_errors=1" \
        -d "error_log=php_errors.log" \
        -d "display_errors=1" \
        -d "display_startup_errors=1" &
    
    PHP_PID=$!
    sleep 3
    
    # Test if server started successfully
    if curl -s -f "http://localhost:8000/health_check.php" > /dev/null 2>&1; then
        print_success "PHP server started successfully on http://localhost:8000"
        echo "PHP Server PID: $PHP_PID"
    else
        print_warning "PHP server started but health check failed"
        print_info "Server may still be starting up..."
    fi
}

# Function to build JavaScript bundle
build_bundle() {
    print_info "Building JavaScript bundle..."
    
    # Install dependencies if needed
    if [ ! -d "node_modules" ]; then
        print_info "Installing npm dependencies..."
        if npm ci > /dev/null 2>&1; then
            print_success "npm dependencies installed"
        else
            print_warning "npm ci failed, trying npm install..."
            npm install > /dev/null 2>&1
        fi
    fi
    
    # Build the bundle
    if npm run build:mvp > /dev/null 2>&1; then
        if [ -f "reports/dist/reports.bundle.js" ]; then
            BUNDLE_SIZE=$(stat -c%s "reports/dist/reports.bundle.js" 2>/dev/null || stat -f%z "reports/dist/reports.bundle.js" 2>/dev/null || echo "unknown")
            print_success "JavaScript bundle built successfully ($BUNDLE_SIZE bytes)"
        else
            print_warning "Build completed but bundle not found"
        fi
    else
        print_error "JavaScript bundle build failed"
        print_info "You can try building manually with: npm run build:mvp"
    fi
}

# Function to run health checks
run_health_checks() {
    print_info "Running health checks..."
    
    local base_url="http://localhost:8000"
    local endpoints=(
        "$base_url/health_check.php:Health Check"
        "$base_url/login.php:Login Page"
        "$base_url/reports/index.php:Reports Page"
        "$base_url/:Main Application"
    )
    
    local success_count=0
    local total_count=${#endpoints[@]}
    
    for endpoint in "${endpoints[@]}"; do
        local url=$(echo "$endpoint" | cut -d: -f1)
        local description=$(echo "$endpoint" | cut -d: -f2)
        
        echo -n "Testing $description: "
        if curl -s -f "$url" > /dev/null 2>&1; then
            print_success "OK"
            success_count=$((success_count + 1))
        else
            print_error "Failed"
        fi
    done
    
    echo ""
    if [ $success_count -eq $total_count ]; then
        print_success "All health checks passed ($success_count/$total_count)"
    elif [ $success_count -gt 0 ]; then
        print_warning "Some health checks passed ($success_count/$total_count)"
    else
        print_error "All health checks failed ($success_count/$total_count)"
    fi
}

# Function to display access information
show_access_info() {
    print_header "MVP Local Environment Ready!"
    
    echo -e "\033[36mAccess Points:\033[0m"
    echo -e "\033[37m   🌐 Main Application: http://localhost:8000\033[0m"
    echo -e "\033[37m   🔐 Login Page: http://localhost:8000/login.php\033[0m"
    echo -e "\033[37m   📊 Reports: http://localhost:8000/reports/index.php\033[0m"
    echo -e "\033[37m   ❤️  Health Check: http://localhost:8000/health_check.php\033[0m"
    
    echo -e "\n\033[36mTesting Commands:\033[0m"
    echo -e "\033[37m   Run all tests: php run_tests.php\033[0m"
    echo -e "\033[37m   Test specific: php run_tests.php csu\033[0m"
    
    echo -e "\n\033[36mLogging Commands:\033[0m"
    echo -e "\033[37m   View recent errors: tail -10 php_errors.log\033[0m"
    echo -e "\033[37m   Monitor logs live: tail -f php_errors.log\033[0m"
    
    echo -e "\n\033[36mServer Management:\033[0m"
    echo -e "\033[37m   Stop server: kill $PHP_PID\033[0m"
    echo -e "\033[37m   Or use Ctrl+C if running in foreground\033[0m"
    
    echo -e "\n\033[32mMVP local environment is ready!\033[0m"
}

# Main function
main() {
    print_header "MVP Local Environment Startup"
    print_info "Starting MVP local testing environment..."
    print_info "Mode: MVP (simplified, no count options complexity)"
    echo ""
    
    # Check if we're in the right directory
    check_directory
    
    # Check dependencies
    check_dependencies
    
    # Stop existing PHP processes
    stop_existing_php
    
    # Start PHP server
    start_php_server
    
    # Build JavaScript bundle
    build_bundle
    
    # Run health checks
    run_health_checks
    
    # Show access information
    show_access_info
    
    # Keep the script running to maintain the server
    print_info "Press Ctrl+C to stop the server and exit"
    wait $PHP_PID 2>/dev/null || true
}

# Cleanup function
cleanup() {
    print_info "Stopping PHP server..."
    if [ -n "$PHP_PID" ] && kill -0 "$PHP_PID" 2>/dev/null; then
        kill -9 "$PHP_PID" 2>/dev/null || true
        print_success "PHP server stopped"
    fi
    exit 0
}

# Set up signal handlers
trap cleanup EXIT INT TERM

# Run main function
main "$@"