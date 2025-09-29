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
