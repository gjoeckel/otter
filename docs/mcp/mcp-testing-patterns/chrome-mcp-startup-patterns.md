# Chrome MCP Startup Testing Patterns

## Overview
This document captures testing patterns derived from Chrome MCP startup logs and identifies common startup issues, successful patterns, and diagnostic approaches.

## Startup Log Analysis

### Successful Startup Pattern
From `startup-20250924-100857.log`:

```
[SUCCESS] === OTTER BROWSERTOOLS TEST ENVIRONMENT STARTUP ===
[INFO] Script Version: 1.0.0
[INFO] PowerShell Version: 5.1.26100.6584
[INFO] OS: Microsoft Windows NT 10.0.26100.0
[SUCCESS] Configuration loaded successfully

[Step 1/5] Cleaning environment...
[INFO] Stopping all Chrome processes...
[WARN] Found 13 Chrome process(es). Terminating...
[SUCCESS] Chrome processes terminated

[Step 2/5] Locating Chrome executable...
[SUCCESS] Found Chrome at: C:\Program Files\Google\Chrome\Application\chrome.exe

[Step 3/5] Starting Chrome with debugging...
[SUCCESS] Chrome started with PID: 32604

[Step 4/5] Verifying Chrome DevTools...
[SUCCESS] Chrome DevTools ready!
```

### Common Startup Issues

#### Issue 1: Chrome Executable Not Found
**Pattern from logs:**
```
[ERROR] FATAL ERROR: Chrome executable not found in any of the configured paths
[DEBUG] Checking: ${env:ProgramFiles}\Google\Chrome\Application\chrome.exe
[DEBUG] Checking: ${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe
[DEBUG] Checking: ${env:LocalAppData}\Google\Chrome\Application\chrome.exe
```

**Diagnostic Pattern:**
1. Check Chrome installation paths
2. Verify environment variables
3. Test manual Chrome launch
4. Update configuration paths

#### Issue 2: DevTools Connection Timeout
**Pattern from logs:**
```
[DEBUG] DevTools not ready yet... (0/10)
[DEBUG] DevTools not ready yet... (1/10)
...
[DEBUG] DevTools not ready yet... (9/10)
[ERROR] FATAL ERROR: Chrome DevTools failed to become ready after 10 attempts
```

**Diagnostic Pattern:**
1. Check port 9222 availability
2. Verify Chrome debug flags
3. Test manual DevTools connection
4. Increase timeout values

#### Issue 3: Configuration Parsing Error
**Pattern from logs:**
```
[ERROR] FATAL ERROR: Cannot process argument transformation on parameter 'Config'. 
Cannot convert value "@{chrome=; mcp=; otter=; logging=}" to type "System.Collections.Hashtable"
```

**Diagnostic Pattern:**
1. Validate JSON configuration format
2. Check PowerShell object conversion
3. Test configuration loading
4. Fix data type mismatches

## Testing Patterns

### Pattern 1: Chrome Process Management
**Objective:** Ensure clean Chrome startup environment

**Steps:**
1. **Terminate existing processes**
   ```powershell
   Get-Process chrome | Stop-Process -Force
   ```

2. **Verify process cleanup**
   ```powershell
   Get-Process chrome -ErrorAction SilentlyContinue
   # Should return no results
   ```

3. **Check process count in logs**
   - Look for: `Found X Chrome process(es). Terminating...`
   - Expect: `Chrome processes terminated`

**Expected Results:**
- All Chrome processes terminated
- Clean startup environment
- No process conflicts

### Pattern 2: Chrome Executable Discovery
**Objective:** Verify Chrome installation and path resolution

**Steps:**
1. **Check standard installation paths**
   - `%ProgramFiles%\Google\Chrome\Application\chrome.exe`
   - `%ProgramFiles(x86)%\Google\Chrome\Application\chrome.exe`
   - `%LOCALAPPDATA%\Google\Chrome\Application\chrome.exe`

2. **Verify executable exists**
   ```powershell
   Test-Path "C:\Program Files\Google\Chrome\Application\chrome.exe"
   ```

3. **Test manual launch**
   ```powershell
   & "C:\Program Files\Google\Chrome\Application\chrome.exe" --version
   ```

**Expected Results:**
- Chrome executable found
- Version information returned
- No path resolution errors

### Pattern 3: DevTools Connection Verification
**Objective:** Ensure Chrome DevTools API is accessible

**Steps:**
1. **Start Chrome with debug flags**
   ```powershell
   --remote-debugging-port=9222
   --user-data-dir=<temp-dir>
   --disable-web-security
   ```

2. **Test DevTools endpoint**
   ```powershell
   Invoke-WebRequest "http://localhost:9222/json/list" -UseBasicParsing
   ```

3. **Verify WebSocket connection**
   - Check for WebSocket URL in response
   - Test connection to DevTools protocol

**Expected Results:**
- DevTools endpoint responds
- WebSocket URL available
- Connection established within timeout

### Pattern 4: Configuration Validation
**Objective:** Ensure MCP configuration is properly loaded

**Steps:**
1. **Load configuration file**
   ```json
   {
     "chrome": {
       "connectionRetries": 10,
       "debugPort": 9222,
       "flags": [...]
     },
     "mcp": {
       "commandTimeout": 5000,
       "port": 3001
     },
     "otter": {
       "baseUrl": "http://localhost:8000",
       "adminUrl": "http://localhost:8000/home/index.php"
     }
   }
   ```

2. **Validate configuration structure**
   - Check required fields
   - Verify data types
   - Test URL accessibility

3. **Test configuration usage**
   - Verify Chrome flags applied
   - Check MCP server startup
   - Test Otter URL connections

**Expected Results:**
- Configuration loaded successfully
- All required fields present
- URLs accessible

## Diagnostic Commands

### Chrome Process Management
```powershell
# List all Chrome processes
Get-Process chrome | Select-Object Id, ProcessName, StartTime, Path

# Kill all Chrome processes
taskkill /F /IM chrome.exe

# Check Chrome installation
Get-ChildItem "C:\Program Files\Google\Chrome\Application\" -Name "chrome.exe"
```

### DevTools Connection Testing
```powershell
# Test DevTools endpoint
try {
    $response = Invoke-WebRequest "http://localhost:9222/json/list" -UseBasicParsing
    $response.Content | ConvertFrom-Json
} catch {
    Write-Host "DevTools not accessible: $($_.Exception.Message)"
}

# Check port availability
Test-NetConnection -ComputerName localhost -Port 9222
```

### Configuration Validation
```powershell
# Test configuration loading
$config = Get-Content "browsertools-mcp/config.json" | ConvertFrom-Json
$config.chrome.debugPort
$config.otter.baseUrl

# Test URL accessibility
try {
    Invoke-WebRequest $config.otter.baseUrl -UseBasicParsing
    Write-Host "Otter server accessible"
} catch {
    Write-Host "Otter server not accessible: $($_.Exception.Message)"
}
```

## Troubleshooting Patterns

### Pattern 1: Chrome Not Found
**Symptoms:**
- `Chrome executable not found in any of the configured paths`
- Configuration shows empty paths

**Solutions:**
1. Install Chrome in standard location
2. Update configuration paths
3. Use absolute paths in config
4. Check environment variables

### Pattern 2: DevTools Timeout
**Symptoms:**
- `Chrome DevTools failed to become ready after 10 attempts`
- DevTools endpoint not responding

**Solutions:**
1. Increase timeout values
2. Check firewall settings
3. Verify Chrome flags
4. Test manual DevTools access

### Pattern 3: Configuration Errors
**Symptoms:**
- PowerShell object conversion errors
- Missing configuration fields

**Solutions:**
1. Validate JSON syntax
2. Check PowerShell version compatibility
3. Update configuration structure
4. Test configuration loading

## Success Metrics

### Startup Success Indicators
- ✅ All Chrome processes terminated
- ✅ Chrome executable found
- ✅ Chrome started with PID
- ✅ DevTools connection established
- ✅ Configuration loaded successfully

### Performance Benchmarks
- **Process cleanup**: < 5 seconds
- **Chrome startup**: < 10 seconds
- **DevTools connection**: < 30 seconds
- **Total startup time**: < 45 seconds

## Integration with MCP Testing

### Pre-Test Setup
1. Run startup diagnostic
2. Verify all success indicators
3. Test basic MCP connectivity
4. Validate Otter server accessibility

### Post-Test Cleanup
1. Terminate Chrome processes
2. Clean temporary directories
3. Reset configuration state
4. Log startup metrics

## Automation Opportunities

### Automated Startup Testing
```powershell
# Automated startup test script
$startupTest = {
    # Test Chrome process cleanup
    # Test Chrome executable discovery
    # Test DevTools connection
    # Test configuration loading
    # Return success/failure status
}
```

### Continuous Monitoring
- Monitor startup success rates
- Track performance metrics
- Alert on configuration issues
- Log diagnostic information

## Best Practices

1. **Always clean environment** before startup
2. **Verify Chrome installation** before testing
3. **Test DevTools connectivity** early
4. **Validate configuration** before use
5. **Monitor startup performance** consistently
6. **Document failure patterns** for troubleshooting
7. **Use consistent timeout values** across tests
8. **Test on multiple environments** for compatibility
