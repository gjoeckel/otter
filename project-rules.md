---
policy_version: 1
ai_agent_optimized: true
critical:
  wait_for_authorization_tokens: ["WAIT", "No Remote Push", "push to github"]
  git_ops_terminal: "Git Bash"
  server_terminal_windows: "PowerShell 7"
  working_dir: "otter/"
authority_model:
  safe:
    - read_search_files
    - start_stop_local_php_server
    - run_health_checks
    - manage_local_branches_and_commits
    - update_changelog
  gated:
    - push_to_remote
    - destructive_edits_or_mass_refactors
    - auth_secret_changes
  forbidden:
    - changing_remotes_without_approval
    - production_infra_changes_without_approval
macros:
  - server_start
  - run_tests
  - cache_refresh
  - push_to_github
  - evaluate_logs
ai_agent_features:
  - decision_trees
  - quick_reference_commands
  - terminal_selection_matrix
  - error_recovery_procedures
  - automated_workflows
---
# Project Rules for AI Agents - Optimized Version

## 🤖 AI AGENT QUICK START

### ⚡ Immediate Actions Required
1. **Check Authorization**: Look for "WAIT" or "push to github" tokens
2. **Select Terminal**: Git Bash for git, PowerShell for server operations
3. **Verify Working Directory**: Must be `otter/` root
4. **Check Server Status**: Run health check if server operations needed

### 🚨 CRITICAL RULES - ALWAYS CHECK FIRST
| Rule | Action Required | Why Critical | AI Agent Impact |
|------|----------------|--------------|-----------------|
| **WAIT** in prompt | **STOP** - Get explicit authorization before acting | Prevents unauthorized actions | Blocks all operations |
| **Git Operations: Git Bash MANDATORY** | **ALWAYS** use Git Bash terminal for Git operations | Ensures reliable git integration and path handling | Use `C:\Program Files\Git\bin\bash.exe` |
| **Server Management: PowerShell PREFERRED** | **USE** PowerShell for server management and testing on Windows | Better Windows process management and diagnostics | Use `pwsh` or PowerShell 7 |
| **No Remote Push** | **NEVER** push without explicit user permission | Security requirement | Requires "push to github" token |
| **AJAX Pattern** | **ALWAYS** use `isset($_POST['action'])` detection | Prevents JSON/HTML errors | Use canonical pattern |
| **Working Directory** | **ALWAYS** operate from `otter/` root | Ensures correct path resolution | Check `pwd` or `Get-Location` |

---

## 🎯 CORE PRINCIPLES
- **MVP Focus:** Simple, reliable, accurate, WCAG compliant
- **No Backwards Compatibility:** Clean implementation, no legacy support needed
- **AI Agent Autonomy:** Handle all tasks without user intervention unless authorization required
- **Context-Based Terminal Usage:** Use appropriate terminal for specific tasks

---

## 🖥️ AI AGENT TERMINAL SELECTION MATRIX

| Task | Required Terminal | AI Agent Command | Why |
|------|-------------------|------------------|-----|
| Git add/commit/branch/log/status | Git Bash | `C:\Program Files\Git\bin\bash.exe` | Reliable Git + path handling on Windows |
| Push to remote | Git Bash (GATED) | `C:\Program Files\Git\bin\bash.exe` | Permission required; robust quoting |
| PHP server management (Windows) | PowerShell 7 | `pwsh` or `powershell` | Better process/diagnostics |
| HTTP tests/diagnostics | PowerShell 7 | `Invoke-WebRequest` | Native tooling on Windows |
| File ops / PHP CLI | Either | Context dependent | Choose based on path style needs |
| Build operations | Either | `npm run build:reports` | Both terminals support npm |

### 🤖 AI Agent Terminal Detection
```bash
# Git Bash detection
if command -v git >/dev/null 2>&1 && [[ "$OSTYPE" == "msys" ]]; then
  echo "Git Bash detected - use for git operations"
fi
```

```powershell
# PowerShell detection
if ($PSVersionTable.PSVersion.Major -ge 7) {
  Write-Host "PowerShell 7 detected - use for server operations"
}
```

- See Appendix A: Command Reference for full command sets and alternatives.
- Robust commit messages: prefer `git commit -F <file>` over `-m` when scripting on Windows.

### Git Identity Setup (Git Bash)
- If you see "Author identity unknown" or similar errors, set identity locally for this repo:
```bash
git config user.name "George"
git config user.email "george@MSI.localdomain"
```
-(Optional) Global config:
```bash
git config --global user.name "George"
git config --global user.email "george@MSI.localdomain"
```

### AJAX Handler Pattern
Moved to best practices. See `best-practices.md` → "AJAX Handler Pattern (PHP)".

---

## 🔧 Authority Model

### Safe (Pre-Approved)
- Read/search files; scoped semantic searches; list directories
- Start/stop local PHP server; run health checks and diagnostics
- Check/kill PHP processes; review `php_errors.log`
- Create/switch local branches; stage/commit changes; update local docs and `changelog.md`

### Gated (Require Explicit Approval)
- Push to remotes; change remotes; force operations
- Destructive edits or mass refactors
- Auth/secrets changes or production-impacting operations

### Forbidden
- Change remotes without approval
- Any production infrastructure changes without approval

### Escalation Triggers
- Repeated error → propose systemic fix
- Destructive risk detected → request confirmation
- Missing tooling (formatter/linter) → suggest adding
- Wrong terminal detected → switch per Terminal Matrix

---

## ✅ SAFE OPERATIONS (PRE-APPROVED)

These actions are low-risk and do not require explicit approval each time:
- Read/search files, run scoped codebase searches, and list directories
- Start/stop local PHP server; run health checks and diagnostics
- Tail/read `php_errors.log` and verify cache directories/files
- Generate caches via admin/dashboard or run CLI tests to refresh data
- Create/switch local branches, stage commits; update local changelog/docs

### Approval still required
- Pushing to remotes, changing remotes, or force operations
- Destructive edits, mass refactors, or changes to auth/secrets
- Any operation impacting production infrastructure

---

## 🧠 TOKEN OPTIMIZATION PRACTICES

- Prefer file/path references and small excerpts over large pastes
- Read large artifacts directly from disk instead of copying into chat
- Batch routine checks (status/build/test/lint) to reduce round trips
- Start with scoped, semantic searches; expand only if no hits
- Use quick checks first: health endpoint, tail `php_errors.log`, list `cache/<ent>`

---

## 🖥️ DEVELOPMENT ENVIRONMENT

### Terminal Configuration
- **Git Operations:** Git Bash (`C:\Program Files\Git\bin\bash.exe`) - **MANDATORY**
- **Server Management:** PowerShell 7 (pwsh) - **PREFERRED**
- **Development Tasks:** Context dependent - choose appropriate terminal
- **How to Open:** 
  - Windows Terminal: Configure profiles for PowerShell 7 (default) and Git Bash (git only)
  - VS Code/Cursor: Terminal dropdown → Select PowerShell 7 (pwsh) or Git Bash
  - Command Palette: "Terminal: Select Default Profile" → Choose PowerShell 7 (pwsh)
  - Direct: Run appropriate terminal executable

### Server Configuration
- **Type:** PHP built-in development server
- **Port:** 8000 (http://localhost:8000)
- **Directory:** Always start from `otter/` root
- **Health Check:** `http://localhost:8000/health_check.php`

### File Structure
- **Root:** `otter/` directory
- **Changelog:** `changelog.md` (root - single source of truth)
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
1. ✅ **Using appropriate terminal for task**
2. ✅ Server running: Check with appropriate command
3. ✅ PHP processes: Check with appropriate command
4. ✅ Working directory: Should show `otter/`
5. ✅ Error log: Check `php_errors.log`

### Testing Sequence
1. **Ensure appropriate terminal is active**
2. Start server with appropriate command
3. Wait 2-3 seconds for initialization
4. Use appropriate HTTP testing command
5. Verify response codes and content
6. Check PHP errors in terminal output
7. Review error log for details

### Error Recovery
- **Port conflicts:** Stop server with appropriate command or use different port
- **Server won't start:** Check PHP processes and port availability
- **Unexpected responses:** Check PHP syntax and error logs
- **Timeouts:** Verify server is actually running
- **Wrong terminal:** Switch to appropriate terminal for task

---

## 🔁 AI AGENT OPERATIONAL MACROS

### 🤖 server_start (PowerShell 7)
```powershell
# AI Agent Server Start Sequence
function Start-OTTERServer {
    # 1. Check if server is running
    $connection = Test-NetConnection -ComputerName localhost -Port 8000 -InformationLevel Quiet
    if (-not $connection) {
        Write-Host "Starting PHP server..."
        Start-Process -FilePath "php" -ArgumentList "-S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log" -WindowStyle Hidden
        Start-Sleep -Seconds 3
    }
    
    # 2. Verify server is responding
    try {
        $response = Invoke-WebRequest -Uri "http://localhost:8000/health_check.php" -TimeoutSec 5
        Write-Host "Server is running: $($response.StatusCode)"
        return $true
    } catch {
        Write-Host "Server health check failed: $($_.Exception.Message)"
        return $false
    }
}
```

### 🤖 run_tests (Either terminal)
```bash
# Git Bash version
php run_tests.php
php run_tests.php csu  # Optional enterprise-specific test
```

```powershell
# PowerShell version
php run_tests.php
php run_tests.php csu  # Optional enterprise-specific test
```

### 🤖 cache_refresh (UI-first)
```powershell
# AI Agent Cache Refresh Sequence
function Refresh-OTTERCache {
    # 1. Use admin/dashboard controls to refresh caches per enterprise
    $enterprises = @("csu", "ccc", "demo")
    foreach ($enterprise in $enterprises) {
        Write-Host "Refreshing cache for $enterprise..."
        # Call admin refresh endpoint or use dashboard
    }
    
    # 2. Verify cache files in cache/<enterprise>/ and check timestamps
    foreach ($enterprise in $enterprises) {
        $cacheDir = "cache/$enterprise"
        if (Test-Path $cacheDir) {
            $files = Get-ChildItem $cacheDir -File
            Write-Host "Cache files for $enterprise`: $($files.Count) files"
        }
    }
}
```

### 🤖 push_to_github (Git Bash; GATED)
```powershell
# AI Agent Push to GitHub (PowerShell wrapper)
function Push-ToGitHub {
    param([string]$AuthorizationToken)
    
    if ($AuthorizationToken -ne "push to github") {
        Write-Error "Invalid authorization token. Required: 'push to github'"
        return $false
    }
    
    # Use script via PowerShell
    & "C:\Program Files\Git\bin\bash.exe" "scripts/push_to_github.sh" "push to github"
}
```

### 🤖 evaluate_logs (Either terminal)
```powershell
# AI Agent Log Evaluation (PowerShell)
function Evaluate-OTTERLogs {
    Write-Host "🔍 AI Agent Log Evaluation Initiated" -ForegroundColor Cyan
    
    # 1. Check server status
    $serverStatus = Test-NetConnection -ComputerName localhost -Port 8000 -InformationLevel Quiet
    Write-Host "Server Status: $(if($serverStatus) {'✅ Running'} else {'❌ Down'})"
    
    # 2. Check recent PHP errors
    Write-Host "`n📊 Recent PHP Errors (Last 20):"
    if (Test-Path "php_errors.log") {
        Get-Content php_errors.log -Tail 20 | ForEach-Object {
            if ($_ -match "ERROR|WARN|FATAL") {
                Write-Host "  ⚠️  $_" -ForegroundColor Red
            } elseif ($_ -match "INFO|NOTICE") {
                Write-Host "  ℹ️  $_" -ForegroundColor Yellow
            } else {
                Write-Host "  📝 $_" -ForegroundColor Gray
            }
        }
    } else {
        Write-Host "  ℹ️  No PHP error log found" -ForegroundColor Yellow
    }
    
    # 3. Check build system health
    $buildStatus = Test-Path "reports/dist/reports.bundle.js"
    Write-Host "`n🔧 Build System: $(if($buildStatus) {'✅ Bundle exists'} else {'❌ Missing bundle'})"
    
    return @{
        ServerRunning = $serverStatus
        BuildExists = $buildStatus
        LogFileExists = (Test-Path "php_errors.log")
    }
}
```

```bash
# AI Agent Log Evaluation (Git Bash)
evaluate_otter_logs() {
    echo "🔍 AI Agent Log Evaluation Initiated"
    
    # 1. Check server status
    server_status=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/health_check.php 2>/dev/null)
    if [ "$server_status" = "200" ]; then
        echo "Server Status: ✅ Running"
    else
        echo "Server Status: ❌ Down or Unresponsive"
    fi
    
    # 2. Check recent PHP errors
    echo ""
    echo "📊 Recent PHP Errors (Last 20):"
    if [ -f "php_errors.log" ]; then
        tail -20 php_errors.log | while read line; do
            if echo "$line" | grep -qE "ERROR|WARN|FATAL"; then
                echo "  ⚠️  $line"
            elif echo "$line" | grep -qE "INFO|NOTICE"; then
                echo "  ℹ️  $line"
            else
                echo "  📝 $line"
            fi
        done
    else
        echo "  ℹ️  No PHP error log found"
    fi
    
    # 3. Check build system health
    if [ -f "reports/dist/reports.bundle.js" ]; then
        echo ""
        echo "🔧 Build System: ✅ Bundle exists"
    else
        echo ""
        echo "🔧 Build System: ❌ Missing bundle"
    fi
}
```

**AI Agent Inline Steps** (if script unavailable):
1. **Authorization Check**: Verify exact message `push to github` (case‑sensitive)
2. **Baseline Detection**: `@{upstream}..HEAD` (fallback `origin/<branch>..HEAD`)
3. **Summary Generation**: Compose one‑line, high‑level summary of all changes since baseline
4. **Changelog Update**: Append `push to github` entry to `changelog.md` with timestamp and summary
5. **Roll‑up Commit**: Write summary to `.commitmsg`, `git add -A`, `git commit -F .commitmsg`
6. **Push**: `git push`
7. **Cleanup**: Remove `.commitmsg`

**AI Agent Tips**:
- Add `.commitmsg` to `.gitignore` to prevent accidental commits
- Use `git restore --staged .commitmsg` before committing if it was staged
- Always verify authorization token before proceeding

---

## 📝 AI AGENT CHANGELOG MANAGEMENT

### 🤖 Automated Changelog Functions
```powershell
# AI Agent Changelog Helper (PowerShell)
function Add-ChangelogEntry {
    param(
        [string]$Summary,
        [string]$Type = "update"
    )
    
    $timestamp = Get-Date -Format "yyyy-MM-dd HH:mm:ss"
    $entry = "## $timestamp - $Summary"
    
    # Add to changelog.md
    Add-Content -Path "changelog.md" -Value $entry
    Write-Host "Added changelog entry: $Summary"
}
```

```bash
# AI Agent Changelog Helper (Git Bash)
function add_changelog_entry() {
    local summary="$1"
    local type="${2:-update}"
    local timestamp=$(date +"%Y-%m-%d %H:%M:%S")
    local entry="## $timestamp - $summary"
    
    echo "$entry" >> changelog.md
    echo "Added changelog entry: $summary"
}
```

### 🤖 Commands
- **`changelog`:** Document all session changes
- **`changelog status`:** Document current application functionality

### 🤖 Timestamp Generation
- Git Bash: `date +"%Y-%m-%d %H:%M:%S"`
- PowerShell: `Get-Date -Format "yyyy-MM-dd HH:mm:ss"`

### 🤖 Changelog Location
`changelog.md` (root) - Single source of truth for all changes

---

## 🧱 AI AGENT FRONTEND BUILD (Reports)

### 🤖 Build System Overview
- **Tooling**: `esbuild` (Node 20 in CI)
- **Entry**: `reports/js/reports-entry.js`
- **Output**: `reports/dist/reports.bundle.js` (ESM; CI build omits sourcemap)
- **HTML include** (reports page):
  - `<script type="module" src="dist/reports.bundle.js?v=<?php echo time(); ?>"></script>`

### 🤖 AI Agent Build Commands
```powershell
# PowerShell Build Functions
function Build-Reports {
    Write-Host "Building reports bundle..."
    npm run build:reports
    if (Test-Path "reports/dist/reports.bundle.js") {
        $size = (Get-Item "reports/dist/reports.bundle.js").Length
        Write-Host "Build successful: $size bytes"
        return $true
    } else {
        Write-Error "Build failed: bundle not found"
        return $false
    }
}

function Watch-Reports {
    Write-Host "Starting watch mode..."
    npm run watch:reports
}
```

```bash
# Git Bash Build Functions
build_reports() {
    echo "Building reports bundle..."
    npm run build:reports
    if [ -f "reports/dist/reports.bundle.js" ]; then
        size=$(stat -c%s "reports/dist/reports.bundle.js")
        echo "Build successful: $size bytes"
        return 0
    else
        echo "Build failed: bundle not found"
        return 1
    fi
}

watch_reports() {
    echo "Starting watch mode..."
    npm run watch:reports
}
```

### 🤖 Build Commands
- **`npm run build:reports`** (production build)
- **`npm run watch:reports`** (development watch mode)
- **`npm run dev:reports`** (development build - referenced in console warnings)

### 🤖 Build Troubleshooting
- **Missing Bundle Warning**: If console shows "dist/reports.bundle.js not found", run `npm run build:reports`
- **Build Verification**: Check file exists and has reasonable size (> 1KB)
- **CI Integration**: Builds bundle before SFTP deploy (no sourcemaps in CI)

### 🤖 AI Agent Build Decision Tree
```
IF bundle_missing:
  → Run npm run build:reports
  → Verify reports/dist/reports.bundle.js exists
  → Check file size > 1KB
ELIF build_fails:
  → Check Node.js version (requires 20+)
  → Verify package.json exists
  → Check npm dependencies installed
ELIF console_warnings:
  → Build required before reports functionality works
```

**AI Agent Notes**:
- Prefer static imports for shared libs; avoid cache-busted dynamic imports inside bundles
- Keep classic non‑module scripts (e.g., `../lib/table-filter-interaction.js`) as separate tags
- Build must complete successfully before reports page will function properly

---

## 🔼 GIT PUSH WORKFLOW (USER SHORTCUT)

When the user types "push to github", perform these steps automatically:
1. Verify authorization: message is exactly `push to github` (case-sensitive, no extra text).
2. Determine baseline: prefer `@{upstream}..HEAD`; fallback to `origin/<current-branch>..HEAD` if upstream unset.
3. Generate a high‑level one‑line summary describing all changes since baseline (e.g., "updated widget HTML/CSS").
4. Update `changelog.md`: add a new entry labeled `push to github` with timestamp and the same one‑line summary.
5. In Git Bash, create `.commitmsg` containing that one‑line summary; `git add .`; `git commit -F .commitmsg` (roll‑up commit on top).
6. `git push` current branch to remote.
7. Remove `.commitmsg`.

---

## 🔒 SAFETY PROCEDURES

### Pre-Operation Checks
- **Verify appropriate terminal is active**
- Verify working directory: Should show `otter/`
- Check repository status: `git status` (Git Bash)
- Confirm branch: `git branch -a` (Git Bash)
- Backup changes: `git stash` if needed (Git Bash)
- For git flows, use a single Git Bash session; avoid nested shells/PowerShell wrappers.

### Operation Validation
- Review changes: `git diff` (Git Bash)
- Validate staged: `git diff --cached` (Git Bash)
- Test functionality after commits
- Confirm remote operations before pushing

### Emergency Procedures
- **Git hangs:** Use Git Bash, or try `git config --global --unset core.pager`
- **Server issues:** Check port conflicts and PHP processes with appropriate terminal
- **AJAX fails:** Verify detection pattern and JSON formatting
- **Paths break:** Confirm relative path implementation
- **Wrong terminal:** Switch to appropriate terminal for task

---

## 🎯 SUCCESS CRITERIA
- ✅ **Appropriate terminal used for specific tasks**
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

Appendix A: Full command reference moved to the end of this document to reduce duplication.

---

## 🚀 IMPLEMENTATION NOTES

### Critical Success Factors
1. **Use Git Bash for all Git operations**
2. **Use PowerShell for server management on Windows**
3. **Choose appropriate terminal for specific tasks**
4. **Never push to remote without explicit user permission**
5. **Follow AJAX detection patterns to prevent JSON errors**
6. **Execute all operations from otter/ directory**
7. **Document all changes in changelog**
8. **Test functionality after every significant change**

### Quality Assurance
- **Verify appropriate terminal is active before operations**
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
- **Terminal Focus:** Document appropriate terminal for each task type
- **Logging Integration:** Comprehensive logging system for debugging and monitoring

**📋 AI Agent Log Access:** See `best-practices.md` → "AI Agent Log Access & Debugging" section for complete logging procedures, visual log viewer usage (`Ctrl+Shift+J`), and user testing monitoring workflows.

**🔍 AI Agent Log Evaluation Trigger:** Use phrase **"evaluate logs"** (case-insensitive) to trigger comprehensive log analysis workflow with structured feedback and actionable recommendations. See `best-practices.md` → "AI Agent Log Evaluation Trigger System" section for complete procedures.

---

## ⚠️ KNOWN ISSUES & BEST PRACTICES

### Git Bash Issues
- **Path Handling:** Git operations work best with Unix-style paths
- **Integration:** Native git integration prevents command conflicts
- **Process Management:** Limited Windows process management capabilities
 - **Nested shells and quoting:** Invoking Git Bash from PowerShell can break quoting and cause PSReadLine crashes.
 - **Ephemeral context:** Non‑persistent sessions lose cwd/env; keep multi‑step git commands in one session.
 - **Interactive prompts:** Always provide `-m` or `-F .commitmsg` to avoid editors in non‑interactive contexts.

### PowerShell Issues
- **Path Separators:** May need forward slashes for PHP commands
- **Git Integration:** Can have issues with git command integration
- **Process Management:** Excellent Windows process management

### Best Practices
- **Git Operations:** Always use Git Bash for reliability
- **Server Management:** Use PowerShell on Windows for better process control
- **Development Tasks:** Choose terminal based on specific task requirements
- **Documentation:** Always specify which terminal for which task
- **Testing:** Use PowerShell for Windows-specific diagnostics

---

### Appendix A: Command Reference

#### Git (Git Bash)
```bash
git log --oneline -10
git diff
git branch -a
git status
git add .
git commit -m "message"
git checkout -b feature-name
git checkout main
# Remote operations (GATED)
git push origin main
```

#### PowerShell (Server Management)
```powershell
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log
Test-NetConnection -ComputerName localhost -Port 8000 | Out-String
tasklist | findstr php | Out-String
taskkill /F /IM php.exe
Invoke-WebRequest http://localhost:8000/health_check.php | Out-String
Get-Date -Format "yyyy-MM-dd HH:mm:ss"
```

#### Git Bash (Alternatives)
```bash
php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log &
ps aux | grep php
pkill -f "php -S localhost:8000"
curl -I http://localhost:8000/health_check.php
date +"%Y-%m-%d %H:%M:%S"
```

---

## 🤖 AI AGENT QUICK REFERENCE

### ⚡ Emergency Procedures
```powershell
# Quick Server Check (PowerShell)
Test-NetConnection -ComputerName localhost -Port 8000 | Out-String
Invoke-WebRequest http://localhost:8000/health_check.php | Out-String
```

```bash
# Quick Git Status (Git Bash)
git status
git log --oneline -5
```

### 🚨 Critical Decision Points
1. **Authorization Required?** → Look for "WAIT" or "push to github"
2. **Terminal Selection?** → Git Bash for git, PowerShell for server
3. **Working Directory?** → Must be `otter/` root
4. **Build Missing?** → Run `npm run build:reports`

### 🔧 Common AI Agent Tasks
| Task | Terminal | Command | Expected Result |
|------|----------|---------|-----------------|
| Check server | PowerShell | `Test-NetConnection localhost -Port 8000` | Port open/closed |
| Start server | PowerShell | `php -S localhost:8000` | Server running |
| Check git status | Git Bash | `git status` | Working directory status |
| Build reports | Either | `npm run build:reports` | Bundle created |
| Run tests | Either | `php run_tests.php` | Test results |

### 🎯 AI Agent Success Criteria
- ✅ **Appropriate terminal used for specific tasks**
- ✅ **Authorization verified before gated operations**
- ✅ **Working directory confirmed as `otter/`**
- ✅ **Server health verified before operations**
- ✅ **Build system functional before reports work**
- ✅ **All changes documented in changelog**

*These optimized rules provide comprehensive guidance for AI agents working with this PHP project, emphasizing context-based terminal usage, automation-friendly procedures, safety measures, and MVP development principles.*