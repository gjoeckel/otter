#!/bin/bash
# Verify MCP servers are functioning correctly
# Windows 11 diagnostic tool

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

echo "=== MCP Server Health Check ==="
echo ""

CRITICAL_ERRORS=0
WARNINGS=0

# 1. Check Chrome remote debugging
echo "[1/7] Chrome DevTools MCP..."
if test_chrome_connectivity; then
    # Success
else
    echo "        Fix: ./scripts/start-chrome-debug.sh"
    ((CRITICAL_ERRORS++))
fi

# 2. Check Node.js for npx-based servers
echo "[2/7] Node.js (required for MCP servers)..."
if check_node_version; then
    # Success
else
    ((CRITICAL_ERRORS++))
fi

# 3. Check Python/uvx for git MCP
echo "[3/7] Git MCP (uvx)..."
if check_command uvx; then
    print_success "uvx available for Git MCP"
elif check_command python; then
    print_warning "uvx not found (Git MCP may not work)"
    if check_command pip; then
        echo "        Fix: pip install uvx"
    elif check_command pip3; then
        echo "        Fix: pip3 install uvx"
    else
        echo "        Fix: Install Python pip first, then: pip install uvx"
    fi
    ((WARNINGS++))
else
    print_error "Python not found (required for uvx/Git MCP)"
    echo "        Fix: Install Python 3.8+ from python.org"
    ((CRITICAL_ERRORS++))
fi

# 4. Check filesystem access
echo "[4/7] Filesystem MCP..."
if [ -d "$PROJECTS_DIR" ]; then
    print_success "Project directory accessible: $PROJECTS_DIR"
else
    print_error "Project directory not found: $PROJECTS_DIR"
    echo "        Override with: export OTTER_PROJECTS_DIR=/your/path"
    ((CRITICAL_ERRORS++))
fi

# 5. Check memory persistence
echo "[5/7] Memory MCP..."
if [ -d "$MEMORY_DIR" ]; then
    print_success "Persistence directory exists"
    FILE_COUNT=$(find "$MEMORY_DIR" -type f 2>/dev/null | wc -l)
    echo "        Memory files: $FILE_COUNT"
else
    print_warning "Persistence directory missing"
    echo "        Creating: $MEMORY_DIR"
    mkdir -p "$MEMORY_DIR"
    ((WARNINGS++))
fi

# 6. Check shell environment
echo "[6/7] Shell Environment..."
if [[ "$SHELL" == *"bash"* ]] || [[ "$BASH_VERSION" ]]; then
    print_success "Git Bash active ($BASH_VERSION)"
else
    print_error "Not running in Git Bash"
    echo "        Current shell: $SHELL"
    echo "        Fix: Switch to Git Bash terminal"
    ((CRITICAL_ERRORS++))
fi

# 7. Cursor IDE MCP Integration
echo "[7/7] Cursor IDE MCP Integration..."
echo "      Please ensure Cursor IDE is running with MCP configuration loaded"
echo "      Testing MCP tool availability within Cursor..."

if check_cursor_integration; then
    # Success
else
    ((CRITICAL_ERRORS++))
fi

# Validate Chrome MCP connectivity within Cursor context
echo "      Testing Chrome MCP connectivity for Cursor integration..."
if curl -s "http://localhost:$CHROME_PORT/json/version" >/dev/null 2>&1; then
    chrome_version=$(curl -s "http://localhost:$CHROME_PORT/json/version" | grep -o '"Browser":"[^"]*"' | cut -d'"' -f4)
    print_success "Chrome MCP ready for Cursor integration (Version: $chrome_version)"
else
    print_error "Chrome MCP not responding - Cursor integration will fail"
    echo "        Fix: ./scripts/start-chrome-debug.sh"
    ((CRITICAL_ERRORS++))
fi

echo ""
echo "==================================="
if [ $CRITICAL_ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo "✓ All MCP servers healthy"
    exit 0
elif [ $CRITICAL_ERRORS -eq 0 ]; then
    echo "⚠ MCP servers healthy with $WARNINGS warning(s)"
    exit 0
else
    echo "✗ Found $CRITICAL_ERRORS critical error(s), $WARNINGS warning(s)"
    echo ""
    echo "After fixing issues:"
    echo "  1. Restart Cursor IDE"
    echo "  2. Re-run: ./scripts/check-mcp-health.sh"
    exit 1
fi
