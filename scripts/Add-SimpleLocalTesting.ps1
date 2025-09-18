# Simple Local Testing Environment Profile Setup
# Adds "test local" command to PowerShell profile

param(
    [string]$ProfilePath = $PROFILE
)

Write-Host "Adding Simple Local Testing Environment to PowerShell Profile..." -ForegroundColor Green

# Check if profile exists
if (-not (Test-Path $ProfilePath)) {
    Write-Host "Creating PowerShell profile at: $ProfilePath" -ForegroundColor Yellow
    New-Item -ItemType File -Path $ProfilePath -Force | Out-Null
}

# Get the current script directory
$scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path

# Content to add to profile
$profileContent = @"

# Local Testing Environment Commands
# Added by Add-SimpleLocalTesting.ps1 on $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

# Function to start local testing environment
function Start-LocalTesting {
    param(
        [switch]`$SkipBuild,
        [switch]`$SkipWebSocket,
        [int]`$PhpPort = 8000
    )
    
    # Get the project root
    `$projectRoot = if (`$PWD.Path -like "*otter*") { `$PWD.Path } else { Get-Location }
    `$scriptPath = Join-Path `$projectRoot "scripts\start-local-testing-simple.ps1"
    
    if (Test-Path `$scriptPath) {
        & `$scriptPath -SkipBuild:`$SkipBuild -SkipWebSocket:`$SkipWebSocket -PhpPort `$PhpPort
    } else {
        Write-Error "Local testing script not found at: `$scriptPath"
        Write-Host "Please run this from the otter project directory." -ForegroundColor Yellow
    }
}

# Create aliases
Set-Alias -Name "start-local-testing" -Value Start-LocalTesting
Set-Alias -Name "slt" -Value Start-LocalTesting

# Simple command for "test local"
function test { 
    param([string]`$Command)
    if (`$Command -eq "local") {
        Start-LocalTesting
    } else {
        Write-Host "Unknown test command: `$Command" -ForegroundColor Yellow
        Write-Host "Use 'test local' to start the local testing environment" -ForegroundColor Cyan
    }
}

Write-Host "Local Testing Environment commands loaded!" -ForegroundColor Green
"@

# Write to profile
Set-Content -Path $ProfilePath -Value $profileContent

Write-Host "✅ Successfully added Local Testing Environment to PowerShell profile!" -ForegroundColor Green
Write-Host ""
Write-Host "Available commands:" -ForegroundColor Cyan
Write-Host "  Start-LocalTesting" -ForegroundColor White
Write-Host "  start-local-testing" -ForegroundColor White
Write-Host "  slt" -ForegroundColor White
Write-Host "  test local" -ForegroundColor White
Write-Host ""
Write-Host "To use immediately, run:" -ForegroundColor Yellow
Write-Host "  . `$PROFILE" -ForegroundColor White
