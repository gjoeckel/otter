# MCP Quick Start Guide - Windows 11

## First Time Setup (10 minutes)

### 1. Verify Prerequisites
```bash
./scripts/validate-environment.sh
```

**If any errors, install missing software:**
- Git Bash: https://git-scm.com/download/win
- Node.js 18+: https://nodejs.org/
- PHP 8.0+: https://windows.php.net/download/
- Chrome: https://www.google.com/chrome/

### 2. Configure Windows Firewall
```powershell
# Run in PowerShell as Administrator:
New-NetFirewallRule -DisplayName "Chrome Remote Debugging MCP" -Direction Inbound -LocalPort 9222 -Protocol TCP -Action Allow
```

### 3. Start Chrome with Debugging
```bash
./scripts/start-chrome-debug.sh
```

### 4. Check MCP Health
```bash
./scripts/check-mcp-health.sh
```

**Expected output:** All green checkmarks (✓)

### 5. Run Tests to Verify
```bash
php tests/run_comprehensive_tests.php
```

## Daily Workflow

### Starting Work
```bash
# 1. Validate environment (quick check)
./scripts/validate-environment.sh

# 2. Start Chrome if not running
./scripts/start-chrome-debug.sh

# 3. Start development server
./tests/start_server.sh

# 4. Verify MCP health
./scripts/check-mcp-health.sh
```

### If MCP Tools Stop Working
```bash
# Quick restart (30 seconds)
./scripts/restart-mcp-servers.sh

# Then reload Cursor window:
# Ctrl+Shift+P → "Developer: Reload Window"
```

### If Everything Breaks
```bash
# Nuclear option (2 minutes)
./scripts/emergency-reset.sh

# Then:
# 1. Close Cursor completely
# 2. Restart Cursor
# 3. Open project
# 4. Verify: ./scripts/validate-environment.sh
```

## Troubleshooting Quick Reference

| Problem | Quick Fix |
|---------|-----------|
| MCP tools not responding | `./scripts/restart-mcp-servers.sh` |
| Chrome won't connect | `./scripts/start-chrome-debug.sh` |
| Wrong shell active | `echo $SHELL` → switch to Git Bash |
| Git operations fail | `git config --global core.autocrlf true` |
| Environment broken | `./scripts/emergency-reset.sh` |

## Verification Checklist

Before starting development, ensure:
- [ ] Git Bash is active (`echo $SHELL` shows `/usr/bin/bash`)
- [ ] Chrome debugging on port 9222 (`netstat -an | grep 9222`)
- [ ] MCP servers healthy (`./scripts/check-mcp-health.sh`)
- [ ] PHP server running (`curl http://localhost:8000`)
- [ ] All tests pass (`php tests/run_comprehensive_tests.php`)
