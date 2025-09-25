# MVP Local Testing PowerShell Function
# Add this to your PowerShell profile or run: . .\mvp-local-function.ps1

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
    else {
        Write-Host "MVP Commands:" -ForegroundColor Cyan
        Write-Host "  mvp local  - Start MVP local testing environment" -ForegroundColor White
        Write-Host "  mvp build  - Build MVP reports bundle only" -ForegroundColor White
        Write-Host "  mvp test   - Test MVP system" -ForegroundColor White
    }
}

# Alternative: Direct command function
function mvp-local {
    Write-Host "🚀 Starting MVP Local Testing Environment..." -ForegroundColor Green
    Write-Host "Token: mvp local" -ForegroundColor Cyan
    Write-Host "Mode: MVP (simplified, no count options complexity)" -ForegroundColor Yellow
    Write-Host ""
    
    # Execute the MVP testing script
    & ".\scripts\start-mvp-testing.ps1"
}
