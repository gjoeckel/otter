# Test script for Local Testing Environment
# Validates that all components are working correctly

Write-Host "🧪 Testing Local Testing Environment Components..." -ForegroundColor Green
Write-Host "=================================================" -ForegroundColor Green

$errors = @()
$success = @()

# Test 1: Check if main script exists
Write-Host "`n1. Checking main script existence..." -ForegroundColor Cyan
if (Test-Path "scripts\start-local-testing.ps1") {
    $success += "Main script exists"
    Write-Host "   ✅ start-local-testing.ps1 found" -ForegroundColor Green
} else {
    $errors += "Main script missing"
    Write-Host "   ❌ start-local-testing.ps1 not found" -ForegroundColor Red
}

# Test 2: Check if alias script exists
Write-Host "`n2. Checking alias script existence..." -ForegroundColor Cyan
if (Test-Path "scripts\start-local-testing-alias.ps1") {
    $success += "Alias script exists"
    Write-Host "   ✅ start-local-testing-alias.ps1 found" -ForegroundColor Green
} else {
    $errors += "Alias script missing"
    Write-Host "   ❌ start-local-testing-alias.ps1 not found" -ForegroundColor Red
}

# Test 3: Check if profile setup script exists
Write-Host "`n3. Checking profile setup script existence..." -ForegroundColor Cyan
if (Test-Path "scripts\Add-LocalTestingToProfile.ps1") {
    $success += "Profile setup script exists"
    Write-Host "   ✅ Add-LocalTestingToProfile.ps1 found" -ForegroundColor Green
} else {
    $errors += "Profile setup script missing"
    Write-Host "   ❌ Add-LocalTestingToProfile.ps1 not found" -ForegroundColor Red
}

# Test 4: Check if batch file exists
Write-Host "`n4. Checking batch file existence..." -ForegroundColor Cyan
if (Test-Path "scripts\start-local-testing.cmd") {
    $success += "Batch file exists"
    Write-Host "   ✅ start-local-testing.cmd found" -ForegroundColor Green
} else {
    $errors += "Batch file missing"
    Write-Host "   ❌ start-local-testing.cmd not found" -ForegroundColor Red
}

# Test 5: Check if documentation exists
Write-Host "`n5. Checking documentation existence..." -ForegroundColor Cyan
if (Test-Path "scripts\README-local-testing.md") {
    $success += "Documentation exists"
    Write-Host "   ✅ README-local-testing.md found" -ForegroundColor Green
} else {
    $errors += "Documentation missing"
    Write-Host "   ❌ README-local-testing.md not found" -ForegroundColor Red
}

# Test 6: Validate PowerShell syntax
Write-Host "`n6. Validating PowerShell syntax..." -ForegroundColor Cyan
try {
    $null = [System.Management.Automation.PSParser]::Tokenize((Get-Content "scripts\start-local-testing.ps1" -Raw), [ref]$null)
    $success += "PowerShell syntax valid"
    Write-Host "   ✅ PowerShell syntax is valid" -ForegroundColor Green
} catch {
    $errors += "PowerShell syntax error"
    Write-Host "   ❌ PowerShell syntax error: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 7: Check project structure dependencies
Write-Host "`n7. Checking project structure dependencies..." -ForegroundColor Cyan
$requiredFiles = @("package.json", "health_check.php", "login.php", "reports/index.php")
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Host "   ✅ $file found" -ForegroundColor Green
    } else {
        $errors += "Required file missing: $file"
        Write-Host "   ❌ $file not found" -ForegroundColor Red
    }
}

# Test 8: Check config files
Write-Host "`n8. Checking enterprise config files..." -ForegroundColor Cyan
$configFiles = @("config/csu.config", "config/ccc.config", "config/demo.config")
foreach ($config in $configFiles) {
    if (Test-Path $config) {
        Write-Host "   ✅ $config found" -ForegroundColor Green
    } else {
        Write-Host "   ⚠️ $config not found (optional)" -ForegroundColor Yellow
    }
}

# Test 9: Test command availability
Write-Host "`n9. Testing command availability..." -ForegroundColor Cyan
try {
    # Source the alias script to test function definition
    . "scripts\start-local-testing-alias.ps1"
    
    # Check if function is defined
    if (Get-Command Start-LocalTesting -ErrorAction SilentlyContinue) {
        $success += "Function definition works"
        Write-Host "   ✅ Start-LocalTesting function available" -ForegroundColor Green
    } else {
        $errors += "Function definition failed"
        Write-Host "   ❌ Start-LocalTesting function not available" -ForegroundColor Red
    }
} catch {
    $errors += "Function loading error"
    Write-Host "   ❌ Error loading functions: $($_.Exception.Message)" -ForegroundColor Red
}

# Test 10: Validate help documentation
Write-Host "`n10. Validating help documentation..." -ForegroundColor Cyan
try {
    . "scripts\start-local-testing-alias.ps1"
    $help = Get-Help Start-LocalTesting -ErrorAction SilentlyContinue
    if ($help) {
        $success += "Help documentation available"
        Write-Host "   ✅ Help documentation accessible" -ForegroundColor Green
    } else {
        $errors += "Help documentation missing"
        Write-Host "   ❌ Help documentation not accessible" -ForegroundColor Red
    }
} catch {
    $errors += "Help documentation error"
    Write-Host "   ❌ Error accessing help: $($_.Exception.Message)" -ForegroundColor Red
}

# Summary
Write-Host "`n📊 TEST SUMMARY" -ForegroundColor Green
Write-Host "===============" -ForegroundColor Green
Write-Host "✅ Successful tests: $($success.Count)" -ForegroundColor Green
Write-Host "❌ Failed tests: $($errors.Count)" -ForegroundColor Red

if ($success.Count -gt 0) {
    Write-Host "`n✅ SUCCESSFUL COMPONENTS:" -ForegroundColor Green
    $success | ForEach-Object { Write-Host "   • $_" -ForegroundColor Green }
}

if ($errors.Count -gt 0) {
    Write-Host "`n❌ FAILED COMPONENTS:" -ForegroundColor Red
    $errors | ForEach-Object { Write-Host "   • $_" -ForegroundColor Red }
    Write-Host "`n⚠️ Please fix the above issues before using the local testing environment." -ForegroundColor Yellow
} else {
    Write-Host "`n🎉 ALL TESTS PASSED!" -ForegroundColor Green
    Write-Host "Local Testing Environment is ready for use." -ForegroundColor Green
    Write-Host "`nTry running: .\scripts\start-local-testing.ps1" -ForegroundColor Cyan
}

Write-Host "`nPress any key to exit..." -NoNewline
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
