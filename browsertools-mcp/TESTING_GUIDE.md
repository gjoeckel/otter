# BrowserTools MCP Testing Guide

This guide provides step-by-step instructions for testing the Otter application with the improved BrowserTools MCP integration.

## Prerequisites

1. **Otter Server Running**: Ensure the PHP server is running on `http://localhost:8000`
2. **Node.js Dependencies**: Run `npm install` in the `browsertools-mcp` directory
3. **Chrome Installed**: Chrome must be installed in a standard location

## Step-by-Step Testing Process

### Step 1: Start Chrome with Robust Launcher

```powershell
# From the project root
cd browsertools-mcp
.\start-chrome-debug-robust.ps1
```

This script will:
- Kill all existing Chrome processes
- Launch a clean Chrome instance with debugging enabled
- Open directly to `http://localhost:8000/login.php`
- Verify the debug connection is ready

**Expected Output:**
```
=== ROBUST CHROME DEBUG LAUNCHER ===
Target URL: http://localhost:8000/login.php
Debug Port: 9222

[1/3] Terminating existing Chrome processes...
Found 8 Chrome processes. Terminating...
Chrome processes terminated.

[2/3] Locating Chrome executable...
Found Chrome at: C:\Program Files\Google\Chrome\Application\chrome.exe

[3/3] Launching Chrome with remote debugging...
Starting Chrome with arguments:
  --remote-debugging-port=9222
  --user-data-dir=C:\Users\...\Temp\chrome-debug-profile
  --disable-web-security
  --disable-features=VizDisplayCompositor
  --no-first-run
  --no-default-browser-check
  --disable-popup-blocking
  --disable-translate
  --disable-background-timer-throttling
  --disable-renderer-backgrounding
  --disable-device-discovery-notifications
  http://localhost:8000/login.php

Verifying Chrome DevTools connection...

Chrome DevTools ready!
Target tab found:
  Title: Otter Login
  URL: http://localhost:8000/login.php
  WebSocket URL: ws://localhost:9222/devtools/page/XXXXX

=== CHROME READY FOR MCP CONNECTION ===
```

### Step 2: Start the MCP Server

In a new PowerShell window:

```powershell
cd browsertools-mcp
npm start
```

**Expected Output:**
```
🌐 Otter BrowserTools MCP Server running on http://localhost:3001
🚀 Otter BrowserTools MCP Server started
📋 Available tools: get_console_logs, get_network_activity, take_screenshot, get_dom_elements, run_lighthouse_audit, execute_js, get_page_info, monitor_errors, get_cookies, inspect_session, navigate_to
🔍 Starting Chrome DevTools discovery process...
🎯 Found 1 Chrome targets
📌 Found target tab: Otter Login - http://localhost:8000/login.php
🔗 Connecting to WebSocket: ws://localhost:9222/devtools/page/XXXXX
✅ Connected to Chrome DevTools Protocol
🔧 Chrome DevTools domains enabled
```

### Step 3: Test Authentication Flow

1. **Login Manually**: In the Chrome window, log in to the Otter application
2. **Verify Admin Access**: You should be redirected to the admin page

### Step 4: Test Session Cookie Inspection

In a third PowerShell window, test the MCP server's cookie inspection:

```powershell
# Check MCP server health
Invoke-WebRequest "http://localhost:3001/health" -UseBasicParsing | ConvertFrom-Json
```

#### Using MCP Tools in Cursor

The MCP tools are accessed through Cursor's AI interface. Here's how:

1. **Open Cursor's AI Chat** (Ctrl+L or Cmd+L)

2. **Ask the AI to use specific tools**. For example:
   ```
   Please use the inspect_session tool to check the current authentication state
   ```
   
   The AI will respond with something like:
   ```json
   {
     "action": "inspect_session",
     "currentUrl": "http://localhost:8000/admin/index.php",
     "sessionFound": true,
     "sessionDetails": {
       "value": "abc123...",
       "domain": "localhost",
       "path": "/",
       "httpOnly": true
     }
   }
   ```

3. **Common tool requests**:
   - "Use `get_cookies` to show all cookies"
   - "Use `navigate_to` to go to http://localhost:8000/reports/index.php"
   - "Use `get_console_logs` to check for JavaScript errors"
   - "Use `take_screenshot` to capture the current page"

4. **Chain commands for debugging**:
   ```
   1. First, use inspect_session to check current state
   2. Then navigate_to the reports page
   3. Finally, use inspect_session again to see if the session persisted
   ```

### Step 5: Debug Authentication Loop

If you experience the authentication loop when navigating to reports:

1. **Before Navigation**: Use `inspect_session` to check the current session state
2. **Navigate to Reports**: Use `navigate_to` with URL `http://localhost:8000/reports/index.php`
3. **After Navigation**: Use `inspect_session` again to see if the session cookie was preserved

### Common Issues and Solutions

#### Issue 1: MCP Server Can't Connect
- **Symptom**: `Chrome DevTools connection error: Unexpected server response: 404`
- **Solution**: Ensure you're using `start-chrome-debug-robust.ps1` which properly discovers targets

#### Issue 2: No Otter Tab Found
- **Symptom**: MCP connects to wrong tab or "New Tab"
- **Solution**: The robust launcher opens directly to login.php, ensuring the correct tab is available

#### Issue 3: Session Cookie Lost
- **Symptom**: Redirected to login when accessing reports
- **Solution**: Use `inspect_session` before and after navigation to identify when the cookie is lost

### Testing Checklist

- [ ] Chrome launches with debugging enabled
- [ ] Chrome opens directly to login.php
- [ ] MCP server connects successfully
- [ ] MCP server finds the correct tab
- [ ] Login works and redirects to admin
- [ ] Session cookie is present after login
- [ ] Navigation to reports preserves session
- [ ] Cookie inspection tools work correctly

### Advanced Debugging

For deeper debugging, you can:

1. **Monitor Network Activity**: Use `get_network_activity` to see all HTTP requests
2. **Check Console Logs**: Use `get_console_logs` to see JavaScript errors
3. **Execute Custom JavaScript**: Use `execute_js` to run diagnostic code
4. **Take Screenshots**: Use `take_screenshot` to capture the current state

### Integration with Cursor

Once the MCP server is running and connected:

1. Cursor should detect the MCP server automatically
2. Use Cursor's MCP tools interface to access all debugging functions
3. The AI agent can now inspect cookies, navigate pages, and debug the authentication flow

## Troubleshooting Commands

```powershell
# Check if Chrome is running with debug port
Get-Process chrome | Select-Object Id, ProcessName, StartTime

# Check Chrome DevTools availability
Invoke-WebRequest "http://localhost:9222/json/list" -UseBasicParsing | ConvertFrom-Json

# Check MCP server health
Invoke-WebRequest "http://localhost:3001/health" -UseBasicParsing | ConvertFrom-Json

# Kill all Chrome processes (if needed)
taskkill /F /IM chrome.exe
```
