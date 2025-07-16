# Project Rules for AI Agents - Optimized Version

## 🚨 CRITICAL RULES - ALWAYS CHECK FIRST
| Rule | Action Required | Why Critical |
|------|----------------|--------------|
| **WAIT** in prompt | **STOP** - Get explicit authorization before acting | Prevents unauthorized actions |
| **Git Bash Default** | **ALWAYS** use Git Bash terminal for all development and automation | Ensures reliable git and Unix command handling |
| **No Remote Push** | **NEVER** push without explicit user permission | Security requirement |
| **AJAX Pattern** | **ALWAYS** use `isset($_POST['action'])` detection | Prevents JSON/HTML errors |
| **Working Directory** | **ALWAYS** operate from `otter/` root | Ensures correct path resolution |

---

## 🎯 CORE PRINCIPLES
- **MVP Focus:** Simple, reliable, accurate, WCAG compliant
- **No Backwards Compatibility:** Clean implementation, no legacy support needed
- **AI Agent Autonomy:** Handle all tasks without user intervention unless authorization required
- **Git Bash Standard:** All commands, scripts, and automation should use Git Bash

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

### Bash Server Management
```bash
# Start server
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log &

# Check server status
lsof -i :8000

# Stop server
pkill -f "php -S localhost:8000"

# HTTP testing (use curl)
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

### 🚨 ESCALATION TRIGGERS
- Same error occurs more than once → Propose systemic solution
- Destructive operation detected → Request explicit confirmation
- No linter/formatter detected → Suggest adding one
- Documentation changes → Update all related references

---

## 🖥️ DEVELOPMENT ENVIRONMENT

### Terminal Configuration
- **Default Terminal:** Git Bash (`C:\Program Files\Git\bin\bash.exe`)
- **How to Open:** Use VS Code/Cursor terminal dropdown or Command Palette to select Git Bash
- **All commands, scripts, and automation should use Bash syntax**

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
1. ✅ Server running: `lsof -i :8000`
2. ✅ PHP processes: `ps aux | grep php`
3. ✅ Working directory: `pwd` should show `otter/`
4. ✅ Error log: Check `php_errors.log`

### Testing Sequence
1. Start server in background if needed
2. Wait 2-3 seconds for initialization
3. Use `curl` for HTTP testing
4. Verify response codes and content
5. Check PHP errors in terminal output
6. Review error log for details

### Error Recovery
- **Port conflicts:** `pkill -f "php -S localhost:8000"` or use different port
- **Server won't start:** Check PHP processes and port availability
- **Unexpected responses:** Check PHP syntax and error logs
- **Timeouts:** Verify server is actually running

---

## 📝 CHANGELOG MANAGEMENT

### Commands
- **`changelog`:** Document all session changes
- **`changelog status`:** Document current application functionality

### Timestamp Generation
```bash
date +"%Y-%m-%d %H:%M:%S"
```

### Changelog Location
`clients-enterprise/changelog.md`

---

## 🔒 SAFETY PROCEDURES

### Pre-Operation Checks
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

---

## 🎯 SUCCESS CRITERIA
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

### Bash Tips
- Use `ls -la` to list files
- Use `pwd` to print working directory
- Use `ps aux | grep php` to check PHP processes
- Use `pkill -f "php -S localhost:8000"` to stop server
- Use `curl` for HTTP requests
- Use `date` for timestamps

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
1. **Always use Git Bash for all terminal operations**
2. **Never push to remote without explicit user permission**
3. **Use Bash commands for server and process management**
4. **Follow AJAX detection patterns to prevent JSON errors**
5. **Execute all operations from otter/ directory**
6. **Document all changes in changelog**
7. **Test functionality after every significant change**

### Quality Assurance
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

---

*These optimized rules provide comprehensive guidance for AI agents working with this PHP project, emphasizing automation-friendly procedures, safety measures, and MVP development principles.*