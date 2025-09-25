# MVP Local Testing Script
# Usage: .\mvp-local.ps1
# Token: "mvp local"

Write-Host "🚀 Starting MVP Local Testing Environment..." -ForegroundColor Green
Write-Host "Token: mvp local" -ForegroundColor Cyan
Write-Host "Mode: MVP (simplified, no count options complexity)" -ForegroundColor Yellow
Write-Host ""

# Execute the MVP testing script
& ".\scripts\start-mvp-testing.ps1"
