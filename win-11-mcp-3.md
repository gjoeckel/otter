\# Windows 11 MCP Optimization - Part 3: Documentation \& Troubleshooting (Complete)



\## Implementation Instructions for AI Agent



This is Part 3 of 4. Add documentation and troubleshooting guides.



---



\## 1. Create Windows Setup Documentation



\*\*File:\*\* `docs/windows-setup.md`



\*\*Create this complete file:\*\*



```markdown

\# Windows 11 Setup Guide for MCP Development



\## Prerequisites



\### Required Software

\- \*\*Windows 11\*\* (build 22000 or later)

\- \*\*Git for Windows\*\* (includes Git Bash) - https://git-scm.com/download/win

\- \*\*Node.js 18+\*\* - https://nodejs.org/

\- \*\*PHP 8.0+\*\* - https://windows.php.net/download/

\- \*\*Google Chrome\*\* - https://www.google.com/chrome/

\- \*\*Cursor IDE\*\* - https://cursor.sh/



\### Optional but Recommended

\- \*\*Windows Terminal\*\* - Microsoft Store (better terminal performance)

\- \*\*Python 3.8+\*\* - https://www.python.org/ (for Git MCP via uvx)



\## Initial Setup Steps



\### 1. Configure Git Bash as Default Shell



\*\*In Cursor IDE:\*\*

1\. Open Settings: `Ctrl+,`

2\. Search: `terminal.integrated.defaultProfile.windows`

3\. Select: `Git Bash`

4\. Restart terminal: `Ctrl+\\`` (close and reopen)



\*\*Verify:\*\*

```bash

echo $SHELL

\# Should show: /usr/bin/bash or /bin/bash

```



\### 2. Configure Git for Windows



```bash

\# Set line ending handling

git config --global core.autocrlf true



\# Set user information

git config --global user.name "Your Name"

git config --global user.email "your.email@example.com"



\# Enable long paths (Windows limitation)

git config --global core.longpaths true

```



\### 3. Install NPM Global Packages



```bash

\# Update npm

npm install -g npm@latest



\# Install MCP dependencies

npm install -g @modelcontextprotocol/server-filesystem

npm install -g @modelcontextprotocol/server-memory

npm install -g chrome-devtools-mcp

```



\### 4. Configure Chrome for MCP



Create startup script for Chrome with debugging:

```bash

./scripts/start-chrome-debug.sh

```



This starts Chrome with:

\- Remote debugging port: 9222

\- Separate profile (won't interfere with regular browsing)

\- All necessary flags for MCP integration



\### 5. Configure Windows Firewall



\*\*Allow Chrome remote debugging:\*\*



\*\*PowerShell (as Administrator):\*\*

```powershell

New-NetFirewallRule -DisplayName "Chrome Remote Debugging MCP" -Direction Inbound -LocalPort 9222 -Protocol TCP -Action Allow

```



\*\*Or manually:\*\*

1\. Windows Security → Firewall \& network protection

2\. Advanced settings → Inbound Rules → New Rule

3\. Port → TCP → 9222 → Allow

4\. All profiles → Name: "Chrome Remote Debugging MCP"



\### 6. Performance Optimizations



\*\*Exclude project directory from Windows Defender:\*\*

1\. Windows Security → Virus \& threat protection

2\. Manage settings → Exclusions → Add folder

3\. Add: `C:\\Users\\George\\Projects`



\*\*Disable Windows Search indexing:\*\*

1\. Right-click project folder → Properties

2\. Advanced → Uncheck "Allow files to have contents indexed"



\*\*Configure NPM cache location:\*\*

```bash

npm config set cache "C:/npm-cache" --global

```



\### 7. Validate Environment



Run comprehensive validation:

```bash

./scripts/validate-environment.sh

```



Should show all green checkmarks.



\## Daily Workflow



\### Starting Development Session



```bash

\# 1. Validate environment

./scripts/validate-environment.sh



\# 2. Start Chrome debugging (if not running)

./scripts/start-chrome-debug.sh



\# 3. Start PHP development server

./tests/start\_server.sh



\# 4. Check MCP health

./scripts/check-mcp-health.sh



\# 5. Run tests to verify

php tests/run\_comprehensive\_tests.php

```



\### Ending Development Session



```bash

\# Stop PHP server

pkill -f "php -S"



\# Chrome can stay running for next session

\# Or close it manually if desired

```



\## Troubleshooting



\### Chrome MCP Not Connecting



\*\*Symptoms:\*\* MCP tools can't connect to Chrome



\*\*Solutions:\*\*

1\. Verify Chrome is running with debugging:

&nbsp;  ```bash

&nbsp;  netstat -an | grep 9222

&nbsp;  ```

2\. Restart Chrome debugging:

&nbsp;  ```bash

&nbsp;  ./scripts/start-chrome-debug.sh

&nbsp;  ```

3\. Check Windows Firewall allows port 9222

4\. Restart Cursor IDE



\### Git Operations Failing



\*\*Symptoms:\*\* Git commands produce path errors



\*\*Solutions:\*\*

1\. Verify Git Bash is active:

&nbsp;  ```bash

&nbsp;  echo $SHELL

&nbsp;  ```

2\. Configure line endings:

&nbsp;  ```bash

&nbsp;  git config --global core.autocrlf true

&nbsp;  ```

3\. Enable long paths:

&nbsp;  ```bash

&nbsp;  git config --global core.longpaths true

&nbsp;  ```



\### MCP Servers Not Starting



\*\*Symptoms:\*\* MCP tools unavailable in Cursor



\*\*Solutions:\*\*

1\. Check MCP health:

&nbsp;  ```bash

&nbsp;  ./scripts/check-mcp-health.sh

&nbsp;  ```

2\. Restart MCP servers:

&nbsp;  ```bash

&nbsp;  ./scripts/restart-mcp-servers.sh

&nbsp;  ```

3\. Verify mcp.json configuration

4\. Restart Cursor IDE



\### Complete Environment Reset



If nothing else works:

```bash

./scripts/emergency-reset.sh

```



Then:

1\. Close Cursor IDE completely

2\. Restart Cursor IDE

3\. Open project

4\. Validate: `./scripts/validate-environment.sh`



\## Best Practices



\### Terminal Usage

\- Always use Git Bash for development commands

\- Use PowerShell only for Windows-specific admin tasks

\- Verify shell before running commands: `echo $SHELL`



\### MCP Tools

\- Start Chrome debugging before using Chrome MCP tools

\- Run health check if MCP tools behave unexpectedly

\- Restart MCP servers rather than entire IDE when possible



\### Performance

\- Keep project directory excluded from Windows Defender

\- Use Windows Terminal for long-running processes

\- Clear caches regularly (npm, MCP, browser)

```



---



\## 2. Add Windows Troubleshooting to chrome-mcp.md



\*\*File:\*\* `chrome-mcp.md`



\*\*Add to "## Chrome MCP Troubleshooting" section (after existing entries):\*\*



```markdown

\### Windows 11-Specific Issues



\#### Issue: Chrome MCP Connection Refused

\*\*Symptoms:\*\*

\- Error: "Connection refused" when using Chrome MCP tools

\- MCP tools timeout when trying to connect to browser



\*\*Solutions:\*\*

1\. \*\*Check Windows Firewall:\*\*

&nbsp;  ```powershell

&nbsp;  # Run in PowerShell as Administrator

&nbsp;  New-NetFirewallRule -DisplayName "Chrome Remote Debugging MCP" -Direction Inbound -LocalPort 9222 -Protocol TCP -Action Allow

&nbsp;  ```



2\. \*\*Verify Chrome is listening:\*\*

&nbsp;  ```bash

&nbsp;  netstat -an | grep 9222

&nbsp;  # Should show: TCP    0.0.0.0:9222    0.0.0.0:0    LISTENING

&nbsp;  ```



3\. \*\*Restart Chrome with debugging:\*\*

&nbsp;  ```bash

&nbsp;  ./scripts/start-chrome-debug.sh

&nbsp;  ```



\#### Issue: NPX Commands Hang or Timeout

\*\*Symptoms:\*\*

\- MCP servers fail to start

\- Terminal shows no output after npx command

\- Cursor reports "MCP server initialization failed"



\*\*Root Cause:\*\* npx prompting for package installation approval



\*\*Solutions:\*\*

1\. \*\*Update mcp.json with `--yes` flag:\*\*

&nbsp;  - Already implemented in updated configuration

&nbsp;  - Ensures npx doesn't wait for user input



2\. \*\*Clear npx cache:\*\*

&nbsp;  ```bash

&nbsp;  rm -rf ~/.npm/\_npx 2>/dev/null

&nbsp;  ```



3\. \*\*Verify Node.js PATH:\*\*

&nbsp;  ```bash

&nbsp;  which node

&nbsp;  which npx

&nbsp;  # Both should show Git Bash paths, not Windows paths

&nbsp;  ```



\#### Issue: Git Operations Fail with Path Errors

\*\*Symptoms:\*\*

\- Git commands show "fatal: cannot create directory"

\- Error: "filename too long"

\- Path separator issues (backslash vs forward slash)



\*\*Solutions:\*\*

1\. \*\*Enable long paths:\*\*

&nbsp;  ```bash

&nbsp;  git config --global core.longpaths true

&nbsp;  ```



2\. \*\*Configure line endings:\*\*

&nbsp;  ```bash

&nbsp;  git config --global core.autocrlf true

&nbsp;  ```



3\. \*\*Verify Git Bash is active:\*\*

&nbsp;  ```bash

&nbsp;  echo $SHELL

&nbsp;  # Must show: /usr/bin/bash

&nbsp;  ```



\#### Issue: MCP Memory Not Persisting

\*\*Symptoms:\*\*

\- Memory MCP loses context between sessions

\- No memory files in persistence directory



\*\*Solutions:\*\*

1\. \*\*Verify memory directory exists:\*\*

&nbsp;  ```bash

&nbsp;  ls -la ~/.cursor/mcp-memory/

&nbsp;  ```



2\. \*\*Check mcp.json has correct cwd:\*\*

&nbsp;  - Must specify: `"cwd": "C:\\\\Users\\\\George\\\\.cursor\\\\mcp-memory"`



3\. \*\*Create directory with proper permissions:\*\*

&nbsp;  ```bash

&nbsp;  mkdir -p ~/.cursor/mcp-memory

&nbsp;  chmod 755 ~/.cursor/mcp-memory

&nbsp;  ```



\#### Issue: Port 9222 Already in Use

\*\*Symptoms:\*\*

\- Chrome debugging won't start

\- Error: "Address already in use"



\*\*Solutions:\*\*

1\. \*\*Find process using port:\*\*

&nbsp;  ```bash

&nbsp;  netstat -ano | findstr :9222

&nbsp;  ```



2\. \*\*Kill process (use PID from above):\*\*

&nbsp;  ```bash

&nbsp;  taskkill /PID <process\_id> /F

&nbsp;  ```



3\. \*\*Or use different port in mcp.json:\*\*

&nbsp;  ```json

&nbsp;  {

&nbsp;    "chrome-devtools": {

&nbsp;      "args": \["--browserUrl", "http://127.0.0.1:9223"]

&nbsp;    }

&nbsp;  }

&nbsp;  ```



\#### Issue: PowerShell Accidentally Being Used

\*\*Symptoms:\*\*

\- Commands work differently than expected

\- Git operations fail

\- Shell scripts won't execute



\*\*Detection:\*\*

```bash

\# In Git Bash, this shows path with forward slashes:

pwd

\# Output: /c/Users/George/Projects/otter



\# In PowerShell, this shows Windows path:

\# Output: C:\\Users\\George\\Projects\\otter

```



\*\*Solutions:\*\*

1\. \*\*Immediately switch to Git Bash:\*\*

&nbsp;  - Close PowerShell terminal

&nbsp;  - Open new terminal (should default to Git Bash)

&nbsp;  - Verify: `echo $SHELL`



2\. \*\*Prevent future occurrences:\*\*

&nbsp;  - Cursor Settings → Search "defaultProfile.windows"

&nbsp;  - Ensure "Git Bash" is selected

&nbsp;  - Restart Cursor if changed



\### Quick Diagnostic Commands (Windows)



```bash

\# Run all diagnostics

./scripts/validate-environment.sh



\# Check specific issues

netstat -an | grep 9222           # Chrome debugging

echo $SHELL                       # Verify Git Bash

git config --get core.autocrlf   # Line ending config

node --version                    # Node.js version

php --version                     # PHP version

```



\### Emergency Recovery (Windows)



If environment is completely broken:



```bash

\# 1. Emergency reset

./scripts/emergency-reset.sh



\# 2. Close Cursor completely (don't just close window)

taskkill /IM Cursor.exe /F



\# 3. Clear Cursor cache

rm -rf ~/.cursor/mcp-cache



\# 4. Restart Cursor



\# 5. Validate environment

./scripts/validate-environment.sh



\# 6. Test MCP tools

./scripts/check-mcp-health.sh

```

```



---



\## Part 3 Complete



\*\*All Windows 11 troubleshooting documentation has been added.\*\*



\*\*Files modified:\*\*

\- ✅ `docs/windows-setup.md` - Complete setup guide created

\- ✅ `chrome-mcp.md` - Windows-specific troubleshooting section added



\*\*Next:\*\* Proceed to Part 4 for final documentation updates and implementation checklist.

