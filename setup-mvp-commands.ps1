# Setup MVP Commands
# Run this once to make 'mvp local' command available

Write-Host "Setting up MVP commands..." -ForegroundColor Green

# Create the mvp function
$mvpFunction = @'
function mvp {
    param(
        [string]$Command
    )
    
    if ($Command -eq "local") {
        Write-Host "🚀 Starting MVP Local Testing Environment..." -ForegroundColor Green
        Write-Host "Token: mvp local" -ForegroundColor Cyan
        Write-Host "Mode: MVP (simplified, no count options complexity)" -ForegroundColor Yellow
        Write-Host ""
        
        # Execute the MVP testing script
        & ".\scripts\start-mvp-testing.ps1"
    }
    elseif ($Command -eq "build") {
        Write-Host "🔧 Building MVP reports bundle..." -ForegroundColor Blue
        npm run build:mvp
    }
    elseif ($Command -eq "test") {
        Write-Host "🧪 Testing MVP system..." -ForegroundColor Blue
        php test_mvp_system.php
    }
    else {
        Write-Host "MVP Commands:" -ForegroundColor Cyan
        Write-Host "  mvp local  - Start MVP local testing environment" -ForegroundColor White
        Write-Host "  mvp build  - Build MVP reports bundle only" -ForegroundColor White
        Write-Host "  mvp test   - Test MVP system" -ForegroundColor White
    }
}
'@

# Add to current session
Invoke-Expression $mvpFunction

Write-Host "✅ MVP commands setup complete!" -ForegroundColor Green
Write-Host ""
Write-Host "Available commands:" -ForegroundColor Cyan
Write-Host "  mvp local  - Start MVP local testing environment" -ForegroundColor White
Write-Host "  mvp build  - Build MVP reports bundle only" -ForegroundColor White
Write-Host "  mvp test   - Test MVP system" -ForegroundColor White
Write-Host ""
Write-Host "Try: mvp local" -ForegroundColor Yellow
