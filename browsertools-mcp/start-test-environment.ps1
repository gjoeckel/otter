#Requires -Version 5.1
<#
.SYNOPSIS
    Idempotent startup script for Otter BrowserTools MCP testing environment
.DESCRIPTION
    This script ensures a clean, consistent testing environment by:
    - Reading configuration from config.json
    - Killing existing Chrome processes
    - Starting Chrome with debug flags
    - Verifying Chrome is ready
    - Starting the MCP server
    - Providing comprehensive logging
#>

param(
    [string]$ConfigPath = "$PSScriptRoot\config.json",
    [string]$LogPath = "$PSScriptRoot\logs\startup-$(Get-Date -Format 'yyyyMMdd-HHmmss').log",
    [switch]$SkipMCPServer
)

# Enable strict mode
Set-StrictMode -Version Latest
$ErrorActionPreference = "Stop"

# Initialize logging
$script:LogFile = $LogPath
$logDir = Split-Path -Parent $LogPath
if (!(Test-Path $logDir)) {
    New-Item -ItemType Directory -Path $logDir -Force | Out-Null
}

function Write-Log {
    param(
        [string]$Message,
        [string]$Level = "INFO"
    )
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $logMessage = "[$timestamp] [$Level] $Message"
    
    # Write to console with color
    switch ($Level) {
        "ERROR" { Write-Host $logMessage -ForegroundColor Red }
        "WARN"  { Write-Host $logMessage -ForegroundColor Yellow }
        "SUCCESS" { Write-Host $logMessage -ForegroundColor Green }
        "DEBUG" { Write-Verbose $logMessage }
        default { Write-Host $logMessage -ForegroundColor White }
    }
    
    # Write to log file
    Add-Content -Path $script:LogFile -Value $logMessage
}

function Read-Configuration {
    param([string]$Path)
    
    Write-Log "Reading configuration from: $Path"
    
    if (!(Test-Path $Path)) {
        throw "Configuration file not found: $Path"
    }
    
    $configContent = Get-Content $Path -Raw
    $config = $configContent | ConvertFrom-Json
    
    # Expand environment variables in paths
    $expandedPaths = @()
    foreach ($path in $config.chrome.paths) {
        $expandedPath = [Environment]::ExpandEnvironmentVariables($path)
        $expandedPaths += $expandedPath
    }
    $config.chrome.paths = $expandedPaths
    
    Write-Log "Configuration loaded successfully" -Level "SUCCESS"
    return $config
}

function Stop-AllChromeProcesses {
    Write-Log "Stopping all Chrome processes..."
    
    try {
        $chromeProcesses = Get-Process chrome -ErrorAction SilentlyContinue
        
        if ($chromeProcesses) {
            $count = $chromeProcesses.Count
            Write-Log "Found $count Chrome process(es). Terminating..." -Level "WARN"
            
            # First try graceful shutdown
            $chromeProcesses | ForEach-Object {
                $_.CloseMainWindow() | Out-Null
            }
            Start-Sleep -Seconds 2
            
            # Force kill any remaining
            $remainingProcesses = Get-Process chrome -ErrorAction SilentlyContinue
            if ($remainingProcesses) {
                Stop-Process -Name chrome -Force -ErrorAction SilentlyContinue
                Start-Sleep -Seconds 2
            }
            
            Write-Log "Chrome processes terminated" -Level "SUCCESS"
        } else {
            Write-Log "No Chrome processes found"
        }
    } catch {
        Write-Log "Error stopping Chrome processes: $_" -Level "ERROR"
        # Continue anyway - the process might have already terminated
    }
}

function Find-ChromeExecutable {
    param([string[]]$Paths)
    
    Write-Log "Locating Chrome executable..."
    
    foreach ($path in $Paths) {
        Write-Log "Checking: $path" -Level "DEBUG"
        if (Test-Path $path) {
            Write-Log "Found Chrome at: $path" -Level "SUCCESS"
            return $path
        }
    }
    
    throw "Chrome executable not found in any of the configured paths"
}

function Start-ChromeWithDebug {
    param(
        [string]$ChromePath,
        [PSCustomObject]$Config
    )
    
    Write-Log "Starting Chrome with remote debugging..."
    
    # Create clean temp directory
    $tempDir = Join-Path $env:TEMP "chrome-debug-profile-$(Get-Date -Format 'yyyyMMddHHmmss')"
    if (Test-Path $tempDir) {
        Remove-Item -Path $tempDir -Recurse -Force
    }
    New-Item -ItemType Directory -Path $tempDir -Force | Out-Null
    Write-Log "Created temp profile directory: $tempDir" -Level "DEBUG"
    
    # Build Chrome arguments
    $chromeArgs = @()
    foreach ($flag in $Config.chrome.flags) {
        $expandedFlag = $flag -replace '{{debugPort}}', $Config.chrome.debugPort
        $expandedFlag = $expandedFlag -replace '{{tempDir}}', $tempDir
        $chromeArgs += $expandedFlag
    }
    $chromeArgs += $Config.otter.loginUrl
    
    Write-Log "Chrome arguments:" -Level "DEBUG"
    $chromeArgs | ForEach-Object { Write-Log "  $_" -Level "DEBUG" }
    
    # Start Chrome
    try {
        $chromeProcess = Start-Process -FilePath $ChromePath -ArgumentList $chromeArgs -PassThru
        Write-Log "Chrome started with PID: $($chromeProcess.Id)" -Level "SUCCESS"
        return @{
            Process = $chromeProcess
            TempDir = $tempDir
        }
    } catch {
        throw "Failed to start Chrome: $_"
    }
}

function Wait-ForChromeDebugger {
    param(
        [int]$Port,
        [int]$MaxRetries,
        [int]$RetryDelay
    )
    
    Write-Log "Waiting for Chrome DevTools to be ready on port $Port..."
    
    $retryCount = 0
    $targetFound = $false
    $targetInfo = $null
    
    while (!$targetFound -and $retryCount -lt $MaxRetries) {
        Start-Sleep -Milliseconds $RetryDelay
        
        try {
            $response = Invoke-WebRequest -Uri "http://localhost:$Port/json/list" -UseBasicParsing -TimeoutSec 2
            
            if ($response.StatusCode -eq 200) {
                $targets = $response.Content | ConvertFrom-Json
                $pageTargets = $targets | Where-Object { $_.type -eq "page" -and $_.url -notlike "chrome://*" }
                
                if ($pageTargets) {
                    $targetInfo = $pageTargets[0]
                    Write-Log "Chrome DevTools ready!" -Level "SUCCESS"
                    Write-Log "Target found: $($targetInfo.title) - $($targetInfo.url)" -Level "SUCCESS"
                    Write-Log "WebSocket URL: $($targetInfo.webSocketDebuggerUrl)" -Level "DEBUG"
                    $targetFound = $true
                } else {
                    Write-Log "Waiting for page target... ($retryCount/$MaxRetries)" -Level "DEBUG"
                }
            }
        } catch {
            Write-Log "DevTools not ready yet... ($retryCount/$MaxRetries)" -Level "DEBUG"
        }
        
        $retryCount++
    }
    
    if (!$targetFound) {
        throw "Chrome DevTools failed to become ready after $MaxRetries attempts"
    }
    
    return $targetInfo
}

function Start-MCPServer {
    param([string]$WorkingDirectory)
    
    Write-Log "Starting MCP server..."
    
    # Check if npm dependencies are installed
    $nodeModulesPath = Join-Path $WorkingDirectory "node_modules"
    if (!(Test-Path $nodeModulesPath)) {
        Write-Log "Installing npm dependencies..." -Level "WARN"
        Push-Location $WorkingDirectory
        try {
            npm install
        } finally {
            Pop-Location
        }
    }
    
    # Start MCP server in background
    Push-Location $WorkingDirectory
    try {
        $mcpProcess = Start-Process -FilePath "npm" -ArgumentList "start" -PassThru -WindowStyle Hidden
        Write-Log "MCP server started with PID: $($mcpProcess.Id)" -Level "SUCCESS"
        
        # Give it time to initialize
        Start-Sleep -Seconds 3
        
        # Verify it's running
        try {
            $health = Invoke-WebRequest -Uri "http://localhost:3001/health" -UseBasicParsing -TimeoutSec 5
            if ($health.StatusCode -eq 200) {
                Write-Log "MCP server health check passed" -Level "SUCCESS"
            }
        } catch {
            Write-Log "MCP server health check failed - server may still be starting" -Level "WARN"
        }
        
        return $mcpProcess
    } finally {
        Pop-Location
    }
}

# Main execution
try {
    Write-Log "=== OTTER BROWSERTOOLS TEST ENVIRONMENT STARTUP ===" -Level "SUCCESS"
    Write-Log "Script Version: 1.0.0"
    Write-Log "PowerShell Version: $($PSVersionTable.PSVersion)"
    Write-Log "OS: $([System.Environment]::OSVersion.VersionString)"
    
    # Load configuration
    $config = Read-Configuration -Path $ConfigPath
    
    # Step 1: Clean environment
    Write-Log "`n[Step 1/5] Cleaning environment..." -Level "SUCCESS"
    Stop-AllChromeProcesses
    
    # Step 2: Find Chrome
    Write-Log "`n[Step 2/5] Locating Chrome executable..." -Level "SUCCESS"
    $chromePath = Find-ChromeExecutable -Paths $config.chrome.paths
    
    # Step 3: Start Chrome
    Write-Log "`n[Step 3/5] Starting Chrome with debugging..." -Level "SUCCESS"
    $chromeInfo = Start-ChromeWithDebug -ChromePath $chromePath -Config $config
    
    # Step 4: Verify Chrome is ready
    Write-Log "`n[Step 4/5] Verifying Chrome DevTools..." -Level "SUCCESS"
    $targetInfo = Wait-ForChromeDebugger `
        -Port $config.chrome.debugPort `
        -MaxRetries $config.chrome.connectionRetries `
        -RetryDelay $config.chrome.retryDelay
    
    # Step 5: Start MCP Server (unless skipped)
    if (!$SkipMCPServer) {
        Write-Log "`n[Step 5/5] Starting MCP server..." -Level "SUCCESS"
        $mcpProcess = Start-MCPServer -WorkingDirectory $PSScriptRoot
    } else {
        Write-Log "`n[Step 5/5] Skipping MCP server (as requested)" -Level "WARN"
    }
    
    # Summary
    Write-Log "`n=== ENVIRONMENT READY ===" -Level "SUCCESS"
    Write-Log "Chrome PID: $($chromeInfo.Process.Id)"
    Write-Log "Chrome Debug Port: $($config.chrome.debugPort)"
    Write-Log "Chrome Temp Profile: $($chromeInfo.TempDir)"
    if (!$SkipMCPServer) {
        Write-Log "MCP Server PID: $($mcpProcess.Id)"
        Write-Log "MCP Server Port: $($config.mcp.port)"
    }
    Write-Log "Target URL: $($targetInfo.url)"
    Write-Log "WebSocket URL: $($targetInfo.webSocketDebuggerUrl)"
    Write-Log "`nLog file: $script:LogFile"
    
    # Keep script running if MCP server was started
    if (!$SkipMCPServer) {
        Write-Log "`nPress Ctrl+C to stop all services..." -Level "WARN"
        try {
            Wait-Process -Id $mcpProcess.Id
        } catch {
            # Process ended or was terminated
        }
    }
    
} catch {
    Write-Log "FATAL ERROR: $_" -Level "ERROR"
    Write-Log $_.ScriptStackTrace -Level "ERROR"
    exit 1
} finally {
    # Cleanup on exit
    Write-Log "`nCleaning up..." -Level "WARN"
    
    if ($chromeInfo -and $chromeInfo.Process -and !$chromeInfo.Process.HasExited) {
        Write-Log "Stopping Chrome..." -Level "WARN"
        Stop-Process -Id $chromeInfo.Process.Id -Force -ErrorAction SilentlyContinue
    }
    
    if ($mcpProcess -and !$mcpProcess.HasExited) {
        Write-Log "Stopping MCP server..." -Level "WARN"
        Stop-Process -Id $mcpProcess.Id -Force -ErrorAction SilentlyContinue
    }
    
    Write-Log "Cleanup complete" -Level "SUCCESS"
}
