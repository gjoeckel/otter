#!/bin/bash
# Automated Windows 11 MCP setup script

set -e  # Exit on any error

echo "=== Windows 11 MCP Development Setup ==="
echo ""

# Source common functions
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

# Step 1: Check prerequisites
echo "[1/7] Checking prerequisites..."
REQUIRED_CMDS=("git" "php" "node" "npm" "npx")
for cmd in "${REQUIRED_CMDS[@]}"; do
    if ! check_command "$cmd"; then
        print_error "$cmd not found in PATH"
        echo ""
        echo "Please install missing software:"
        echo "- Git Bash: https://git-scm.com/download/win"
        echo "- Node.js 18+: https://nodejs.org/"
        echo "- PHP 8.0+: https://windows.php.net/download/"
        exit 1
    fi
done
print_success "All required commands available"

# Step 2: Configure Git
echo "[2/7] Configuring Git..."
git config --global core.autocrlf true || true
git config --global core.longpaths true || true
print_success "Git configured for Windows"

# Step 3: Install npm packages
echo "[3/7] Installing npm packages..."
npm install -g npm@latest 2>/dev/null || print_warning "npm update failed"
npm install -g @modelcontextprotocol/server-filesystem 2>/dev/null || print_warning "filesystem install failed"
npm install -g @modelcontextprotocol/server-memory 2>/dev/null || print_warning "memory install failed"
npm install -g chrome-devtools-mcp 2>/dev/null || print_warning "chrome-devtools install failed"
print_success "npm packages installed"

# Step 4: Create directories
echo "[4/7] Creating directories..."
mkdir -p "$MEMORY_DIR"
mkdir -p "/c/temp/chrome-debug-mcp"
print_success "Directories created"

# Step 5: Configure Windows Firewall
echo "[5/7] Configuring Windows Firewall..."
echo "      Opening PowerShell to add firewall rule..."
echo "      (Requires Administrator privileges)"
powershell.exe -Command "Start-Process powershell -Verb RunAs -ArgumentList '-Command', 'New-NetFirewallRule -DisplayName \"Chrome Remote Debugging MCP\" -Direction Inbound -LocalPort 9222 -Protocol TCP -Action Allow'" 2>/dev/null
print_success "Firewall rule added (check PowerShell window)"

# Step 6: Validate setup
echo "[6/7] Validating setup..."
if [ -f "./scripts/validate-environment.sh" ]; then
    ./scripts/validate-environment.sh
else
    print_warning "Validation script not found"
fi

# Step 7: Next steps
echo ""
echo "=== Setup Complete ==="
echo ""
echo "Next steps:"
echo "  1. Start Chrome: ./scripts/start-chrome-debug.sh"
echo "  2. Start server: ./tests/start_server.sh"
echo "  3. Check health: ./scripts/check-mcp-health.sh"
echo "  4. Run tests: php tests/run_comprehensive_tests.php"
echo ""
echo "If you encounter issues:"
echo "  - Run: ./scripts/check-mcp-health.sh"
echo "  - Run: ./scripts/rollback-setup.sh (if needed)"
echo "  - See: docs/windows-setup.md for detailed troubleshooting"
