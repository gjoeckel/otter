#!/bin/bash
# Emergency reset when development environment is completely broken
# Nuclear option - use only when nothing else works

echo "⚠️  EMERGENCY ENVIRONMENT RESET"
echo "This will stop all processes and clear caches"
echo ""
read -p "Continue? (y/N) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled"
    exit 0
fi

echo ""
echo "=== Emergency Reset in Progress ==="

# 1. Kill all development processes
echo "[1/7] Stopping all processes..."
taskkill //F //IM php.exe 2>/dev/null && echo "      ✓ Stopped PHP"
taskkill //F //IM node.exe 2>/dev/null && echo "      ✓ Stopped Node.js"
taskkill //F //IM chrome.exe 2>/dev/null && echo "      ✓ Stopped Chrome"
pkill -f "mcp-server" 2>/dev/null && echo "      ✓ Stopped MCP servers"

sleep 2

# 2. Clear MCP cache and memory
echo "[2/7] Clearing MCP cache..."
rm -rf ~/.cursor/mcp-cache/* 2>/dev/null
rm -rf ~/.cursor/mcp-memory/* 2>/dev/null
rm -rf /tmp/mcp-* 2>/dev/null
echo "      ✓ MCP cache cleared"

# 3. Clear Node.js cache
echo "[3/7] Clearing Node.js cache..."
npm cache clean --force 2>/dev/null
echo "      ✓ Node cache cleared"

# 4. Clear npm/npx cache
echo "[4/7] Clearing npx cache..."
rm -rf ~/.npm/_npx 2>/dev/null
echo "      ✓ npx cache cleared"

# 5. Reset Chrome debug profile
echo "[5/7] Resetting Chrome debug profile..."
rm -rf "C:/temp/chrome-debug-mcp" 2>/dev/null
mkdir -p "C:/temp/chrome-debug-mcp"
echo "      ✓ Chrome profile reset"

# 6. Restart services
echo "[6/7] Restarting services..."
./scripts/start-chrome-debug.sh
./tests/start_server.sh > /dev/null 2>&1 &
sleep 3
echo "      ✓ Services restarted"

# 7. Verify environment
echo "[7/7] Verifying environment..."
./scripts/check-mcp-health.sh

echo ""
echo "==================================="
echo "✓ Emergency reset complete"
echo ""
echo "NEXT STEPS (IMPORTANT):"
echo "  1. Close Cursor IDE completely"
echo "  2. Restart Cursor IDE"
echo "  3. Open project in new window"
echo "  4. Open Git Bash terminal (Ctrl+\`)"
echo "  5. Run validation: ./scripts/validate-environment.sh"
echo "  6. Run tests: php tests/run_comprehensive_tests.php"
