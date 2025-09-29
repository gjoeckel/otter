#!/bin/bash
# Start Chrome with remote debugging for MCP integration
# Windows 11 optimized version

CHROME_PATH="/c/Program Files/Google/Chrome/Application/chrome.exe"
USER_DATA_DIR="C:/temp/chrome-debug-mcp"
REMOTE_PORT=9222

echo "Starting Chrome with remote debugging for MCP..."

# Check if Chrome is already running with debugging
if netstat -an | grep -q ":$REMOTE_PORT.*LISTEN"; then
    echo "✓ Chrome already running with remote debugging on port $REMOTE_PORT"
    exit 0
fi

# Create user data directory if it doesn't exist
mkdir -p "$USER_DATA_DIR"

# Check if Chrome executable exists
if [ ! -f "$CHROME_PATH" ]; then
    echo "✗ Chrome not found at: $CHROME_PATH"
    echo "  Please install Chrome or update CHROME_PATH in this script"
    exit 1
fi

# Start Chrome with debugging flags
"$CHROME_PATH" \
    --remote-debugging-port=$REMOTE_PORT \
    --user-data-dir="$USER_DATA_DIR" \
    --no-first-run \
    --no-default-browser-check \
    --disable-background-networking \
    --disable-sync \
    --disable-extensions \
    --disable-default-apps \
    > /dev/null 2>&1 &

# Wait for Chrome to start
sleep 3

# Verify Chrome started successfully
if netstat -an | grep -q ":$REMOTE_PORT.*LISTEN"; then
    echo "✓ Chrome started successfully with remote debugging"
    echo "  Port: $REMOTE_PORT"
    echo "  Data dir: $USER_DATA_DIR"
    echo "  Access DevTools: http://localhost:$REMOTE_PORT"
else
    echo "✗ Chrome failed to start with remote debugging"
    echo "  Check if port $REMOTE_PORT is already in use"
    exit 1
fi
