#!/bin/bash
# Verify MCP servers are functioning correctly
# Windows 11 diagnostic tool

echo "=== MCP Server Health Check ==="
echo ""

ISSUES_FOUND=0

# 1. Check Chrome remote debugging
echo "[1/6] Chrome DevTools MCP..."
if netstat -an | grep -q ":9222.*LISTEN"; then
    echo "      ✓ Port 9222 active (Chrome DevTools MCP running)"
else
    echo "      ✗ Port 9222 not listening"
    echo "        Fix: ./scripts/start-chrome-debug.sh"
    ((ISSUES_FOUND++))
fi

# 2. Check Node.js for npx-based servers
echo "[2/6] Node.js (required for MCP servers)..."
if command -v node >/dev/null 2>&1; then
    NODE_VERSION=$(node --version)
    echo "      ✓ Node.js available: $NODE_VERSION"

    # Check if version is adequate (>= 18)
    MAJOR_VERSION=$(echo $NODE_VERSION | grep -oP '\d+' | head -n 1)
    if [ "$MAJOR_VERSION" -lt 18 ]; then
        echo "      ⚠ Node.js $NODE_VERSION detected (recommend >= 18)"
    fi
else
    echo "      ✗ Node.js not found (required for npx MCP servers)"
    echo "        Fix: Install Node.js from https://nodejs.org"
    ((ISSUES_FOUND++))
fi

# 3. Check Python/uvx for git MCP
echo "[3/6] Git MCP (uvx)..."
if command -v uvx >/dev/null 2>&1; then
    echo "      ✓ uvx available for Git MCP"
elif command -v python >/dev/null 2>&1; then
    echo "      ⚠ uvx not found (Git MCP may not work)"
    echo "        Fix: pip install uvx"
    ((ISSUES_FOUND++))
else
    echo "      ✗ Python not found (required for uvx/Git MCP)"
    echo "        Fix: Install Python 3.8+ from python.org"
    ((ISSUES_FOUND++))
fi

# 4. Check filesystem access
echo "[4/6] Filesystem MCP..."
if [ -d "C:/Users/George/Projects" ]; then
    echo "      ✓ Project directory accessible"
else
    echo "      ✗ Project directory not found: C:/Users/George/Projects"
    ((ISSUES_FOUND++))
fi

# 5. Check memory persistence
echo "[5/6] Memory MCP..."
MEMORY_DIR="C:/Users/George/.cursor/mcp-memory"
if [ -d "$MEMORY_DIR" ]; then
    echo "      ✓ Persistence directory exists"
    FILE_COUNT=$(find "$MEMORY_DIR" -type f 2>/dev/null | wc -l)
    echo "        Memory files: $FILE_COUNT"
else
    echo "      ⚠ Persistence directory missing"
    echo "        Creating: $MEMORY_DIR"
    mkdir -p "$MEMORY_DIR"
fi

# 6. Check shell environment
echo "[6/6] Shell Environment..."
if [[ "$SHELL" == *"bash"* ]] || [[ "$BASH_VERSION" ]]; then
    echo "      ✓ Git Bash active ($BASH_VERSION)"
else
    echo "      ✗ Not running in Git Bash"
    echo "        Current shell: $SHELL"
    echo "        Fix: Switch to Git Bash terminal"
    ((ISSUES_FOUND++))
fi

echo ""
echo "==================================="
if [ $ISSUES_FOUND -eq 0 ]; then
    echo "✓ All MCP servers healthy"
else
    echo "✗ Found $ISSUES_FOUND issue(s) - see fixes above"
    echo ""
    echo "After fixing issues:"
    echo "  1. Restart Cursor IDE"
    echo "  2. Re-run: ./scripts/check-mcp-health.sh"
fi
