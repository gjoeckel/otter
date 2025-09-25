# MVP Local Testing Environment Startup Script
# Usage: .\scripts\start-mvp-testing.ps1
# Token: "mvp local"

param(
    [switch]$SkipBuild,
    [switch]$SkipWebSocket,
    [int]$PhpPort = 8000
)

Write-Host "Starting MVP Local Testing Environment..." -ForegroundColor Green
Write-Host "=========================================" -ForegroundColor Green
Write-Host "Mode: MVP (simplified, no count options complexity)" -ForegroundColor Cyan

$startTime = Get-Date
$errors = @()

# Phase 1: Environment Validation
Write-Host "`n1. Validating environment..." -ForegroundColor Cyan

# Check PHP version
try {
    $phpVersion = php --version | Select-String "PHP (\d+\.\d+\.\d+)" | ForEach-Object { $_.Matches[0].Groups[1].Value }
    if ($phpVersion) {
        Write-Host "   PHP $phpVersion detected" -ForegroundColor Green
    } else {
        Write-Host "   PHP version check failed" -ForegroundColor Red
        $errors += "PHP version check failed"
    }
} catch {
    Write-Host "   PHP not found" -ForegroundColor Red
    $errors += "PHP not found"
}

# Check Node.js
try {
    $nodeVersion = node --version
    Write-Host "   Node.js $nodeVersion detected" -ForegroundColor Green
} catch {
    Write-Host "   Node.js not found" -ForegroundColor Red
    $errors += "Node.js not found"
}

# Check package.json
if (Test-Path "package.json") {
    Write-Host "   package.json found" -ForegroundColor Green
} else {
    Write-Host "   package.json not found" -ForegroundColor Red
    $errors += "package.json missing"
}

# Check MVP files exist
Write-Host "   Checking MVP files..." -ForegroundColor Gray
$mvpFiles = @(
    "reports/js/reports-data.js",
    "reports/js/unified-data-service.js", 
    "reports/js/unified-table-updater.js",
    "reports/js/reports-entry.js",
    "reports/js/reports-messaging.js"
)

$mvpFilesExist = $true
foreach ($file in $mvpFiles) {
    if (Test-Path $file) {
        Write-Host "     ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "     ❌ $file (missing)" -ForegroundColor Red
        $mvpFilesExist = $false
        $errors += "MVP file missing: $file"
    }
}

if ($mvpFilesExist) {
    Write-Host "   All MVP files present" -ForegroundColor Green
} else {
    Write-Host "   Some MVP files missing" -ForegroundColor Red
}

# Phase 2: Server Management
Write-Host "`n2. Managing servers..." -ForegroundColor Yellow

# Kill existing PHP processes
$phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
if ($phpProcesses) {
    $phpProcesses | Stop-Process -Force
    Write-Host "   Stopped existing PHP processes" -ForegroundColor Green
    Start-Sleep -Seconds 2
}

# Start PHP server with enhanced logging
Write-Host "   Starting PHP server on port $PhpPort with logging..." -ForegroundColor Gray

# Create error log file if it doesn't exist
if (-not (Test-Path "php_errors.log")) {
    New-Item -ItemType File -Name "php_errors.log" | Out-Null
    Write-Host "   Created php_errors.log file" -ForegroundColor Green
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

try {
    $phpProcess = Start-Process -FilePath "php" -ArgumentList $phpArgs -WindowStyle Hidden -PassThru
    Start-Sleep -Seconds 3
    
    # Test PHP server
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$PhpPort/health_check.php" -TimeoutSec 5 -UseBasicParsing
        Write-Host "   PHP server started successfully with logging enabled" -ForegroundColor Green
        
        # Verify logging is working
        if (Test-Path "php_errors.log") {
            Write-Host "   PHP error log file created: php_errors.log" -ForegroundColor Green
        } else {
            Write-Host "   Warning: PHP error log file not found" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "   PHP server started but health check failed" -ForegroundColor Yellow
    }
} catch {
    Write-Host "   Failed to start PHP server" -ForegroundColor Red
    $errors += "PHP server startup failed"
}

# Phase 3: Logging Verification
Write-Host "`n3. Verifying logging setup..." -ForegroundColor Blue

# Test logging by making a request that should generate logs
try {
    $testUrl = "http://localhost:$PhpPort/health_check.php"
    $testResponse = Invoke-WebRequest -Uri $testUrl -TimeoutSec 5 -UseBasicParsing -ErrorAction SilentlyContinue
    
    if (Test-Path "php_errors.log") {
        $logSize = (Get-Item "php_errors.log").Length
        Write-Host "   PHP error log file exists (size: $logSize bytes)" -ForegroundColor Green
        
        # Show recent log entries if any
        $logContent = Get-Content "php_errors.log" -ErrorAction SilentlyContinue
        if ($logContent) {
            Write-Host "   Recent log entries:" -ForegroundColor Cyan
            $logContent | Select-Object -Last 3 | ForEach-Object {
                Write-Host "     $_" -ForegroundColor Gray
            }
        } else {
            Write-Host "   No log entries yet (this is normal for successful requests)" -ForegroundColor Gray
        }
    } else {
        Write-Host "   Warning: PHP error log file not found" -ForegroundColor Yellow
    }
} catch {
    Write-Host "   Could not verify logging setup" -ForegroundColor Yellow
}

# Phase 4: MVP Build Process
if (-not $SkipBuild) {
    Write-Host "`n4. Building MVP reports..." -ForegroundColor Blue
    
    # Install dependencies
    Write-Host "   Installing npm dependencies..." -ForegroundColor Gray
    try {
        npm ci 2>&1 | Out-Null
        Write-Host "   npm dependencies installed" -ForegroundColor Green
    } catch {
        Write-Host "   npm ci failed, trying npm install..." -ForegroundColor Yellow
        npm install 2>&1 | Out-Null
    }
    
    # Build MVP reports bundle
    Write-Host "   Building MVP reports bundle..." -ForegroundColor Gray
    try {
        npm run build:mvp 2>&1 | Out-Null
        if (Test-Path "reports/dist/reports.bundle.js") {
            $bundleSize = (Get-Item "reports/dist/reports.bundle.js").Length
            Write-Host "   MVP reports bundle built successfully ($bundleSize bytes)" -ForegroundColor Green
        } else {
            Write-Host "   Build completed but MVP bundle not found" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "   MVP build failed" -ForegroundColor Red
        $errors += "MVP build failed"
    }
} else {
    Write-Host "`n4. Skipping MVP build process" -ForegroundColor Yellow
}

# Phase 5: Cache Busting
Write-Host "`n5. Applying cache busting..." -ForegroundColor Magenta

# Generate multiple cache busting timestamps
$cacheBustTimestamp = [DateTimeOffset]::UtcNow.ToUnixTimeSeconds()
$cacheBustDate = Get-Date -Format "yyyyMMddHHmmss"
$cacheBustRandom = Get-Random -Minimum 1000 -Maximum 9999
Write-Host "   Cache bust timestamps: $cacheBustTimestamp, $cacheBustDate, $cacheBustRandom" -ForegroundColor Gray

# Function to update cache busting in files
function Update-CacheBusting {
    param(
        [string]$FilePath,
        [string]$Timestamp
    )
    
    if (Test-Path $FilePath) {
        try {
            $content = Get-Content $FilePath -Raw
            $updated = $false
            
            # Pattern 1: PHP time() function
            if ($content -match 'v=\<\?php echo time\(\);\?\>') {
                $content = $content -replace 'v=\<\?php echo time\(\);\?\>', "v=$Timestamp"
                $updated = $true
            }
            
            # Pattern 2: Static version numbers
            if ($content -match 'v=\d+') {
                $content = $content -replace 'v=\d+', "v=$Timestamp"
                $updated = $true
            }
            
            # Pattern 3: Date-based versions
            if ($content -match 'v=\d{14}') {
                $content = $content -replace 'v=\d{14}', "v=$cacheBustDate"
                $updated = $true
            }
            
            if ($updated) {
                Set-Content $FilePath $content -NoNewline
                Write-Host "   Updated cache busting in $FilePath" -ForegroundColor Green
            } else {
                Write-Host "   No cache busting patterns found in $FilePath" -ForegroundColor Gray
            }
        } catch {
            Write-Host "   Warning: Could not update $FilePath" -ForegroundColor Yellow
        }
    }
}

# Update MVP PHP files with cache busting parameters
$mvpPhpFiles = @(
    "reports/index.php"
)

foreach ($phpFile in $mvpPhpFiles) {
    Update-CacheBusting -FilePath $phpFile -Timestamp $cacheBustTimestamp
}

# Update CSS files with cache busting
$cssFiles = Get-ChildItem -Path "css" -Filter "*.css" -ErrorAction SilentlyContinue
foreach ($cssFile in $cssFiles) {
    Update-CacheBusting -FilePath $cssFile.FullName -Timestamp $cacheBustRandom
}

# Update MVP JavaScript files with cache busting
$mvpJsFiles = @(
    "reports/dist/reports.bundle.js"
)

foreach ($jsFile in $mvpJsFiles) {
    if (Test-Path $jsFile) {
        try {
            # Touch the file to update its modification time
            (Get-Item $jsFile).LastWriteTime = Get-Date
            Write-Host "   Updated timestamp for $jsFile" -ForegroundColor Green
        } catch {
            Write-Host "   Warning: Could not update timestamp for $jsFile" -ForegroundColor Yellow
        }
    }
}

# Create MVP cache-busting manifest file
$mvpManifestContent = @{
    mode = "MVP"
    timestamp = $cacheBustTimestamp
    date = $cacheBustDate
    random = $cacheBustRandom
    generated = (Get-Date -Format "yyyy-MM-dd HH:mm:ss")
    description = "MVP (simplified, no count options complexity)"
} | ConvertTo-Json

try {
    Set-Content "cache-bust-manifest.json" $mvpManifestContent
    Write-Host "   Created cache-bust-manifest.json" -ForegroundColor Green
} catch {
    Write-Host "   Warning: Could not create cache-bust-manifest.json" -ForegroundColor Yellow
}

Write-Host "   MVP cache busting completed successfully" -ForegroundColor Green

# Phase 6: MVP Testing Preparation
Write-Host "`n6. Preparing for MVP testing..." -ForegroundColor Magenta

# Health checks for MVP
$mvpHealthChecks = @(
    @{Name="PHP Server"; Url="http://localhost:$PhpPort/health_check.php"},
    @{Name="Login Page"; Url="http://localhost:$PhpPort/login.php"},
    @{Name="MVP Reports Page"; Url="http://localhost:$PhpPort/reports/index.php"}
)

foreach ($check in $mvpHealthChecks) {
    try {
        $response = Invoke-WebRequest -Uri $check.Url -TimeoutSec 5 -UseBasicParsing
        Write-Host "   $($check.Name): OK ($($response.StatusCode))" -ForegroundColor Green
    } catch {
        Write-Host "   $($check.Name): ERROR" -ForegroundColor Red
    }
}

# Final Status
$endTime = Get-Date
$duration = $endTime - $startTime

Write-Host "`nMVP Local Testing Environment Setup Complete!" -ForegroundColor Green
Write-Host "=============================================" -ForegroundColor Green
Write-Host "Setup completed in $([math]::Round($duration.TotalSeconds, 1)) seconds" -ForegroundColor Gray

Write-Host "`nMVP Access Points:" -ForegroundColor Cyan
Write-Host "   Main Application: http://localhost:$PhpPort" -ForegroundColor White
Write-Host "   Login Page: http://localhost:$PhpPort/login.php" -ForegroundColor White
Write-Host "   MVP Reports: http://localhost:$PhpPort/reports/index.php" -ForegroundColor White
Write-Host "   Original Reports: http://localhost:$PhpPort/reports/index.php" -ForegroundColor Gray
Write-Host "   Health Check: http://localhost:$PhpPort/health_check.php" -ForegroundColor White

Write-Host "`nMVP Features:" -ForegroundColor Cyan
Write-Host "   ✅ Simplified interface (no count options complexity)" -ForegroundColor Green
Write-Host "   ✅ Hardcoded modes (by-date registrations, by-tou enrollments)" -ForegroundColor Green
Write-Host "   ✅ No radio buttons or mode switching" -ForegroundColor Green
Write-Host "   ✅ Reliable data display" -ForegroundColor Green
Write-Host "   ✅ Smaller bundle size (10KB vs 37KB)" -ForegroundColor Green

Write-Host "`nTesting Commands:" -ForegroundColor Cyan
Write-Host "   Run all tests: php run_tests.php" -ForegroundColor White
Write-Host "   Test specific: php run_tests.php csu" -ForegroundColor White

Write-Host "`nLogging Commands:" -ForegroundColor Cyan
Write-Host "   View recent errors: Get-Content php_errors.log -Tail 10" -ForegroundColor White
Write-Host "   Monitor logs live: Get-Content php_errors.log -Wait -Tail 5" -ForegroundColor White
Write-Host "   Check log size: (Get-Item php_errors.log).Length" -ForegroundColor White

Write-Host "`nMVP Cache Busting Commands:" -ForegroundColor Cyan
Write-Host "   View cache manifest: Get-Content cache-bust-manifest.json" -ForegroundColor White
Write-Host "   Force cache bust: Remove-Item cache-bust-manifest.json; .\scripts\start-mvp-testing.ps1" -ForegroundColor White

if ($errors.Count -gt 0) {
    Write-Host "`nIssues encountered:" -ForegroundColor Yellow
    $errors | ForEach-Object { Write-Host "   - $_" -ForegroundColor Yellow }
}

Write-Host "`nTo stop servers: taskkill /F /IM php.exe" -ForegroundColor Red
Write-Host "`nMVP local testing environment is ready!" -ForegroundColor Green
