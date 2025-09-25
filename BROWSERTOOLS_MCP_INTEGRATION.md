# 🤖 Otter BrowserTools MCP Integration v2.0

## **System Status: Active**

This document provides AI agents with the protocol for using the BrowserTools MCP (Model Context Protocol) server to interact with Chrome browser for testing and debugging.

---

## 🤖 **AI AGENT GUIDELINES**

### **Primary Rules**

1. **Terminal Discipline**: 
   - **PowerShell**: Primary terminal for all operations (recommended)
   - **Git Bash**: Alternative for npm commands if PowerShell fails
   - **Integrated Approach**: Use `start-test-environment.ps1` for one-command startup

2. **Working Directory**: All operations must be executed from `C:\Users\George\Projects\otter`

3. **Verification First**: Before using browser tools, verify both components are running:
   - Chrome Debug Instance (port 9222)
   - MCP Server (port 3001)

4. **State Awareness**: The system requires both components running for tools to function

### **AI Agent Decision Matrix**

| Scenario | Action | Terminal | Verification Command |
|----------|--------|----------|---------------------|
| Start complete environment | `.\browsertools-mcp\start-test-environment.ps1` | PowerShell | Built-in verification |
| Start Chrome only | `.\browsertools-mcp\start-test-environment.ps1 -SkipMCPServer` | PowerShell | `Test-NetConnection localhost -Port 9222` |
| Check system health | `Invoke-WebRequest "http://localhost:3001/health"` | PowerShell | Returns JSON with status |
| Emergency stop | `Get-Process chrome,node \| Stop-Process -Force` | PowerShell | All processes terminated |
| View recent logs | `Get-Content "browsertools-mcp\logs\*.log" -Tail 50` | PowerShell | Shows last 50 log lines |

---

## 🚀 **System Activation Protocol**

### **Recommended: One-Command Startup**

```powershell
# This single command handles everything
.\browsertools-mcp\start-test-environment.ps1

# What it does:
# 1. Kills existing Chrome processes
# 2. Starts Chrome with debugging on port 9222
# 3. Verifies Chrome is ready
# 4. Installs npm dependencies if needed
# 5. Starts MCP server on port 3001
# 6. Provides comprehensive logging
```

### **Alternative: Manual Steps**

If the integrated script fails, use these manual steps:

#### **Step 1: Chrome Debug Instance (PowerShell)**
```powershell
# Kill existing Chrome
Get-Process chrome -ErrorAction SilentlyContinue | Stop-Process -Force

# Start Chrome with debugging
.\browsertools-mcp\start-chrome-debug-robust.ps1

# Verify
Test-NetConnection localhost -Port 9222
```

#### **Step 2: MCP Server (PowerShell or Git Bash)**
```bash
cd browsertools-mcp
npm install  # First time only
npm start
```

---

## 🛠️ **Available Browser Tools**

Once the system is running, these tools are available through the MCP interface:

### **Debugging Tools**

| Tool | Purpose | Example Usage | Return Format |
|------|---------|---------------|---------------|
| `get_console_logs` | Retrieve browser console output | `clear: true` to clear after reading | JSON with log entries |
| `get_network_activity` | Monitor HTTP requests/responses | `clear: false` to preserve history | JSON with network logs |
| `monitor_errors` | Real-time error tracking | `duration: 30` for 30-second monitoring | Error stream |

### **Inspection Tools**

| Tool | Purpose | Parameters | Return Format |
|------|---------|------------|---------------|
| `get_cookies` | Inspect all cookies | `domain: "localhost"` to filter | Cookie array with details |
| `inspect_session` | Check PHP session state | None | Session cookie info + URL |
| `get_dom_elements` | Query DOM elements | `selector: "[data-testid='login-form']"` | Element details |
| `get_page_info` | Current page metadata | None | Title, URL, viewport |

### **Interaction Tools**

| Tool | Purpose | Parameters | Notes |
|------|---------|------------|-------|
| `navigate_to` | Go to URL | `url`, `waitUntil: "networkidle2"` | Waits for page load |
| `click_element` | Click element | `selector: "[data-testid='submit']"` | Waits for element |
| `type_text` | Type in input | `selector`, `text`, `delay: 50` | Simulates typing |
| `execute_js` | Run JavaScript | `code: "document.title"` | Returns execution result |

### **Analysis Tools**

| Tool | Purpose | Parameters | Output |
|------|---------|------------|--------|
| `take_screenshot` | Capture page | `fullPage: true`, `path: "screenshot.png"` | Image saved + path |
| `run_lighthouse_audit` | Performance audit | `categories: ["performance", "seo"]` | Lighthouse report |

---

## 🚨 **AI Agent Troubleshooting Protocol**

### **Issue Detection & Resolution**

| Issue | Detection | Resolution | Command |
|-------|-----------|------------|---------|
| **Chrome not running** | `Test-NetConnection localhost -Port 9222` returns `False` | Restart Chrome | `.\browsertools-mcp\start-test-environment.ps1` |
| **MCP server down** | Health check fails or timeout | Check logs and restart | `Get-Content "browsertools-mcp\logs\mcp-error-*.log" -Tail 20` |
| **Port conflict** | "Address in use" error | Kill process using port | `Get-Process -Id (Get-NetTCPConnection -LocalPort 9222).OwningProcess \| Stop-Process -Force` |
| **Session lost** | Authentication redirects | Inspect cookies | Use `inspect_session` tool |
| **Tools not responding** | Any tool returns error | Full system restart | `Get-Process chrome,node \| Stop-Process -Force; .\browsertools-mcp\start-test-environment.ps1` |

### **Diagnostic Commands**

```powershell
# Full system status check
@"
Chrome Status: $(Test-NetConnection localhost -Port 9222 -InformationLevel Quiet)
MCP Health: $((Invoke-WebRequest "http://localhost:3001/health" -UseBasicParsing).StatusCode -eq 200)
Chrome Processes: $(Get-Process chrome -ErrorAction SilentlyContinue | Measure-Object).Count
Node Processes: $(Get-Process node -ErrorAction SilentlyContinue | Measure-Object).Count
"@

# View all recent errors
Get-ChildItem "browsertools-mcp\logs\*error*.log" | 
    Sort-Object LastWriteTime -Descending | 
    Select-Object -First 1 | 
    Get-Content -Tail 50
```

---

## 📊 **Performance Considerations**

### **Resource Usage**
- Chrome with debugging: ~500MB RAM
- MCP Server: ~100MB RAM
- Puppeteer connection: ~50MB RAM

### **Optimization Tips**
1. Use `start-test-environment.ps1` for fastest startup
2. Keep logs under 10MB (auto-rotation configured)
3. Clear console/network logs periodically with `clear: true`
4. Close unused Chrome tabs to reduce memory

---

## 🔒 **Security Notes**

1. **Local Only**: Services bound to localhost only
2. **No Credentials**: Never store credentials in scripts
3. **Clean Profiles**: Temporary Chrome profiles deleted on restart
4. **Test Environment**: Use `OTTER_TEST_ENV=true` for test-specific features

---

## 📚 **Additional Resources**

- **Logs**: `browsertools-mcp/logs/`
- **Config**: `browsertools-mcp/config.json`
- **Best Practices**: `browsertools-mcp/BEST_PRACTICES.md`
- **Testing Guide**: `browsertools-mcp/TESTING_GUIDE.md`

---

## ✅ **Quick Verification**

Run this to verify everything is working:

```powershell
# One-line system check
if ((Test-NetConnection localhost -Port 9222 -InformationLevel Quiet) -and ((Invoke-WebRequest "http://localhost:3001/health" -UseBasicParsing -ErrorAction SilentlyContinue).StatusCode -eq 200)) { Write-Host "✅ System Ready" -ForegroundColor Green } else { Write-Host "❌ System Not Ready" -ForegroundColor Red }
```
