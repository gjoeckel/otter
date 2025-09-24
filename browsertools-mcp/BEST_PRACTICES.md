# BrowserTools MCP Best Practices Guide

This guide documents best practices for maintaining a robust, reliable testing system based on extensive research and implementation experience.

## 🤖 AI Agent Guidelines

### Primary Rules for AI Agents

1. **Terminal Discipline**: 
   - **Git Bash**: Use for `git` commands and optionally for `npm` commands
   - **PowerShell**: Use for all system operations, Chrome management, and the integrated startup script
   - **Preferred**: Use `start-test-environment.ps1` which handles everything in PowerShell

2. **Working Directory**: Always operate from the project root (`C:\Users\George\Projects\otter`)

3. **State Awareness**: The system has two components that must both be running:
   - Chrome Debug Instance (port 9222)
   - MCP Server (port 3001)

4. **Verification First**: Always verify components are running before proceeding

### AI Agent Decision Matrix

| Scenario | Action | Terminal | Verification |
|----------|--------|----------|--------------|
| Start complete environment | `.\browsertools-mcp\start-test-environment.ps1` | PowerShell | Script provides verification |
| Start Chrome only | `.\browsertools-mcp\start-test-environment.ps1 -SkipMCPServer` | PowerShell | `Test-NetConnection localhost -Port 9222` |
| Check Chrome status | `Test-NetConnection localhost -Port 9222` | PowerShell | Returns `True` if running |
| Check MCP status | `Invoke-WebRequest "http://localhost:3001/health"` | PowerShell | Returns health JSON |
| View logs | `Get-Content "browsertools-mcp\logs\*.log" -Tail 50` | PowerShell | Shows recent activity |
| Stop everything | `Get-Process chrome,node | Stop-Process -Force` | PowerShell | Processes terminated |

### Structured Workflow for AI Agents

```powershell
# Step 1: Start Everything (Recommended Approach)
.\browsertools-mcp\start-test-environment.ps1

# The script will:
# - Kill existing Chrome processes
# - Start Chrome with debugging
# - Verify Chrome is ready
# - Start MCP server
# - Provide comprehensive logging

# Step 2: Verify System Status
Invoke-WebRequest "http://localhost:3001/health" -UseBasicParsing | ConvertFrom-Json

# Step 3: Use Browser Tools
# Tools are now available through the MCP interface
```

### Troubleshooting Decision Tree

| Issue | Detection | Solution | Command |
|-------|-----------|----------|---------|
| Chrome won't start | Port 9222 not responding | Kill Chrome and restart | `Get-Process chrome \| Stop-Process -Force; .\browsertools-mcp\start-test-environment.ps1` |
| MCP connection fails | Health check fails | Check logs and restart | `Get-Content "browsertools-mcp\logs\mcp-error-*.log" -Tail 20` |
| Session cookie lost | Authentication redirects | Use `inspect_session` tool | Via MCP: `inspect_session` |
| Port conflict | `netstat` shows port in use | Find and kill process | `Get-Process -Id (Get-NetTCPConnection -LocalPort 9222).OwningProcess \| Stop-Process -Force` |

## Table of Contents

1. [PowerShell Orchestration](#powershell-orchestration)
2. [Chrome DevTools Protocol (CDP) Interaction](#chrome-devtools-protocol-interaction)
3. [MCP Server Development](#mcp-server-development)
4. [PHP Application Testing](#php-application-testing)
5. [Troubleshooting Guide](#troubleshooting-guide)

## PowerShell Orchestration

### Idempotent Startup Sequence

The startup script (`start-test-environment.ps1`) implements the "kill-launch-verify" pattern:

```powershell
# 1. Kill - Ensure clean state
Stop-Process -Name chrome -Force -ErrorAction SilentlyContinue

# 2. Launch - Start with known configuration
Start-Process -FilePath $chromePath -ArgumentList $chromeArgs -PassThru

# 3. Verify - Confirm readiness before proceeding
$response = Invoke-WebRequest -Uri "http://localhost:9222/json/list"
```

### Centralized Configuration

All settings are stored in `config.json`:
- Chrome paths and flags
- Port numbers
- Timeouts and retry settings
- Application URLs

### Robust Logging

The script uses structured logging with levels:
- **ERROR**: Critical failures
- **WARN**: Important warnings
- **SUCCESS**: Successful operations
- **INFO**: General information
- **DEBUG**: Detailed debugging (use `-Verbose`)

### Error Handling

```powershell
try {
    # Main logic
} catch {
    Write-Log "FATAL ERROR: $_" -Level "ERROR"
    Write-Log $_.ScriptStackTrace -Level "ERROR"
    exit 1
} finally {
    # Cleanup always runs
    Stop-Process -Id $chromeProcess.Id -Force -ErrorAction SilentlyContinue
}
```

## Chrome DevTools Protocol Interaction

### Use Puppeteer Instead of Raw CDP

The new `server-puppeteer.js` uses Puppeteer for robust browser automation:

```javascript
// Connect to existing Chrome instance
this.browser = await puppeteer.connect({
  browserURL: `http://localhost:${this.config.chrome.debugPort}`,
  defaultViewport: null
});
```

Benefits:
- Automatic reconnection handling
- Built-in wait strategies
- Simplified API
- Better error messages

### Avoid Race Conditions

Always wait for elements before interacting:

```javascript
// Wait for element to be ready
await this.page.waitForSelector(selector, { timeout: 30000 });
await this.page.click(selector);
```

### Handle Target Crashes

The server automatically reconnects on disconnection:

```javascript
this.browser.on('disconnected', () => {
  logger.warn('Browser disconnected, attempting to reconnect...');
  setTimeout(() => this.connectToBrowser(), 5000);
});
```

## MCP Server Development

### WebSocket Keep-Alive

The server implements a 30-second keep-alive ping:

```javascript
this.keepAliveInterval = setInterval(async () => {
  if (this.browser && this.browser.isConnected()) {
    await this.browser.version();
    logger.debug('Keep-alive ping successful');
  }
}, 30000);
```

### Stateless Tool Design

Each tool is self-contained and doesn't rely on server state:

```javascript
async navigateTo(url, waitUntil = 'networkidle2') {
  // All parameters provided in the request
  const response = await this.page.goto(url, { waitUntil, timeout: 30000 });
  return { /* complete response */ };
}
```

### Detailed Error Propagation

Errors include full context for debugging:

```javascript
return {
  content: [{
    type: 'text',
    text: JSON.stringify({
      error: true,
      tool: name,
      message: error.message,
      details: error.stack,
      timestamp: new Date().toISOString()
    }, null, 2)
  }]
};
```

### Comprehensive Logging

Winston logger captures all events:
- Error logs: `logs/mcp-error-YYYY-MM-DD.log`
- Combined logs: `logs/mcp-combined-YYYY-MM-DD.log`
- Console output with colors

## PHP Application Testing

### Test-Specific Selectors

Add `data-testid` attributes to key elements:

```php
// login.php
<form data-testid="login-form">
  <input type="text" name="username" data-testid="login-username" />
  <input type="password" name="password" data-testid="login-password" />
  <button type="submit" data-testid="login-submit">Login</button>
</form>

// reports/index.php
<div data-testid="reports-container">
  <button data-testid="refresh-reports">Refresh</button>
  <table data-testid="reports-table">
    <!-- ... -->
  </table>
</div>
```

### Test Environment Detection

Add environment awareness to your PHP application:

```php
// config.php
define('IS_TEST_ENV', getenv('OTTER_TEST_ENV') === 'true');

if (IS_TEST_ENV) {
    // Disable CSRF for testing
    define('CSRF_ENABLED', false);
    
    // Use test database
    define('DB_NAME', 'otter_test');
    
    // Enable debug logging
    define('DEBUG_MODE', true);
}
```

### Helper Endpoints

Create test-specific endpoints:

```php
// test-helpers.php (only accessible in test environment)
if (!IS_TEST_ENV) {
    http_response_code(403);
    exit('Forbidden');
}

// Reset database
if ($_POST['action'] === 'reset_database') {
    resetTestDatabase();
    echo json_encode(['success' => true]);
}

// Quick login
if ($_POST['action'] === 'quick_login') {
    $_SESSION['user_id'] = $_POST['user_id'];
    $_SESSION['authenticated'] = true;
    echo json_encode(['success' => true, 'session_id' => session_id()]);
}
```

## Troubleshooting Guide

### Common Issues and Solutions

#### Chrome Won't Start
```powershell
# Check if port is already in use
netstat -an | findstr :9222

# Kill any process using the port
Get-Process | Where-Object { $_.ProcessName -eq "chrome" } | Stop-Process -Force
```

#### MCP Server Can't Connect
```powershell
# Verify Chrome DevTools is accessible
Invoke-WebRequest "http://localhost:9222/json/list" -UseBasicParsing

# Check MCP server logs
Get-Content "browsertools-mcp/logs/mcp-error-*.log" -Tail 50
```

#### Session Cookie Issues
Use the MCP tools to debug:
1. `inspect_session` - Check current session state
2. `get_network_activity` - Monitor HTTP requests
3. `get_cookies` - Inspect all cookies

### Debug Commands

```powershell
# Full system status
.\browsertools-mcp\debug-status.ps1

# View recent logs
Get-Content "browsertools-mcp\logs\startup-*.log" -Tail 100

# Test specific component
.\browsertools-mcp\test-component.ps1 -Component "chrome-connection"
```

### Performance Optimization

1. **Reduce Chrome Flags**: Only use necessary flags
2. **Limit Log Retention**: Rotate logs after 10MB
3. **Use Specific Waits**: Prefer `waitForSelector` over fixed delays
4. **Cache Selectors**: Store frequently used selectors in config

### Security Considerations

1. **Never Commit Credentials**: Use environment variables
2. **Restrict Test Endpoints**: Check `IS_TEST_ENV` flag
3. **Clean Temp Profiles**: Delete Chrome profiles after testing
4. **Validate Input**: Sanitize all tool parameters

## Continuous Improvement

### Monitoring

Track these metrics:
- Test execution time
- Connection stability
- Error frequency
- Resource usage

### Regular Maintenance

Weekly tasks:
- Review error logs
- Update dependencies
- Clean temp files
- Verify configurations

### Documentation

Keep updated:
- Tool descriptions
- Error solutions
- Configuration changes
- Best practices

## Conclusion

Following these best practices ensures a robust, maintainable testing system. The key principles are:

1. **Idempotency**: Same result every time
2. **Observability**: Comprehensive logging
3. **Resilience**: Automatic recovery
4. **Simplicity**: Clear, maintainable code

For questions or improvements, consult the logs first, then the documentation, and finally the source code.
