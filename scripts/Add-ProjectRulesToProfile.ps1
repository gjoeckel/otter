# Script to add "project rules" command to PowerShell profile
# Run this script to add the "project rules" functionality to your PowerShell profile

param(
    [switch]$Force,
    [string]$ProfilePath = $PROFILE
)

Write-Host "Adding Project Rules command to PowerShell Profile..." -ForegroundColor Green

# Ensure profile exists
if (-not (Test-Path $ProfilePath)) {
    Write-Host "Creating PowerShell profile at: $ProfilePath" -ForegroundColor Yellow
    New-Item -ItemType File -Path $ProfilePath -Force | Out-Null
}

# Content to append to profile
$profileContent = @"

# Project Rules Commands
# Added by Add-ProjectRulesToProfile.ps1 on $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')

function project {
    param([string]`$Command)
    if (`$Command -eq "rules") {
        `$path = "C:\Users\George\Projects\otter\.cursor\rules\00-startup.mdc"
        if (Test-Path `$path) {
            Write-Host "=== AGENT PREP: READ AND FOLLOW THE RULES BELOW FOR THIS SESSION ===" -ForegroundColor Cyan
            Get-Content -Raw `$path
            Write-Host "=== END RULES ===" -ForegroundColor Cyan
            
            # Also open the rules file in Cursor/VS Code so the agent sees it as an open file
            if (Get-Command cursor -ErrorAction SilentlyContinue) {
                & cursor `$path
            } elseif (Get-Command code -ErrorAction SilentlyContinue) {
                & code `$path
            } else {
                Start-Process `$path
            }
        } else {
            Write-Error "Rules file not found at: `$path"
        }
    } else {
        # Fallback to any existing external 'project' command if present
        if (Get-Command project -CommandType Application -ErrorAction SilentlyContinue) {
            & (Get-Command project -CommandType Application) `$Command
        } else {
            Write-Host "Unknown project command: `$Command" -ForegroundColor Yellow
            Write-Host "Try: project rules" -ForegroundColor Cyan
        }
    }
}
"@

# Read current profile content
$currentContent = if (Test-Path $ProfilePath) { Get-Content $ProfilePath -Raw } else { "" }

# Skip if already present unless forced
if ($currentContent -like "*Project Rules Commands*" -and -not $Force) {
    Write-Host "Project Rules command already exists in profile." -ForegroundColor Yellow
    Write-Host "Use -Force to overwrite or manually edit: $ProfilePath" -ForegroundColor Yellow
    return
}

# Append to profile
if ($currentContent) {
    $newContent = $currentContent + $profileContent
} else {
    $newContent = $profileContent
}

Set-Content -Path $ProfilePath -Value $newContent

Write-Host "✅ Successfully added Project Rules command to PowerShell profile!" -ForegroundColor Green
Write-Host "" 
Write-Host "Usage:" -ForegroundColor Cyan
Write-Host "  project rules" -ForegroundColor White
Write-Host "" 
Write-Host "To use immediately, run:" -ForegroundColor Yellow
Write-Host "  . `$PROFILE" -ForegroundColor White
Write-Host "" 
Write-Host "Or restart PowerShell to load the new profile." -ForegroundColor Gray


