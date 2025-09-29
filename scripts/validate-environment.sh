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
