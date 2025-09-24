@echo off
REM MVP Command Line Tool
REM Usage: mvp local

if "%1"=="local" (
    echo 🚀 Starting MVP Local Testing Environment...
    echo Token: mvp local
    echo Mode: MVP (simplified, no count options complexity)
    echo.
    powershell.exe -ExecutionPolicy Bypass -File "scripts\start-mvp-testing.ps1"
) else if "%1"=="build" (
    echo 🔧 Building MVP reports bundle...
    npm run build:mvp
) else if "%1"=="test" (
    echo 🧪 Testing MVP system...
    php test_mvp_system.php
) else (
    echo MVP Commands:
    echo   mvp local  - Start MVP local testing environment
    echo   mvp build  - Build MVP reports bundle only
    echo   mvp test   - Test MVP system
)
