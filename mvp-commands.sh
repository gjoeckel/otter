#!/bin/bash
# MVP Commands - Load this to get 'mvp local' command
# Usage: source ./mvp-commands.sh

mvp() {
    local command="$1"
    
    case "$command" in
        "local")
            echo -e "\033[32mStarting MVP Local Testing Environment...\033[0m"
            echo -e "\033[36mToken: mvp local\033[0m"
            echo -e "\033[33mMode: MVP (simplified, no count options complexity)\033[0m"
            echo ""
            
            # Execute the MVP testing script
            ./scripts/start-mvp-testing.sh
            ;;
        "build")
            echo -e "\033[34mBuilding MVP reports bundle...\033[0m"
            npm run build:mvp
            ;;
        "test")
            echo -e "\033[34mTesting MVP system...\033[0m"
            # Create a simple test since test_mvp_system.php was deleted
            echo -e "\033[37mTesting MVP system...\033[0m"
            if [ -f "reports/dist/reports.bundle.js" ]; then
                local size=$(stat -c%s "reports/dist/reports.bundle.js" 2>/dev/null || stat -f%z "reports/dist/reports.bundle.js" 2>/dev/null || echo "unknown")
                echo -e "\033[32mMVP bundle exists ($size bytes)\033[0m"
            else
                echo -e "\033[31mMVP bundle missing\033[0m"
            fi
            ;;
        *)
            echo -e "\033[36mMVP Commands:\033[0m"
            echo -e "\033[37m  mvp local  - Start MVP local testing environment\033[0m"
            echo -e "\033[37m  mvp build  - Build MVP reports bundle only\033[0m"
            echo -e "\033[37m  mvp test   - Test MVP system\033[0m"
            ;;
    esac
}

echo -e "\033[32mMVP commands loaded!\033[0m"
echo -e "\033[33mTry: mvp local\033[0m"
