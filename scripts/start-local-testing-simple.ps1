# Simple Local Testing Environment Startup Script
# Usage: .\scripts\start-local-testing-simple.ps1

param(
    [switch]$SkipBuild,
    [switch]$SkipWebSocket,
    [int]$PhpPort = 8000
)

Write-Host "Starting Local Testing Environment..." -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Green

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

# Phase 2: Server Management
Write-Host "`n2. Managing servers..." -ForegroundColor Yellow

# Kill existing PHP processes
$phpProcesses = Get-Process -Name "php" -ErrorAction SilentlyContinue
if ($phpProcesses) {
    $phpProcesses | Stop-Process -Force
    Write-Host "   Stopped existing PHP processes" -ForegroundColor Green
    Start-Sleep -Seconds 2
}

# Start PHP server
Write-Host "   Starting PHP server on port $PhpPort..." -ForegroundColor Gray
try {
    $phpProcess = Start-Process -FilePath "php" -ArgumentList "-S localhost:$PhpPort" -WindowStyle Hidden -PassThru
    Start-Sleep -Seconds 3
    
    # Test PHP server
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:$PhpPort/health_check.php" -TimeoutSec 5 -UseBasicParsing
        Write-Host "   PHP server started successfully" -ForegroundColor Green
    } catch {
        Write-Host "   PHP server started but health check failed" -ForegroundColor Yellow
    }
} catch {
    Write-Host "   Failed to start PHP server" -ForegroundColor Red
    $errors += "PHP server startup failed"
}

# Phase 3: Build Process (optional)
if (-not $SkipBuild) {
    Write-Host "`n3. Building reports..." -ForegroundColor Blue
    
    # Install dependencies
    Write-Host "   Installing npm dependencies..." -ForegroundColor Gray
    try {
        npm ci 2>&1 | Out-Null
        Write-Host "   npm dependencies installed" -ForegroundColor Green
    } catch {
        Write-Host "   npm ci failed, trying npm install..." -ForegroundColor Yellow
        npm install 2>&1 | Out-Null
    }
    
    # Build reports
    Write-Host "   Building reports bundle..." -ForegroundColor Gray
    try {
        npm run build:reports 2>&1 | Out-Null
        if (Test-Path "reports/dist/reports.bundle.js") {
            Write-Host "   Reports bundle built successfully" -ForegroundColor Green
        } else {
            Write-Host "   Build completed but bundle not found" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "   Build failed" -ForegroundColor Red
        $errors += "Build failed"
    }
} else {
    Write-Host "`n3. Skipping build process" -ForegroundColor Yellow
}

# Phase 4: Testing Preparation
Write-Host "`n4. Preparing for testing..." -ForegroundColor Magenta

# Health checks
$healthChecks = @(
    @{Name="PHP Server"; Url="http://localhost:$PhpPort/health_check.php"},
    @{Name="Login Page"; Url="http://localhost:$PhpPort/login.php"},
    @{Name="Reports Page"; Url="http://localhost:$PhpPort/reports/index.php"}
)

foreach ($check in $healthChecks) {
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

Write-Host "`nLocal Testing Environment Setup Complete!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Green
Write-Host "Setup completed in $([math]::Round($duration.TotalSeconds, 1)) seconds" -ForegroundColor Gray

Write-Host "`nAccess Points:" -ForegroundColor Cyan
Write-Host "   Main Application: http://localhost:$PhpPort" -ForegroundColor White
Write-Host "   Login Page: http://localhost:$PhpPort/login.php" -ForegroundColor White
Write-Host "   Reports: http://localhost:$PhpPort/reports/index.php" -ForegroundColor White
Write-Host "   Health Check: http://localhost:$PhpPort/health_check.php" -ForegroundColor White

Write-Host "`nTesting Commands:" -ForegroundColor Cyan
Write-Host "   Run all tests: php run_tests.php" -ForegroundColor White
Write-Host "   Test specific: php run_tests.php csu" -ForegroundColor White

if ($errors.Count -gt 0) {
    Write-Host "`nIssues encountered:" -ForegroundColor Yellow
    $errors | ForEach-Object { Write-Host "   - $_" -ForegroundColor Yellow }
}

Write-Host "`nTo stop servers: taskkill /F /IM php.exe" -ForegroundColor Red
Write-Host "`nLocal testing environment is ready!" -ForegroundColor Green
