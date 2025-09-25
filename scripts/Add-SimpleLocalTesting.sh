#!/bin/bash
# Simple Local Testing Environment Profile Setup
# Adds "test local" command to bash profile

PROFILE_PATH="$HOME/.bashrc"

# Parse command line arguments
while [[ $# -gt 0 ]]; do
    case $1 in
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

echo -e "\033[32mAdding Simple Local Testing Environment to Bash Profile...\033[0m"

# Check if profile exists
if [ ! -f "$PROFILE_PATH" ]; then
    echo -e "\033[33mCreating bash profile at: $PROFILE_PATH\033[0m"
    touch "$PROFILE_PATH"
fi

# Get the current script directory
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Content to add to profile
PROFILE_CONTENT="

# Local Testing Environment Commands
# Added by Add-SimpleLocalTesting.sh on $(date '+%Y-%m-%d %H:%M:%S')

# Function to start local testing environment
start_local_testing() {
    local skip_build=false
    local skip_websocket=false
    local php_port=8000
    
    # Parse arguments
    while [[ \$# -gt 0 ]]; do
        case \$1 in
            --skip-build)
                skip_build=true
                shift
                ;;
            --skip-websocket)
                skip_websocket=true
                shift
                ;;
            --php-port)
                php_port=\"\$2\"
                shift 2
                ;;
            *)
                shift
                ;;
        esac
    done
    
    # Get the project root
    local project_root=\$(pwd)
    if [[ \$project_root == *\"otter\"* ]]; then
        local script_path=\"\$project_root/scripts/start-local-testing-simple.sh\"
    else
        echo -e \"\\033[33mPlease run this from the otter project directory.\\033[0m\"
        return 1
    fi
    
    if [ -f \"\$script_path\" ]; then
        \"\$script_path\" --skip-build:\$skip_build --skip-websocket:\$skip_websocket --php-port \"\$php_port\"
    else
        echo -e \"\\033[31mLocal testing script not found at: \$script_path\\033[0m\"
        echo -e \"\\033[33mPlease run this from the otter project directory.\\033[0m\"
        return 1
    fi
}

# Create aliases
alias start-local-testing=start_local_testing
alias slt=start_local_testing

# Simple command for \"test local\"
test() {
    local command=\"\$1\"
    if [ \"\$command\" = \"local\" ]; then
        start_local_testing
    else
        echo -e \"\\033[33mUnknown test command: \$command\\033[0m\"
        echo -e \"\\033[36mUse 'test local' to start the local testing environment\\033[0m\"
    fi
}

echo -e \"\\033[32mLocal Testing Environment commands loaded!\\033[0m\"
"

# Write to profile
echo "$PROFILE_CONTENT" >> "$PROFILE_PATH"

echo -e "\033[32m✅ Successfully added Local Testing Environment to bash profile!\033[0m"
echo ""
echo -e "\033[36mAvailable commands:\033[0m"
echo -e "\033[37m  start_local_testing\033[0m"
echo -e "\033[37m  start-local-testing\033[0m"
echo -e "\033[37m  slt\033[0m"
echo -e "\033[37m  test local\033[0m"
echo ""
echo -e "\033[33mTo use immediately, run:\033[0m"
echo -e "\033[37m  source $PROFILE_PATH\033[0m"
