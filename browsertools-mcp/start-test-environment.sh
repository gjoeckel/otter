#!/bin/bash
# BrowserTools MCP Test Environment Startup Script
# Usage: ./browsertools-mcp/start-test-environment.sh

# Default parameters
CONFIG_PATH="./browsertools-mcp/config.json"
LOG_PATH="./browsertools-mcp/logs/startup-$(date '+%Y%m%d-%H%M%S').log"
SKIP_MCP_SERVER=false

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --config)
            CONFIG_PATH="$2"
            shift 2
            ;;
        --log)
            LOG_PATH="$2"
            shift 2
            ;;
        --skip-mcp)
            SKIP_MCP_SERVER=true
            shift
            ;;
        *)
            echo "Unknown option $1"
            exit 1
            ;;
    esac
done

# Initialize logging
LOG_DIR=$(dirname "$LOG_PATH")
mkdir -p "$LOG_DIR"

log_message() {
    local message="$1"
    local level="${2:-INFO}"
    local timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    local log_entry="[$timestamp] [$level] $message"
    
    # Write to console with color
    case $level in
        "ERROR") echo -e "\033[31m$log_entry\033[0m" ;;
        "WARN")  echo -e "\033[33m$log_entry\033[0m" ;;
        "SUCCESS") echo -e "\033[32m$log_entry\033[0m" ;;
        "DEBUG") echo -e "\033[90m$log_entry\033[0m" ;;
        *) echo "$log_entry" ;;
    esac
    
    # Write to log file
    echo "$log_entry" >> "$LOG_PATH"
}

# Function to read configuration
read_configuration() {
    local path="$1"
    
    log_message "Reading configuration from: $path"
    
    if [ ! -f "$path" ]; then
        log_message "Configuration file not found: $path" "ERROR"
        exit 1
    fi
    
    # Check if jq is available for JSON parsing
    if command -v jq &> /dev/null; then
        log_message "Configuration loaded successfully" "SUCCESS"
        return 0
    else
        log_message "jq not found - JSON parsing may be limited" "WARN"
        return 0
    fi
}

# Function to stop all Chrome processes
stop_all_chrome_processes() {
    log_message "Stopping all Chrome processes..."
    
    # Find Chrome processes
    CHROME_PIDS=$(pgrep -f chrome 2>/dev/null || true)
    
    if [ -n "$CHROME_PIDS" ]; then
        local count=$(echo "$CHROME_PIDS" | wc -l)
        log_message "Found $count Chrome process(es). Terminating..." "WARN"
        
        # First try graceful shutdown
        echo "$CHROME_PIDS" | xargs kill -TERM 2>/dev/null || true
        sleep 2
        
        # Force kill any remaining
        REMAINING_PIDS=$(pgrep -f chrome 2>/dev/null || true)
        if [ -n "$REMAINING_PIDS" ]; then
            echo "$REMAINING_PIDS" | xargs kill -9 2>/dev/null || true
            sleep 2
        fi
        
        log_message "Chrome processes terminated" "SUCCESS"
    else
        log_message "No Chrome processes found"
    fi
}

# Function to find Chrome executable
find_chrome_executable() {
    local paths=("$@")
    
    log_message "Locating Chrome executable..."
    
    for path in "${paths[@]}"; do
        # Expand environment variables
        expanded_path=$(eval echo "$path")
        log_message "Checking: $expanded_path" "DEBUG"
        
        if [ -f "$expanded_path" ]; then
            log_message "Found Chrome at: $expanded_path" "SUCCESS"
            echo "$expanded_path"
            return 0
        fi
    done
    
    log_message "Chrome executable not found in any of the configured paths" "ERROR"
    exit 1
}

# Function to start Chrome with debug
start_chrome_with_debug() {
    local chrome_path="$1"
    local config_path="$2"
    
    log_message "Starting Chrome with remote debugging..."
    
    # Create clean temp directory
    local temp_dir="/tmp/chrome-debug-profile-$(date '+%Y%m%d%H%M%S')"
    rm -rf "$temp_dir"
    mkdir -p "$temp_dir"
    log_message "Created temp profile directory: $temp_dir" "DEBUG"
    
    # Build Chrome arguments (simplified version)
    local chrome_args=(
        "--remote-debugging-port=9222"
        "--user-data-dir=$temp_dir"
        "--no-first-run"
        "--no-default-browser-check"
        "--disable-default-apps"
        "--disable-popup-blocking"
        "http://localhost:8000/login.php"
    )
    
    log_message "Chrome arguments:" "DEBUG"
    for arg in "${chrome_args[@]}"; do
        log_message "  $arg" "DEBUG"
    done
    
    # Start Chrome
    "$chrome_path" "${chrome_args[@]}" &
    local chrome_pid=$!
    log_message "Chrome started with PID: $chrome_pid" "SUCCESS"
    
    echo "$chrome_pid:$temp_dir"
}

# Function to wait for Chrome debugger
wait_for_chrome_debugger() {
    local port="$1"
    local max_retries="${2:-30}"
    local retry_delay="${3:-1000}"
    
    log_message "Waiting for Chrome DevTools to be ready on port $port..."
    
    local retry_count=0
    local target_found=false
    
    while [ $retry_count -lt $max_retries ] && [ "$target_found" = false ]; do
        sleep 1
        
        if curl -s "http://localhost:$port/json/list" > /dev/null 2>&1; then
            local response=$(curl -s "http://localhost:$port/json/list")
            if echo "$response" | grep -q '"type":"page"' && ! echo "$response" | grep -q "chrome://"; then
                log_message "Chrome DevTools ready!" "SUCCESS"
                target_found=true
            else
                log_message "Waiting for page target... ($retry_count/$max_retries)" "DEBUG"
            fi
        else
            log_message "DevTools not ready yet... ($retry_count/$max_retries)" "DEBUG"
        fi
        
        retry_count=$((retry_count + 1))
    done
    
    if [ "$target_found" = false ]; then
        log_message "Chrome DevTools failed to become ready after $max_retries attempts" "ERROR"
        exit 1
    fi
}

# Function to start MCP server
start_mcp_server() {
    local working_directory="$1"
    
    log_message "Starting MCP server..."
    
    # Check if npm dependencies are installed
    local node_modules_path="$working_directory/node_modules"
    if [ ! -d "$node_modules_path" ]; then
        log_message "Installing npm dependencies..." "WARN"
        cd "$working_directory"
        npm install
        cd - > /dev/null
    fi
    
    # Start MCP server in background
    cd "$working_directory"
    npm start &
    local mcp_pid=$!
    cd - > /dev/null
    
    log_message "MCP server started with PID: $mcp_pid" "SUCCESS"
    
    # Give it time to initialize
    sleep 3
    
    # Verify it's running
    if curl -s "http://localhost:3001/health" > /dev/null 2>&1; then
        log_message "MCP server health check passed" "SUCCESS"
    else
        log_message "MCP server health check failed - server may still be starting" "WARN"
    fi
    
    echo "$mcp_pid"
}

# Main execution
main() {
    log_message "=== OTTER BROWSERTOOLS TEST ENVIRONMENT STARTUP ===" "SUCCESS"
    log_message "Script Version: 1.0.0"
    log_message "Bash Version: $BASH_VERSION"
    log_message "OS: $(uname -a)"
    
    # Load configuration
    read_configuration "$CONFIG_PATH"
    
    # Step 1: Clean environment
    log_message "[Step 1/5] Cleaning environment..." "SUCCESS"
    stop_all_chrome_processes
    
    # Step 2: Find Chrome (simplified - using common paths)
    log_message "[Step 2/5] Locating Chrome executable..." "SUCCESS"
    local chrome_paths=(
        "/usr/bin/google-chrome"
        "/usr/bin/chromium-browser"
        "/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
        "/c/Program Files/Google/Chrome/Application/chrome.exe"
        "/c/Program Files (x86)/Google/Chrome/Application/chrome.exe"
    )
    local chrome_path=$(find_chrome_executable "${chrome_paths[@]}")
    
    # Step 3: Start Chrome
    log_message "[Step 3/5] Starting Chrome with debugging..." "SUCCESS"
    local chrome_info=$(start_chrome_with_debug "$chrome_path" "$CONFIG_PATH")
    local chrome_pid=$(echo "$chrome_info" | cut -d: -f1)
    local temp_dir=$(echo "$chrome_info" | cut -d: -f2)
    
    # Step 4: Verify Chrome is ready
    log_message "[Step 4/5] Verifying Chrome DevTools..." "SUCCESS"
    wait_for_chrome_debugger 9222 30 1000
    
    # Step 5: Start MCP Server (unless skipped)
    local mcp_pid=""
    if [ "$SKIP_MCP_SERVER" = false ]; then
        log_message "[Step 5/5] Starting MCP server..." "SUCCESS"
        mcp_pid=$(start_mcp_server "$(dirname "$0")")
    else
        log_message "[Step 5/5] Skipping MCP server (as requested)" "WARN"
    fi
    
    # Summary
    log_message "=== ENVIRONMENT READY ===" "SUCCESS"
    log_message "Chrome PID: $chrome_pid"
    log_message "Chrome Debug Port: 9222"
    log_message "Chrome Temp Profile: $temp_dir"
    if [ -n "$mcp_pid" ]; then
        log_message "MCP Server PID: $mcp_pid"
        log_message "MCP Server Port: 3001"
    fi
    log_message "Target URL: http://localhost:8000/login.php"
    log_message "Log file: $LOG_PATH"
    
    # Keep script running if MCP server was started
    if [ -n "$mcp_pid" ]; then
        log_message "Press Ctrl+C to stop all services..." "WARN"
        wait $mcp_pid 2>/dev/null || true
    fi
}

# Cleanup function
cleanup() {
    log_message "Cleaning up..." "WARN"
    
    if [ -n "$chrome_pid" ] && kill -0 "$chrome_pid" 2>/dev/null; then
        log_message "Stopping Chrome..." "WARN"
        kill -9 "$chrome_pid" 2>/dev/null || true
    fi
    
    if [ -n "$mcp_pid" ] && kill -0 "$mcp_pid" 2>/dev/null; then
        log_message "Stopping MCP server..." "WARN"
        kill -9 "$mcp_pid" 2>/dev/null || true
    fi
    
    log_message "Cleanup complete" "SUCCESS"
}

# Set up signal handlers
trap cleanup EXIT INT TERM

# Run main function
main "$@"
