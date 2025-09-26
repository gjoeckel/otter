# Chrome MCP Error Handling Testing Patterns

## Overview
This document captures error handling patterns derived from Chrome MCP logs and identifies common error scenarios, diagnostic approaches, and recovery strategies.

## Error Categories

### 1. Chrome Process Management Errors

#### Pattern: Chrome Process Termination Issues
**Log Evidence:**
```
[WARN] Found 26 Chrome process(es). Terminating...
[SUCCESS] Chrome processes terminated
```

**Testing Pattern:**
1. **Pre-test cleanup verification**
   ```powershell
   $chromeProcesses = Get-Process chrome -ErrorAction SilentlyContinue
   if ($chromeProcesses.Count -gt 0) {
       Write-Host "Found $($chromeProcesses.Count) Chrome processes"
       $chromeProcesses | Stop-Process -Force
       Start-Sleep -Seconds 2
   }
   ```

2. **Post-cleanup verification**
   ```powershell
   $remainingProcesses = Get-Process chrome -ErrorAction SilentlyContinue
   if ($remainingProcesses.Count -eq 0) {
       Write-Host "✅ Chrome cleanup successful"
   } else {
       Write-Host "❌ Chrome cleanup failed: $($remainingProcesses.Count) processes remaining"
   }
   ```

**Error Scenarios:**
- Processes not terminating
- Partial termination
- Process restart during cleanup
- Permission issues

### 2. Chrome Executable Discovery Errors

#### Pattern: Chrome Not Found
**Log Evidence:**
```
[ERROR] FATAL ERROR: Chrome executable not found in any of the configured paths
[DEBUG] Checking: ${env:ProgramFiles}\Google\Chrome\Application\chrome.exe
[DEBUG] Checking: ${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe
[DEBUG] Checking: ${env:LocalAppData}\Google\Chrome\Application\chrome.exe
```

**Testing Pattern:**
1. **Path validation test**
   ```powershell
   $chromePaths = @(
       "${env:ProgramFiles}\Google\Chrome\Application\chrome.exe",
       "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
       "${env:LocalAppData}\Google\Chrome\Application\chrome.exe"
   )
   
   foreach ($path in $chromePaths) {
       if (Test-Path $path) {
           Write-Host "✅ Found Chrome at: $path"
           return $path
       } else {
           Write-Host "❌ Chrome not found at: $path"
       }
   }
   ```

2. **Alternative discovery methods**
   ```powershell
   # Check registry for Chrome installation
   $chromeRegPath = "HKLM:\SOFTWARE\Microsoft\Windows\CurrentVersion\App Paths\chrome.exe"
   if (Test-Path $chromeRegPath) {
       $chromePath = (Get-ItemProperty $chromeRegPath)."(Default)"
       Write-Host "✅ Found Chrome via registry: $chromePath"
   }
   ```

**Error Scenarios:**
- Chrome not installed
- Non-standard installation path
- Environment variable issues
- Permission problems

### 3. DevTools Connection Errors

#### Pattern: DevTools Timeout
**Log Evidence:**
```
[DEBUG] DevTools not ready yet... (0/10)
[DEBUG] DevTools not ready yet... (1/10)
...
[DEBUG] DevTools not ready yet... (9/10)
[ERROR] FATAL ERROR: Chrome DevTools failed to become ready after 10 attempts
```

**Testing Pattern:**
1. **DevTools readiness test**
   ```powershell
   $maxAttempts = 10
   $attempt = 0
   $devToolsReady = $false
   
   while ($attempt -lt $maxAttempts -and -not $devToolsReady) {
       try {
           $response = Invoke-WebRequest "http://localhost:9222/json/list" -UseBasicParsing -TimeoutSec 5
           $devToolsReady = $true
           Write-Host "✅ DevTools ready after $attempt attempts"
       } catch {
           $attempt++
           Write-Host "⏳ DevTools not ready yet... ($attempt/$maxAttempts)"
           Start-Sleep -Seconds 3
       }
   }
   ```

2. **Connection diagnostic**
   ```powershell
   # Test port availability
   $portTest = Test-NetConnection -ComputerName localhost -Port 9222 -WarningAction SilentlyContinue
   if ($portTest.TcpTestSucceeded) {
       Write-Host "✅ Port 9222 is accessible"
   } else {
       Write-Host "❌ Port 9222 is not accessible"
   }
   ```

**Error Scenarios:**
- Chrome not starting with debug flags
- Port conflicts
- Firewall blocking
- Chrome crash during startup

### 4. Configuration Parsing Errors

#### Pattern: PowerShell Object Conversion
**Log Evidence:**
```
[ERROR] FATAL ERROR: Cannot process argument transformation on parameter 'Config'. 
Cannot convert value "@{chrome=; mcp=; otter=; logging=}" to type "System.Collections.Hashtable"
```

**Testing Pattern:**
1. **Configuration validation**
   ```powershell
   try {
       $config = Get-Content "browsertools-mcp/config.json" | ConvertFrom-Json
       Write-Host "✅ Configuration loaded successfully"
       
       # Test object conversion
       $hashtable = @{}
       $config.PSObject.Properties | ForEach-Object {
           $hashtable[$_.Name] = $_.Value
       }
       Write-Host "✅ Object conversion successful"
   } catch {
       Write-Host "❌ Configuration error: $($_.Exception.Message)"
   }
   ```

2. **JSON syntax validation**
   ```powershell
   $jsonContent = Get-Content "browsertools-mcp/config.json" -Raw
   try {
       $jsonContent | ConvertFrom-Json | Out-Null
       Write-Host "✅ JSON syntax valid"
   } catch {
       Write-Host "❌ JSON syntax error: $($_.Exception.Message)"
   }
   ```

**Error Scenarios:**
- Invalid JSON syntax
- Missing required fields
- Type mismatches
- PowerShell version compatibility

## Error Recovery Patterns

### Pattern 1: Graceful Degradation
**Objective:** Continue testing with reduced functionality

**Implementation:**
```powershell
function Test-ChromeMCPWithFallback {
    try {
        # Try full MCP setup
        Start-ChromeMCP
        return "Full MCP functionality"
    } catch {
        Write-Host "⚠️ MCP setup failed, trying fallback"
        try {
            # Fallback to basic Chrome testing
            Start-ChromeBasic
            return "Basic Chrome functionality"
        } catch {
            Write-Host "❌ All Chrome testing failed"
            return "No Chrome functionality"
        }
    }
}
```

### Pattern 2: Retry with Backoff
**Objective:** Handle transient errors with intelligent retry

**Implementation:**
```powershell
function Invoke-WithRetry {
    param(
        [ScriptBlock]$ScriptBlock,
        [int]$MaxRetries = 3,
        [int]$BaseDelay = 1
    )
    
    for ($i = 0; $i -lt $MaxRetries; $i++) {
        try {
            return & $ScriptBlock
        } catch {
            if ($i -eq $MaxRetries - 1) {
                throw
            }
            $delay = $BaseDelay * [Math]::Pow(2, $i)
            Write-Host "⏳ Retry $($i + 1)/$MaxRetries in $delay seconds"
            Start-Sleep -Seconds $delay
        }
    }
}
```

### Pattern 3: Environment Reset
**Objective:** Clean slate recovery from persistent errors

**Implementation:**
```powershell
function Reset-ChromeEnvironment {
    Write-Host "🔄 Resetting Chrome environment..."
    
    # Kill all Chrome processes
    Get-Process chrome -ErrorAction SilentlyContinue | Stop-Process -Force
    
    # Clean temporary directories
    $tempDirs = Get-ChildItem $env:TEMP -Name "chrome-debug-*" -Directory
    foreach ($dir in $tempDirs) {
        Remove-Item "$env:TEMP\$dir" -Recurse -Force -ErrorAction SilentlyContinue
    }
    
    # Reset network connections
    netstat -ano | findstr :9222 | ForEach-Object {
        $pid = ($_ -split '\s+')[-1]
        if ($pid -ne "0") {
            Stop-Process -Id $pid -Force -ErrorAction SilentlyContinue
        }
    }
    
    Write-Host "✅ Environment reset complete"
}
```

## Diagnostic Testing Patterns

### Pattern 1: Comprehensive Health Check
**Objective:** Validate entire Chrome MCP stack

**Implementation:**
```powershell
function Test-ChromeMCPHealth {
    $results = @{
        ChromeProcesses = $false
        ChromeExecutable = $false
        DevToolsPort = $false
        MCPConfig = $false
        OtterServer = $false
    }
    
    # Test Chrome processes
    $chromeProcesses = Get-Process chrome -ErrorAction SilentlyContinue
    $results.ChromeProcesses = $chromeProcesses.Count -gt 0
    
    # Test Chrome executable
    $chromePath = Get-ChromeExecutablePath
    $results.ChromeExecutable = $chromePath -ne $null
    
    # Test DevTools port
    $portTest = Test-NetConnection -ComputerName localhost -Port 9222 -WarningAction SilentlyContinue
    $results.DevToolsPort = $portTest.TcpTestSucceeded
    
    # Test MCP configuration
    try {
        $config = Get-Content "browsertools-mcp/config.json" | ConvertFrom-Json
        $results.MCPConfig = $config -ne $null
    } catch {
        $results.MCPConfig = $false
    }
    
    # Test Otter server
    try {
        $response = Invoke-WebRequest "http://localhost:8000/health_check.php" -UseBasicParsing -TimeoutSec 5
        $results.OtterServer = $response.StatusCode -eq 200
    } catch {
        $results.OtterServer = $false
    }
    
    return $results
}
```

### Pattern 2: Error Pattern Recognition
**Objective:** Identify common error patterns for automated diagnosis

**Implementation:**
```powershell
function Get-ErrorPattern {
    param([string]$ErrorMessage)
    
    $patterns = @{
        "Chrome executable not found" = "ChromeInstallation"
        "DevTools failed to become ready" = "DevToolsConnection"
        "Cannot process argument transformation" = "ConfigurationError"
        "Chrome processes terminated" = "ProcessManagement"
        "Port 9222" = "PortConflict"
    }
    
    foreach ($pattern in $patterns.Keys) {
        if ($ErrorMessage -like "*$pattern*") {
            return $patterns[$pattern]
        }
    }
    
    return "Unknown"
}
```

## Monitoring and Alerting Patterns

### Pattern 1: Error Rate Monitoring
**Objective:** Track error frequency and patterns

**Implementation:**
```powershell
function Monitor-ErrorRates {
    $errorLog = "browsertools-mcp/logs/error-rates.log"
    $errors = @{}
    
    # Parse recent logs for errors
    $recentLogs = Get-ChildItem "browsertools-mcp/logs" -Filter "*.log" | 
                  Where-Object { $_.LastWriteTime -gt (Get-Date).AddHours(-1) }
    
    foreach ($log in $recentLogs) {
        $content = Get-Content $log.FullName
        $errorLines = $content | Where-Object { $_ -like "*[ERROR]*" }
        
        foreach ($line in $errorLines) {
            $pattern = Get-ErrorPattern $line
            if ($errors.ContainsKey($pattern)) {
                $errors[$pattern]++
            } else {
                $errors[$pattern] = 1
            }
        }
    }
    
    # Alert on high error rates
    foreach ($errorType in $errors.Keys) {
        if ($errors[$errorType] -gt 5) {
            Write-Host "🚨 High error rate for $errorType : $($errors[$errorType]) errors in last hour"
        }
    }
}
```

### Pattern 2: Performance Degradation Detection
**Objective:** Identify performance issues before they become critical

**Implementation:**
```powershell
function Test-StartupPerformance {
    $startTime = Get-Date
    
    # Measure startup time
    $startupResult = Start-ChromeMCP
    $endTime = Get-Date
    $duration = ($endTime - $startTime).TotalSeconds
    
    # Log performance metrics
    $metrics = @{
        Timestamp = $startTime
        Duration = $duration
        Success = $startupResult -eq "Success"
    }
    
    $metrics | ConvertTo-Json | Add-Content "browsertools-mcp/logs/performance.log"
    
    # Alert on slow startup
    if ($duration -gt 60) {
        Write-Host "⚠️ Slow startup detected: $duration seconds"
    }
}
```

## Best Practices for Error Handling

### 1. Proactive Error Prevention
- Always clean environment before startup
- Validate configuration before use
- Test connectivity early in process
- Use consistent timeout values

### 2. Comprehensive Error Logging
- Log all error conditions with context
- Include timestamps and error codes
- Capture system state at error time
- Maintain error history for analysis

### 3. Graceful Error Recovery
- Implement fallback mechanisms
- Use retry logic with exponential backoff
- Provide clear error messages
- Enable manual intervention when needed

### 4. Error Pattern Analysis
- Track error frequency and types
- Identify common failure modes
- Monitor performance degradation
- Alert on error rate thresholds

### 5. Testing Error Scenarios
- Test with missing Chrome installation
- Test with port conflicts
- Test with invalid configurations
- Test with network connectivity issues

## Integration with MCP Testing

### Error-Aware Test Execution
```powershell
function Invoke-MCPTestWithErrorHandling {
    param([ScriptBlock]$TestScript)
    
    try {
        # Pre-test health check
        $health = Test-ChromeMCPHealth
        if (-not $health.Values -contains $false) {
            Write-Host "✅ All systems healthy, proceeding with test"
            & $TestScript
        } else {
            Write-Host "⚠️ System health issues detected, attempting recovery"
            Reset-ChromeEnvironment
            Start-Sleep -Seconds 5
            & $TestScript
        }
    } catch {
        Write-Host "❌ Test failed with error: $($_.Exception.Message)"
        # Log error details
        $errorDetails = @{
            Timestamp = Get-Date
            Error = $_.Exception.Message
            StackTrace = $_.ScriptStackTrace
        }
        $errorDetails | ConvertTo-Json | Add-Content "test-errors.log"
    }
}
```

This comprehensive error handling pattern documentation provides a foundation for robust Chrome MCP testing with proper error detection, recovery, and monitoring capabilities.
