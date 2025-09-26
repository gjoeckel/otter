#!/bin/bash
# Server Diagnosis Script
# Usage: ./tests/diagnose_server.sh

# Default parameters
PORT=8000
SERVER_HOST="localhost"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --port)
            PORT="$2"
            shift 2
            ;;
        --host)
            SERVER_HOST="$2"
            shift 2
            ;;
        *)
            echo "Unknown option $1"
            exit 1
            ;;
    esac
done

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

# Function to check if port is in use
check_port() {
    local port="$1"
    
    if command -v netstat &> /dev/null; then
        netstat -an 2>/dev/null | grep -q ":$port "
    elif command -v ss &> /dev/null; then
        ss -an 2>/dev/null | grep -q ":$port "
    else
        # Fallback: try to connect to the port
        timeout 1 bash -c "</dev/tcp/localhost/$port" 2>/dev/null
    fi
}

# Function to get process using port
get_port_process() {
    local port="$1"
    
    if command -v lsof &> /dev/null; then
        lsof -ti:$port 2>/dev/null || echo ""
    elif command -v fuser &> /dev/null; then
        fuser $port/tcp 2>/dev/null | tr -d ' ' || echo ""
    else
        echo ""
    fi
}

# Function to test HTTP endpoint
test_endpoint() {
    local url="$1"
    local description="$2"
    
    echo -n "Testing $description: "
    
    if curl -s -f "$url" > /dev/null 2>&1; then
        local http_code=$(curl -s -o /dev/null -w "%{http_code}" "$url")
        print_success "HTTP $http_code"
        return 0
    else
        print_error "Failed to connect"
        return 1
    fi
}

# Function to check PHP installation
check_php() {
    print_info "Checking PHP installation..."
    
    if command -v php &> /dev/null; then
        local php_version=$(php --version | grep -oP 'PHP \K\d+\.\d+\.\d+' | head -1)
        print_success "PHP $php_version is installed"
        
        # Check PHP modules
        local modules=$(php -m 2>/dev/null | grep -E "(curl|json|pdo)" | wc -l)
        if [ "$modules" -ge 3 ]; then
            print_success "Required PHP modules are available"
        else
            print_warning "Some required PHP modules may be missing"
        fi
    else
        print_error "PHP is not installed or not in PATH"
        return 1
    fi
}

# Function to check Node.js installation
check_node() {
    print_info "Checking Node.js installation..."
    
    if command -v node &> /dev/null; then
        local node_version=$(node --version)
        print_success "Node.js $node_version is installed"
        
        if command -v npm &> /dev/null; then
            local npm_version=$(npm --version)
            print_success "npm $npm_version is installed"
        else
            print_warning "npm is not installed"
        fi
    else
        print_warning "Node.js is not installed"
    fi
}

# Function to check curl installation
check_curl() {
    print_info "Checking curl installation..."
    
    if command -v curl &> /dev/null; then
        local curl_version=$(curl --version | head -1)
        print_success "curl is installed: $curl_version"
    else
        print_error "curl is not installed"
        return 1
    fi
}

# Function to check server status
check_server_status() {
    print_info "Checking server status on port $PORT..."
    
    if check_port "$PORT"; then
        local pid=$(get_port_process "$PORT")
        if [ -n "$pid" ]; then
            print_success "Port $PORT is in use by process $pid"
            
            # Check if it's a PHP process
            if ps -p "$pid" -o comm= 2>/dev/null | grep -q php; then
                print_success "Process $pid is running PHP"
            else
                print_warning "Process $pid is not PHP"
            fi
        else
            print_warning "Port $PORT is in use but process info unavailable"
        fi
    else
        print_error "Port $PORT is not in use"
        return 1
    fi
}

# Function to test server endpoints
test_server_endpoints() {
    print_info "Testing server endpoints..."
    
    local base_url="http://$SERVER_HOST:$PORT"
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
        
        if test_endpoint "$url" "$description"; then
            success_count=$((success_count + 1))
        fi
    done
    
    echo ""
    print_info "Endpoint test results: $success_count/$total_count successful"
    
    if [ $success_count -eq $total_count ]; then
        print_success "All endpoints are responding"
    elif [ $success_count -gt 0 ]; then
        print_warning "Some endpoints are responding"
    else
        print_error "No endpoints are responding"
    fi
}

# Function to check error logs
check_error_logs() {
    print_info "Checking error logs..."
    
    if [ -f "php_errors.log" ]; then
        local log_size=$(stat -c%s "php_errors.log" 2>/dev/null || stat -f%z "php_errors.log" 2>/dev/null || echo "0")
        if [ "$log_size" -gt 0 ]; then
            print_warning "PHP error log contains $log_size bytes of data"
            echo "Recent errors:"
            tail -5 "php_errors.log" | while read -r line; do
                echo "  $line"
            done
        else
            print_success "PHP error log is clean"
        fi
    else
        print_warning "PHP error log file not found"
    fi
}

# Function to check build output
check_build_output() {
    print_info "Checking build output..."
    
    if [ -f "reports/dist/reports.bundle.js" ]; then
        local bundle_size=$(stat -c%s "reports/dist/reports.bundle.js" 2>/dev/null || stat -f%z "reports/dist/reports.bundle.js" 2>/dev/null || echo "unknown")
        print_success "Reports bundle exists ($bundle_size bytes)"
    else
        print_warning "Reports bundle not found"
        echo "  Build with: npm run build:reports"
    fi
}

# Function to check config files
check_config_files() {
    print_info "Checking config files..."
    
    local config_files=("config/csu.config" "config/ccc.config" "config/demo.config")
    local found_count=0
    
    for config in "${config_files[@]}"; do
        if [ -f "$config" ]; then
            print_success "Config file found: $config"
            found_count=$((found_count + 1))
        else
            print_warning "Config file missing: $config"
        fi
    done
    
    if [ $found_count -eq ${#config_files[@]} ]; then
        print_success "All config files are present"
    elif [ $found_count -gt 0 ]; then
        print_warning "Some config files are missing"
    else
        print_error "No config files found"
    fi
}

# Function to provide recommendations
provide_recommendations() {
    print_info "Recommendations:"
    
    if ! check_port "$PORT"; then
        echo "  - Start the server: ./tests/start_server.sh"
    fi
    
    if [ ! -f "reports/dist/reports.bundle.js" ]; then
        echo "  - Build the reports: npm run build:reports"
    fi
    
    if [ ! -f "php_errors.log" ]; then
        echo "  - Check if PHP error logging is enabled"
    fi
    
    echo "  - View logs: tail -f php_errors.log"
    echo "  - Test endpoints: curl http://$SERVER_HOST:$PORT/health_check.php"
    echo "  - Stop server: kill \$(pgrep -f 'php.*-S.*$SERVER_HOST:$PORT')"
}

# Main execution
main() {
    print_info "=== SERVER DIAGNOSIS ==="
    print_info "Host: $SERVER_HOST"
    print_info "Port: $PORT"
    echo ""
    
    # Check dependencies
    check_php
    check_node
    check_curl
    echo ""
    
    # Check server status
    check_server_status
    echo ""
    
    # Test endpoints if server is running
    if check_port "$PORT"; then
        test_server_endpoints
        echo ""
    fi
    
    # Check logs and build output
    check_error_logs
    echo ""
    check_build_output
    echo ""
    check_config_files
    echo ""
    
    # Provide recommendations
    provide_recommendations
    
    echo ""
    print_info "=== DIAGNOSIS COMPLETE ==="
}

# Run main function
main "$@"
