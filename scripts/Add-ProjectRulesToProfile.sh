#!/bin/bash
# Script to add "project rules" command to bash profile
# Run this script to add the "project rules" functionality to your bash profile

FORCE=false
PROFILE_PATH="$HOME/.bashrc"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        -f|--force)
            FORCE=true
            shift
            ;;
        --profile)
            PROFILE_PATH="$2"
            shift 2
            ;;
        *)
            echo "Unknown option $1"
            exit 1
            ;;
    esac
done

echo -e "\033[32mAdding Project Rules command to Bash Profile...\033[0m"

# Ensure profile exists
if [ ! -f "$PROFILE_PATH" ]; then
    echo -e "\033[33mCreating bash profile at: $PROFILE_PATH\033[0m"
    touch "$PROFILE_PATH"
fi

# Content to append to profile
PROFILE_CONTENT="

# Project Rules Commands
# Added by Add-ProjectRulesToProfile.sh on $(date '+%Y-%m-%d %H:%M:%S')

project() {
    local command=\"\$1\"
    if [ \"\$command\" = \"rules\" ]; then
        local path=\"C:/Users/George/Projects/otter/.cursor/rules/00-startup.mdc\"
        if [ -f \"\$path\" ]; then
            echo -e \"\\033[36m=== AGENT PREP: READ AND FOLLOW THE RULES BELOW FOR THIS SESSION ===\\033[0m\"
            cat \"\$path\"
            echo -e \"\\033[36m=== END RULES ===\\033[0m\"
            
            # Also open the rules file in Cursor/VS Code so the agent sees it as an open file
            if command -v cursor &> /dev/null; then
                cursor \"\$path\"
            elif command -v code &> /dev/null; then
                code \"\$path\"
            else
                # Try to open with default application
                if command -v xdg-open &> /dev/null; then
                    xdg-open \"\$path\"
                elif command -v open &> /dev/null; then
                    open \"\$path\"
                else
                    echo \"Could not open rules file. Please open manually: \$path\"
                fi
            fi
        else
            echo -e \"\\033[31mRules file not found at: \$path\\033[0m\"
        fi
    else
        # Fallback to any existing external 'project' command if present
        if command -v project &> /dev/null; then
            project \"\$command\"
        else
            echo -e \"\\033[33mUnknown project command: \$command\\033[0m\"
            echo -e \"\\033[36mTry: project rules\\033[0m\"
        fi
    fi
}
"

# Read current profile content
CURRENT_CONTENT=""
if [ -f "$PROFILE_PATH" ]; then
    CURRENT_CONTENT=$(cat "$PROFILE_PATH")
fi

# Skip if already present unless forced
if echo "$CURRENT_CONTENT" | grep -q "Project Rules Commands" && [ "$FORCE" = false ]; then
    echo -e "\033[33mProject Rules command already exists in profile.\033[0m"
    echo -e "\033[33mUse -f to force overwrite or manually edit: $PROFILE_PATH\033[0m"
    exit 0
fi

# Append to profile
echo "$PROFILE_CONTENT" >> "$PROFILE_PATH"

echo -e "\033[32m✅ Successfully added Project Rules command to bash profile!\033[0m"
echo ""
echo -e "\033[36mUsage:\033[0m"
echo -e "\033[37m  project rules\033[0m"
echo ""
echo -e "\033[33mTo use immediately, run:\033[0m"
echo -e "\033[37m  source $PROFILE_PATH\033[0m"
echo ""
echo -e "\033[37mOr restart your terminal to load the new profile.\033[0m"
