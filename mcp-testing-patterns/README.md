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

### 2. Demo Transformation Validation (`demo-transformation-validation.md`)
Comprehensive testing sequence specifically for validating demo transformation functionality:
- Cache management and cleanup for demo enterprise
- Browser testing setup for demo reports page
- Date range functionality testing
- Data validation with specific organization names (e.g., "Bakersfield College Demo")
- Console and network monitoring
- Verification that organization names show " Demo" suffix instead of generic "Demo Organization"

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

## Snapshot Optimization Strategy

**Eliminate snapshots for static pages** to reduce overhead and focus on dynamic content:

### Static Pages (No Snapshots Needed)
- **Login page**: Static form, no dynamic content
- **Admin interface**: Static navigation, no dynamic content

### Dynamic Pages (Snapshots Required)
- **Reports page with data**: Dynamic date ranges, data tables, organization names
- **Data tables after filtering**: Dynamic content changes
- **Date range updates**: Dynamic ADR display changes
- **Organization data**: Dynamic organization names with transformations

### Benefits
- **Faster testing**: Reduced snapshot overhead
- **Focused validation**: Only capture meaningful state changes
- **Clearer documentation**: Snapshots show actual test results, not static pages

## Contributing

When adding new testing patterns:
1. Follow the established structure
2. Include specific UID patterns where possible
3. Document expected results clearly
4. Add troubleshooting information
5. Test the pattern thoroughly before documenting
