# Windows 11 & Chrome 136+ Known Issues and Solutions

This document catalogs known compatibility issues with browser automation on Windows 11 and Chrome 136+, along with the solutions implemented in our BrowserTools MCP system.

## Compatibility Matrix

| Chrome Version | Issue | Our Solution | Implementation | Status |
|----------------|-------|--------------|----------------|--------|
| 136+ | Requires `--user-data-dir` flag | Auto-generated temp directories | `start-test-environment.ps1` | ✅ Fixed |
| All | WebSocket 404 connection errors | Two-step CDP discovery | `server-puppeteer.js` | ✅ Fixed |
| All | Session cookie persistence | Cookie inspection tools | `inspect_session` tool | ✅ Fixed |
| All | Multiple Chrome processes | Kill-launch-verify pattern | `start-test-environment.ps1` | ✅ Fixed |
| All | PowerShell execution policies | Comprehensive error handling | All PS1 scripts | ✅ Fixed |

## Chrome 136+ Breaking Changes

### Issue: Mandatory --user-data-dir Requirement

**Symptom**: 
```
DevTools remote debugging requires a non-default data directory. Specify this using --user-data-dir
```

**Root Cause**: Chrome 136 introduced security enhancement to prevent malware from extracting cookies via remote debugging.

**Our Solution**:
```powershell
# Implemented in start-test-environment.ps1
$tempDir = Join-Path $env:TEMP "chrome-debug-profile-$(Get-Date -Format 'yyyyMMddHHmmss')"
$chromeArgs += "--user-data-dir=$tempDir"
```

**Manual Fix** (if needed):
```powershell
chrome.exe --remote-debugging-port=9222 --user-data-dir="C:\temp\chrome-debug"
```

### Issue: Only One Instance Per User Data Directory

**Symptom**: Chrome fails to start or debugging port not accessible

**Our Solution**: 
- Unique timestamp-based directories for each session
- Forceful termination of existing Chrome processes
- Cleanup of old temp directories

## Windows 11 Specific Issues

### Issue: PowerShell Execution Policy Restrictions

**Symptom**: 
```
cannot be loaded because running scripts is disabled on this system
```

**Solutions Implemented**:

1. **For Users**: Set execution policy
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser -Force
```

2. **For Enterprise** (Group Policy restricted):
```powershell
Set-ExecutionPolicy -ExecutionPolicy Bypass -Scope Process -Force
```

3. **Our Scripts**: Include bypass in script execution
```powershell
powershell.exe -ExecutionPolicy Bypass -File .\start-test-environment.ps1
```

### Issue: Windows Firewall Blocking Debug Port

**Symptom**: Cannot connect to localhost:9222

**Solution**:
```powershell
# Create firewall rule (run as Administrator)
New-NetFirewallRule -DisplayName "Chrome Debug Port" -Direction Inbound -Port 9222 -Protocol TCP -Action Allow
```

### Issue: Character Encoding (Emoji in Scripts)

**Symptom**: PowerShell parsing errors with emoji characters

**Our Solution**: 
- Created `start-chrome-debug-simple.ps1` without emojis
- Set proper encoding in scripts:
```powershell
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
```

## MCP Integration Issues

### Issue: Complex Multi-Component Architecture

**Original Problem**: 
- MCP client in Cursor
- MCP server process
- Node.js middleware
- Chrome extension
- Multiple failure points

**Our Solution**: Simplified to two components
1. Chrome with debugging (managed by PowerShell)
2. Puppeteer-based MCP server (single Node.js process)

### Issue: WebSocket Connection Failures

**Symptoms**:
- "Unexpected server response: 404"
- "Chrome DevTools connection error"

**Our Solution** (in `server-puppeteer.js`):
```javascript
// Two-step discovery process
const targets = await this.discoverTargets();
const targetTab = targets.find(tab => tab.url.includes('localhost:8000'));
this.browser = await puppeteer.connect({
  browserURL: `http://localhost:${this.config.chrome.debugPort}`,
  defaultViewport: null
});
```

### Issue: Connection Stability

**Our Solutions**:
- WebSocket keep-alive (30-second interval)
- Automatic reconnection on disconnect
- Comprehensive error logging with Winston

## Authentication & Session Issues

### Issue: Login Redirect Loops

**Symptom**: After login, redirected back to login page

**Root Causes**:
1. Session cookie not preserved
2. Cross-domain cookie issues
3. Race conditions in navigation

**Our Solutions**:

1. **Cookie Inspection Tool**:
```javascript
// Use inspect_session to debug
{
  "action": "inspect_session",
  "sessionFound": true,
  "sessionDetails": {
    "value": "abc123...",
    "domain": "localhost",
    "httpOnly": true
  }
}
```

2. **Proper Navigation Waiting**:
```javascript
await this.page.goto(url, { 
  waitUntil: 'networkidle2',
  timeout: 30000 
});
```

3. **Session Persistence**:
- User data directories maintain cookies
- Cookie inspection before/after navigation
- Network activity monitoring

### Issue: CORS and Mixed Content

**For Development Only**:
```powershell
# Flags in config.json
"--disable-web-security",
"--disable-site-isolation-trials"
```

**Warning**: Never use these flags in production

## Chrome Process Management

### Issue: Multiple Chrome Processes

**Chrome's Architecture**:
- Main browser process
- Renderer process per tab
- Extension processes
- GPU process
- Network service process

**Our Solution**:
```powershell
# Force kill all Chrome processes
Stop-Process -Name chrome -Force -ErrorAction SilentlyContinue
# Or more aggressive
taskkill /F /IM chrome.exe
```

## Path Resolution Issues

### Issue: Chrome Installation Varies by System

**Common Locations**:
- `C:\Program Files\Google\Chrome\Application\chrome.exe`
- `C:\Program Files (x86)\Google\Chrome\Application\chrome.exe`
- `%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe`

**Our Solution** (in `config.json`):
```json
"chrome": {
  "paths": [
    "${env:ProgramFiles}\\Google\\Chrome\\Application\\chrome.exe",
    "${env:ProgramFiles(x86)}\\Google\\Chrome\\Application\\chrome.exe",
    "${env:LocalAppData}\\Google\\Chrome\\Application\\chrome.exe"
  ]
}
```

## Lessons Learned

### Why We Chose Puppeteer Over Raw CDP

1. **Automatic Reconnection**: Handles disconnects gracefully
2. **Built-in Wait Strategies**: No more race conditions
3. **Simplified API**: Less code, fewer bugs
4. **Better Error Messages**: Easier debugging

### Benefits of Our Approach

1. **Idempotent Startup**: Same clean state every time
2. **Centralized Configuration**: One place for all settings
3. **Comprehensive Logging**: Full audit trail
4. **Graceful Degradation**: Fallbacks for common failures

## Migration Guide

### From Old Scripts to New System

1. **Replace Manual Chrome Startup**:
   ```powershell
   # Old
   chrome.exe --remote-debugging-port=9222
   
   # New
   .\browsertools-mcp\start-test-environment.ps1
   ```

2. **Update MCP Configuration**:
   - Remove complex multi-server setup
   - Use single `server-puppeteer.js`

3. **Fix Chrome 136+ Compatibility**:
   - All scripts now include `--user-data-dir`
   - Temporary directories auto-managed

## Troubleshooting Quick Reference

| Problem | Check | Fix |
|---------|-------|-----|
| Chrome won't start | `Get-Process chrome` | Run `start-test-environment.ps1` |
| Port 9222 blocked | `Test-NetConnection localhost -Port 9222` | Check firewall, kill Chrome |
| MCP server fails | Check logs in `browsertools-mcp/logs/` | Restart with script |
| Session lost | Use `inspect_session` tool | Check cookie domain/path |
| PowerShell blocked | `Get-ExecutionPolicy` | Set appropriate policy |

## Version History

- **v2.0.0**: Current implementation with Puppeteer
- **v1.0.0**: Initial raw CDP implementation (deprecated)

## Related Documentation

- [`BEST_PRACTICES.md`](./BEST_PRACTICES.md) - Current recommended approaches
- [`start-test-environment.ps1`](./start-test-environment.ps1) - Main startup script
- [`server-puppeteer.js`](./server-puppeteer.js) - MCP server implementation
- [`config.json`](./config.json) - Centralized configuration
