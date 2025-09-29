#!/bin/bash
# Shared functions and environment variables for MCP scripts

# Environment variable defaults
readonly PROJECTS_DIR="${OTTER_PROJECTS_DIR:-$HOME/Projects}"
readonly MEMORY_DIR="${MCP_MEMORY_DIR:-$HOME/.cursor/mcp-memory}"
readonly MCP_CONFIG="${MCP_CONFIG:-$HOME/.cursor/mcp.json}"
readonly CHROME_PORT="${CHROME_DEBUG_PORT:-9222}"

# Colors (if terminal supports it)
if [ -t 1 ]; then
    readonly RED='\033[0;31m'
    readonly GREEN='\033[0;32m'
    readonly YELLOW='\033[1;33m'
    readonly NC='\033[0m'
else
    readonly RED='' GREEN='' YELLOW='' NC=''
fi

# Common functions
check_command() {
    command -v "$1" >/dev/null 2>&1
}

get_node_major_version() {
    node -v 2>/dev/null | sed 's/v//' | cut -d. -f1
}

check_port() {
    netstat -an | grep -q ":$1.*LISTEN"
}

print_success() {
    echo -e "      ${GREEN}✓${NC} $1"
}

print_error() {
    echo -e "      ${RED}✗${NC} $1"
}

print_warning() {
    echo -e "      ${YELLOW}⚠${NC} $1"
}

# Version checking functions (portable)
check_php_version() {
    local php_version=$(php -v 2>/dev/null | head -n 1 | sed -n 's/.*PHP \([0-9]*\)\..*/\1/p')
    if [ -n "$php_version" ] && [ "$php_version" -ge 8 ]; then
        print_success "PHP $php_version (>= 8.0)"
        return 0
    else
        print_error "PHP version check failed (requires >= 8.0)"
        return 1
    fi
}

check_node_version() {
    local node_major=$(get_node_major_version)
    if [ -n "$node_major" ] && [ "$node_major" -ge 18 ]; then
        print_success "Node.js v$node_major (>= 18)"
        return 0
    else
        print_warning "Node.js v$node_major (recommend >= 18)"
        return 1
    fi
}

# Chrome connectivity test
test_chrome_connectivity() {
    if check_port "$CHROME_PORT"; then
        if curl -s "http://localhost:$CHROME_PORT/json/version" >/dev/null 2>&1; then
            print_success "Chrome DevTools responding on port $CHROME_PORT"
            return 0
        else
            print_warning "Port $CHROME_PORT open but Chrome not responding"
            return 1
        fi
    else
        print_error "Port $CHROME_PORT not listening"
        return 1
    fi
}

# MCP tool availability check
check_mcp_tool_availability() {
    local tool="$1"
    case "$tool" in
        "chrome")
            test_chrome_connectivity
            ;;
        "filesystem")
            [ -d "$PROJECTS_DIR" ] && print_success "Filesystem MCP directory accessible" || print_error "Filesystem MCP directory not found"
            ;;
        "memory")
            [ -d "$MEMORY_DIR" ] && print_success "Memory MCP directory accessible" || print_error "Memory MCP directory not found"
            ;;
        "git")
            check_command git && print_success "Git MCP available" || print_error "Git MCP not available"
            ;;
        *)
            print_error "Unknown MCP tool: $tool"
            return 1
            ;;
    esac
}

# Performance measurement
measure_response_time() {
    local url="$1"
    local max_time="${2:-5000}"

    local start_time=$(date +%s%3N)
    if curl -s "$url" >/dev/null 2>&1; then
        local response_time=$(($(date +%s%3N) - start_time))
        if [ "$response_time" -le "$max_time" ]; then
            print_success "Response time: ${response_time}ms (target: ≤${max_time}ms)"
            return 0
        else
            print_error "Response time: ${response_time}ms (target: ≤${max_time}ms) - TOO SLOW"
            return 1
        fi
    else
        print_error "Request failed"
        return 1
    fi
}

# Memory usage check
check_memory_usage() {
    local max_memory="${1:-100}"
    local node_processes=$(ps aux | grep -c "node.*mcp" 2>/dev/null || echo "0")

    if [ "$node_processes" -gt 0 ]; then
        local memory_usage=$(ps aux | grep "node.*mcp" | awk '{sum+=$6} END {print sum+0}' 2>/dev/null)
        local memory_mb=$((memory_usage / 1024))

        if [ "$memory_mb" -le "$max_memory" ]; then
            print_success "MCP servers memory usage: ${memory_mb}MB (target: ≤${max_memory}MB)"
            return 0
        else
            print_warning "MCP servers memory usage: ${memory_mb}MB (target: ≤${max_memory}MB) - HIGH"
            return 1
        fi
    else
        print_warning "No MCP server processes detected"
        return 1
    fi
}

# Cursor IDE integration check
check_cursor_integration() {
    if [ -f "$MCP_CONFIG" ]; then
        print_success "MCP configuration file exists"

        # Validate MCP JSON structure
        if node -e "JSON.parse(require('fs').readFileSync('$MCP_CONFIG', 'utf8'))" 2>/dev/null; then
            print_success "MCP configuration is valid JSON"
        else
            print_error "MCP configuration contains invalid JSON"
            return 1
        fi

        # Check if Cursor IDE is running
        if pgrep -f "cursor" >/dev/null 2>&1; then
            print_success "Cursor IDE is running"
            echo "        Manual verification required: Check MCP tools are available in Cursor AI interface"
        else
            print_warning "Cursor IDE not detected - MCP tools may not be available"
            echo "        Fix: Start Cursor IDE and restart MCP servers"
        fi
    else
        print_error "MCP configuration file not found"
        echo "        Fix: Create $MCP_CONFIG from mcp.json.example"
        return 1
    fi
}

# Enterprise configuration check
check_enterprise_configs() {
    local config_dir="${PROJECTS_DIR}/otter/config"
    local enterprises=("csu" "ccc" "demo")
    local all_valid=true

    for enterprise in "${enterprises[@]}"; do
        local config_file="${config_dir}/${enterprise}.config"
        if [ -f "$config_file" ]; then
            if node -e "JSON.parse(require('fs').readFileSync('$config_file', 'utf8'))" 2>/dev/null; then
                print_success "$enterprise configuration valid"
            else
                print_error "$enterprise configuration invalid JSON"
                all_valid=false
            fi
        else
            print_error "$enterprise configuration file missing"
            all_valid=false
        fi
    done

    if [ "$all_valid" = true ]; then
        return 0
    else
        return 1
    fi
}
