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
        "verify")
            echo -e "\033[34mVerifying MVP direct module loading...\033[0m"
            # Check that essential JavaScript modules exist
            local modules=(
                "reports/js/reports-data.js"
                "reports/js/unified-data-service.js"
                "reports/js/unified-table-updater.js"
                "reports/js/reports-entry.js"
                "reports/js/reports-messaging.js"
                "reports/js/date-range-picker.js"
            )
            
            local all_modules_exist=true
            for module in "${modules[@]}"; do
                if [ -f "$module" ]; then
                    echo -e "\033[32m✅ $module\033[0m"
                else
                    echo -e "\033[31m❌ $module (missing)\033[0m"
                    all_modules_exist=false
                fi
            done
            
            if [ "$all_modules_exist" = true ]; then
                echo -e "\033[32mAll MVP modules present - direct loading ready\033[0m"
            else
                echo -e "\033[31mSome MVP modules missing\033[0m"
            fi
            ;;
        "test")
            echo -e "\033[34mTesting MVP system...\033[0m"
            echo -e "\033[37mTesting MVP direct module loading system...\033[0m"
            # Check that essential JavaScript modules exist
            local modules=(
                "reports/js/reports-data.js"
                "reports/js/unified-data-service.js"
                "reports/js/unified-table-updater.js"
                "reports/js/reports-entry.js"
                "reports/js/reports-messaging.js"
                "reports/js/date-range-picker.js"
            )
            
            local all_modules_exist=true
            for module in "${modules[@]}"; do
                if [ -f "$module" ]; then
                    echo -e "\033[32m✅ $module\033[0m"
                else
                    echo -e "\033[31m❌ $module (missing)\033[0m"
                    all_modules_exist=false
                fi
            done
            
            if [ "$all_modules_exist" = true ]; then
                echo -e "\033[32mMVP direct module loading system ready\033[0m"
            else
                echo -e "\033[31mMVP direct module loading system incomplete\033[0m"
            fi
            ;;
        *)
            echo -e "\033[36mMVP Commands:\033[0m"
            echo -e "\033[37m  mvp local  - Start MVP local testing environment\033[0m"
            echo -e "\033[37m  mvp verify - Verify MVP direct module loading setup\033[0m"
            echo -e "\033[37m  mvp test   - Test MVP direct module loading system\033[0m"
            ;;
    esac
}

echo -e "\033[32mMVP commands loaded!\033[0m"
echo -e "\033[33mTry: mvp local\033[0m"
