---
alwaysApply: false
description: "MVP system development rules and patterns"
---

# MVP System Development Rules

## MVP Architecture Overview

The MVP (Minimum Viable Product) system is the core of the Otter project, providing enterprise reporting functionality with unified data management and Chrome MCP testing integration.

## Core MVP Components

### JavaScript Modules
- **`reports/js/unified-data-service.js`** - Main data service (MvpReportsDataService class)
- **`reports/js/unified-table-updater.js`** - Table update logic (MvpUnifiedTableUpdater class)
- **`reports/js/reports-data.js`** - Core data fetching and table update functions
- **`reports/js/reports-entry.js`** - Entry point for the reports system
- **`reports/js/date-range-picker.js`** - Date range selection functionality
- **`reports/js/reports-messaging.js`** - Simple messaging system
- **`reports/js/date-utils.js`** - Date utility functions
- **`reports/js/logging-utils.js`** - Logging utilities

### API Endpoints
- **`reports/reports_api.php`** - External API endpoint
- **`reports/reports_api_internal.php`** - Internal API endpoint
- **`reports/mvp_reports_api.php`** - MVP-specific external API
- **`reports/mvp_reports_api_internal.php`** - MVP-specific internal API

### Bundle Management
- **Entry Point:** `reports/js/reports-entry.js`
- **Output:** `reports/dist/reports.bundle.js`
- **Build Commands:** `npm run build:mvp` or `npm run dev:mvp`

## MVP Development Rules

### 1. File Naming
- **NO "mvp-" prefix** - All MVP files have been migrated to standard names
- **Use descriptive names** - Files should clearly indicate their purpose
- **Follow ES6 module conventions** - Use proper import/export syntax

### 2. JavaScript Development
- **ES6+ modules only** - No legacy JavaScript patterns
- **Proper imports/exports** - Use named exports and imports
- **Async/await** - Use modern async patterns, not callbacks
- **Error handling** - Always include proper error handling

### 3. API Development
- **RESTful patterns** - Follow REST conventions
- **Proper HTTP status codes** - Use appropriate status codes
- **JSON responses** - All API responses should be JSON
- **Error responses** - Include meaningful error messages

### 4. Testing Requirements
- **Chrome MCP integration** - Use browser automation for frontend tests
- **TestBase class** - Extend the enhanced TestBase class
- **Performance testing** - Include performance metrics
- **User journey testing** - Test complete workflows

### 5. Enterprise Integration
- **Unified configuration** - Use UnifiedEnterpriseConfig class
- **Enterprise-specific data** - Handle CSU, CCC, DEMO differences
- **Start date handling** - Use enterprise start_date for data filtering
- **Organization management** - Handle multiple organizations per enterprise

## MVP Testing Patterns

### Test Structure
```php
// Initialize enterprise
TestBase::initEnterprise('csu');

// Initialize Chrome MCP
TestBase::initChromeMCP('http://localhost:8000');

// Run tests
$this->runMvpTest('Test Name', function() {
    // Test implementation
});
```

### Chrome MCP Integration
```php
// Navigate to page
TestBase::navigateToPage($url, 'Description');

// Take screenshot
TestBase::takeScreenshot('test_name', 'Description');

// Click element
TestBase::clickElement('#button-id', 'Click button');

// Evaluate JavaScript
$result = TestBase::evaluateScript('window.someFunction()', 'Get result');
```

## Common MVP Patterns

### Data Service Pattern
```javascript
class MvpReportsDataService {
    async fetchAllData(start, end, enrollmentMode, cohortMode = false) {
        // Implementation
    }
    
    async updateAllTables(start, end, enrollmentMode, cohortMode = false) {
        // Implementation
    }
}
```

### Table Updater Pattern
```javascript
class MvpUnifiedTableUpdater {
    updateTable(tableName, data) {
        // Implementation
    }
    
    handleEnrollmentModeChange(newMode) {
        // Implementation
    }
}
```

### Date Range Picker Pattern
```javascript
// Initialize with enterprise start date
if (window.ENTERPRISE_START_DATE) {
    startInput.value = window.ENTERPRISE_START_DATE;
    endInput.value = getTodayMMDDYY();
}

// Handle preset selection
switch (preset) {
    case 'all':
        startDate = window.ENTERPRISE_START_DATE;
        endDate = getTodayMMDDYY();
        break;
}
```

## MVP Troubleshooting

### Common Issues
1. **Bundle not loading** - Run `npm run build:mvp`
2. **Date picker not working** - Check `date-range-picker.js` import
3. **Apply button not working** - Verify `window.handleApplyClick` function
4. **Enterprise data not loading** - Check enterprise configuration
5. **Chrome MCP not available** - Restart Cursor and verify MCP config

### Debug Commands
```bash
# Check bundle exists
ls reports/dist/reports.bundle.js

# Validate MVP system
php tests/mvp/mvp_reports_validation_test.php

# Test frontend integration
php tests/chrome-mcp/mvp_frontend_integration_test.php

# Performance testing
php tests/performance/mvp_bundle_performance_test.php
```

## MVP Best Practices

1. **Always test changes** - Run relevant tests after modifications
2. **Update documentation** - Keep changelog and documentation current
3. **Follow naming conventions** - Use established patterns
4. **Handle errors gracefully** - Include proper error handling
5. **Use Chrome MCP** - Leverage browser automation for testing
6. **Maintain enterprise compatibility** - Ensure all enterprises work
7. **Performance monitoring** - Include performance metrics in tests
8. **User experience focus** - Test complete user journeys
