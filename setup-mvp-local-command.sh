#!/bin/bash
# Setup MVP Local Command for Git Bash
# This script adds the "mvp local" command to your bash profile

echo -e "\033[32mSetting up MVP local command for Git Bash...\033[0m"

# Get the current script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Determine the profile file to use
if [ -n "$BASH_VERSION" ]; then
    PROFILE_FILE="$HOME/.bashrc"
elif [ -n "$ZSH_VERSION" ]; then
    PROFILE_FILE="$HOME/.zshrc"
else
    PROFILE_FILE="$HOME/.bashrc"
fi

# Content to add to profile
PROFILE_CONTENT="

# MVP Local Command
# Added by setup-mvp-local-command.sh on $(date '+%Y-%m-%d %H:%M:%S')

mvp() {
    local command=\"\$1\"
    
    case \"\$command\" in
        \"local\")
            echo -e \"\\033[32m🚀 Starting MVP Local Environment...\\033[0m\"
            echo -e \"\\033[36mToken: mvp local\\033[0m\"
            echo -e \"\\033[33mMode: MVP (simplified, no count options complexity)\\033[0m\"
            echo \"\"
            
            # Execute the MVP local script
            \"$SCRIPT_DIR/mvp-local.sh\"
            ;;
        \"build\")
            echo -e \"\\033[34m🔧 Building MVP reports bundle...\\033[0m\"
            npm run build:mvp
            ;;
        \"test\")
            echo -e \"\\033[34m🧪 Testing MVP system...\\033[0m\"
            if [ -f \"reports/dist/reports.bundle.js\" ]; then
                local size=\$(stat -c%s \"reports/dist/reports.bundle.js\" 2>/dev/null || stat -f%z \"reports/dist/reports.bundle.js\" 2>/dev/null || echo \"unknown\")
                echo -e \"\\033[32mMVP bundle exists (\$size bytes)\\033[0m\"
            else
                echo -e \"\\033[31mMVP bundle missing\\033[0m\"
            fi
            ;;
        \"help\"|\"--help\"|\"-h\")
            echo -e \"\\033[36mMVP Commands:\\033[0m\"
            echo -e \"\\033[37m  mvp local  - Start MVP local testing environment\\033[0m\"
            echo -e \"\\033[37m  mvp build  - Build MVP reports bundle only\\033[0m\"
            echo -e \"\\033[37m  mvp test   - Test MVP system\\033[0m\"
            echo -e \"\\033[37m  mvp help   - Show this help message\\033[0m\"
            ;;
        *)
            echo -e \"\\033[33mUnknown MVP command: \$command\\033[0m\"
            echo -e \"\\033[36mAvailable commands: local, build, test, help\\033[0m\"
            echo -e \"\\033[37mUse 'mvp help' for more information\\033[0m\"
            ;;
    esac
}

# Create alias for convenience
alias mvp-local='mvp local'

echo -e \"\\033[32mMVP commands loaded!\\033[0m\"
"

# Check if already present
if [ -f "$PROFILE_FILE" ] && grep -q "MVP Local Command" "$PROFILE_FILE"; then
    echo -e "\033[33mMVP local command already exists in profile.\033[0m"
    echo -e "\033[33mUse -f to force overwrite or manually edit: $PROFILE_FILE\033[0m"
    exit 0
fi

# Append to profile
echo "$PROFILE_CONTENT" >> "$PROFILE_FILE"

echo -e "\033[32m✅ Successfully added MVP local command to bash profile!\033[0m"
echo ""
echo -e "\033[36mUsage:\033[0m"
echo -e "\033[37m  mvp local  - Start MVP local testing environment\033[0m"
echo -e "\033[37m  mvp build  - Build MVP reports bundle only\033[0m"
echo -e "\033[37m  mvp test   - Test MVP system\033[0m"
echo -e "\033[37m  mvp help   - Show help message\033[0m"
echo -e "\033[37m  mvp-local  - Alias for 'mvp local'\033[0m"
echo ""
echo -e "\033[33mTo use immediately, run:\033[0m"
echo -e "\033[37m  source $PROFILE_FILE\033[0m"
echo ""
echo -e "\033[37mOr restart your terminal to load the new profile.\033[0m"
echo ""
echo -e "\033[32mThen you can use: mvp local\033[0m"
