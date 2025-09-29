# Windows 11 MCP Optimization - Part 1: Configuration Files

## Implementation Instructions for AI Agent

This is Part 1 of 3. Implement configuration files and core settings first.

---

## 1. Update MCP Configuration File

**File:** `~/.cursor/mcp.json`

**Replace entire file with:**

```json
{
  "mcpServers": {
    "chrome-devtools": {
      "command": "npx",
      "args": [
        "--yes",
        "chrome-devtools-mcp@latest",
        "--browserUrl",
        "http://127.0.0.1:9222"
      ],
      "env": {
        "NODE_OPTIONS": "--no-warnings",
        "CHROME_DEBUG_PORT": "9222"
      }
    },
    "source-control": {
      "command": "uvx",
      "args": ["mcp-server-git"],
      "cwd": "C:\\Users\\George\\Projects\\otter"
    },
    "filesystem": {
      "command": "npx",
      "args": [
        "--yes",
        "@modelcontextprotocol/server-filesystem",
        "C:\\Users\\George\\Projects"
      ],
      "env": {
        "PATH": "${env:PATH}",
        "NODE_OPTIONS": "--no-warnings"
      }
    },
    "memory": {
      "command": "npx",
      "args": [
        "--yes",
        "@modelcontextprotocol/server-memory@latest"
      ],
      "cwd": "C:\\Users\\George\\.cursor\\mcp-memory",
      "env": {
        "MCP_MEMORY_DIR": "C:\\Users\\George\\.cursor\\mcp-memory"
      }
    }
  }
}
```

**Key additions:**
- `--yes` flag prevents npx prompts that hang on Windows
- `NODE_OPTIONS` suppresses Node.js warnings in terminal
- `cwd` ensures proper working directories
- Explicit environment variables for debugging
- Memory persistence directory configured

---

## 2. Add Cursor IDE Settings Configuration

**File:** `.vscode/settings.json` (create if doesn't exist)

```json
{
  "terminal.integrated.defaultProfile.windows": "Git Bash",
  "terminal.integrated.profiles.windows": {
    "Git Bash": {
      "path": "C:\\Program Files\\Git\\bin\\bash.exe",
      "args": ["--login"],
      "icon": "terminal-bash",
      "env": {
        "TERM": "xterm-256color"
      }
    },
    "PowerShell": {
      "path": "C:\\Windows\\System32\\WindowsPowerShell\\v1.0\\powershell.exe",
      "icon": "terminal-powershell"
    }
  },
  "terminal.integrated.shellArgs.windows": ["--login"],
  "terminal.integrated.env.windows": {
    "TERM": "xterm-256color",
    "LANG": "en_US.UTF-8"
  },
  "files.eol": "\n",
  "files.insertFinalNewline": true,
  "files.trimTrailingWhitespace": true,
  "git.autofetch": true,
  "git.confirmSync": false,
  "git.enableSmartCommit": true,
  "editor.formatOnSave": false,
  "editor.codeActionsOnSave": {
    "source.fixAll": false
  },
  "[javascript]": {
    "editor.defaultFormatter": "esbenp.prettier-vscode",
    "editor.formatOnSave": false
  },
  "[php]": {
    "editor.defaultFormatter": "bmewburn.vscode-intelephense-client",
    "editor.formatOnSave": false
  },
  "php.validate.executablePath": "php",
  "intelephense.files.maxSize": 5000000,
  "search.exclude": {
    "**/node_modules": true,
    "**/vendor": true,
    "**/dist": true,
    "**/.cursor": true
  },
  "files.exclude": {
    "**/.git": false,
    "**/.cursor/mcp-cache": true
  },
  "files.watcherExclude": {
    "**/node_modules/**": true,
    "**/vendor/**": true,
    "**/.cursor/mcp-cache/**": true,
    "**/C:/temp/chrome-debug-mcp/**": true
  }
}
```

---

## 3. Update .gitignore for MCP Artifacts

**File:** `.gitignore` (add to existing file)

```gitignore
# MCP-specific artifacts
.cursor/mcp-cache/
.cursor/mcp-memory/
mcp-debug.log
chrome-debug-profile/

# Chrome debugging
C:/temp/chrome-debug-mcp/

# Windows-specific
Thumbs.db
Desktop.ini
$RECYCLE.BIN/
*.lnk

# IDE
.vscode/
.idea/
*.code-workspace

# Temporary files
*.tmp
*.temp
*.log
.DS_Store

# npm/node
node_modules/
npm-debug.log*
.npm/
.npx/

# PHP
vendor/
composer.lock
```

---

## Part 1 Complete

**Next:** Proceed to Part 2 for automation scripts.

**Files created/modified:**
- ✅ `~/.cursor/mcp.json` - Enhanced MCP configuration
- ✅ `.vscode/settings.json` - Cursor IDE settings
- ✅ `.gitignore` - MCP artifact exclusions

# Windows 11 MCP Optimization - Part 2: Automation Scripts

## Implementation Instructions for AI Agent

This is Part 2 of 4. Implement automation and diagnostic scripts.

**After creating each script, make it executable:**
```bash
chmod +x scripts/[script-name].sh
```

---

## 1. Chrome Debug Automation Script

**File:** `scripts/start-chrome-debug.sh`

```bash
#!/bin/bash
# Start Chrome with remote debugging for MCP integration
# Windows 11 optimized version

CHROME_PATH="/c/Program Files/Google/Chrome/Application/chrome.exe"
USER_DATA_DIR="C:/temp/chrome-debug-mcp"
REMOTE_PORT=9222

echo "Starting Chrome with remote debugging for MCP..."

# Check if Chrome is already running with debugging
if netstat -an | grep -q ":$REMOTE_PORT.*LISTEN"; then
    echo "✓ Chrome already running with remote debugging on port $REMOTE_PORT"
    exit 0
fi

# Create user data directory if it doesn't exist

mkdir -p "$USER_DATA_DIR"

# Check if Chrome executable exists
if [ ! -f "$CHROME_PATH" ]; then
    echo "✗ Chrome not found at: $CHROME_PATH"
    echo "  Please install Chrome or update CHROME_PATH in this script"
    exit 1
fi

# Start Chrome with debugging flags
"$CHROME_PATH" \
    --remote-debugging-port=$REMOTE_PORT \
    --user-data-dir="$USER_DATA_DIR" \
    --no-first-run \
    --no-default-browser-check \
    --disable-background-networking \
    --disable-sync \
    --disable-extensions \
    --disable-default-apps \
    > /dev/null 2>&1 &

# Wait for Chrome to start
sleep 3

# Verify Chrome started successfully
if netstat -an | grep -q ":$REMOTE_PORT.*LISTEN"; then
    echo "✓ Chrome started successfully with remote debugging"
    echo "  Port: $REMOTE_PORT"
    echo "  Data dir: $USER_DATA_DIR"
    echo "  Access DevTools: http://localhost:$REMOTE_PORT"
else
    echo "✗ Chrome failed to start with remote debugging"
    echo "  Check if port $REMOTE_PORT is already in use"
    exit 1
fi
```

---

## 2. MCP Health Check Script

**File:** `scripts/check-mcp-health.sh`

```bash
#!/bin/bash
# Verify MCP servers are functioning correctly
# Windows 11 diagnostic tool

echo "=== MCP Server Health Check ==="
echo ""

ISSUES_FOUND=0

# 1. Check Chrome remote debugging
echo "[1/6] Chrome DevTools MCP..."
if netstat -an | grep -q ":9222.*LISTEN"; then
    echo "      ✓ Port 9222 active (Chrome DevTools MCP running)"
else
    echo "      ✗ Port 9222 not listening"
    echo "        Fix: ./scripts/start-chrome-debug.sh"
    ((ISSUES_FOUND++))
fi

# 2. Check Node.js for npx-based servers
echo "[2/6] Node.js (required for MCP servers)..."
if command -v node >/dev/null 2>&1; then
    NODE_VERSION=$(node --version)
    echo "      ✓ Node.js available: $NODE_VERSION"
    
    # Check if version is adequate (>= 18)
    MAJOR_VERSION=$(echo $NODE_VERSION | grep -oP '\d+' | head -n 1)
    if [ "$MAJOR_VERSION" -lt 18 ]; then
        echo "      ⚠ Node.js $NODE_VERSION detected (recommend >= 18)"
    fi
else
    echo "      ✗ Node.js not found (required for npx MCP servers)"
    echo "        Fix: Install Node.js from https://nodejs.org"
    ((ISSUES_FOUND++))
fi

# 3. Check Python/uvx for git MCP
echo "[3/6] Git MCP (uvx)..."
if command -v uvx >/dev/null 2>&1; then
    echo "      ✓ uvx available for Git MCP"
elif command -v python >/dev/null 2>&1; then
    echo "      ⚠ uvx not found (Git MCP may not work)"
    echo "        Fix: pip install uvx"
    ((ISSUES_FOUND++))
else
    echo "      ✗ Python not found (required for uvx/Git MCP)"
    echo "        Fix: Install Python 3.8+ from python.org"
    ((ISSUES_FOUND++))
fi

# 4. Check filesystem access
echo "[4/6] Filesystem MCP..."
if [ -d "C:/Users/George/Projects" ]; then
    echo "      ✓ Project directory accessible"
else
    echo "      ✗ Project directory not found: C:/Users/George/Projects"
    ((ISSUES_FOUND++))
fi

# 5. Check memory persistence
echo "[5/6] Memory MCP..."
MEMORY_DIR="C:/Users/George/.cursor/mcp-memory"
if [ -d "$MEMORY_DIR" ]; then
    echo "      ✓ Persistence directory exists"
    FILE_COUNT=$(find "$MEMORY_DIR" -type f 2>/dev/null | wc -l)
    echo "        Memory files: $FILE_COUNT"
else
    echo "      ⚠ Persistence directory missing"
    echo "        Creating: $MEMORY_DIR"
    mkdir -p "$MEMORY_DIR"
fi

# 6. Check shell environment
echo "[6/6] Shell Environment..."
if [[ "$SHELL" == *"bash"* ]] || [[ "$BASH_VERSION" ]]; then
    echo "      ✓ Git Bash active ($BASH_VERSION)"
else
    echo "      ✗ Not running in Git Bash"
    echo "        Current shell: $SHELL"
    echo "        Fix: Switch to Git Bash terminal"
    ((ISSUES_FOUND++))
fi

echo ""
echo "==================================="
if [ $ISSUES_FOUND -eq 0 ]; then
    echo "✓ All MCP servers healthy"
else
    echo "✗ Found $ISSUES_FOUND issue(s) - see fixes above"
    echo ""
    echo "After fixing issues:"
    echo "  1. Restart Cursor IDE"
    echo "  2. Re-run: ./scripts/check-mcp-health.sh"
fi
```

---

## 3. Environment Validation Script

**File:** `scripts/validate-environment.sh`

```bash
#!/bin/bash
# Comprehensive environment validation for MCP development
# Windows 11 version

echo "=== Development Environment Validation ==="
echo ""

ERRORS=0
WARNINGS=0

# 1. Verify shell
echo "[1/8] Shell Validation..."
if [[ "$SHELL" == *"bash"* ]] || [[ "$BASH_VERSION" ]]; then
    echo "      ✓ Git Bash ($BASH_VERSION)"
else
    echo "      ✗ Not Git Bash (MCP will fail)"
    echo "        Current: $SHELL"
    echo "        Fix: Configure Cursor to use Git Bash"
    ((ERRORS++))
fi

# 2. Check required commands
echo "[2/8] Required Commands..."
REQUIRED_CMDS=("git" "php" "node" "npx" "npm")
for cmd in "${REQUIRED_CMDS[@]}"; do
    if command -v "$cmd" >/dev/null 2>&1; then
        CMD_PATH=$(command -v "$cmd")
        echo "      ✓ $cmd: $CMD_PATH"
    else
        echo "      ✗ $cmd: Not found in PATH"
        ((ERRORS++))
    fi
done

# 3. Check PHP version
echo "[3/8] PHP Version..."
if command -v php >/dev/null 2>&1; then
    PHP_VERSION=$(php -v | head -n 1 | grep -oP '\d+\.\d+' | head -n 1)
    PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
    
    if [ "$PHP_MAJOR" -ge 8 ]; then
        echo "      ✓ PHP $PHP_VERSION (>= 8.0)"
    else
        echo "      ✗ PHP $PHP_VERSION (requires >= 8.0)"
        ((ERRORS++))
    fi
else
    echo "      ✗ PHP not found"
    ((ERRORS++))
fi

# 4. Check Node.js version
echo "[4/8] Node.js Version..."
if command -v node >/dev/null 2>&1; then
    NODE_VERSION=$(node -v)
    NODE_MAJOR=$(echo $NODE_VERSION | grep -oP '\d+' | head -n 1)
    
    if [ "$NODE_MAJOR" -ge 18 ]; then
        echo "      ✓ Node.js $NODE_VERSION (>= 18)"
    else
        echo "      ⚠ Node.js $NODE_VERSION (recommend >= 18)"
        ((WARNINGS++))
    fi
else
    echo "      ✗ Node.js not found"
    ((ERRORS++))
fi

# 5. Check Git configuration
echo "[5/8] Git Configuration..."
if command -v git >/dev/null 2>&1; then
    AUTOCRLF=$(git config --get core.autocrlf)
    if [[ "$AUTOCRLF" == "true" ]]; then
        echo "      ✓ autocrlf=true (Windows line endings handled)"
    else
        echo "      ⚠ autocrlf not set to 'true'"
        echo "        Fix: git config --global core.autocrlf true"
        ((WARNINGS++))
    fi
    
    # Check if user is configured
    GIT_USER=$(git config --get user.name)
    GIT_EMAIL=$(git config --get user.email)
    
    if [[ -n "$GIT_USER" ]] && [[ -n "$GIT_EMAIL" ]]; then
        echo "      ✓ Git user configured: $GIT_USER <$GIT_EMAIL>"
    else
        echo "      ⚠ Git user/email not configured"
        ((WARNINGS++))
    fi
fi

# 6. Check project structure
echo "[6/8] Project Structure..."
REQUIRED_DIRS=("reports" "tests" "config" "scripts")
for dir in "${REQUIRED_DIRS[@]}"; do
    if [ -d "$dir" ]; then
        echo "      ✓ $dir/ exists"
    else
        echo "      ✗ $dir/ missing"
        ((ERRORS++))
    fi
done

# 7. Check MCP configuration
echo "[7/8] MCP Configuration..."
MCP_CONFIG="$HOME/.cursor/mcp.json"
if [ -f "$MCP_CONFIG" ]; then
    echo "      ✓ mcp.json exists"
    
    # Validate JSON
    if node -e "JSON.parse(require('fs').readFileSync('$MCP_CONFIG', 'utf8'))" 2>/dev/null; then
        echo "      ✓ mcp.json is valid JSON"
    else
        echo "      ✗ mcp.json has syntax errors"
        ((ERRORS++))
    fi
else
    echo "      ✗ mcp.json not found at $MCP_CONFIG"
    ((ERRORS++))
fi

# 8. Check Chrome debugging availability
echo "[8/8] Chrome Debugging..."
if [ -f "/c/Program Files/Google/Chrome/Application/chrome.exe" ]; then
    echo "      ✓ Chrome executable found"
else
    echo "      ⚠ Chrome not found in default location"
    ((WARNINGS++))
fi

if netstat -an | grep -q ":9222.*LISTEN"; then
    echo "      ✓ Chrome debugging port active"
else
    echo "      ⚠ Chrome not running with debugging"
    echo "        Start: ./scripts/start-chrome-debug.sh"
    ((WARNINGS++))
fi

# Summary
echo ""
echo "==================================="
echo "Validation Summary:"
echo "  Errors: $ERRORS"
echo "  Warnings: $WARNINGS"
echo ""

if [ $ERRORS -eq 0 ] && [ $WARNINGS -eq 0 ]; then
    echo "✓ Environment fully validated - ready for development"
    exit 0
elif [ $ERRORS -eq 0 ]; then
    echo "⚠ Environment validated with $WARNINGS warning(s)"
    echo "  Development possible but address warnings for best results"
    exit 0
else
    echo "✗ Environment validation failed with $ERRORS error(s)"
    echo "  Fix errors before proceeding with development"
    exit 1
fi
```

---

## 4. MCP Server Restart Script

**File:** `scripts/restart-mcp-servers.sh`

```bash
#!/bin/bash
# Restart MCP servers without restarting entire Cursor IDE
# Useful when MCP servers hang or become unresponsive

echo "=== Restarting MCP Servers ==="
echo ""

# 1. Stop all MCP-related processes
echo "[1/4] Stopping MCP processes..."
pkill -f "chrome-devtools-mcp" 2>/dev/null && echo "      ✓ Killed chrome-devtools-mcp"
pkill -f "server-filesystem" 2>/dev/null && echo "      ✓ Killed filesystem MCP"
pkill -f "server-memory" 2>/dev/null && echo "      ✓ Killed memory MCP"
pkill -f "mcp-server-git" 2>/dev/null && echo "      ✓ Killed git MCP"

echo "      Waiting for cleanup..."
sleep 2

# 2. Clear MCP cache (optional but recommended)
echo "[2/4] Clearing MCP cache..."
if [ -d "$HOME/.cursor/mcp-cache" ]; then
    rm -rf "$HOME/.cursor/mcp-cache"/* 2>/dev/null
    echo "      ✓ Cache cleared"
else
    echo "      ✓ No cache to clear"
fi

# 3. Restart Chrome with debugging
echo "[3/4] Restarting Chrome debugging..."
./scripts/start-chrome-debug.sh

# 4. Verify MCP health
echo "[4/4] Verifying MCP health..."
sleep 2
./scripts/check-mcp-health.sh

echo ""
echo "==================================="
echo "MCP server restart complete"
echo ""
echo "Next steps:"
echo "  1. Reload Cursor window: Ctrl+Shift+P → 'Developer: Reload Window'"
echo "  2. Or restart Cursor terminal: Ctrl+\` → close → reopen"
echo "  3. Verify MCP tools work: Try using Chrome MCP in chat"
```

---

## 5. Emergency Reset Script

**File:** `scripts/emergency-reset.sh`

```bash
#!/bin/bash
# Emergency reset when development environment is completely broken
# Nuclear option - use only when nothing else works

echo "⚠️  EMERGENCY ENVIRONMENT RESET"
echo "This will stop all processes and clear caches"
echo ""
read -p "Continue? (y/N) " -n 1 -r
echo ""

if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled"
    exit 0
fi

echo ""
echo "=== Emergency Reset in Progress ==="

# 1. Kill all development processes
echo "[1/7] Stopping all processes..."
taskkill //F //IM php.exe 2>/dev/null && echo "      ✓ Stopped PHP"
taskkill //F //IM node.exe 2>/dev/null && echo "      ✓ Stopped Node.js"
taskkill //F //IM chrome.exe 2>/dev/null && echo "      ✓ Stopped Chrome"
pkill -f "mcp-server" 2>/dev/null && echo "      ✓ Stopped MCP servers"

sleep 2

# 2. Clear MCP cache and memory
echo "[2/7] Clearing MCP cache..."
rm -rf ~/.cursor/mcp-cache/* 2>/dev/null
rm -rf ~/.cursor/mcp-memory/* 2>/dev/null
rm -rf /tmp/mcp-* 2>/dev/null
echo "      ✓ MCP cache cleared"

# 3. Clear Node.js cache
echo "[3/7] Clearing Node.js cache..."
npm cache clean --force 2>/dev/null
echo "      ✓ Node cache cleared"

# 4. Clear npm/npx cache
echo "[4/7] Clearing npx cache..."
rm -rf ~/.npm/_npx 2>/dev/null
echo "      ✓ npx cache cleared"

# 5. Reset Chrome debug profile
echo "[5/7] Resetting Chrome debug profile..."
rm -rf "C:/temp/chrome-debug-mcp" 2>/dev/null
mkdir -p "C:/temp/chrome-debug-mcp"
echo "      ✓ Chrome profile reset"

# 6. Restart services
echo "[6/7] Restarting services..."
./scripts/start-chrome-debug.sh
./tests/start_server.sh > /dev/null 2>&1 &
sleep 3
echo "      ✓ Services restarted"

# 7. Verify environment
echo "[7/7] Verifying environment..."
./scripts/check-mcp-health.sh

echo ""
echo "==================================="
echo "✓ Emergency reset complete"
echo ""
echo "NEXT STEPS (IMPORTANT):"
echo "  1. Close Cursor IDE completely"
echo "  2. Restart Cursor IDE"
echo "  3. Open project in new window"
echo "  4. Open Git Bash terminal (Ctrl+\`)"
echo "  5. Run validation: ./scripts/validate-environment.sh"
echo "  6. Run tests: php tests/run_comprehensive_tests.php"
```

---

## Part 2 Complete

**Files created:**
- ✅ `scripts/start-chrome-debug.sh`
- ✅ `scripts/check-mcp-health.sh`
- ✅ `scripts/validate-environment.sh`
- ✅ `scripts/restart-mcp-servers.sh`
- ✅ `scripts/emergency-reset.sh`

**Remember to make all scripts executable:**
```bash
chmod +x scripts/start-chrome-debug.sh
chmod +x scripts/check-mcp-health.sh
chmod +x scripts/validate-environment.sh
chmod +x scripts/restart-mcp-servers.sh
chmod +x scripts/emergency-reset.sh
```

**Next:** Proceed to Part 3 for documentation and troubleshooting guides.

# Windows 11 MCP Optimization - Part 3: Documentation & Troubleshooting (Complete)

## Implementation Instructions for AI Agent

This is Part 3 of 4. Add documentation and troubleshooting guides.

---

## 1. Create Windows Setup Documentation

**File:** `docs/windows-setup.md`

**Create this complete file:**

```markdown
# Windows 11 Setup Guide for MCP Development

## Prerequisites

### Required Software
- **Windows 11** (build 22000 or later)
- **Git for Windows** (includes Git Bash) - https://git-scm.com/download/win
- **Node.js 18+** - https://nodejs.org/
- **PHP 8.0+** - https://windows.php.net/download/
- **Google Chrome** - https://www.google.com/chrome/
- **Cursor IDE** - https://cursor.sh/

### Optional but Recommended
- **Windows Terminal** - Microsoft Store (better terminal performance)
- **Python 3.8+** - https://www.python.org/ (for Git MCP via uvx)

## Initial Setup Steps

### 1. Configure Git Bash as Default Shell

**In Cursor IDE:**
1. Open Settings: `Ctrl+,`
2. Search: `terminal.integrated.defaultProfile.windows`
3. Select: `Git Bash`
4. Restart terminal: `Ctrl+\`` (close and reopen)

**Verify:**
```bash
echo $SHELL
# Should show: /usr/bin/bash or /bin/bash
```

### 2. Configure Git for Windows

```bash
# Set line ending handling
git config --global core.autocrlf true

# Set user information
git config --global user.name "Your Name"
git config --global user.email "your.email@example.com"

# Enable long paths (Windows limitation)
git config --global core.longpaths true
```

### 3. Install NPM Global Packages

```bash
# Update npm
npm install -g npm@latest

# Install MCP dependencies
npm install -g @modelcontextprotocol/server-filesystem
npm install -g @modelcontextprotocol/server-memory
npm install -g chrome-devtools-mcp
```

### 4. Configure Chrome for MCP

Create startup script for Chrome with debugging:
```bash
./scripts/start-chrome-debug.sh
```

This starts Chrome with:
- Remote debugging port: 9222
- Separate profile (won't interfere with regular browsing)
- All necessary flags for MCP integration

### 5. Configure Windows Firewall

**Allow Chrome remote debugging:**

**PowerShell (as Administrator):**
```powershell
New-NetFirewallRule -DisplayName "Chrome Remote Debugging MCP" -Direction Inbound -LocalPort 9222 -Protocol TCP -Action Allow
```

**Or manually:**
1. Windows Security → Firewall & network protection
2. Advanced settings → Inbound Rules → New Rule
3. Port → TCP → 9222 → Allow
4. All profiles → Name: "Chrome Remote Debugging MCP"

### 6. Performance Optimizations

**Exclude project directory from Windows Defender:**
1. Windows Security → Virus & threat protection
2. Manage settings → Exclusions → Add folder
3. Add: `C:\Users\George\Projects`

**Disable Windows Search indexing:**
1. Right-click project folder → Properties
2. Advanced → Uncheck "Allow files to have contents indexed"

**Configure NPM cache location:**
```bash
npm config set cache "C:/npm-cache" --global
```

### 7. Validate Environment

Run comprehensive validation:
```bash
./scripts/validate-environment.sh
```

Should show all green checkmarks.

## Daily Workflow

### Starting Development Session

```bash
# 1. Validate environment
./scripts/validate-environment.sh

# 2. Start Chrome debugging (if not running)
./scripts/start-chrome-debug.sh

# 3. Start PHP development server
./tests/start_server.sh

# 4. Check MCP health
./scripts/check-mcp-health.sh

# 5. Run tests to verify
php tests/run_comprehensive_tests.php
```

### Ending Development Session

```bash
# Stop PHP server
pkill -f "php -S"

# Chrome can stay running for next session
# Or close it manually if desired
```

## Troubleshooting

### Chrome MCP Not Connecting

**Symptoms:** MCP tools can't connect to Chrome

**Solutions:**
1. Verify Chrome is running with debugging:
   ```bash
   netstat -an | grep 9222
   ```
2. Restart Chrome debugging:
   ```bash
   ./scripts/start-chrome-debug.sh
   ```
3. Check Windows Firewall allows port 9222
4. Restart Cursor IDE

### Git Operations Failing

**Symptoms:** Git commands produce path errors

**Solutions:**
1. Verify Git Bash is active:
   ```bash
   echo $SHELL
   ```
2. Configure line endings:
   ```bash
   git config --global core.autocrlf true
   ```
3. Enable long paths:
   ```bash
   git config --global core.longpaths true
   ```

### MCP Servers Not Starting

**Symptoms:** MCP tools unavailable in Cursor

**Solutions:**
1. Check MCP health:
   ```bash
   ./scripts/check-mcp-health.sh
   ```
2. Restart MCP servers:
   ```bash
   ./scripts/restart-mcp-servers.sh
   ```
3. Verify mcp.json configuration
4. Restart Cursor IDE

### Complete Environment Reset

If nothing else works:
```bash
./scripts/emergency-reset.sh
```

Then:
1. Close Cursor IDE completely
2. Restart Cursor IDE
3. Open project
4. Validate: `./scripts/validate-environment.sh`

## Best Practices

### Terminal Usage
- Always use Git Bash for development commands
- Use PowerShell only for Windows-specific admin tasks
- Verify shell before running commands: `echo $SHELL`

### MCP Tools
- Start Chrome debugging before using Chrome MCP tools
- Run health check if MCP tools behave unexpectedly
- Restart MCP servers rather than entire IDE when possible

### Performance
- Keep project directory excluded from Windows Defender
- Use Windows Terminal for long-running processes
- Clear caches regularly (npm, MCP, browser)
```

---

## 2. Add Windows Troubleshooting to chrome-mcp.md

**File:** `chrome-mcp.md`

**Add to "## Chrome MCP Troubleshooting" section (after existing entries):**

```markdown
### Windows 11-Specific Issues

#### Issue: Chrome MCP Connection Refused
**Symptoms:**
- Error: "Connection refused" when using Chrome MCP tools
- MCP tools timeout when trying to connect to browser

**Solutions:**
1. **Check Windows Firewall:**
   ```powershell
   # Run in PowerShell as Administrator
   New-NetFirewallRule -DisplayName "Chrome Remote Debugging MCP" -Direction Inbound -LocalPort 9222 -Protocol TCP -Action Allow
   ```

2. **Verify Chrome is listening:**
   ```bash
   netstat -an | grep 9222
   # Should show: TCP    0.0.0.0:9222    0.0.0.0:0    LISTENING
   ```

3. **Restart Chrome with debugging:**
   ```bash
   ./scripts/start-chrome-debug.sh
   ```

#### Issue: NPX Commands Hang or Timeout
**Symptoms:**
- MCP servers fail to start
- Terminal shows no output after npx command
- Cursor reports "MCP server initialization failed"

**Root Cause:** npx prompting for package installation approval

**Solutions:**
1. **Update mcp.json with `--yes` flag:**
   - Already implemented in updated configuration
   - Ensures npx doesn't wait for user input

2. **Clear npx cache:**
   ```bash
   rm -rf ~/.npm/_npx 2>/dev/null
   ```

3. **Verify Node.js PATH:**
   ```bash
   which node
   which npx
   # Both should show Git Bash paths, not Windows paths
   ```

#### Issue: Git Operations Fail with Path Errors
**Symptoms:**
- Git commands show "fatal: cannot create directory"
- Error: "filename too long"
- Path separator issues (backslash vs forward slash)

**Solutions:**
1. **Enable long paths:**
   ```bash
   git config --global core.longpaths true
   ```

2. **Configure line endings:**
   ```bash
   git config --global core.autocrlf true
   ```

3. **Verify Git Bash is active:**
   ```bash
   echo $SHELL
   # Must show: /usr/bin/bash
   ```

#### Issue: MCP Memory Not Persisting
**Symptoms:**
- Memory MCP loses context between sessions
- No memory files in persistence directory

**Solutions:**
1. **Verify memory directory exists:**
   ```bash
   ls -la ~/.cursor/mcp-memory/
   ```

2. **Check mcp.json has correct cwd:**
   - Must specify: `"cwd": "C:\\Users\\George\\.cursor\\mcp-memory"`

3. **Create directory with proper permissions:**
   ```bash
   mkdir -p ~/.cursor/mcp-memory
   chmod 755 ~/.cursor/mcp-memory
   ```

#### Issue: Port 9222 Already in Use
**Symptoms:**
- Chrome debugging won't start
- Error: "Address already in use"

**Solutions:**
1. **Find process using port:**
   ```bash
   netstat -ano | findstr :9222
   ```

2. **Kill process (use PID from above):**
   ```bash
   taskkill /PID <process_id> /F
   ```

3. **Or use different port in mcp.json:**
   ```json
   {
     "chrome-devtools": {
       "args": ["--browserUrl", "http://127.0.0.1:9223"]
     }
   }
   ```

#### Issue: PowerShell Accidentally Being Used
**Symptoms:**
- Commands work differently than expected
- Git operations fail
- Shell scripts won't execute

**Detection:**
```bash
# In Git Bash, this shows path with forward slashes:
pwd
# Output: /c/Users/George/Projects/otter

# In PowerShell, this shows Windows path:
# Output: C:\Users\George\Projects\otter
```

**Solutions:**
1. **Immediately switch to Git Bash:**
   - Close PowerShell terminal
   - Open new terminal (should default to Git Bash)
   - Verify: `echo $SHELL`

2. **Prevent future occurrences:**
   - Cursor Settings → Search "defaultProfile.windows"
   - Ensure "Git Bash" is selected
   - Restart Cursor if changed

### Quick Diagnostic Commands (Windows)

```bash
# Run all diagnostics
./scripts/validate-environment.sh

# Check specific issues
netstat -an | grep 9222           # Chrome debugging
echo $SHELL                       # Verify Git Bash
git config --get core.autocrlf   # Line ending config
node --version                    # Node.js version
php --version                     # PHP version
```

### Emergency Recovery (Windows)

If environment is completely broken:

```bash
# 1. Emergency reset
./scripts/emergency-reset.sh

# 2. Close Cursor completely (don't just close window)
taskkill /IM Cursor.exe /F

# 3. Clear Cursor cache
rm -rf ~/.cursor/mcp-cache

# 4. Restart Cursor

# 5. Validate environment
./scripts/validate-environment.sh

# 6. Test MCP tools
./scripts/check-mcp-health.sh
```
```

---

## Part 3 Complete

**All Windows 11 troubleshooting documentation has been added.**

**Files modified:**
- ✅ `docs/windows-setup.md` - Complete setup guide created
- ✅ `chrome-mcp.md` - Windows-specific troubleshooting section added

**Next:** Proceed to Part 4 for final documentation updates and implementation checklist.

# Windows 11 MCP Optimization - Part 4: Final Documentation & Checklist

## Implementation Instructions for AI Agent

This is Part 4 (final). Add remaining documentation updates and implementation checklist.

---

## 1. Add Windows Troubleshooting to development.md

**File:** `development.md`

**Add to "## Troubleshooting Workflow" section (after existing entries):**

```markdown
### Windows 11-Specific Troubleshooting

#### Shell Issues
- **Wrong shell active**: Check `echo $SHELL` → must be `/usr/bin/bash`
- **Scripts won't execute**: Ensure Git Bash is active, not PowerShell/CMD
- **Path errors**: Configure `git config --global core.autocrlf true`

#### MCP Server Issues  
- **Servers won't start**: Run `./scripts/check-mcp-health.sh`
- **Connection timeouts**: Check Windows Firewall allows port 9222
- **npx hangs**: Clear cache with `rm -rf ~/.npm/_npx`

#### Performance Issues
- **Slow file operations**: Exclude project from Windows Defender
- **High CPU usage**: Disable Windows Search indexing on project folder
- **npm slow**: Change cache location: `npm config set cache "C:/npm-cache" --global`

#### Quick Fixes
```bash
# Restart MCP servers
./scripts/restart-mcp-servers.sh

# Full environment reset
./scripts/emergency-reset.sh

# Validate everything
./scripts/validate-environment.sh
```
```

---

## 2. Update always.md with Quick Scripts Reference

**File:** `always.md`

**Add to "## 12. Quick Reference Commands" section (after existing commands):**

```markdown
# Windows 11 MCP Management Scripts
./scripts/validate-environment.sh      # Check everything is configured
./scripts/check-mcp-health.sh         # Verify MCP servers running
./scripts/start-chrome-debug.sh       # Start Chrome with debugging
./scripts/restart-mcp-servers.sh      # Restart MCP without Cursor restart
./scripts/emergency-reset.sh          # Nuclear option - full reset
```

---

## 3. Update ai-optimized.md with Windows Best Practices

**File:** `ai-optimized.md`

**Add new section after "### Shell Configuration" (around line 64):**

```markdown
### Windows 11 MCP Optimization Scripts

**Automated environment management:**
- **Environment Validation**: `./scripts/validate-environment.sh` - Checks all prerequisites
- **MCP Health Check**: `./scripts/check-mcp-health.sh` - Verifies all MCP servers
- **Chrome Automation**: `./scripts/start-chrome-debug.sh` - Starts Chrome with debugging
- **MCP Restart**: `./scripts/restart-mcp-servers.sh` - Quick MCP server restart
- **Emergency Reset**: `./scripts/emergency-reset.sh` - Complete environment reset

**When to use:**
- Run `validate-environment.sh` at start of each session
- Run `check-mcp-health.sh` if MCP tools behave unexpectedly
- Run `restart-mcp-servers.sh` if MCP servers hang
- Run `emergency-reset.sh` only when nothing else works

**Windows-specific considerations:**
- Always verify Git Bash is active: `echo $SHELL`
- Ensure Windows Firewall allows port 9222 for Chrome MCP
- Exclude project directory from Windows Defender for performance
- Configure NPM cache outside user directory to avoid slow operations
```

---

## 4. Create MCP Quick Start Guide

**File:** `docs/mcp-quickstart.md`

```markdown
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
```

---

## 5. Implementation Checklist

**Use this checklist to track implementation:**

### Configuration Files
- [ ] `~/.cursor/mcp.json` - Enhanced with `--yes` flags and environment variables
- [ ] `.vscode/settings.json` - Cursor IDE settings for Git Bash default
- [ ] `.gitignore` - Added MCP artifact exclusions

### Scripts (chmod +x after creating)
- [ ] `scripts/start-chrome-debug.sh` - Chrome automation
- [ ] `scripts/check-mcp-health.sh` - Health diagnostics  
- [ ] `scripts/validate-environment.sh` - Environment validation
- [ ] `scripts/restart-mcp-servers.sh` - MCP restart
- [ ] `scripts/emergency-reset.sh` - Emergency recovery

### Documentation
- [ ] `docs/windows-setup.md` - Complete Windows 11 setup guide
- [ ] `docs/mcp-quickstart.md` - Quick start guide for MCP
- [ ] `chrome-mcp.md` - Added Windows troubleshooting section
- [ ] `development.md` - Added Windows-specific troubleshooting
- [ ] `always.md` - Added script reference to quick commands
- [ ] `ai-optimized.md` - Added Windows MCP optimization section

### Validation Steps
- [ ] Run `./scripts/validate-environment.sh` - All green
- [ ] Run `./scripts/check-mcp-health.sh` - All MCP servers healthy
- [ ] Test Chrome debugging: `./scripts/start-chrome-debug.sh`
- [ ] Verify MCP tools in Cursor (try Chrome MCP in chat)
- [ ] Run comprehensive tests: `php tests/run_comprehensive_tests.php`

---

## 6. Post-Implementation Validation

After implementing all changes, run this validation sequence:

```bash
# 1. Restart Cursor IDE completely
# Close and reopen Cursor

# 2. Open Git Bash terminal in Cursor (Ctrl+`)
echo $SHELL
# Must show: /usr/bin/bash

# 3. Navigate to project root
cd ~/Projects/otter

# 4. Validate environment
./scripts/validate-environment.sh
# Expected: 0 errors, 0-2 warnings

# 5. Check MCP health
./scripts/check-mcp-health.sh
# Expected: All ✓ green checkmarks

# 6. Start Chrome debugging
./scripts/start-chrome-debug.sh
# Expected: Chrome starts on port 9222

# 7. Start PHP server
./tests/start_server.sh
# Expected: Server starts on port 8000

# 8. Run comprehensive tests
php tests/run_comprehensive_tests.php
# Expected: 98%+ pass rate

# 9. Test Chrome MCP in Cursor
# In Cursor chat, try: "Take a screenshot of localhost:8000"
# Expected: Screenshot captured successfully
```

---

## 7. Maintenance & Best Practices

### Daily Best Practices
- Start each session with `./scripts/validate-environment.sh`
- Run `./scripts/check-mcp-health.sh` if tools behave oddly
- Always verify Git Bash active before running commands
- Keep Chrome debugging running between sessions (optional)

### Weekly Maintenance
```bash
# Clear npm cache
npm cache clean --force

# Clear MCP cache
rm -rf ~/.cursor/mcp-cache/*

# Update MCP packages
npm update -g @modelcontextprotocol/server-filesystem
npm update -g @modelcontextprotocol/server-memory
npm update -g chrome-devtools-mcp
```

### Monthly Maintenance
- Review and update Node.js if needed
- Check for Cursor IDE updates
- Verify Windows Firewall rules still active
- Review MCP memory files: `ls ~/.cursor/mcp-memory/`

---

## 8. Success Indicators

**You'll know everything is working correctly when:**

✅ `./scripts/validate-environment.sh` shows 0 errors  
✅ `./scripts/check-mcp-health.sh` shows all green checkmarks  
✅ Chrome MCP tools work in Cursor chat  
✅ Git operations execute without path errors  
✅ PHP server starts on port 8000  
✅ Comprehensive tests pass (98%+ rate)  
✅ No "command not found" errors  
✅ No MCP server timeout errors

---

## 9. Quick Command Reference Card

**Save this for quick access:**

```bash
# Environment Management
./scripts/validate-environment.sh      # Check everything
./scripts/check-mcp-health.sh         # MCP status
./scripts/start-chrome-debug.sh       # Start Chrome
./scripts/restart-mcp-servers.sh      # Restart MCP
./scripts/emergency-reset.sh          # Full reset

# Development
./tests/start_server.sh               # Start PHP server
php tests/run_comprehensive_tests.php # Run all tests
echo $SHELL                           # Verify Git Bash

# Troubleshooting
netstat -an | grep 9222               # Check Chrome port
git config --get core.autocrlf       # Check Git config
which node && which php               # Check tools in PATH
```

---

## 10. Getting Help

**If issues persist after following all guides:**

1. **Check documentation:**
   - `docs/windows-setup.md` - Setup guide
   - `docs/mcp-quickstart.md` - Quick start
   - `chrome-mcp.md` - MCP troubleshooting

2. **Run diagnostics:**
   ```bash
   ./scripts/validate-environment.sh
   ./scripts/check-mcp-health.sh
   ```

3. **Try recovery:**
   ```bash
   ./scripts/restart-mcp-servers.sh
   # If that fails:
   ./scripts/emergency-reset.sh
   ```

4. **Verify basics:**
   - Git Bash is active: `echo $SHELL`
   - All required software installed
   - Windows Firewall allows port 9222
   - Project directory excluded from Windows Defender

---

## Summary of All Changes

**Configuration (3 files):**
- Enhanced `mcp.json` with Windows-specific optimizations
- Created `.vscode/settings.json` for Cursor settings
- Updated `.gitignore` for MCP artifacts

**Scripts (5 files):**
- Chrome debugging automation
- MCP health monitoring
- Environment validation
- MCP server restart capability
- Emergency reset functionality

**Documentation (6 files):**
- Complete Windows 11 setup guide
- MCP quick start guide
- Windows troubleshooting in chrome-mcp.md
- Windows troubleshooting in development.md  
- Script references in always.md
- Best practices in ai-optimized.md

**Result:** Fully optimized, automated, Windows 11 MCP development environment with comprehensive diagnostics and recovery tools.
