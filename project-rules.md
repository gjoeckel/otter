# Project Rules for AI Agents

## SUMMARY OF CRITICAL RULES
| Rule | Description | Section |
|------|-------------|---------|
| WAIT Instruction | Must obtain explicit authorization before acting if 'WAIT' is in prompt | CRITICAL INSTRUCTIONS |
| No Remote Push | Never push to remote without explicit user permission | CRITICAL INSTRUCTIONS |
| Git Pager Prevention | Always use --no-pager flag or \| cat to prevent terminal hanging | CRITICAL INSTRUCTIONS |
| Pre-Testing Protocol | Strict HTTP/server testing requirements | CRITICAL INSTRUCTIONS |
| PowerShell/Command Requirements | Use only approved commands for HTTP, status, process management | CRITICAL INSTRUCTIONS |
| AJAX Detection Pattern | Use required AJAX handler pattern to prevent JSON/HTML errors | CRITICAL INSTRUCTIONS |

---

## CRITICAL INSTRUCTIONS
- **WAIT Instruction:** When "WAIT" is part of the user prompt, explicit authorization must be obtained before taking any action after reporting to the user.
- **NO REMOTE PUSH WITHOUT PERMISSION:** Never push to remote server without explicit user permission.
- **CRITICAL GIT PAGER PREVENTION:**
  - **MANDATORY:** Always use `--no-pager` flag with ALL Git commands that may trigger pagers
  - **MANDATORY:** Use `| cat` suffix for commands that don't support `--no-pager`
  - **Failure Prevention:** This prevents terminal hanging and ensures AI agents can complete Git operations autonomously
- **CRITICAL TESTING PROCEDURES:**
  - Review PowerShell Commands section for correct HTTP testing method
  - Review Server Testing Protocol for proper approach
  - Confirm server is running with `netstat -an | findstr :8000`
  - Use `Invoke-WebRequest` as primary HTTP testing method
- **CRITICAL POWERSHELL/COMMAND REQUIREMENTS:**
  - Use only approved PowerShell commands for HTTP, status, process management
  - Use `;` for command chaining, `is_background: true` for background tasks
- **CRITICAL AJAX DETECTION PATTERN:**
  - Always use the established AJAX detection pattern to prevent "Unexpected token '<'" errors (see AJAX Implementation Standards)

---

## CORE PRINCIPLES
- **MVP Focus:** Simple, reliable, accurate, WCAG compliant code
- **Primary Concerns:** Simplicity, Reliability, Accuracy, WCAG compliance

---

## AI AGENT RESPONSIBILITIES
### Terminal Operations
- Handle ALL terminal interactions without asking user to perform them
- Only request authorization for system-level changes, security prompts, or when user input is specifically needed
- Proactively manage development environment

### User Task Management
- DO NOT ask user to perform tasks you can do—only ask for authorization or confirmation
- Take initiative to complete tasks within scope
- Report results clearly and concisely
- **Proactively suggest git hooks or automated tools:** If recurring code hygiene or formatting issues are detected (e.g., trailing whitespace, PHP closing tags, etc.), immediately recommend setting up git hooks or other automated solutions, and offer to help implement them early in the process.
- **Error Escalation/Early Warning:** If the same error or warning is encountered more than once, escalate to the user and propose a systemic solution (e.g., automation, documentation update, or codebase refactor).
- **AI Agent Self-Check:** Before executing any destructive or irreversible operation (delete, overwrite, mass refactor), summarize the action and request explicit user confirmation, even if not prompted by WAIT.
- **Linting/Formatting:** If a linter or code formatter is not detected, suggest adding one for the relevant language(s) at the first sign of style or whitespace issues.
- **Documentation Consistency:** When making changes to project rules, changelog, or documentation, always check for and update any related references elsewhere in the repo to maintain consistency.
- **Security/Privacy:** Never introduce or suggest security or privacy features unless explicitly requested, but always warn the user if a change could introduce a known security risk.

---

## DEVELOPMENT & SERVER GUIDELINES
### Local Server Management
- Server Type: PHP built-in development server
- Port: Use port 8000 by default (http://localhost:8000)
- Directory: Always start server from `otter/` directory (root level)
- Enhanced Startup: Use `./tests/start_server.ps1` for better error logging and monitoring
- Basic Command: `php -S localhost:8000` (PowerShell: `php -S localhost:8000`)
- Enhanced Command: `php -S localhost:8000 -d error_reporting=E_ALL -d log_errors=1 -d error_log=php_errors.log`
- Background: Start server in background for testing: `php -S localhost:8000` with `is_background: true`
- Stop Server: Use `taskkill /F /IM php.exe` to stop all PHP processes
- Multiple Instances: Check for existing servers with `netstat -an | findstr :8000`
- Port Conflict Resolution: If port 8000 is in use, use `netstat -an | findstr :8000` to identify the process, and `taskkill /PID <pid> /F` to terminate it, or start the server on a different port.
- Check for Other Test/Server Processes: Use `tasklist` to ensure no other test servers or background processes are running that could interfere.
- Check PHP Server Output: Always review the terminal output where the PHP server is running for errors, warnings, or stack traces after starting or when issues occur.
- Check PHP Error Log: Review `php_errors.log` for additional details on server-side errors.
- Health Check: Use `http://localhost:8000/health_check.php` for server status and configuration details.
- Diagnostic Tool: Use `./tests/diagnose_server.ps1` for comprehensive server health analysis.

### Development Guidelines
- No Security Enhancements: Unless specifically requested
- No Advanced Error Handling: Unless specifically requested
- No Performance Enhancements: Unless specifically requested
- MVP Focus: Simple, reliable, accurate, WCAG compliant

### File Naming Conventions
- Allowed: snake_case, kebab-case
- Preferred: snake_case for consistency

### Testing Requirements
- Test Location: Always test in `otter/` directory (root level) only
- MVP Principles: Maintain simple, reliable, accurate, WCAG compliant approach
- No Backwards Compatibility: No existing users, so no legacy considerations needed
- Focus: Functionality and data integrity across enterprises

### Universal Relative Paths Implementation
- No Environment Detection: All paths use simple relative navigation
- No BASE_PATH Logic: Removed all complex path generation
- Simple URLs: All URLs are direct relative paths (e.g., `assets/css/admin.css`)
- Cross-Server Compatibility: Works on any server structure without configuration
- No Complex URL Generation: Removed UnifiedUrlGenerator and related complexity
- Consistent Paths: Root level uses `assets/css/`, subdirectories use `../assets/css/`
- PATH_INFO Handling: PHP built-in server may treat `/file.php/path/` as PATH_INFO, causing redirect loops
- PATH_INFO Fix: Always detect and clean PATH_INFO before redirects in PHP files

---

## GIT OPERATIONS
- **CRITICAL: NO PAGER COMMANDS ALWAYS USED**
  - **MANDATORY:** Always use `--no-pager` flag with ALL Git commands that may trigger pagers
  - **MANDATORY:** Use `| cat` suffix for commands that don't support `--no-pager`
  - **Pager Issue:** Git commands like `git branch -a`, `git log`, `git diff` pipe output through pagers (less/more) requiring user interaction, causing process to hang
  - **Solution:** Use `--no-pager` flag to output directly to terminal without pager intervention
  - **Examples:**
    - `git branch -a --no-pager` (instead of `git branch -a`)
    - `git log --no-pager` (instead of `git log`)
    - `git diff --no-pager` (instead of `git diff`)
    - `git show --no-pager` (instead of `git show`)
    - `git status | cat` (for commands without --no-pager support)
  - **Failure Prevention:** This prevents terminal hanging and ensures AI agents can complete Git operations autonomously

---

## MVP CONTEXT
- No Existing Users: MVP with no backwards compatibility requirements
- Clean Implementation: No legacy code management needed
- Direct Changes: Implement changes directly without URL redirection or legacy support

---

## SUCCESS CRITERIA
- No duplicate code between classes
- Universal relative paths work correctly across all scenarios
- URL generation produces simple, consistent relative paths
- Error messages are specific and actionable
- Code is simpler and more maintainable
- Multi-enterprise architecture is supported
- WCAG compliance is maintained
- Clean implementation without legacy compatibility requirements

---

## CHANGELOG & DOCUMENTATION
### Changelog Commands
- changelog: Document all changes made during current session (see detailed instructions)
- changelog status: Document current application functionality at high level (see detailed instructions)
- Timestamp Generation: Use `Get-Date -Format "yyyy-MM-dd HH:mm:ss"` (PowerShell)
- Changelog Location: `clients-enterprise/changelog.md`

---

## AJAX IMPLEMENTATION STANDARDS
- Always use the established AJAX detection pattern to prevent "Unexpected token '<'" errors (see code examples)
- DO NOT use `X-Requested-With` header checks
- ALWAYS use `isset($_POST['action'])` for AJAX detection
- ALWAYS use output buffering
- ALWAYS set proper Content-Type header
- ALWAYS handle exceptions
- ALWAYS clean output buffer
- ALWAYS exit after JSON response
- See code examples for PHP and JavaScript patterns

---

## DOCUMENTATION STANDARDS
- Target Audience: AI agents unless directed otherwise
- Optimization: Structure for AI agent comprehension and action
- Clarity: Use clear, actionable language
- Completeness: Provide sufficient context for autonomous operation 