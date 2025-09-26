# Git Bash Terminal Requirements - Documentation Updates

## Implementation Instructions for AI Agent

Apply these changes to strengthen Git Bash terminal requirements across documentation files. Each section includes the file path, location indicator, and exact content to add or modify.

---

## 1. always.md - Update Testing Protocol Section

**Location:** Line ~45, in the "6. MANDATORY Testing Protocol" section

**Current text:**
```markdown
1. **Always start local server using:** `./mvp-local.sh` or `./tests/start_server.sh`
```

**Replace with:**
```markdown
1. **Always start local server using Git Bash terminal:** `./mvp-local.sh` or `./tests/start_server.sh`
   - **Windows users MUST use Git Bash** (not PowerShell or CMD)
   - Configure in Cursor: Settings > Terminal > Default Profile (Windows) > Git Bash
   - Verify shell: `echo $SHELL` should show `/usr/bin/bash` or similar
```

---

## 2. chrome-mcp.md - Add Critical Notice at Top

**Location:** Insert after line 7 (after the `# Chrome MCP Testing Integration Rules` header and before "## MANDATORY: Default Testing Protocol")

**Add this new section:**
```markdown
## ⚠️ CRITICAL: Windows Terminal Requirement

**Windows users MUST use Git Bash for all MCP operations:**
- PowerShell/CMD will cause Git and MCP tool failures
- Configure in Cursor: Settings > Terminal > Default Profile (Windows) > Git Bash
- All command examples in this document assume Git Bash environment
- Verify you're in Git Bash: `echo $SHELL` should show `/usr/bin/bash`

**Why Git Bash is required:**
- MCP tools expect Unix-like shell environment  
- PowerShell has incompatible path handling for shell scripts
- Git operations fail in PowerShell/CMD with path errors
- All test scripts (*.sh files) require Bash interpreter

---
```

---

## 3. development.md - Strengthen Shell Configuration Section

**Location:** Move and expand the "Shell Configuration (Windows)" section to appear earlier, around line 20 (after "## Development Environment Setup" and before "### Local Development")

**Replace existing "Shell Configuration (Windows)" section with:**
```markdown
### ⚠️ Shell Configuration - WINDOWS REQUIRED SETUP

**Git Bash is MANDATORY for Windows users - this is not optional:**

```bash
# ❌ DON'T USE: PowerShell or CMD - these WILL cause failures
# ✅ ALWAYS USE: Git Bash

# Configuration steps:
# 1. Open Cursor Settings (Ctrl+,)
# 2. Search: "terminal.integrated.defaultProfile.windows"
# 3. Select: "Git Bash"
# 4. Restart terminal (close and reopen)
# 5. Verify: echo $SHELL
#    Should show: /usr/bin/bash or /bin/bash
```

**Why Git Bash is required:**
- **MCP Tools:** All MCP tools expect Unix-like shell environment
- **Path Handling:** PowerShell uses incompatible path separators and escaping
- **Git Operations:** Git commands fail in PowerShell/CMD with path errors
- **Shell Scripts:** All `.sh` scripts require Bash interpreter (`./mvp-local.sh`, `./tests/start_server.sh`, etc.)
- **Test Framework:** Entire testing system designed for Bash environment

**Symptoms of using wrong shell:**
- Git operations fail with path errors
- MCP tools not recognized or fail to initialize
- Shell scripts won't execute (`./mvp-local.sh` fails)
- Test commands produce unexpected errors

**Quick verification:**
```bash
# Run this in your terminal:
echo $SHELL

# ✅ Correct output (Git Bash):
#    /usr/bin/bash  OR  /bin/bash

# ❌ Wrong output (PowerShell):
#    (no output or error)

# ❌ Wrong output (CMD):
#    (error or unexpected text)
```
```

---

## 4. testing.md - Add Terminal Requirements Section

**Location:** Insert after line 380, in the "## Test Execution" section, before "### Master Test Runner"

**Add this new subsection:**
```markdown
### Terminal Requirements for Testing

**Before running any tests, verify your terminal environment:**

```bash
# Windows users: Verify you're in Git Bash
echo $SHELL  # Should show: /usr/bin/bash or /bin/bash

# ❌ If you see PowerShell prompt (PS C:\>) or CMD prompt (C:\>)
#    You MUST switch to Git Bash immediately

# ✅ Correct: Git Bash prompt shows:
#    user@machine MINGW64 ~/Projects/otter (branch)
```

**All test commands require Git Bash environment:**
- `php tests/run_comprehensive_tests.php` - Requires Bash for session handling
- `php tests/chrome-mcp/run_chrome_mcp_tests.php` - Requires Bash for MCP integration
- `./tests/start_server.sh` - Shell script, only runs in Bash
- Any command starting with `./` requires Bash interpreter

**If tests fail with "command not found" or path errors:**
1. Check your shell: `echo $SHELL`
2. If not Git Bash, switch terminals
3. Configure default in Cursor settings
4. Restart terminal and try again

```

---

## 5. development.md - Add to Troubleshooting Section

**Location:** In the "## Troubleshooting Workflow" section, under "### Common Issues and Solutions", add as issue #6

**Add this new troubleshooting entry:**
```markdown
#### 6. MCP Tools Not Working / Git Operations Failing
**Problem:** MCP tools failing, Git operations not working, "command not found" errors, path errors
**Root Cause:** Using PowerShell or CMD instead of Git Bash
**Symptoms:**
- Git commands fail with path-related errors
- MCP tools not recognized or fail to initialize  
- Shell scripts won't execute (`./mvp-local.sh` fails)
- Error: `bash: ./script.sh: No such file or directory`
- Error: `The term 'mcp' is not recognized...` (PowerShell)

**Solution:** 
```bash
# Step 1: Check current shell
echo $SHELL
# If output is NOT /usr/bin/bash or /bin/bash, continue:

# Step 2: Open Cursor Settings
# Press: Ctrl+, (Windows) or Cmd+, (Mac)

# Step 3: Search for shell setting
# Search: "terminal.integrated.defaultProfile.windows"

# Step 4: Change to Git Bash
# Select: "Git Bash" from dropdown

# Step 5: Restart terminal
# Close terminal panel and reopen (Ctrl+` or View > Terminal)

# Step 6: Verify fix
echo $SHELL
# Should now show: /usr/bin/bash

# Step 7: Test MCP/Git operations
git status
# Should work without errors
```

**Prevention:** Always use Git Bash for all development work on Windows
```

---

## 6. ai-optimized.md - Strengthen Shell Configuration Notice

**Location:** Line 32, in the "### Shell Configuration" section

**Replace existing content with:**
```markdown
### Shell Configuration - CRITICAL FOR WINDOWS

**Default Shell: Git Bash (MANDATORY for Windows users)**

**Configuration Status:**
- ✅ Configured in Cursor settings as default terminal
- ✅ Resolves: PowerShell/CMD compatibility issues with Git operations  
- ✅ Provides: Unix-like command environment required by MCP tools
- ✅ Enables: All shell scripts (*.sh files) to execute properly

**Why this matters:**
- **All development commands assume Git Bash environment**
- **MCP tools will fail in PowerShell or CMD**
- **Test scripts require Bash interpreter**
- **Git operations need Unix-style path handling**

**Verification Command:**
```bash
echo $SHELL  # Must show: /usr/bin/bash or /bin/bash
```

**If using PowerShell/CMD by mistake:**
1. Open Cursor Settings (Ctrl+,)
2. Search: "terminal.integrated.defaultProfile.windows"
3. Change to: "Git Bash"
4. Restart terminal
```

---

## 7. always.md - Add to Quick Reference Commands Section

**Location:** Section 12 "Quick Reference Commands", after the comment about Git Bash

**Replace the existing comment line:**
```markdown
# Development (using Git Bash - recommended shell)
```

**With this expanded notice:**
```markdown
# ⚠️ WINDOWS USERS: All commands below require Git Bash terminal
# Verify your shell: echo $SHELL (should show /usr/bin/bash)
# If using PowerShell/CMD, switch to Git Bash immediately
# Configure: Cursor Settings > Search "defaultProfile.windows" > Select "Git Bash"
```

---

## 8. Create New Troubleshooting Entry in Multiple Files

**Files to update:** ai-optimized.md, always.md, chrome-mcp.md

**Location in each file:** In the "Common Issues & Solutions" or "Troubleshooting" section

**Add this entry:**
```markdown
### Wrong Terminal Shell (Windows)
- **Problem:** Commands failing, MCP tools not working, path errors
- **Solution:** Switch to Git Bash terminal (NOT PowerShell/CMD)
- **How to verify:** Run `echo $SHELL` - should show `/usr/bin/bash`
- **How to fix:** Cursor Settings > "defaultProfile.windows" > "Git Bash" > Restart terminal
```

---

## Summary of Changes

**Impact:** These updates will make Git Bash requirement impossible to miss:

1. ✅ **Prominent warnings** with ⚠️ symbols in critical sections
2. ✅ **Verification commands** so users can confirm correct shell
3. ✅ **Moved earlier** in documentation (before instructions, not buried in config)
4. ✅ **Added to troubleshooting** with clear symptoms and solutions  
5. ✅ **Explicit "MUST"** and "MANDATORY" language removes ambiguity
6. ✅ **Visual indicators** (❌ ✅) show right vs wrong approaches
7. ✅ **Multiple touchpoints** ensure message is seen regardless of entry point

**Implementation Order:**
1. Start with `always.md` (most critical file)
2. Then `chrome-mcp.md` (testing entry point)
3. Then `development.md` (developer workflow)
4. Then `testing.md` (test execution)
5. Finally `ai-optimized.md` (AI agent reference)

**Testing the updates:**
After implementing, verify that:
- Warning sections are visually distinct
- Verification commands are easy to find
- Troubleshooting entries are comprehensive
- Shell requirement appears BEFORE command examples