#!/bin/bash
# MVP Local Testing Bash Function
# Add this to your bash profile or run: source ./mvp-local-function.sh

mvp() {
    local command="$1"
    
    case "$command" in
        "local")
            echo -e "\033[32m🚀 Starting MVP Local Testing Environment...\033[0m"
            echo -e "\033[36mToken: mvp local\033[0m"
            echo -e "\033[33mMode: MVP (simplified, no count options complexity)\033[0m"
            echo ""
            
            # Execute the MVP testing script
            ./scripts/start-mvp-testing.sh
            ;;
        *)
            echo -e "\033[36mMVP Commands:\033[0m"
            echo -e "\033[37m  mvp local  - Start MVP local testing environment\033[0m"
            echo -e "\033[37m  mvp build  - Build MVP reports bundle only\033[0m"
            echo -e "\033[37m  mvp test   - Test MVP system\033[0m"
            ;;
    esac
}

# Alternative: Direct command function
mvp-local() {
    echo -e "\033[32m🚀 Starting MVP Local Testing Environment...\033[0m"
    echo -e "\033[36mToken: mvp local\033[0m"
    echo -e "\033[33mMode: MVP (simplified, no count options complexity)\033[0m"
    echo ""
    
    # Execute the MVP testing script
    ./scripts/start-mvp-testing.sh
}
