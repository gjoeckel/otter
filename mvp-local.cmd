@echo off
REM MVP Local Testing Command
REM Usage: mvp local
REM This command resets the local server, builds MVP reports, and busts caches

echo 🚀 Starting MVP Local Testing Environment...
echo Token: mvp local
echo Mode: MVP (simplified, no count options complexity)
echo.

REM Execute the MVP testing script
powershell.exe -ExecutionPolicy Bypass -File "scripts\start-mvp-testing.ps1"
