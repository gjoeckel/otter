# Script to add Local Testing Environment commands to PowerShell profile
# Run this script to add the "start local testing" functionality to your PowerShell profile

param(
    [switch]$Force,
    [string]$ProfilePath = $PROFILE
)

Write-Host "Adding Local Testing Environment to PowerShell Profile..." -ForegroundColor Green

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
# Added by Add-LocalTestingToProfile.ps1 on $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

# Function to start local testing environment
function Start-LocalTesting {
    [CmdletBinding()]
    param(
        [switch]`$SkipBuild,
        [switch]`$SkipWebSocket,
        [switch]`$SkipValidation,
        [int]`$PhpPort = 8000,
        [int]`$WebSocketPort = 8080,
        [switch]`$Verbose
    )
    
    # Get the project root (assuming this profile is in the project)
    `$projectRoot = if (`$PWD.Path -like "*otter*") { `$PWD.Path } else { Get-Location }
    `$scriptPath = Join-Path `$projectRoot "scripts\start-local-testing.ps1"
    
    if (Test-Path `$scriptPath) {
        & `$scriptPath -SkipBuild:`$SkipBuild -SkipWebSocket:`$SkipWebSocket -SkipValidation:`$SkipValidation -PhpPort `$PhpPort -WebSocketPort `$WebSocketPort -Verbose:`$Verbose
    } else {
        Write-Error "Local testing script not found at: `$scriptPath"
        Write-Host "Please run this from the otter project directory." -ForegroundColor Yellow
    }
}

# Create aliases
Set-Alias -Name "start-local-testing" -Value Start-LocalTesting
Set-Alias -Name "slt" -Value Start-LocalTesting

# Quick command for "test local" (case insensitive) - avoids conflict with Start-Process
function test { 
    param([string]`$Command)
    if (`$Command -eq "local") {
        Start-LocalTesting
    } else {
        # Fall back to default test command if it exists
        if (Get-Command test -CommandType Application -ErrorAction SilentlyContinue) {
            & (Get-Command test -CommandType Application) `$Command
        } else {
            Write-Host "Unknown test command: `$Command" -ForegroundColor Yellow
            Write-Host "Use 'test local' to start the local testing environment" -ForegroundColor Cyan
        }
    }
}

Write-Host "Local Testing Environment commands loaded!" -ForegroundColor Green
Write-Host "Available commands:" -ForegroundColor Cyan
Write-Host "  Start-LocalTesting, start-local-testing, slt" -ForegroundColor White
Write-Host "  test local" -ForegroundColor White
"@

# Read current profile content
$currentContent = if (Test-Path $ProfilePath) { Get-Content $ProfilePath -Raw } else { "" }

# Check if content already exists
if ($currentContent -like "*Local Testing Environment*" -and -not $Force) {
    Write-Host "Local Testing Environment commands already exist in profile." -ForegroundColor Yellow
    Write-Host "Use -Force to overwrite or manually edit: $ProfilePath" -ForegroundColor Yellow
    return
}

# Add content to profile
if ($currentContent) {
    $newContent = $currentContent + $profileContent
} else {
    $newContent = $profileContent
}

# Write to profile
Set-Content -Path $ProfilePath -Value $newContent

Write-Host "✅ Successfully added Local Testing Environment to PowerShell profile!" -ForegroundColor Green
Write-Host ""
Write-Host "Available commands:" -ForegroundColor Cyan
Write-Host "  Start-LocalTesting" -ForegroundColor White
Write-Host "  start-local-testing" -ForegroundColor White
Write-Host "  slt" -ForegroundColor White
Write-Host "  start local testing" -ForegroundColor White
Write-Host ""
Write-Host "To use immediately, run:" -ForegroundColor Yellow
Write-Host "  . `$PROFILE" -ForegroundColor White
Write-Host ""
Write-Host "Or restart PowerShell to load the new profile." -ForegroundColor Gray
