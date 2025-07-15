# Git Hooks Documentation

## Overview

This document describes the git hooks implemented to prevent common PHP errors that have been recurring in the changelog. The hooks are designed to catch issues before they reach the repository and cause problems in production.

## Implemented Hooks

### 1. Pre-commit Hook

**Purpose**: Validates staged PHP files before committing to prevent common errors.

**Checks Performed**:
- **PHP Closing Tags (`?>`)**: Prevents JSON contamination that causes "Unexpected token '<'" errors
- **Trailing Whitespace**: Prevents whitespace contamination in JSON responses
- **Session Management**: Warns about `session_start()` without proper status checks
- **PHP Syntax Errors**: Basic PHP syntax validation
- **AJAX Output Buffering**: Warns about JSON headers without output buffering

**Files**:
- `.git/hooks/pre-commit` (Bash version with Windows detection)
- `.git/hooks/pre-commit.ps1` (PowerShell version for Windows)

### 2. Pre-push Hook

**Purpose**: Comprehensive validation before any potential remote operations.

**Checks Performed**:
- **Critical API Issues**: Blocks push if PHP closing tags found in API/reports files
- **PHP Syntax Errors**: Comprehensive syntax validation across all PHP files
- **Session Management**: Warns about session issues
- **File Structure**: Checks for critical project files
- **Git Configuration**: Validates git user settings

**Files**:
- `.git/hooks/pre-push` (Bash version with Windows detection)
- `.git/hooks/pre-push.ps1` (PowerShell version for Windows)

## Error Prevention

### 1. PHP Closing Tags (`?>`)

**Problem**: PHP closing tags in files that output JSON cause HTML to be sent instead of JSON, resulting in "Unexpected token '<'" errors in browser console.

**Solution**: Hooks detect and block commits containing `?>` in PHP files.

**Example Error**:
```php
<?php
// API code here
echo json_encode($data);
?>  // ← This causes the problem
```

**Fix**:
```php
<?php
// API code here
echo json_encode($data);
// Remove the closing tag entirely
```

### 2. Trailing Whitespace

**Problem**: Trailing whitespace can contaminate JSON responses and cause parsing errors.

**Solution**: Hooks detect and block commits with trailing whitespace.

### 3. Session Management

**Problem**: `session_start()` called multiple times causes "session already started" warnings.

**Solution**: Hooks warn about `session_start()` without proper status checks.

**Recommended Pattern**:
```php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
```

### 4. AJAX Output Buffering

**Problem**: JSON responses without output buffering can be contaminated by unexpected output.

**Solution**: Hooks warn about JSON headers without `ob_start()`.

**Recommended Pattern**:
```php
ob_start();
header('Content-Type: application/json');
// ... AJAX logic ...
ob_end_clean();
echo json_encode($result);
```

## Usage

### Automatic Execution

The hooks run automatically when you:
- **Commit**: `git commit` triggers pre-commit hook
- **Push**: `git push` triggers pre-push hook

### Manual Testing

You can test the hooks manually:

```bash
# Test pre-commit hook
.git/hooks/pre-commit

# Test pre-push hook
.git/hooks/pre-push
```

### PowerShell Testing (Windows)

```powershell
# Test PowerShell versions directly
powershell.exe -ExecutionPolicy Bypass -File .git/hooks/pre-commit.ps1
powershell.exe -ExecutionPolicy Bypass -File .git/hooks/pre-push.ps1
```

## Error Messages

### Pre-commit Hook Messages

- **❌ ERROR**: Critical issues that block the commit
- **⚠️ WARNING**: Issues that should be addressed but don't block the commit

### Pre-push Hook Messages

- **❌ CRITICAL**: Issues that completely block pushing
- **❌ ERROR**: Issues that block pushing
- **⚠️ WARNING**: Issues that should be addressed

## Common Fixes

When hooks fail, here are the most common fixes:

1. **Remove PHP closing tags** from files that output JSON
2. **Remove trailing whitespace** from files
3. **Add session status checks** before `session_start()`
4. **Add output buffering** for AJAX handlers
5. **Fix PHP syntax errors** identified by the hooks

## Cross-Platform Compatibility

The hooks are designed to work on both Windows and Unix-like systems:

- **Windows**: Uses PowerShell versions for better integration
- **Unix/Linux/macOS**: Uses Bash versions
- **Automatic Detection**: Bash hooks detect Windows environment and call PowerShell versions

## Configuration

### Disabling Hooks (Temporary)

To temporarily disable hooks for a single operation:

```bash
git commit --no-verify
git push --no-verify
```

### Permanently Disabling Hooks

Remove or rename the hook files:
```bash
mv .git/hooks/pre-commit .git/hooks/pre-commit.disabled
mv .git/hooks/pre-push .git/hooks/pre-push.disabled
```

## Maintenance

### Updating Hooks

To update the hooks:
1. Edit the appropriate hook file
2. Test manually to ensure it works
3. Commit the changes

### Adding New Checks

To add new validation checks:
1. Identify the recurring error pattern
2. Add the check to both Bash and PowerShell versions
3. Test thoroughly
4. Document the new check

## Troubleshooting

### Hook Not Running

1. Check file permissions: `ls -la .git/hooks/`
2. Verify hook is executable: `chmod +x .git/hooks/pre-commit`
3. Check for syntax errors in hook files

### False Positives

If hooks are blocking valid code:
1. Review the specific error message
2. Consider if the code pattern is actually problematic
3. Modify the hook to be more specific if needed

### Performance Issues

If hooks are slow:
1. Check if PHP syntax validation is taking too long
2. Consider excluding large files or directories
3. Optimize regex patterns in the hooks

## Integration with Project Rules

These hooks align with the project's core principles:

- **Simple**: Focus on specific, documented problems
- **Reliable**: Prevent errors that break functionality
- **Accurate**: Target issues that cause actual problems
- **MVP Focus**: Address immediate needs without over-engineering

The hooks specifically enforce the AJAX implementation standards and prevent the recurring issues documented in the changelog. 