# Alias function for "start local testing" command
# This file can be sourced to create the alias function

function Start-LocalTesting {
    <#
    .SYNOPSIS
    Starts the local testing environment with comprehensive setup.
    
    .DESCRIPTION
    This function provides a complete local testing environment setup including:
    - Environment validation (PHP, Node.js, npm, config files)
    - Server management (PHP server, WebSocket server)
    - Build process (npm dependencies, reports bundle)
    - Testing preparation (health checks, validation)
    
    .PARAMETER SkipBuild
    Skip the npm build process
    
    .PARAMETER SkipWebSocket
    Skip starting the WebSocket server
    
    .PARAMETER SkipValidation
    Skip environment validation checks
    
    .PARAMETER PhpPort
    Port for PHP server (default: 8000)
    
    .PARAMETER WebSocketPort
    Port for WebSocket server (default: 8080)
    
    .PARAMETER Verbose
    Enable verbose output and logging
    
    .EXAMPLE
    Start-LocalTesting
    Starts the complete local testing environment
    
    .EXAMPLE
    Start-LocalTesting -SkipBuild -Verbose
    Starts environment without rebuilding, with verbose output
    #>
    [CmdletBinding()]
    param(
        [switch]$SkipBuild,
        [switch]$SkipWebSocket,
        [switch]$SkipValidation,
        [int]$PhpPort = 8000,
        [int]$WebSocketPort = 8080,
        [switch]$Verbose
    )
    
    # Get the directory where this script is located
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
    $projectRoot = Split-Path -Parent $scriptDir
    
    # Change to project root directory
    Push-Location $projectRoot
    
    try {
        # Execute the main startup script
        & "$scriptDir\start-local-testing.ps1" -SkipBuild:$SkipBuild -SkipWebSocket:$SkipWebSocket -SkipValidation:$SkipValidation -PhpPort $PhpPort -WebSocketPort $WebSocketPort -Verbose:$Verbose
    }
    finally {
        # Return to original directory
        Pop-Location
    }
}

# Create aliases for common variations
Set-Alias -Name "start-local-testing" -Value Start-LocalTesting
Set-Alias -Name "slt" -Value Start-LocalTesting

# Display usage information
Write-Host "Local Testing Environment Commands:" -ForegroundColor Green
Write-Host "  Start-LocalTesting     - Complete setup (recommended)" -ForegroundColor Cyan
Write-Host "  start-local-testing    - Alias for Start-LocalTesting" -ForegroundColor Cyan
Write-Host "  slt                    - Short alias" -ForegroundColor Cyan
Write-Host ""
Write-Host "Examples:" -ForegroundColor Yellow
Write-Host "  Start-LocalTesting" -ForegroundColor White
Write-Host "  Start-LocalTesting -SkipBuild -Verbose" -ForegroundColor White
Write-Host "  slt -SkipWebSocket" -ForegroundColor White
