---
policy_version: 1
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
---
# Project Rules for AI Agents - Optimized Version

## 🚨 CRITICAL RULES - ALWAYS CHECK FIRST
| Rule | Action Required | Why Critical |
|------|----------------|--------------|
| **WAIT** in prompt | **STOP** - Get explicit authorization before acting | Prevents unauthorized actions |
| **Git Operations: Git Bash MANDATORY** | **ALWAYS** use Git Bash terminal for Git operations | Ensures reliable git integration and path handling |
| **Server Management: PowerShell PREFERRED** | **USE** PowerShell for server management and testing on Windows | Better Windows process management and diagnostics |
| **No Remote Push** | **NEVER** push without explicit user permission | Security requirement |
| **AJAX Pattern** | **ALWAYS** use `isset($_POST['action'])` detection | Prevents JSON/HTML errors |
| **Working Directory** | **ALWAYS** operate from `otter/` root | Ensures correct path resolution |

---

## 🎯 CORE PRINCIPLES
- **MVP Focus:** Simple, reliable, accurate, WCAG compliant
- **No Backwards Compatibility:** Clean implementation, no legacy support needed
- **AI Agent Autonomy:** Handle all tasks without user intervention unless authorization required
- **Context-Based Terminal Usage:** Use appropriate terminal for specific tasks

---

## 🖥️ Terminal Selection Matrix

| Task | Required Terminal | Why |
|------|-------------------|-----|
| Git add/commit/branch/log/status | Git Bash | Reliable Git + path handling on Windows |
| Push to remote | Git Bash (GATED) | Permission required; robust quoting |
| PHP server management (Windows) | PowerShell 7 | Better process/diagnostics |
| HTTP tests/diagnostics | PowerShell 7 | Native tooling on Windows |
| File ops / PHP CLI | Either | Choose based on path style needs |

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

## 🔁 Operational Macros

### server_start (PowerShell 7)
1. `Test-NetConnection -ComputerName localhost -Port 8000 | Out-String`
2. If closed, run: `php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log`
3. `Start-Sleep -Seconds 2`
4. `Invoke-WebRequest http://localhost:8000/health_check.php | Out-String`

### run_tests (Either terminal)
1. `php run_tests.php`
2. Optional: `php run_tests.php csu`

### cache_refresh (UI-first)
1. Use admin/dashboard controls to refresh caches per enterprise
2. Verify cache files in `cache/<enterprise>/` and check timestamps

### push_to_github (Git Bash; GATED)
Prefer using the script via PowerShell (Windows):

```powershell
# Normal (executes commit + push)
& "C:\Program Files\Git\bin\bash.exe" "scripts/push_to_github.sh" "push to github"

# Dry run (no commit/push), with verbose details
$env:VERBOSE='1'; $env:DRY_RUN='1'; & "C:\Program Files\Git\bin\bash.exe" "scripts/push_to_github.sh" "push to github"
```

Optional flags via env vars (used with the PowerShell examples above):
- `DRY_RUN=1` to print planned actions without committing/pushing
- `VERBOSE=1` to print branch, range, files, and summary

Inline steps if script unavailable:
1. Authorization: require exact message `push to github` (case‑sensitive)
2. Baseline: `@{upstream}..HEAD` (fallback `origin/<branch>..HEAD`)
3. Compose one‑line, high‑level summary of all changes since baseline
4. Append `push to github` entry to `changelog.md` with timestamp and summary
5. Roll‑up commit: write summary to `.commitmsg`, `git add -A`, `git commit -F .commitmsg`
6. `git push`
7. Clean up `.commitmsg`

Tip: Add `.commitmsg` to `.gitignore` to prevent accidental commits; prefer `git restore --staged .commitmsg` before committing if it was staged.

---

## 📝 CHANGELOG MANAGEMENT

### Commands
- **`changelog`:** Document all session changes
- **`changelog status`:** Document current application functionality

### Timestamp Generation
- Git Bash: `date +"%Y-%m-%d %H:%M:%S"`
- PowerShell: `Get-Date -Format "yyyy-MM-dd HH:mm:ss"`

### Changelog Location
`changelog.md` (root)

---

## 🧱 Frontend Build (Reports)

- Tooling: `esbuild` (Node 20 in CI)
- Entry: `reports/js/reports-entry.js`
- Output: `reports/dist/reports.bundle.js` (ESM + sourcemap)
- HTML include (reports page):
  - `<script type="module" src="dist/reports.bundle.js?v=<?php echo time(); ?>"></script>`
- Local commands:
  - `npm run build:reports`
  - `npm run watch:reports`
- CI build step (deploy workflow): builds bundle before SFTP deploy

Notes:
- Prefer static imports for shared libs; avoid cache-busted dynamic imports inside bundles.
- Keep classic non‑module scripts (e.g., `../lib/table-filter-interaction.js`) as separate tags.

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

*These optimized rules provide comprehensive guidance for AI agents working with this PHP project, emphasizing context-based terminal usage, automation-friendly procedures, safety measures, and MVP development principles.*