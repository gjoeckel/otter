# Robust Chrome launcher for Otter BrowserTools MCP
# Implements proper process management and connection verification

param(
    [string]$StartUrl = "http://localhost:8000/login.php",
    [int]$DebugPort = 9222,
    [int]$MaxRetries = 10
)

Write-Host "=== ROBUST CHROME DEBUG LAUNCHER ===" -ForegroundColor Cyan
Write-Host "Target URL: $StartUrl" -ForegroundColor Yellow
Write-Host "Debug Port: $DebugPort" -ForegroundColor Yellow

# Step 1: Kill all existing Chrome processes
Write-Host "`n[1/3] Terminating existing Chrome processes..." -ForegroundColor Yellow
try {
    $chromeProcesses = Get-Process chrome -ErrorAction SilentlyContinue
    if ($chromeProcesses) {
        Write-Host "Found $($chromeProcesses.Count) Chrome processes. Terminating..." -ForegroundColor Red
        # Use taskkill for forceful termination
        & taskkill /F /IM chrome.exe 2>&1 | Out-Null
        Start-Sleep -Seconds 2
        Write-Host "Chrome processes terminated." -ForegroundColor Green
    } else {
        Write-Host "No Chrome processes found." -ForegroundColor Green
    }
} catch {
    Write-Host "Error terminating Chrome: $_" -ForegroundColor Red
}

# Step 2: Find Chrome executable
Write-Host "`n[2/3] Locating Chrome executable..." -ForegroundColor Yellow
$chromePaths = @(
    "${env:ProgramFiles}\Google\Chrome\Application\chrome.exe",
    "${env:ProgramFiles(x86)}\Google\Chrome\Application\chrome.exe",
    "${env:LocalAppData}\Google\Chrome\Application\chrome.exe"
)

$chromePath = $null
foreach ($path in $chromePaths) {
    if (Test-Path $path) {
        $chromePath = $path
        Write-Host "Found Chrome at: $chromePath" -ForegroundColor Green
        break
    }
}

if (-not $chromePath) {
    Write-Host "ERROR: Chrome not found in standard locations!" -ForegroundColor Red
    exit 1
}

# Step 3: Launch Chrome with debug flags and target URL
Write-Host "`n[3/3] Launching Chrome with remote debugging..." -ForegroundColor Yellow

# Create user data directory for clean profile
$debugDir = "$env:TEMP\chrome-debug-profile"
if (Test-Path $debugDir) {
    Remove-Item -Path $debugDir -Recurse -Force
}
New-Item -ItemType Directory -Path $debugDir -Force | Out-Null

$chromeArgs = @(
    "--remote-debugging-port=$DebugPort",
    "--user-data-dir=$debugDir",
    "--disable-web-security",
    "--disable-features=VizDisplayCompositor",
    "--no-first-run",
    "--no-default-browser-check",
    "--disable-popup-blocking",
    "--disable-translate",
    "--disable-background-timer-throttling",
    "--disable-renderer-backgrounding",
    "--disable-device-discovery-notifications",
    $StartUrl  # Open directly to the target URL
)

Write-Host "Starting Chrome with arguments:" -ForegroundColor Gray
$chromeArgs | ForEach-Object { Write-Host "  $_" -ForegroundColor Gray }

Start-Process -FilePath $chromePath -ArgumentList $chromeArgs

# Step 4: Verify debug connection is ready
Write-Host "`nVerifying Chrome DevTools connection..." -ForegroundColor Yellow
$connected = $false
$retryCount = 0

while (-not $connected -and $retryCount -lt $MaxRetries) {
    Start-Sleep -Seconds 1
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$DebugPort/json/list" -UseBasicParsing -TimeoutSec 2
        if ($response.StatusCode -eq 200) {
            $tabs = $response.Content | ConvertFrom-Json
            $targetTab = $tabs | Where-Object { $_.url -like "*$StartUrl*" -or $_.url -like "*localhost:8000*" }
            
            if ($targetTab) {
                Write-Host "`nChrome DevTools ready!" -ForegroundColor Green
                Write-Host "Target tab found:" -ForegroundColor Green
                Write-Host "  Title: $($targetTab.title)" -ForegroundColor White
                Write-Host "  URL: $($targetTab.url)" -ForegroundColor White
                Write-Host "  WebSocket URL: $($targetTab.webSocketDebuggerUrl)" -ForegroundColor Cyan
                $connected = $true
            } else {
                Write-Host "." -NoNewline -ForegroundColor Yellow
            }
        }
    } catch {
        Write-Host "." -NoNewline -ForegroundColor Yellow
    }
    $retryCount++
}

if (-not $connected) {
    Write-Host "`nWARNING: Could not verify Chrome DevTools connection!" -ForegroundColor Red
    Write-Host "Chrome may still be starting up. Try checking manually:" -ForegroundColor Yellow
    Write-Host "  http://localhost:$DebugPort/json/list" -ForegroundColor White
} else {
    Write-Host "`n=== CHROME READY FOR MCP CONNECTION ===" -ForegroundColor Green
    Write-Host "Next steps:" -ForegroundColor Yellow
    Write-Host "1. The MCP server should connect to the WebSocket URL shown above" -ForegroundColor White
    Write-Host "2. Use the MCP server to inspect cookies after login" -ForegroundColor White
    Write-Host "3. Monitor the Network tab for session management issues" -ForegroundColor White
}
