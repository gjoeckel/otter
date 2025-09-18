# Enhanced Local Testing Environment Startup Script
# Provides comprehensive environment preparation for local testing
# Usage: "start local testing" or ./scripts/start-local-testing.ps1

param(
    [switch]$SkipBuild,
    [switch]$SkipWebSocket,
    [switch]$SkipValidation,
    [int]$PhpPort = 8000,
    [int]$WebSocketPort = 8080,
    [switch]$Verbose
)

# Color functions for better output
function Write-ColorOutput {
    param([string]$Message, [string]$Color = "White")
    Write-Host $Message -ForegroundColor $Color
}

function Write-Progress {
    param([string]$Message, [int]$Percent = 0)
    Write-Progress -Activity "Local Testing Environment Setup" -Status $Message -PercentComplete $Percent
}

function Write-Step {
    param([string]$Step, [string]$Message)
    Write-ColorOutput "`n🔧 $Step" "Cyan"
    Write-ColorOutput "   $Message" "Gray"
}

function Write-Success {
    param([string]$Message)
    Write-ColorOutput "✅ $Message" "Green"
}

function Write-Warning {
    param([string]$Message)
    Write-ColorOutput "⚠️  $Message" "Yellow"
}

function Write-Error {
    param([string]$Message)
    Write-ColorOutput "❌ $Message" "Red"
}

# Main execution starts here
Write-ColorOutput "`n🚀 STARTING LOCAL TESTING ENVIRONMENT" "Green"
Write-ColorOutput "===========================================" "Green"
Write-ColorOutput "Time: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" "Gray"
Write-ColorOutput "PHP Port: $PhpPort | WebSocket Port: $WebSocketPort" "Gray"

$startTime = Get-Date
$errors = @()

# PHASE 1: Environment Validation
if (-not $SkipValidation) {
    Write-Progress "Validating environment..." 10
    
    Write-Step "Environment Validation" "Checking dependencies and configuration"
    
    # Check PHP version
    try {
        $phpVersion = php --version | Select-String "PHP (\d+\.\d+\.\d+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }
        if ($phpVersion -and [version]$phpVersion -ge [version]"8.4.6") {
            Write-Success "PHP $phpVersion detected (8.4.6+ required)"
        } else {
            Write-Error "PHP 8.4.6+ required, found: $phpVersion"
            $errors += "PHP version check failed"
        }
    } catch {
        Write-Error "PHP not found or not accessible"
        $errors += "PHP not found"
    }
    
    # Check Node.js and npm
    try {
        $nodeVersion = node --version
        $npmVersion = npm --version
        Write-Success "Node.js $nodeVersion and npm $npmVersion detected"
    } catch {
        Write-Error "Node.js or npm not found"
        $errors += "Node.js/npm not found"
    }
    
    # Validate package.json exists
    if (Test-Path "package.json") {
        Write-Success "package.json found"
    } else {
        Write-Error "package.json not found"
        $errors += "package.json missing"
    }
    
    # Check critical config files
    $requiredConfigs = @("config/csu.config", "config/ccc.config", "config/demo.config")
    foreach ($config in $requiredConfigs) {
        if (Test-Path $config) {
            Write-Success "Config file found: $config"
        } else {
            Write-Warning "Config file missing: $config"
        }
    }
    
    # Clean cache directories
    Write-Step "Cache Cleanup" "Clearing stale cache data"
    $cacheDirs = @("cache/ccc", "cache/csu", "cache/demo")
    foreach ($dir in $cacheDirs) {
        if (Test-Path $dir) {
            Remove-Item "$dir/*" -Recurse -Force -ErrorAction SilentlyContinue
            Write-Success "Cleared cache: $dir"
        }
    }
    
    if ($errors.Count -gt 0) {
        Write-ColorOutput "`n❌ Validation failed with $($errors.Count) error(s):" "Red"
        $errors | ForEach-Object { Write-ColorOutput "   - $_" "Red" }
        Write-ColorOutput "`nContinue anyway? (y/N): " "Yellow" -NoNewline
        $continue = Read-Host
        if ($continue -ne 'y' -and $continue -ne 'Y') {
            exit 1
        }
    } else {
        Write-Success "Environment validation completed successfully"
    }
}

# PHASE 2: Server Management
Write-Progress "Managing servers..." 30

Write-Step "Server Management" "Stopping existing processes and starting servers"

# Kill existing PHP processes
Write-ColorOutput "   Stopping existing PHP processes..." "Gray"
$phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
if ($phpProcesses) {
    $phpProcesses | Stop-Process -Force
    Write-Success "Stopped $($phpProcesses.Count) PHP process(es)"
    Start-Sleep -Seconds 2
} else {
    Write-Success "No existing PHP processes found"
}

# Check port availability
function Test-Port {
    param([int]$Port)
    try {
        $connection = Test-NetConnection -ComputerName localhost -Port $Port -InformationLevel Quiet -WarningAction SilentlyContinue
        return -not $connection
    } catch {
        return $true
    }
}

# Start PHP server
Write-ColorOutput "   Starting PHP server on port $PhpPort..." "Gray"
if (-not (Test-Port $PhpPort)) {
    Write-Warning "Port $PhpPort is in use, attempting to free it..."
    netstat -ano | findstr ":$PhpPort" | ForEach-Object {
        $pid = ($_ -split '\s+')[-1]
        if ($pid -match '^\d+$') {
            Stop-Process -Id $pid -Force -ErrorAction SilentlyContinue
        }
    }
    Start-Sleep -Seconds 3
}

# Create error log file if it doesn't exist
if (-not (Test-Path "php_errors.log")) {
    New-Item -ItemType File -Name "php_errors.log" | Out-Null
}

# Start PHP server with enhanced configuration
$phpArgs = @(
    "-S", "localhost:$PhpPort",
    "-d", "error_reporting=E_ALL",
    "-d", "log_errors=1", 
    "-d", "error_log=php_errors.log",
    "-d", "display_errors=1",
    "-d", "display_startup_errors=1"
)

if ($Verbose) {
    $phpArgs += "-d", "display_errors=1"
    Write-ColorOutput "   Starting with verbose logging..." "Cyan"
}

try {
    $phpProcess = Start-Process -FilePath "php" -ArgumentList $phpArgs -WindowStyle Hidden -PassThru
    Start-Sleep -Seconds 3
    
    # Test PHP server
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$PhpPort/health_check.php" -TimeoutSec 5 -UseBasicParsing
        if ($response.StatusCode -eq 200) {
            Write-Success "PHP server started successfully on http://localhost:$PhpPort"
        } else {
            Write-Warning "PHP server started but health check returned status: $($response.StatusCode)"
        }
    } catch {
        Write-Warning "PHP server started but health check failed: $($_.Exception.Message)"
    }
} catch {
    Write-Error "Failed to start PHP server: $($_.Exception.Message)"
    $errors += "PHP server startup failed"
}

# Start WebSocket server (optional)
if (-not $SkipWebSocket) {
    Write-ColorOutput "   Starting WebSocket server on port $WebSocketPort..." "Gray"
    if (Test-Port $WebSocketPort) {
        try {
            $wsProcess = Start-Process -FilePath "php" -ArgumentList "lib/websocket/websocket-server.php" -WindowStyle Hidden -PassThru
            Start-Sleep -Seconds 2
            Write-Success "WebSocket server started on port $WebSocketPort"
        } catch {
            Write-Warning "Failed to start WebSocket server: $($_.Exception.Message)"
        }
    } else {
        Write-Warning "WebSocket port $WebSocketPort is in use, skipping WebSocket server"
    }
}

# PHASE 3: Build Process
if (-not $SkipBuild) {
    Write-Progress "Building reports..." 60
    
    Write-Step "Build Process" "Installing dependencies and building reports bundle"
    
    # Install/update npm dependencies
    Write-ColorOutput "   Installing npm dependencies..." "Gray"
    try {
        $npmOutput = npm ci 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "npm dependencies installed successfully"
        } else {
            Write-Warning "npm ci failed, trying npm install..."
            $npmOutput = npm install 2>&1
            if ($LASTEXITCODE -eq 0) {
                Write-Success "npm dependencies installed with npm install"
            } else {
                Write-Error "npm dependency installation failed"
                $errors += "npm dependency installation failed"
            }
        }
    } catch {
        Write-Error "npm command failed: $($_.Exception.Message)"
        $errors += "npm command failed"
    }
    
    # Build reports bundle
    Write-ColorOutput "   Building reports bundle..." "Gray"
    try {
        $buildOutput = npm run build:reports 2>&1
        if ($LASTEXITCODE -eq 0) {
            Write-Success "Reports bundle built successfully"
            
            # Verify build output
            if (Test-Path "reports/dist/reports.bundle.js") {
                $fileSize = (Get-Item "reports/dist/reports.bundle.js").Length
                Write-Success "Build output verified: reports.bundle.js ($([math]::Round($fileSize/1KB, 1)) KB)"
            } else {
                Write-Warning "Build completed but reports.bundle.js not found"
            }
        } else {
            Write-Error "Reports build failed"
            Write-ColorOutput "Build output:" "Red"
            Write-ColorOutput $buildOutput "Red"
            $errors += "Reports build failed"
        }
    } catch {
        Write-Error "Build command failed: $($_.Exception.Message)"
        $errors += "Build command failed"
    }
} else {
    Write-ColorOutput "`n⏭️  Skipping build process" "Yellow"
}

# PHASE 4: Testing Preparation
Write-Progress "Preparing for testing..." 80

Write-Step "Testing Preparation" "Running health checks and validation"

# Run comprehensive health checks
$healthChecks = @(
    @{Name="PHP Server"; Url="http://localhost:$PhpPort/health_check.php"; ExpectedStatus=200},
    @{Name="Login Page"; Url="http://localhost:$PhpPort/login.php"; ExpectedStatus=200},
    @{Name="Reports Page"; Url="http://localhost:$PhpPort/reports/index.php"; ExpectedStatus=200}
)

$healthResults = @()
foreach ($check in $healthChecks) {
    try {
        $response = Invoke-WebRequest -Uri $check.Url -TimeoutSec 5 -UseBasicParsing
        if ($response.StatusCode -eq $check.ExpectedStatus) {
            Write-Success "$($check.Name): OK ($($response.StatusCode))"
            $healthResults += @{Name=$check.Name; Status="OK"; Code=$response.StatusCode}
        } else {
            Write-Warning "$($check.Name): WARNING ($($response.StatusCode), expected $($check.ExpectedStatus))"
            $healthResults += @{Name=$check.Name; Status="WARNING"; Code=$response.StatusCode}
        }
    } catch {
        Write-Error "$($check.Name): ERROR ($($_.Exception.Message))"
        $healthResults += @{Name=$check.Name; Status="ERROR"; Message=$_.Exception.Message}
    }
}

# Check error logs
if (Test-Path "php_errors.log") {
    $logSize = (Get-Item "php_errors.log").Length
    if ($logSize -gt 0) {
        Write-Warning "PHP error log contains $logSize bytes of data"
        if ($Verbose) {
            Write-ColorOutput "Recent errors:" "Yellow"
            Get-Content "php_errors.log" -Tail 5 | ForEach-Object { Write-ColorOutput "   $_" "Red" }
        }
    } else {
        Write-Success "PHP error log is clean"
    }
}

# PHASE 5: Final Status and Instructions
Write-Progress "Setup complete!" 100

$endTime = Get-Date
$duration = $endTime - $startTime

Write-ColorOutput "`n🎉 LOCAL TESTING ENVIRONMENT READY!" "Green"
Write-ColorOutput "===========================================" "Green"
Write-ColorOutput "Setup completed in $([math]::Round($duration.TotalSeconds, 1)) seconds" "Gray"

# Display access information
Write-ColorOutput "`n📱 ACCESS POINTS:" "Cyan"
Write-ColorOutput "   🌐 Main Application: http://localhost:$PhpPort" "White"
Write-ColorOutput "   🔐 Login Page: http://localhost:$PhpPort/login.php" "White"
Write-ColorOutput "   📊 Reports: http://localhost:$PhpPort/reports/index.php" "White"
Write-ColorOutput "   ❤️  Health Check: http://localhost:$PhpPort/health_check.php" "White"

if (-not $SkipWebSocket) {
    Write-ColorOutput "   🔌 WebSocket Console: ws://localhost:$WebSocketPort/console-monitor" "White"
}

Write-ColorOutput "`n📁 IMPORTANT FILES:" "Cyan"
Write-ColorOutput "   📝 Error Log: php_errors.log" "White"
Write-ColorOutput "   📦 Build Output: reports/dist/reports.bundle.js" "White"
Write-ColorOutput "   ⚙️  Config Files: config/*.config" "White"

Write-ColorOutput "`n🧪 TESTING COMMANDS:" "Cyan"
Write-ColorOutput "   Run all tests: php run_tests.php" "White"
Write-ColorOutput "   Test specific enterprise: php run_tests.php csu" "White"
Write-ColorOutput "   View logs: Get-Content php_errors.log -Tail 10" "White"

# Display any errors or warnings
if ($errors.Count -gt 0) {
    Write-ColorOutput "`n⚠️  ISSUES ENCOUNTERED:" "Yellow"
    $errors | ForEach-Object { Write-ColorOutput "   - $_" "Yellow" }
    Write-ColorOutput "`nEnvironment is ready but some issues were noted above." "Yellow"
}

Write-ColorOutput "`n🛑 TO STOP SERVERS:" "Red"
Write-ColorOutput "   Stop PHP server: taskkill /F /IM php.exe" "White"
Write-ColorOutput "   Or use Ctrl+C if running in foreground" "White"

Write-ColorOutput "`nPress any key to continue..." "Gray" -NoNewline
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")

Write-ColorOutput "`n`n✅ Local testing environment is ready for use!" "Green"
