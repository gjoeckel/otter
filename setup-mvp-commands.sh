#!/bin/bash
# Setup MVP Commands
# Run this once to make 'mvp local' command available

echo -e "\033[32mSetting up MVP commands...\033[0m"

# Get the current script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Create the mvp function and add to bashrc
PROFILE_CONTENT="
# MVP Commands
# Added by setup-mvp-commands.sh on $(date '+%Y-%m-%d %H:%M:%S')

mvp() {
    local command=\"\$1\"
    
    case \"\$command\" in
        \"local\")
            echo -e \"\\033[32m🚀 Starting MVP Local Testing Environment...\\033[0m\"
            echo -e \"\\033[36mToken: mvp local\\033[0m\"
            echo -e \"\\033[33mMode: MVP (simplified, no count options complexity)\\033[0m\"
            echo \"\"
            
            # Execute the MVP testing script
            \"$SCRIPT_DIR/scripts/start-mvp-testing.sh\"
            ;;
        \"build\")
            echo -e \"\\033[34m🔧 Building MVP reports bundle...\\033[0m\"
            npm run build:mvp
            ;;
        \"test\")
            echo -e \"\\033[34m🧪 Testing MVP system...\\033[0m\"
            php test_mvp_system.php
            ;;
        *)
            echo -e \"\\033[36mMVP Commands:\\033[0m\"
            echo -e \"\\033[37m  mvp local  - Start MVP local testing environment\\033[0m\"
            echo -e \"\\033[37m  mvp build  - Build MVP reports bundle only\\033[0m\"
            echo -e \"\\033[37m  mvp test   - Test MVP system\\033[0m\"
            ;;
    esac
}
"

# Determine the profile file to use
if [ -n "$BASH_VERSION" ]; then
    PROFILE_FILE="$HOME/.bashrc"
elif [ -n "$ZSH_VERSION" ]; then
    PROFILE_FILE="$HOME/.zshrc"
else
    PROFILE_FILE="$HOME/.bashrc"
fi

# Check if already present
if [ -f "$PROFILE_FILE" ] && grep -q "MVP Commands" "$PROFILE_FILE"; then
    echo -e "\033[33mMVP commands already exist in profile.\033[0m"
    echo -e "\033[33mUse -f to force overwrite or manually edit: $PROFILE_FILE\033[0m"
    exit 0
fi

# Append to profile
echo "$PROFILE_CONTENT" >> "$PROFILE_FILE"

echo -e "\033[32m✅ Successfully added MVP commands to profile!\033[0m"
echo ""
echo -e "\033[36mUsage:\033[0m"
echo -e "\033[37m  mvp local  - Start MVP local testing environment\033[0m"
echo -e "\033[37m  mvp build  - Build MVP reports bundle only\033[0m"
echo -e "\033[37m  mvp test   - Test MVP system\033[0m"
echo ""
echo -e "\033[33mTo use immediately, run:\033[0m"
echo -e "\033[37m  source $PROFILE_FILE\033[0m"
echo ""
echo -e "\033[37mOr restart your terminal to load the new profile.\033[0m"
