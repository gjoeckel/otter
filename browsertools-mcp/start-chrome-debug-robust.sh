#!/bin/bash
# Robust Chrome Debug Startup Script
# Usage: ./browsertools-mcp/start-chrome-debug-robust.sh

# Default parameters
DEBUG_PORT=9222
TEMP_DIR="/tmp/chrome-debug-$(date '+%Y%m%d%H%M%S')"
TARGET_URL="http://localhost:8000/login.php"
CHROME_PATH=""

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --debug-port)
            DEBUG_PORT="$2"
            shift 2
            ;;
        --temp-dir)
            TEMP_DIR="$2"
            shift 2
            ;;
        --target-url)
            TARGET_URL="$2"
            shift 2
            ;;
        --chrome-path)
            CHROME_PATH="$2"
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

# Function to find Chrome executable
find_chrome() {
    if [ -n "$CHROME_PATH" ] && [ -f "$CHROME_PATH" ]; then
        echo "$CHROME_PATH"
        return 0
    fi
    
    local chrome_paths=(
        "/usr/bin/google-chrome"
        "/usr/bin/chromium-browser"
        "/usr/bin/chromium"
        "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
        "/c/Program Files/Google/Chrome/Application/chrome.exe"
        "/c/Program Files (x86)/Google/Chrome/Application/chrome.exe"
        "/mnt/c/Program Files/Google/Chrome/Application/chrome.exe"
        "/mnt/c/Program Files (x86)/Google/Chrome/Application/chrome.exe"
    )
    
    for path in "${chrome_paths[@]}"; do
        if [ -f "$path" ]; then
            echo "$path"
            return 0
        fi
    done
    
    return 1
}

# Function to stop existing Chrome processes
stop_existing_chrome() {
    print_info "Stopping existing Chrome processes..."
    
    local chrome_pids=$(pgrep -f chrome 2>/dev/null || true)
    if [ -n "$chrome_pids" ]; then
        local count=$(echo "$chrome_pids" | wc -l)
        print_warning "Found $count Chrome process(es). Terminating..."
        
        # Try graceful shutdown first
        echo "$chrome_pids" | xargs kill -TERM 2>/dev/null || true
        sleep 3
        
        # Force kill any remaining
        local remaining_pids=$(pgrep -f chrome 2>/dev/null || true)
        if [ -n "$remaining_pids" ]; then
            echo "$remaining_pids" | xargs kill -9 2>/dev/null || true
            sleep 2
        fi
        
        print_success "Chrome processes terminated"
    else
        print_info "No existing Chrome processes found"
    fi
}

# Function to check if port is available
check_port() {
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

# Function to free up port
free_port() {
    local port="$1"
    
    print_warning "Port $port is in use, attempting to free it..."
    
    if command -v lsof &> /dev/null; then
        local pid=$(lsof -ti:$port 2>/dev/null || true)
        if [ -n "$pid" ]; then
            kill -9 "$pid" 2>/dev/null || true
            sleep 2
        fi
    elif command -v fuser &> /dev/null; then
        fuser -k $port/tcp 2>/dev/null || true
        sleep 2
    fi
}

# Function to create temp directory
create_temp_dir() {
    print_info "Creating temporary Chrome profile directory..."
    
    if [ -d "$TEMP_DIR" ]; then
        rm -rf "$TEMP_DIR"
    fi
    
    mkdir -p "$TEMP_DIR"
    print_success "Created temp directory: $TEMP_DIR"
}

# Function to start Chrome with debugging
start_chrome_debug() {
    local chrome_path="$1"
    
    print_info "Starting Chrome with remote debugging..."
    
    # Check if port is available
    if ! check_port "$DEBUG_PORT"; then
        free_port "$DEBUG_PORT"
    fi
    
    # Chrome arguments for debugging
    local chrome_args=(
        "--remote-debugging-port=$DEBUG_PORT"
        "--user-data-dir=$TEMP_DIR"
        "--no-first-run"
        "--no-default-browser-check"
        "--disable-default-apps"
        "--disable-popup-blocking"
        "--disable-extensions"
        "--disable-plugins"
        "--disable-images"
        "--disable-javascript"
        "--disable-web-security"
        "--allow-running-insecure-content"
        "--disable-features=VizDisplayCompositor"
        "$TARGET_URL"
    )
    
    print_info "Chrome arguments:"
    for arg in "${chrome_args[@]}"; do
        echo "  $arg"
    done
    
    # Start Chrome
    "$chrome_path" "${chrome_args[@]}" &
    local chrome_pid=$!
    
    print_success "Chrome started with PID: $chrome_pid"
    echo "$chrome_pid"
}

# Function to wait for Chrome DevTools
wait_for_devtools() {
    local port="$1"
    local max_retries=30
    local retry_count=0
    
    print_info "Waiting for Chrome DevTools to be ready on port $port..."
    
    while [ $retry_count -lt $max_retries ]; do
        if curl -s "http://localhost:$port/json/list" > /dev/null 2>&1; then
            local response=$(curl -s "http://localhost:$port/json/list")
            if echo "$response" | grep -q '"type":"page"' && ! echo "$response" | grep -q "chrome://"; then
                print_success "Chrome DevTools ready!"
                return 0
            fi
        fi
        
        retry_count=$((retry_count + 1))
        echo -n "."
        sleep 1
    done
    
    echo ""
    print_error "Chrome DevTools failed to become ready after $max_retries attempts"
    return 1
}

# Function to test DevTools connection
test_devtools() {
    local port="$1"
    
    print_info "Testing DevTools connection..."
    
    if curl -s "http://localhost:$port/json/list" | grep -q '"type":"page"'; then
        print_success "DevTools connection successful"
        
        # Get target information
        local targets=$(curl -s "http://localhost:$port/json/list")
        echo "Available targets:"
        echo "$targets" | grep -o '"title":"[^"]*"' | sed 's/"title":"//g' | sed 's/"//g' | while read -r title; do
            echo "  - $title"
        done
    else
        print_error "DevTools connection failed"
        return 1
    fi
}

# Cleanup function
cleanup() {
    print_info "Cleaning up..."
    
    if [ -n "$CHROME_PID" ] && kill -0 "$CHROME_PID" 2>/dev/null; then
        print_info "Stopping Chrome (PID: $CHROME_PID)..."
        kill -TERM "$CHROME_PID" 2>/dev/null || true
        sleep 2
        kill -9 "$CHROME_PID" 2>/dev/null || true
    fi
    
    if [ -d "$TEMP_DIR" ]; then
        print_info "Removing temporary directory: $TEMP_DIR"
        rm -rf "$TEMP_DIR"
    fi
    
    print_success "Cleanup complete"
}

# Set up signal handlers
trap cleanup EXIT INT TERM

# Main execution
main() {
    print_info "=== CHROME DEBUG STARTUP ==="
    print_info "Debug Port: $DEBUG_PORT"
    print_info "Temp Directory: $TEMP_DIR"
    print_info "Target URL: $TARGET_URL"
    print_info "Chrome Path: $CHROME_PATH"
    echo ""
    
    # Step 1: Find Chrome
    print_info "Step 1: Finding Chrome executable..."
    CHROME_PATH=$(find_chrome)
    if [ -z "$CHROME_PATH" ]; then
        print_error "Chrome executable not found"
        print_info "Please install Chrome or specify path with --chrome-path"
        exit 1
    fi
    print_success "Found Chrome: $CHROME_PATH"
    
    # Step 2: Stop existing Chrome
    print_info "Step 2: Stopping existing Chrome processes..."
    stop_existing_chrome
    
    # Step 3: Create temp directory
    print_info "Step 3: Setting up temporary directory..."
    create_temp_dir
    
    # Step 4: Start Chrome
    print_info "Step 4: Starting Chrome with debugging..."
    CHROME_PID=$(start_chrome_debug "$CHROME_PATH")
    
    # Step 5: Wait for DevTools
    print_info "Step 5: Waiting for DevTools..."
    if ! wait_for_devtools "$DEBUG_PORT"; then
        exit 1
    fi
    
    # Step 6: Test connection
    print_info "Step 6: Testing DevTools connection..."
    if ! test_devtools "$DEBUG_PORT"; then
        exit 1
    fi
    
    # Success
    echo ""
    print_success "=== CHROME DEBUG ENVIRONMENT READY ==="
    print_info "Chrome PID: $CHROME_PID"
    print_info "Debug Port: $DEBUG_PORT"
    print_info "DevTools URL: http://localhost:$DEBUG_PORT"
    print_info "Target URL: $TARGET_URL"
    print_info "Temp Directory: $TEMP_DIR"
    echo ""
    print_info "Press Ctrl+C to stop Chrome and cleanup"
    
    # Wait for user interrupt
    wait $CHROME_PID 2>/dev/null || true
}

# Run main function
main "$@"
