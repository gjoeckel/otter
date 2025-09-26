# MCP Testing Patterns

This directory contains step-by-step testing patterns derived from actual MCP Chrome DevTools testing sessions. These patterns can be reused for automated testing and serve as documentation for common testing workflows.

## Available Patterns

### 1. Reports Page Testing (`reports-page-testing.md`)
Complete testing patterns for the Reports page functionality including:
- Basic access and login
- Date range selection
- Data tables visibility
- Console and network monitoring
- Data validation
- Performance and error handling

### 2. OTRS Testing Sequence (`otrs-testing-sequence.md`)
Comprehensive testing sequence for cache regeneration and demo transformation validation:
- Cache management and cleanup
- Browser testing setup
- Date range functionality
- Data validation with specific organization names
- Console and network monitoring
- Complete system health verification

### 3. OTTER Shorthand Reference (`otter-shorthand-reference.md`)
Quick reference for OTTER shorthand notation used in testing:
- Core shorthand definitions
- Usage examples
- Integration with MCP testing patterns
- Benefits for efficient testing communication

## Pattern Structure

Each testing pattern follows this structure:
- **Objective**: Clear description of what the test validates
- **Steps**: Detailed step-by-step instructions with specific MCP tool calls
- **Expected Results**: What should happen at each step
- **UID Patterns**: Common element identification patterns
- **Troubleshooting**: Common issues and solutions

## Usage

These patterns can be used for:
- Manual testing procedures
- Automated testing script generation
- Regression testing
- New feature validation
- Bug reproduction

## MCP Tools Used

- `mcp_chrome-devtools_new_page`: Create new browser tabs
- `mcp_chrome-devtools_navigate_page`: Navigate to URLs
- `mcp_chrome-devtools_take_snapshot`: Capture page state
- `mcp_chrome-devtools_click`: Click elements
- `mcp_chrome-devtools_fill`: Fill form fields
- `mcp_chrome-devtools_evaluate_script`: Execute JavaScript
- `mcp_chrome-devtools_list_console_messages`: Check console output
- `mcp_chrome-devtools_list_network_requests`: Monitor network activity
- `mcp_chrome-devtools_performance_start_trace`: Performance monitoring

## Contributing

When adding new testing patterns:
1. Follow the established structure
2. Include specific UID patterns where possible
3. Document expected results clearly
4. Add troubleshooting information
5. Test the pattern thoroughly before documenting
