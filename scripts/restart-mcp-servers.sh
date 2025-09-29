#!/bin/bash
# Restart MCP servers without restarting entire Cursor IDE
# Useful when MCP servers hang or become unresponsive

echo "=== Restarting MCP Servers ==="
echo ""

# 1. Stop all MCP-related processes
echo "[1/4] Stopping MCP processes..."
pkill -f "chrome-devtools-mcp" 2>/dev/null && echo "      ✓ Killed chrome-devtools-mcp"
pkill -f "server-filesystem" 2>/dev/null && echo "      ✓ Killed filesystem MCP"
pkill -f "server-memory" 2>/dev/null && echo "      ✓ Killed memory MCP"
pkill -f "mcp-server-git" 2>/dev/null && echo "      ✓ Killed git MCP"

echo "      Waiting for cleanup..."
sleep 2

# 2. Clear MCP cache (optional but recommended)
echo "[2/4] Clearing MCP cache..."
if [ -d "$HOME/.cursor/mcp-cache" ]; then
    rm -rf "$HOME/.cursor/mcp-cache"/* 2>/dev/null
    echo "      ✓ Cache cleared"
else
    echo "      ✓ No cache to clear"
fi

# 3. Restart Chrome with debugging
echo "[3/4] Restarting Chrome debugging..."
./scripts/start-chrome-debug.sh

# 4. Verify MCP health
echo "[4/4] Verifying MCP health..."
sleep 2
./scripts/check-mcp-health.sh

echo ""
echo "==================================="
echo "MCP server restart complete"
echo ""
echo "Next steps:"
echo "  1. Reload Cursor window: Ctrl+Shift+P → 'Developer: Reload Window'"
echo "  2. Or restart Cursor terminal: Ctrl+\` → close → reopen"
echo "  3. Verify MCP tools work: Try using Chrome MCP in chat"
