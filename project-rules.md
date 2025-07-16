# Project Rules for AI Agents - Optimized Version

## 🚨 CRITICAL RULES - ALWAYS CHECK FIRST
| Rule | Action Required | Why Critical |
|------|----------------|--------------|
| **WAIT** in prompt | **STOP** - Get explicit authorization before acting | Prevents unauthorized actions |
| **Git Bash MANDATORY** | **ALWAYS** use Git Bash terminal for ALL operations | Ensures reliable git and Unix command handling |
| **No Remote Push** | **NEVER** push without explicit user permission | Security requirement |
| **AJAX Pattern** | **ALWAYS** use `isset($_POST['action'])` detection | Prevents JSON/HTML errors |
| **Working Directory** | **ALWAYS** operate from `otter/` root | Ensures correct path resolution |

---

## 🎯 CORE PRINCIPLES
- **MVP Focus:** Simple, reliable, accurate, WCAG compliant
- **No Backwards Compatibility:** Clean implementation, no legacy support needed
- **AI Agent Autonomy:** Handle all tasks without user intervention unless authorization required
- **Git Bash Standard:** **MANDATORY** - All commands, scripts, and automation MUST use Git Bash

---

## 🖥️ GIT BASH TERMINAL REQUIREMENTS

### **MANDATORY TERMINAL: Git Bash**
- **Path:** `C:\Program Files\Git\bin\bash.exe`
- **Why Required:** Provides Unix-like environment with reliable git integration
- **Alternative Terminals:** **NOT ALLOWED** - PowerShell, CMD, WSL, or other terminals
- **How to Open:** 
  - VS Code/Cursor: Terminal dropdown → Select "Git Bash"
  - Command Palette: "Terminal: Select Default Profile" → Choose "Git Bash"
  - Direct: Run `C:\Program Files\Git\bin\bash.exe`

### **Git Bash Commands Only**
```bash
# ✅ CORRECT - Use these Git Bash commands
ls -la                    # List files with details
pwd                       # Print working directory
ps aux | grep php         # Check PHP processes
curl -I http://localhost:8000/health_check.php  # HTTP testing
date +"%Y-%m-%d %H:%M:%S" # Generate timestamps

# ❌ WRONG - Don't use these (PowerShell/CMD commands)
dir                       # Use ls -la instead
cd                        # Use pwd instead
tasklist | findstr php    # Use ps aux | grep php instead
```

### **Git Bash Environment**
- **Shell:** Bash (Unix-like)
- **Path Separator:** Forward slash `/` (not backslash `\`)
- **Command Syntax:** Unix/Linux style
- **Git Integration:** Native git commands work seamlessly
- **Process Management:** Unix-style process commands

---

## ⚡ QUICK REFERENCE COMMANDS

### Git Operations (Safe Commands)
```bash
# View operations
git log --oneline -10
git diff
git branch -a
git status

# Basic operations
git add .
git commit -m "message"
git checkout -b feature-name
git checkout main

# Remote operations (REQUIRES USER PERMISSION)
git push origin main  # NEVER without permission
```

### Git Bash Server Management
```bash
# Start server (Git Bash only)
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log &

# Check server status (Git Bash alternatives)
ps aux | grep php        # Check PHP processes
netstat -an | grep 8000  # Check port usage (if available)

# Stop server (Git Bash only)
pkill -f "php -S localhost:8000"

# HTTP testing (Git Bash only)
curl -I http://localhost:8000/health_check.php
```

### AJAX Handler Pattern (PHP)
```php
<?php
ob_start();
header('Content-Type: application/json');

if (isset($_POST['action'])) {
    try {
        ob_clean();
        $response = ['success' => true, 'data' => 'result'];
        echo json_encode($response);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
ob_end_flush();
?>
```

---

## 🔧 AI AGENT RESPONSIBILITIES

### ✅ DO THESE AUTOMATICALLY
- **ALWAYS use Git Bash terminal for all operations**
- Handle ALL terminal operations without asking user
- Manage development environment proactively
- Test functionality after every significant change
- Update changelog before major commits
- Suggest automation tools when recurring issues detected
- Escalate repeated errors to user with systemic solutions

### ❌ NEVER DO THESE WITHOUT PERMISSION
- Push to remote repositories
- Execute destructive operations without confirmation
- Introduce security/privacy features unless requested
- Ask user to perform tasks you can do yourself
- **Use any terminal other than Git Bash**

### 🚨 ESCALATION TRIGGERS
- Same error occurs more than once → Propose systemic solution
- Destructive operation detected → Request explicit confirmation
- No linter/formatter detected → Suggest adding one
- Documentation changes → Update all related references
- **Wrong terminal detected → Switch to Git Bash immediately**

---

## 🖥️ DEVELOPMENT ENVIRONMENT

### Terminal Configuration
- **Default Terminal:** Git Bash (`C:\Program Files\Git\bin\bash.exe`) - **MANDATORY**
- **How to Open:** Use VS Code/Cursor terminal dropdown or Command Palette to select Git Bash
- **All commands, scripts, and automation MUST use Bash syntax**
- **No PowerShell, CMD, or other terminals allowed**

### Server Configuration
- **Type:** PHP built-in development server
- **Port:** 8000 (http://localhost:8000)
- **Directory:** Always start from `otter/` root
- **Health Check:** `http://localhost:8000/health_check.php`

### File Structure
- **Root:** `otter/` directory
- **Changelog:** `clients-enterprise/changelog.md`
- **Config:** `config/` directory
- **Tests:** `tests/` directory
- **Assets:** `css/`, `js/`, `lib/` directories

### Path Implementation
- **Universal Relative Paths:** No environment detection needed
- **Simple URLs:** Direct relative paths (e.g., `assets/css/admin.css`)
- **Cross-Server Compatible:** Works on any server structure
- **PATH_INFO Handling:** Always clean PATH_INFO before redirects

---

## 🧪 TESTING PROTOCOL

### Pre-Testing Checklist
1. ✅ **Using Git Bash terminal**
2. ✅ Server running: `ps aux | grep php`
3. ✅ PHP processes: `ps aux | grep php`
4. ✅ Working directory: `pwd` should show `otter/`
5. ✅ Error log: Check `php_errors.log`

### Testing Sequence
1. **Ensure Git Bash terminal is active**
2. Start server in background if needed
3. Wait 2-3 seconds for initialization
4. Use `curl` for HTTP testing
5. Verify response codes and content
6. Check PHP errors in terminal output
7. Review error log for details

### Error Recovery
- **Port conflicts:** `pkill -f "php -S localhost:8000"` or use different port
- **Server won't start:** Check PHP processes and port availability
- **Unexpected responses:** Check PHP syntax and error logs
- **Timeouts:** Verify server is actually running
- **Wrong terminal:** Switch to Git Bash immediately

---

## 📝 CHANGELOG MANAGEMENT

### Commands
- **`changelog`:** Document all session changes
- **`changelog status`:** Document current application functionality

### Timestamp Generation (Git Bash only)
```bash
date +"%Y-%m-%d %H:%M:%S"
```

### Changelog Location
`clients-enterprise/changelog.md`

---

## 🔒 SAFETY PROCEDURES

### Pre-Operation Checks
- **Verify Git Bash terminal is active**
- Verify working directory: `pwd` should show `otter/`
- Check repository status: `git status`
- Confirm branch: `git branch -a`
- Backup changes: `git stash` if needed

### Operation Validation
- Review changes: `git diff`
- Validate staged: `git diff --cached`
- Test functionality after commits
- Confirm remote operations before pushing

### Emergency Procedures
- **Git hangs:** Use Git Bash, or try `git config --global --unset core.pager`
- **Server issues:** Check port conflicts and PHP processes
- **AJAX fails:** Verify detection pattern and JSON formatting
- **Paths break:** Confirm relative path implementation
- **Wrong terminal:** Switch to Git Bash immediately

---

## 🎯 SUCCESS CRITERIA
- ✅ **Git Bash terminal used for all operations**
- ✅ No duplicate code between classes
- ✅ Universal relative paths work across all scenarios
- ✅ Simple, consistent relative paths generated
- ✅ Specific, actionable error messages
- ✅ Simpler, more maintainable code
- ✅ Multi-enterprise architecture supported
- ✅ WCAG compliance maintained
- ✅ Clean implementation without legacy requirements

---

## 📋 COMMAND REFERENCE

### Git Bash Commands (MANDATORY)
- Use `ls -la` to list files
- Use `pwd` to print working directory
- Use `ps aux | grep php` to check PHP processes
- Use `pkill -f "php -S localhost:8000"` to stop server
- Use `curl` for HTTP requests
- Use `date +"%Y-%m-%d %H:%M:%S"` for timestamps

### Git Commands
```bash
git log --oneline -10
git diff
git branch -a
git status
git add .
git commit -m "message"
git push origin main  # NEVER without permission
git stash
git checkout -b feature-name
git checkout main
```

---

## 🚀 IMPLEMENTATION NOTES

### Critical Success Factors
1. **ALWAYS use Git Bash for all terminal operations**
2. **Never push to remote without explicit user permission**
3. **Use Bash commands for server and process management**
4. **Follow AJAX detection patterns to prevent JSON errors**
5. **Execute all operations from otter/ directory**
6. **Document all changes in changelog**
7. **Test functionality after every significant change**

### Quality Assurance
- **Verify Git Bash terminal is active before all operations**
- Test all git operations before implementing
- Verify server functionality after changes
- Check for PHP syntax errors
- Validate AJAX responses
- Confirm WCAG compliance
- Maintain MVP focus throughout development

---

## 📚 DOCUMENTATION STANDARDS
- **Target Audience:** AI agents unless directed otherwise
- **Optimization:** Structure for AI agent comprehension and action
- **Clarity:** Use clear, actionable language
- **Completeness:** Provide sufficient context for autonomous operation
- **Terminal Focus:** Emphasize Git Bash requirements throughout

---

*These optimized rules provide comprehensive guidance for AI agents working with this PHP project, emphasizing Git Bash terminal usage, automation-friendly procedures, safety measures, and MVP development principles.*