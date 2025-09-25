# 🚀 Otter BrowserTools MCP Setup Instructions

## Overview

This setup provides direct Chrome DevTools integration for Cursor AI agents through the BrowserTools MCP (Model Context Protocol). Your AI agent will have "browser superpowers" including real-time console monitoring, network analysis, screenshot capture, and interactive debugging.

## 📋 Prerequisites

- **Node.js** (v16 or higher)
- **Chrome Browser** (latest version)
- **Cursor IDE** (latest version)
- **Otter Application** running on `http://localhost:8000` (see [Starting Otter](#starting-otter) below)

## 🎯 Quick Start (Recommended)

The easiest way to get started is using our integrated startup script:

```bash
# From the project root directory
./browsertools-mcp/start-test-environment.sh
```

This single command will:
1. Kill any existing Chrome processes
2. Start Chrome with remote debugging enabled
3. Verify Chrome is ready
4. Install npm dependencies (if needed)
5. Start the MCP server
6. Provide comprehensive logging

**That's it!** Skip to [Step 4: Configure Cursor](#step-4-configure-cursor-mcp-settings) if using this method.

## 🔧 Manual Setup (Alternative)

If you prefer manual control or the automated script fails, follow these steps:

### Step 1: Starting Otter

Before using the browser tools, ensure the Otter application is running:

```bash
# From the project root
php -S localhost:8000 &
```

Or use the local testing script if available:
```bash
./tests/start_server.sh
```

Verify by opening `http://localhost:8000` in your browser.

### Step 2: Install Dependencies

```bash
# Navigate to the browsertools-mcp directory
cd browsertools-mcp

# Install dependencies
npm install
```

### Step 3: Start Chrome with Remote Debugging

**Windows (Git Bash) - Recommended:**
```bash
# Use the robust startup script
./browsertools-mcp/start-chrome-debug-robust.sh
```

**Manual Chrome Start (if script fails):**
```bash
# Close all Chrome instances first
pkill -f chrome 2>/dev/null || true

# Create temp directory
TEMP_DIR="/tmp/chrome-debug-$(date '+%Y%m%d%H%M%S')"
mkdir -p "$TEMP_DIR"

# Start Chrome with all required flags
google-chrome \
  --remote-debugging-port=9222 \
  --user-data-dir="$TEMP_DIR" \
  --disable-web-security \
  --disable-features=VizDisplayCompositor \
  --no-first-run \
  --no-default-browser-check \
  http://localhost:8000/login.php &
```

### Step 4: Start the MCP Server

**Using Puppeteer-based server (Recommended):**
```bash
# In the browsertools-mcp directory
npm start
```

**Using legacy server (if needed):**
```bash
npm run start:legacy
```

You should see:
```
🌐 Otter BrowserTools MCP Server running on http://localhost:3001
🚀 Otter BrowserTools MCP Server (Puppeteer) started
✅ Connected to Chrome DevTools Protocol
```

## 📝 Step 4: Configure Cursor MCP Settings

1. Open Cursor IDE
2. Go to **Settings** → **MCP**
3. Add the following configuration:

```json
{
  "mcpServers": {
    "otter-browsertools": {
      "command": "node",
      "args": ["server-simple.js"],
      "cwd": "<path-to-your-project>\\browsertools-mcp"
    }
  }
}
```

**Important:** Replace `<path-to-your-project>` with your actual project path, for example:
- `C:\\Users\\YourName\\Projects\\otter`
- `D:\\Development\\otter`

### Step 5: Restart Cursor

Restart Cursor IDE to load the MCP configuration.

## 🧪 Testing the Integration

### Test 1: Verify System Status

**Bash:**
```bash
# Check Chrome DevTools
curl -s http://localhost:9222/json/list | head -5

# Check MCP Server
curl -s http://localhost:3001/health
```

### Test 2: Available MCP Tools

Once connected, your AI agent has access to these tools:

| Tool | Purpose | Example Usage |
|------|---------|---------------|
| `get_console_logs` | Retrieve browser console output | Debug JavaScript errors |
| `get_network_activity` | Monitor HTTP requests/responses | Analyze API performance |
| `get_cookies` | Inspect browser cookies | Debug authentication |
| `inspect_session` | Check PHP session state | Troubleshoot login issues |
| `navigate_to` | Navigate to URLs | Test page flows |
| `click_element` | Click page elements | Automate interactions |
| `type_text` | Type in input fields | Fill forms |
| `take_screenshot` | Capture page state | Visual debugging |
| `execute_js` | Run JavaScript code | Interactive debugging |
| `get_page_info` | Get page metadata | Analyze current state |

### Test 3: Quick Functionality Check

Ask your AI agent to:
1. "Use `get_page_info` to check the current page"
2. "Use `inspect_session` to verify authentication"
3. "Use `get_console_logs` to check for errors"

## ✅ Success Indicators

You'll know everything is working when:

- ✅ Chrome starts with "Chrome is being controlled by automated test software" banner
- ✅ MCP server shows "Connected to Chrome DevTools Protocol"
- ✅ Health check returns `{"status": "healthy", "browserConnected": true}`
- ✅ AI agent can execute browser commands successfully
- ✅ No errors in `browsertools-mcp/logs/` directory

## 🚨 Troubleshooting

### Chrome Won't Start
```bash
# Check if port is in use
netstat -an | grep :9222 || ss -an | grep :9222

# Kill all Chrome processes
pkill -f chrome

# Try the automated script
./browsertools-mcp/start-test-environment.sh
```

### MCP Server Connection Failed
```bash
# Check recent errors
tail -20 browsertools-mcp/logs/mcp-error-*.log

# Verify Chrome is running with debugging
curl -s http://localhost:9222/json/list
```

### Cursor Can't Find Tools
1. Verify MCP configuration path is correct
2. Ensure server is running (`npm start`)
3. Restart Cursor after configuration changes
4. Check Cursor logs for MCP errors

### Session/Authentication Issues
Use the `inspect_session` tool to debug:
- Check if PHPSESSID cookie exists
- Verify cookie domain and path
- Monitor network activity for redirects

## 📚 Next Steps

- Review [`BEST_PRACTICES.md`](./BEST_PRACTICES.md) for advanced usage
- Check [`WINDOWS_11_CHROME_136_ISSUES.md`](./WINDOWS_11_CHROME_136_ISSUES.md) for known issues
- Explore [`TESTING_GUIDE.md`](./TESTING_GUIDE.md) for debugging workflows

## 🎯 Daily Workflow

For daily use, simply run:
```bash
./browsertools-mcp/start-test-environment.sh
```

This handles everything automatically and ensures a clean, consistent environment every time.
