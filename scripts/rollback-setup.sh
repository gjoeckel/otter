#!/bin/bash
# scripts/rollback-setup.sh
# Rollback Windows MCP setup (granular options)

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
source "$SCRIPT_DIR/lib/common.sh"

echo "=== MCP Setup Rollback ==="
echo "Choose rollback option:"
echo "1. Partial rollback (keep configs, remove cache)"
echo "2. Full rollback (remove all MCP setup)"
echo "3. Custom rollback (select components)"
echo "4. Emergency reset (nuclear option)"
read -p "Enter choice (1-4): " choice

case $choice in
    1) # Partial rollback
        echo "Performing partial rollback..."
        echo "      Removing MCP cache directories..."
        rm -rf "$HOME/.cursor/mcp-cache" 2>/dev/null
        rm -rf "$MEMORY_DIR" 2>/dev/null
        rm -rf "/c/temp/chrome-debug-mcp" 2>/dev/null

        echo "      Clearing npm cache..."
        npm cache clean --force 2>/dev/null

        echo "      Stopping MCP server processes..."
        pkill -f "chrome-devtools-mcp" 2>/dev/null
        pkill -f "mcp-server" 2>/dev/null

        print_success "Partial rollback complete"
        echo "        MCP configurations preserved"
        echo "        Cache and temporary files removed"
        echo "        MCP server processes stopped"
        ;;
    2) # Full rollback
        echo "Performing full rollback..."
        echo "      Removing MCP configurations..."
        rm -f "$MCP_CONFIG" 2>/dev/null
        rm -rf "$MEMORY_DIR" 2>/dev/null
        rm -rf "$HOME/.cursor/mcp-cache" 2>/dev/null
        rm -rf "/c/temp/chrome-debug-mcp" 2>/dev/null

        echo "      Removing globally installed MCP packages..."
        npm uninstall -g @modelcontextprotocol/server-filesystem 2>/dev/null
        npm uninstall -g @modelcontextprotocol/server-memory 2>/dev/null
        npm uninstall -g chrome-devtools-mcp 2>/dev/null

        echo "      Removing Windows Firewall rule..."
        powershell.exe -Command "Remove-NetFirewallRule -DisplayName 'Chrome Remote Debugging MCP' -ErrorAction SilentlyContinue" 2>/dev/null

        echo "      Stopping all MCP processes..."
        pkill -f "chrome-devtools-mcp" 2>/dev/null
        pkill -f "mcp-server" 2>/dev/null
        pkill -f "chrome.*9222" 2>/dev/null

        print_success "Full rollback complete"
        echo "        All MCP setup removed"
        echo "        System restored to pre-MCP state"
        ;;
    3) # Custom rollback
        echo "Custom rollback options:"
        echo "a) Remove only Chrome MCP"
        echo "b) Remove only Filesystem MCP"
        echo "c) Remove only Memory MCP"
        echo "d) Remove only Git MCP"
        echo "e) Remove performance baselines"
        read -p "Select components to remove (a-e, comma-separated): " components

        IFS=',' read -ra COMPONENTS <<< "$components"
        for component in "${COMPONENTS[@]}"; do
            case $component in
                "a")
                    echo "      Removing Chrome MCP..."
                    npm uninstall -g chrome-devtools-mcp 2>/dev/null
                    pkill -f "chrome-devtools-mcp" 2>/dev/null
                    pkill -f "chrome.*9222" 2>/dev/null
                    powershell.exe -Command "Remove-NetFirewallRule -DisplayName 'Chrome Remote Debugging MCP' -ErrorAction SilentlyContinue" 2>/dev/null
                    ;;
                "b")
                    echo "      Removing Filesystem MCP..."
                    npm uninstall -g @modelcontextprotocol/server-filesystem 2>/dev/null
                    pkill -f "server-filesystem" 2>/dev/null
                    ;;
                "c")
                    echo "      Removing Memory MCP..."
                    npm uninstall -g @modelcontextprotocol/server-memory 2>/dev/null
                    pkill -f "server-memory" 2>/dev/null
                    rm -rf "$MEMORY_DIR" 2>/dev/null
                    ;;
                "d")
                    echo "      Removing Git MCP..."
                    # Note: Git MCP is installed via uvx, not npm
                    pkill -f "mcp-server-git" 2>/dev/null
                    ;;
                "e")
                    echo "      Removing performance baselines..."
                    rm -f "$HOME/.cursor/mcp-performance-baseline.json" 2>/dev/null
                    ;;
            esac
        done

        print_success "Custom rollback complete"
        ;;
    4) # Emergency reset
        echo "Performing emergency reset..."
        echo "      WARNING: This will remove ALL MCP setup and configurations"
        read -p "Are you sure? Type 'yes' to continue: " confirm

        if [ "$confirm" = "yes" ]; then
            # Use existing emergency reset script
            if [ -f "$SCRIPT_DIR/emergency-reset.sh" ]; then
                "$SCRIPT_DIR/emergency-reset.sh"
            else
                echo "      Emergency reset script not found, performing manual reset..."

                # Remove everything
                rm -f "$MCP_CONFIG" 2>/dev/null
                rm -rf "$MEMORY_DIR" 2>/dev/null
                rm -rf "$HOME/.cursor/mcp-cache" 2>/dev/null
                rm -rf "/c/temp/chrome-debug-mcp" 2>/dev/null
                rm -f "$HOME/.cursor/mcp-performance-baseline.json" 2>/dev/null

                # Uninstall packages
                npm uninstall -g @modelcontextprotocol/server-filesystem 2>/dev/null
                npm uninstall -g @modelcontextprotocol/server-memory 2>/dev/null
                npm uninstall -g chrome-devtools-mcp 2>/dev/null

                # Kill processes
                pkill -f "chrome-devtools-mcp" 2>/dev/null
                pkill -f "mcp-server" 2>/dev/null
                pkill -f "chrome.*9222" 2>/dev/null

                # Remove firewall rule
                powershell.exe -Command "Remove-NetFirewallRule -DisplayName 'Chrome Remote Debugging MCP' -ErrorAction SilentlyContinue" 2>/dev/null

                print_success "Emergency reset complete"
            fi
        else
            echo "Emergency reset cancelled"
        fi
        ;;
    *)
        echo "Invalid choice. Rollback cancelled."
        exit 1
        ;;
esac

echo ""
echo "Rollback completed. Next steps:"
echo "  1. Restart Cursor IDE"
echo "  2. Run: ./scripts/validate-environment.sh"
echo "  3. Run: ./scripts/check-mcp-health.sh"
echo "  4. If issues persist, run: ./scripts/setup-windows-mcp.sh"
