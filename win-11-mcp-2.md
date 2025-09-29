\# Windows 11 MCP Optimization - Part 2: Automation Scripts



\## Implementation Instructions for AI Agent



This is Part 2 of 4. Implement automation and diagnostic scripts.



\*\*After creating each script, make it executable:\*\*

```bash

chmod +x scripts/\[script-name].sh

```



---



\## 1. Chrome Debug Automation Script



\*\*File:\*\* `scripts/start-chrome-debug.sh`



```bash

\#!/bin/bash

\# Start Chrome with remote debugging for MCP integration

\# Windows 11 optimized version



CHROME\_PATH="/c/Program Files/Google/Chrome/Application/chrome.exe"

USER\_DATA\_DIR="C:/temp/chrome-debug-mcp"

REMOTE\_PORT=9222



echo "Starting Chrome with remote debugging for MCP..."



\# Check if Chrome is already running with debugging

if netstat -an | grep -q ":$REMOTE\_PORT.\*LISTEN"; then

&nbsp;   echo "✓ Chrome already running with remote debugging on port $REMOTE\_PORT"

&nbsp;   exit 0

fi



\# Create user data directory if it doesn't exist

mkdir -p "$USER\_DATA\_DIR"



\# Check if Chrome executable exists

if \[ ! -f "$CHROME\_PATH" ]; then

&nbsp;   echo "✗ Chrome not found at: $CHROME\_PATH"

&nbsp;   echo "  Please install Chrome or update CHROME\_PATH in this script"

&nbsp;   exit 1

fi



\# Start Chrome with debugging flags

"$CHROME\_PATH" \\

&nbsp;   --remote-debugging-port=$REMOTE\_PORT \\

&nbsp;   --user-data-dir="$USER\_DATA\_DIR" \\

&nbsp;   --no-first-run \\

&nbsp;   --no-default-browser-check \\

&nbsp;   --disable-background-networking \\

&nbsp;   --disable-sync \\

&nbsp;   --disable-extensions \\

&nbsp;   --disable-default-apps \\

&nbsp;   > /dev/null 2>\&1 \&



\# Wait for Chrome to start

sleep 3



\# Verify Chrome started successfully

if netstat -an | grep -q ":$REMOTE\_PORT.\*LISTEN"; then

&nbsp;   echo "✓ Chrome started successfully with remote debugging"

&nbsp;   echo "  Port: $REMOTE\_PORT"

&nbsp;   echo "  Data dir: $USER\_DATA\_DIR"

&nbsp;   echo "  Access DevTools: http://localhost:$REMOTE\_PORT"

else

&nbsp;   echo "✗ Chrome failed to start with remote debugging"

&nbsp;   echo "  Check if port $REMOTE\_PORT is already in use"

&nbsp;   exit 1

fi

```



---



\## 2. MCP Health Check Script



\*\*File:\*\* `scripts/check-mcp-health.sh`



```bash

\#!/bin/bash

\# Verify MCP servers are functioning correctly

\# Windows 11 diagnostic tool



echo "=== MCP Server Health Check ==="

echo ""



ISSUES\_FOUND=0



\# 1. Check Chrome remote debugging

echo "\[1/6] Chrome DevTools MCP..."

if netstat -an | grep -q ":9222.\*LISTEN"; then

&nbsp;   echo "      ✓ Port 9222 active (Chrome DevTools MCP running)"

else

&nbsp;   echo "      ✗ Port 9222 not listening"

&nbsp;   echo "        Fix: ./scripts/start-chrome-debug.sh"

&nbsp;   ((ISSUES\_FOUND++))

fi



\# 2. Check Node.js for npx-based servers

echo "\[2/6] Node.js (required for MCP servers)..."

if command -v node >/dev/null 2>\&1; then

&nbsp;   NODE\_VERSION=$(node --version)

&nbsp;   echo "      ✓ Node.js available: $NODE\_VERSION"

&nbsp;   

&nbsp;   # Check if version is adequate (>= 18)

&nbsp;   MAJOR\_VERSION=$(echo $NODE\_VERSION | grep -oP '\\d+' | head -n 1)

&nbsp;   if \[ "$MAJOR\_VERSION" -lt 18 ]; then

&nbsp;       echo "      ⚠ Node.js $NODE\_VERSION detected (recommend >= 18)"

&nbsp;   fi

else

&nbsp;   echo "      ✗ Node.js not found (required for npx MCP servers)"

&nbsp;   echo "        Fix: Install Node.js from https://nodejs.org"

&nbsp;   ((ISSUES\_FOUND++))

fi



\# 3. Check Python/uvx for git MCP

echo "\[3/6] Git MCP (uvx)..."

if command -v uvx >/dev/null 2>\&1; then

&nbsp;   echo "      ✓ uvx available for Git MCP"

elif command -v python >/dev/null 2>\&1; then

&nbsp;   echo "      ⚠ uvx not found (Git MCP may not work)"

&nbsp;   echo "        Fix: pip install uvx"

&nbsp;   ((ISSUES\_FOUND++))

else

&nbsp;   echo "      ✗ Python not found (required for uvx/Git MCP)"

&nbsp;   echo "        Fix: Install Python 3.8+ from python.org"

&nbsp;   ((ISSUES\_FOUND++))

fi



\# 4. Check filesystem access

echo "\[4/6] Filesystem MCP..."

if \[ -d "C:/Users/George/Projects" ]; then

&nbsp;   echo "      ✓ Project directory accessible"

else

&nbsp;   echo "      ✗ Project directory not found: C:/Users/George/Projects"

&nbsp;   ((ISSUES\_FOUND++))

fi



\# 5. Check memory persistence

echo "\[5/6] Memory MCP..."

MEMORY\_DIR="C:/Users/George/.cursor/mcp-memory"

if \[ -d "$MEMORY\_DIR" ]; then

&nbsp;   echo "      ✓ Persistence directory exists"

&nbsp;   FILE\_COUNT=$(find "$MEMORY\_DIR" -type f 2>/dev/null | wc -l)

&nbsp;   echo "        Memory files: $FILE\_COUNT"

else

&nbsp;   echo "      ⚠ Persistence directory missing"

&nbsp;   echo "        Creating: $MEMORY\_DIR"

&nbsp;   mkdir -p "$MEMORY\_DIR"

fi



\# 6. Check shell environment

echo "\[6/6] Shell Environment..."

if \[\[ "$SHELL" == \*"bash"\* ]] || \[\[ "$BASH\_VERSION" ]]; then

&nbsp;   echo "      ✓ Git Bash active ($BASH\_VERSION)"

else

&nbsp;   echo "      ✗ Not running in Git Bash"

&nbsp;   echo "        Current shell: $SHELL"

&nbsp;   echo "        Fix: Switch to Git Bash terminal"

&nbsp;   ((ISSUES\_FOUND++))

fi



echo ""

echo "==================================="

if \[ $ISSUES\_FOUND -eq 0 ]; then

&nbsp;   echo "✓ All MCP servers healthy"

else

&nbsp;   echo "✗ Found $ISSUES\_FOUND issue(s) - see fixes above"

&nbsp;   echo ""

&nbsp;   echo "After fixing issues:"

&nbsp;   echo "  1. Restart Cursor IDE"

&nbsp;   echo "  2. Re-run: ./scripts/check-mcp-health.sh"

fi

```



---



\## 3. Environment Validation Script



\*\*File:\*\* `scripts/validate-environment.sh`



```bash

\#!/bin/bash

\# Comprehensive environment validation for MCP development

\# Windows 11 version



echo "=== Development Environment Validation ==="

echo ""



ERRORS=0

WARNINGS=0



\# 1. Verify shell

echo "\[1/8] Shell Validation..."

if \[\[ "$SHELL" == \*"bash"\* ]] || \[\[ "$BASH\_VERSION" ]]; then

&nbsp;   echo "      ✓ Git Bash ($BASH\_VERSION)"

else

&nbsp;   echo "      ✗ Not Git Bash (MCP will fail)"

&nbsp;   echo "        Current: $SHELL"

&nbsp;   echo "        Fix: Configure Cursor to use Git Bash"

&nbsp;   ((ERRORS++))

fi



\# 2. Check required commands

echo "\[2/8] Required Commands..."

REQUIRED\_CMDS=("git" "php" "node" "npx" "npm")

for cmd in "${REQUIRED\_CMDS\[@]}"; do

&nbsp;   if command -v "$cmd" >/dev/null 2>\&1; then

&nbsp;       CMD\_PATH=$(command -v "$cmd")

&nbsp;       echo "      ✓ $cmd: $CMD\_PATH"

&nbsp;   else

&nbsp;       echo "      ✗ $cmd: Not found in PATH"

&nbsp;       ((ERRORS++))

&nbsp;   fi

done



\# 3. Check PHP version

echo "\[3/8] PHP Version..."

if command -v php >/dev/null 2>\&1; then

&nbsp;   PHP\_VERSION=$(php -v | head -n 1 | grep -oP '\\d+\\.\\d+' | head -n 1)

&nbsp;   PHP\_MAJOR=$(echo $PHP\_VERSION | cut -d. -f1)

&nbsp;   

&nbsp;   if \[ "$PHP\_MAJOR" -ge 8 ]; then

&nbsp;       echo "      ✓ PHP $PHP\_VERSION (>= 8.0)"

&nbsp;   else

&nbsp;       echo "      ✗ PHP $PHP\_VERSION (requires >= 8.0)"

&nbsp;       ((ERRORS++))

&nbsp;   fi

else

&nbsp;   echo "      ✗ PHP not found"

&nbsp;   ((ERRORS++))

fi



\# 4. Check Node.js version

echo "\[4/8] Node.js Version..."

if command -v node >/dev/null 2>\&1; then

&nbsp;   NODE\_VERSION=$(node -v)

&nbsp;   NODE\_MAJOR=$(echo $NODE\_VERSION | grep -oP '\\d+' | head -n 1)

&nbsp;   

&nbsp;   if \[ "$NODE\_MAJOR" -ge 18 ]; then

&nbsp;       echo "      ✓ Node.js $NODE\_VERSION (>= 18)"

&nbsp;   else

&nbsp;       echo "      ⚠ Node.js $NODE\_VERSION (recommend >= 18)"

&nbsp;       ((WARNINGS++))

&nbsp;   fi

else

&nbsp;   echo "      ✗ Node.js not found"

&nbsp;   ((ERRORS++))

fi



\# 5. Check Git configuration

echo "\[5/8] Git Configuration..."

if command -v git >/dev/null 2>\&1; then

&nbsp;   AUTOCRLF=$(git config --get core.autocrlf)

&nbsp;   if \[\[ "$AUTOCRLF" == "true" ]]; then

&nbsp;       echo "      ✓ autocrlf=true (Windows line endings handled)"

&nbsp;   else

&nbsp;       echo "      ⚠ autocrlf not set to 'true'"

&nbsp;       echo "        Fix: git config --global core.autocrlf true"

&nbsp;       ((WARNINGS++))

&nbsp;   fi

&nbsp;   

&nbsp;   # Check if user is configured

&nbsp;   GIT\_USER=$(git config --get user.name)

&nbsp;   GIT\_EMAIL=$(git config --get user.email)

&nbsp;   

&nbsp;   if \[\[ -n "$GIT\_USER" ]] \&\& \[\[ -n "$GIT\_EMAIL" ]]; then

&nbsp;       echo "      ✓ Git user configured: $GIT\_USER <$GIT\_EMAIL>"

&nbsp;   else

&nbsp;       echo "      ⚠ Git user/email not configured"

&nbsp;       ((WARNINGS++))

&nbsp;   fi

fi



\# 6. Check project structure

echo "\[6/8] Project Structure..."

REQUIRED\_DIRS=("reports" "tests" "config" "scripts")

for dir in "${REQUIRED\_DIRS\[@]}"; do

&nbsp;   if \[ -d "$dir" ]; then

&nbsp;       echo "      ✓ $dir/ exists"

&nbsp;   else

&nbsp;       echo "      ✗ $dir/ missing"

&nbsp;       ((ERRORS++))

&nbsp;   fi

done



\# 7. Check MCP configuration

echo "\[7/8] MCP Configuration..."

MCP\_CONFIG="$HOME/.cursor/mcp.json"

if \[ -f "$MCP\_CONFIG" ]; then

&nbsp;   echo "      ✓ mcp.json exists"

&nbsp;   

&nbsp;   # Validate JSON

&nbsp;   if node -e "JSON.parse(require('fs').readFileSync('$MCP\_CONFIG', 'utf8'))" 2>/dev/null; then

&nbsp;       echo "      ✓ mcp.json is valid JSON"

&nbsp;   else

&nbsp;       echo "      ✗ mcp.json has syntax errors"

&nbsp;       ((ERRORS++))

&nbsp;   fi

else

&nbsp;   echo "      ✗ mcp.json not found at $MCP\_CONFIG"

&nbsp;   ((ERRORS++))

fi



\# 8. Check Chrome debugging availability

echo "\[8/8] Chrome Debugging..."

if \[ -f "/c/Program Files/Google/Chrome/Application/chrome.exe" ]; then

&nbsp;   echo "      ✓ Chrome executable found"

else

&nbsp;   echo "      ⚠ Chrome not found in default location"

&nbsp;   ((WARNINGS++))

fi



if netstat -an | grep -q ":9222.\*LISTEN"; then

&nbsp;   echo "      ✓ Chrome debugging port active"

else

&nbsp;   echo "      ⚠ Chrome not running with debugging"

&nbsp;   echo "        Start: ./scripts/start-chrome-debug.sh"

&nbsp;   ((WARNINGS++))

fi



\# Summary

echo ""

echo "==================================="

echo "Validation Summary:"

echo "  Errors: $ERRORS"

echo "  Warnings: $WARNINGS"

echo ""



if \[ $ERRORS -eq 0 ] \&\& \[ $WARNINGS -eq 0 ]; then

&nbsp;   echo "✓ Environment fully validated - ready for development"

&nbsp;   exit 0

elif \[ $ERRORS -eq 0 ]; then

&nbsp;   echo "⚠ Environment validated with $WARNINGS warning(s)"

&nbsp;   echo "  Development possible but address warnings for best results"

&nbsp;   exit 0

else

&nbsp;   echo "✗ Environment validation failed with $ERRORS error(s)"

&nbsp;   echo "  Fix errors before proceeding with development"

&nbsp;   exit 1

fi

```



---



\## 4. MCP Server Restart Script



\*\*File:\*\* `scripts/restart-mcp-servers.sh`



```bash

\#!/bin/bash

\# Restart MCP servers without restarting entire Cursor IDE

\# Useful when MCP servers hang or become unresponsive



echo "=== Restarting MCP Servers ==="

echo ""



\# 1. Stop all MCP-related processes

echo "\[1/4] Stopping MCP processes..."

pkill -f "chrome-devtools-mcp" 2>/dev/null \&\& echo "      ✓ Killed chrome-devtools-mcp"

pkill -f "server-filesystem" 2>/dev/null \&\& echo "      ✓ Killed filesystem MCP"

pkill -f "server-memory" 2>/dev/null \&\& echo "      ✓ Killed memory MCP"

pkill -f "mcp-server-git" 2>/dev/null \&\& echo "      ✓ Killed git MCP"



echo "      Waiting for cleanup..."

sleep 2



\# 2. Clear MCP cache (optional but recommended)

echo "\[2/4] Clearing MCP cache..."

if \[ -d "$HOME/.cursor/mcp-cache" ]; then

&nbsp;   rm -rf "$HOME/.cursor/mcp-cache"/\* 2>/dev/null

&nbsp;   echo "      ✓ Cache cleared"

else

&nbsp;   echo "      ✓ No cache to clear"

fi



\# 3. Restart Chrome with debugging

echo "\[3/4] Restarting Chrome debugging..."

./scripts/start-chrome-debug.sh



\# 4. Verify MCP health

echo "\[4/4] Verifying MCP health..."

sleep 2

./scripts/check-mcp-health.sh



echo ""

echo "==================================="

echo "MCP server restart complete"

echo ""

echo "Next steps:"

echo "  1. Reload Cursor window: Ctrl+Shift+P → 'Developer: Reload Window'"

echo "  2. Or restart Cursor terminal: Ctrl+\\` → close → reopen"

echo "  3. Verify MCP tools work: Try using Chrome MCP in chat"

```



---



\## 5. Emergency Reset Script



\*\*File:\*\* `scripts/emergency-reset.sh`



```bash

\#!/bin/bash

\# Emergency reset when development environment is completely broken

\# Nuclear option - use only when nothing else works



echo "⚠️  EMERGENCY ENVIRONMENT RESET"

echo "This will stop all processes and clear caches"

echo ""

read -p "Continue? (y/N) " -n 1 -r

echo ""



if \[\[ ! $REPLY =~ ^\[Yy]$ ]]; then

&nbsp;   echo "Cancelled"

&nbsp;   exit 0

fi



echo ""

echo "=== Emergency Reset in Progress ==="



\# 1. Kill all development processes

echo "\[1/7] Stopping all processes..."

taskkill //F //IM php.exe 2>/dev/null \&\& echo "      ✓ Stopped PHP"

taskkill //F //IM node.exe 2>/dev/null \&\& echo "      ✓ Stopped Node.js"

taskkill //F //IM chrome.exe 2>/dev/null \&\& echo "      ✓ Stopped Chrome"

pkill -f "mcp-server" 2>/dev/null \&\& echo "      ✓ Stopped MCP servers"



sleep 2



\# 2. Clear MCP cache and memory

echo "\[2/7] Clearing MCP cache..."

rm -rf ~/.cursor/mcp-cache/\* 2>/dev/null

rm -rf ~/.cursor/mcp-memory/\* 2>/dev/null

rm -rf /tmp/mcp-\* 2>/dev/null

echo "      ✓ MCP cache cleared"



\# 3. Clear Node.js cache

echo "\[3/7] Clearing Node.js cache..."

npm cache clean --force 2>/dev/null

echo "      ✓ Node cache cleared"



\# 4. Clear npm/npx cache

echo "\[4/7] Clearing npx cache..."

rm -rf ~/.npm/\_npx 2>/dev/null

echo "      ✓ npx cache cleared"



\# 5. Reset Chrome debug profile

echo "\[5/7] Resetting Chrome debug profile..."

rm -rf "C:/temp/chrome-debug-mcp" 2>/dev/null

mkdir -p "C:/temp/chrome-debug-mcp"

echo "      ✓ Chrome profile reset"



\# 6. Restart services

echo "\[6/7] Restarting services..."

./scripts/start-chrome-debug.sh

./tests/start\_server.sh > /dev/null 2>\&1 \&

sleep 3

echo "      ✓ Services restarted"



\# 7. Verify environment

echo "\[7/7] Verifying environment..."

./scripts/check-mcp-health.sh



echo ""

echo "==================================="

echo "✓ Emergency reset complete"

echo ""

echo "NEXT STEPS (IMPORTANT):"

echo "  1. Close Cursor IDE completely"

echo "  2. Restart Cursor IDE"

echo "  3. Open project in new window"

echo "  4. Open Git Bash terminal (Ctrl+\\`)"

echo "  5. Run validation: ./scripts/validate-environment.sh"

echo "  6. Run tests: php tests/run\_comprehensive\_tests.php"

```



---



\## Part 2 Complete



\*\*Files created:\*\*

\- ✅ `scripts/start-chrome-debug.sh`

\- ✅ `scripts/check-mcp-health.sh`

\- ✅ `scripts/validate-environment.sh`

\- ✅ `scripts/restart-mcp-servers.sh`

\- ✅ `scripts/emergency-reset.sh`



\*\*Remember to make all scripts executable:\*\*

```bash

chmod +x scripts/start-chrome-debug.sh

chmod +x scripts/check-mcp-health.sh

chmod +x scripts/validate-environment.sh

chmod +x scripts/restart-mcp-servers.sh

chmod +x scripts/emergency-reset.sh

```



\*\*Next:\*\* Proceed to Part 3 for documentation and troubleshooting guides.

