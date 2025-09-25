@echo off
REM Local Testing Environment Startup Script
REM Usage: start-local-testing.cmd or "start local testing"

echo Starting Local Testing Environment...
echo.

REM Check if we're in the right directory
if not exist "package.json" (
    echo Error: package.json not found. Please run this from the otter project root.
    pause
    exit /b 1
)

REM Check if PowerShell is available
powershell -Command "Get-Host" >nul 2>&1
if errorlevel 1 (
    echo Error: PowerShell not found. Please install PowerShell 5.1 or later.
    pause
    exit /b 1
)

REM Execute the PowerShell script
powershell -ExecutionPolicy Bypass -File "%~dp0start-local-testing.ps1" %*

REM Keep window open if there was an error
if errorlevel 1 (
    echo.
    echo Press any key to exit...
    pause >nul
)
