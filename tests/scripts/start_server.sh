#!/bin/bash
# Enhanced PHP Server Startup Script for Testing
# Usage: ./tests/start_server.sh

# Default parameters
PORT=8000
SERVER_HOST="localhost"
VERBOSE=false

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

# Function to stop existing PHP processes
stop_existing_php() {
    print_info "Checking for existing PHP processes on port $PORT..."
    
    if check_port "$PORT"; then
        print_warning "Port $PORT is already in use. Stopping existing processes..."
        
        # Try to find and kill PHP processes
        local php_pids=$(pgrep -f "php.*-S.*$SERVER_HOST:$PORT" 2>/dev/null || true)
        if [ -n "$php_pids" ]; then
            echo "$php_pids" | xargs kill -9 2>/dev/null || true
            print_success "Stopped existing PHP processes"
        else
            # Try to find process using the port
            if command -v lsof &> /dev/null; then
                local pid=$(lsof -ti:$PORT 2>/dev/null || true)
                if [ -n "$pid" ]; then
                    kill -9 "$pid" 2>/dev/null || true
                    print_success "Stopped process using port $PORT"
                fi
            fi
        fi
        sleep 2
    else
        print_success "Port $PORT is available"
    fi
}

# Function to create error log
create_error_log() {
    if [ ! -f "php_errors.log" ]; then
        touch "php_errors.log"
        print_success "Created php_errors.log file"
    else
        print_info "Using existing php_errors.log file"
    fi
}

# Function to start PHP server
start_php_server() {
    print_info "Starting PHP Development Server..."
    
    # Enhanced PHP server startup with better error reporting
    local php_args=(
        "-S" "$SERVER_HOST:$PORT"
        "-d" "error_reporting=E_ALL"
        "-d" "log_errors=1"
        "-d" "error_log=php_errors.log"
        "-d" "display_errors=1"
        "-d" "display_startup_errors=1"
    )
    
    if [ "$VERBOSE" = true ]; then
        php_args+=("-d" "display_errors=1")
        print_info "Starting with verbose logging..."
    fi
    
    print_info "Server will be available at: http://$SERVER_HOST:$PORT"
    print_info "Health check available at: http://$SERVER_HOST:$PORT/health_check.php"
    print_info "Error log: php_errors.log"
    print_info "Press Ctrl+C to stop the server"
    echo ""
    
    # Start the server
    php "${php_args[@]}"
}

# Function to test server
test_server() {
    local url="http://$SERVER_HOST:$PORT/health_check.php"
    
    print_info "Testing server health..."
    
    if curl -s -f "$url" > /dev/null 2>&1; then
        print_success "Server is responding at $url"
    else
        print_warning "Server health check failed at $url"
    fi
}

# Cleanup function
cleanup() {
    print_info "Stopping PHP server..."
    # The server will be stopped by the signal handler
    print_success "Server stopped"
}

# Set up signal handlers
trap cleanup EXIT INT TERM

# Main execution
main() {
    print_info "=== PHP DEVELOPMENT SERVER STARTUP ==="
    print_info "Host: $SERVER_HOST"
    print_info "Port: $PORT"
    print_info "Verbose: $VERBOSE"
    echo ""
    
    # Step 1: Stop existing processes
    stop_existing_php
    
    # Step 2: Create error log
    create_error_log
    
    # Step 3: Start server
    start_php_server
}

# Run main function
main "$@"
