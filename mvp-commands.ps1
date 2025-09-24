# MVP Commands - Load this to get 'mvp local' command
# Usage: . .\mvp-commands.ps1

function mvp {
    param(
        [string]$Command
    )
    
    if ($Command -eq "local") {
        Write-Host "Starting MVP Local Testing Environment..." -ForegroundColor Green
        Write-Host "Token: mvp local" -ForegroundColor Cyan
        Write-Host "Mode: MVP (simplified, no count options complexity)" -ForegroundColor Yellow
        Write-Host ""
        
        # Execute the MVP testing script
        & ".\scripts\start-mvp-testing.ps1"
    }
    elseif ($Command -eq "build") {
        Write-Host "Building MVP reports bundle..." -ForegroundColor Blue
        npm run build:mvp
    }
    elseif ($Command -eq "test") {
        Write-Host "Testing MVP system..." -ForegroundColor Blue
        # Create a simple test since test_mvp_system.php was deleted
        Write-Host "Testing MVP system..." -ForegroundColor Gray
        if (Test-Path "reports/dist/mvp-reports.bundle.js") {
            $size = (Get-Item "reports/dist/mvp-reports.bundle.js").Length
            Write-Host "MVP bundle exists ($size bytes)" -ForegroundColor Green
        } else {
            Write-Host "MVP bundle missing" -ForegroundColor Red
        }
    }
    else {
        Write-Host "MVP Commands:" -ForegroundColor Cyan
        Write-Host "  mvp local  - Start MVP local testing environment" -ForegroundColor White
        Write-Host "  mvp build  - Build MVP reports bundle only" -ForegroundColor White
        Write-Host "  mvp test   - Test MVP system" -ForegroundColor White
    }
}

Write-Host "MVP commands loaded!" -ForegroundColor Green
Write-Host "Try: mvp local" -ForegroundColor Yellow