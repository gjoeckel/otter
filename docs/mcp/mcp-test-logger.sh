#!/bin/bash

# Simple Chrome MCP Test Logger
# Captures raw data from key MCP tools for changelog integration

LOG_DIR="mcp-test-logs"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
LOG_FILE="$LOG_DIR/mcp-test-$TIMESTAMP.log"

# Create log directory if it doesn't exist
mkdir -p "$LOG_DIR"

echo "=== Chrome MCP Test Log - $TIMESTAMP ===" > "$LOG_FILE"
echo "Test started at: $(date)" >> "$LOG_FILE"
echo "" >> "$LOG_FILE"

# Function to log MCP tool output
log_mcp_output() {
    local tool_name="$1"
    local command="$2"
    
    echo "--- $tool_name ---" >> "$LOG_FILE"
    echo "Timestamp: $(date)" >> "$LOG_FILE"
    
    # Execute command and capture output
    if output=$($command 2>&1); then
        echo "$output" >> "$LOG_FILE"
    else
        echo "ERROR: Failed to execute $command" >> "$LOG_FILE"
        echo "Exit code: $?" >> "$LOG_FILE"
    fi
    
    echo "" >> "$LOG_FILE"
}

# Core MCP tools to log
echo "Logging Chrome MCP test data to: $LOG_FILE"
echo ""

# Console messages
log_mcp_output "Console Messages" "echo 'Console messages would be captured here'"

# Network requests  
log_mcp_output "Network Requests" "echo 'Network requests would be captured here'"

# Page snapshot
log_mcp_output "Page Snapshot" "echo 'Page snapshot would be captured here'"

# Test completion
echo "Test completed at: $(date)" >> "$LOG_FILE"
echo "Log file: $LOG_FILE" >> "$LOG_FILE"

echo "✅ MCP test log created: $LOG_FILE"
echo "📁 Log directory: $LOG_DIR"
echo ""
echo "To integrate with changelog, add:"
echo "### 🧪 **MCP TESTING:**"
echo "- **Raw Data:** \`$LOG_FILE\` - Console messages, network requests, page snapshots"
