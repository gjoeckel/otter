---
alwaysApply: false
description: "AI-optimized context for maximum agent effectiveness"
---

# AI Agent Optimization Rules

## CRITICAL: File Path Accuracy

### Reports System Files (VERIFIED)
```
reports/
├── index.php                    # MAIN reports page (use this)
├── mvp-reports-index.php        # Legacy MVP page (avoid)
├── reports_api.php              # External API (15 lines)
├── reports_api_internal.php     # Internal API (27 lines)
├── mvp_reports_api.php          # MVP external API
├── mvp_reports_api_internal.php # MVP internal API
└── js/
    ├── reports-entry.js         # Bundle entry point
    ├── reports-data.js          # Core data functions
    ├── unified-data-service.js  # MvpReportsDataService class
    ├── unified-table-updater.js # MvpUnifiedTableUpdater class
    ├── date-range-picker.js     # Date picker functionality
    ├── reports-messaging.js     # Simple messaging
    ├── date-utils.js            # Date utilities
    ├── logging-utils.js         # Logging utilities
    └── archive/                 # Original files (archived)
```

### Test System Files (VERIFIED)
```
tests/
├── test_base.php                # Enhanced with Chrome MCP
├── run_comprehensive_tests.php  # Master test runner
├── test_all_enterprises.php     # Enterprise validation
├── chrome-mcp/
│   ├── mvp_frontend_integration_test.php
│   └── run_chrome_mcp_tests.php
├── performance/
│   └── mvp_bundle_performance_test.php
├── e2e/
│   └── mvp_user_journey_test.php
├── mvp/
│   └── mvp_reports_validation_test.php
├── unit/
│   └── config_test.php
├── integration/
│   ├── reports_tables_validation_test.php
│   └── login_test.php
├── enterprise/
│   └── csu_test.php
└── archive/                     # Obsolete files
```

## CRITICAL: Command Accuracy

### Development Commands (VERIFIED)
```bash
# Primary development command
.\mvp-local.ps1

# Alternative commands
.\scripts\start-mvp-testing.ps1
.\mvp-local-function.ps1
.\mvp-local.cmd

# JavaScript bundle commands
npm run build:mvp               # Production build
npm run dev:mvp                 # Development with watch
npm run build:reports           # Legacy build
npm run dev:reports             # Legacy dev

# Bundle output location
reports/dist/reports.bundle.js
```

### Test Commands (VERIFIED)
```bash
# Master test runners
php tests/run_comprehensive_tests.php
php tests/chrome-mcp/run_chrome_mcp_tests.php

# Specific test categories
php tests/mvp/mvp_reports_validation_test.php
php tests/chrome-mcp/mvp_frontend_integration_test.php
php tests/performance/mvp_bundle_performance_test.php
php tests/e2e/mvp_user_journey_test.php
php tests/test_all_enterprises.php

# Enterprise-specific tests
php tests/unit/config_test.php
php tests/integration/reports_tables_validation_test.php
php tests/enterprise/csu_test.php
```

## CRITICAL: Enterprise Configuration

### Supported Enterprises (VERIFIED)
- **CSU:** California State University (config/csu.config)
- **CCC:** California Community Colleges (config/ccc.config)
- **DEMO:** Demo environment (config/demo.config)

### Key Configuration Elements
- **start_date:** Enterprise start date for data filtering
- **organizations:** List of organizations with admin credentials
- **cache_ttl:** Cache time-to-live settings
- **enterprise_code:** Unique identifier

### Enterprise Initialization Pattern
```php
TestBase::initEnterprise('csu');  // or 'ccc', 'demo'
$config = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate();
```

## CRITICAL: Database Architecture

### JSON File Storage (NO MySQL)
- **passwords.json:** Organization authentication data
- **cache/:** Enterprise-specific cache files
- **config/*.config:** Enterprise configuration files
- **Google Sheets API v4:** Data retrieval

### Key Classes
- **UnifiedDatabase:** JSON file management
- **UnifiedEnterpriseConfig:** Enterprise configuration
- **EnterpriseCacheManager:** Cache management
- **EnterpriseDataService:** Google Sheets integration

## CRITICAL: Chrome MCP Integration

### TestBase Class Methods (VERIFIED)
```php
// Initialization
TestBase::initChromeMCP($base_url);

// Browser automation
TestBase::navigateToPage($url, $description);
TestBase::clickElement($selector, $description);
TestBase::fillForm($form_data, $description);
TestBase::evaluateScript($script, $description);

// Monitoring
TestBase::takeScreenshot($name, $description);
TestBase::getConsoleErrors();
TestBase::getNetworkRequests();
TestBase::startPerformanceTrace($reload, $auto_stop);
TestBase::stopPerformanceTrace();

// Utilities
TestBase::waitForText($text, $timeout);
TestBase::takePageSnapshot($description);
```

## CRITICAL: Common Issues & Solutions

### Bundle Issues
- **Problem:** `dist/reports.bundle.js not found`
- **Solution:** Run `npm run build:mvp`

### Session Issues
- **Problem:** Authentication failures
- **Solution:** Check enterprise config and admin credentials

### Date Range Picker Issues
- **Problem:** Date fields not populating
- **Solution:** Verify `date-range-picker.js` is imported in bundle

### Apply Button Issues
- **Problem:** Apply button not working
- **Solution:** Check `window.handleApplyClick` function exists

### Chrome MCP Issues
- **Problem:** Chrome MCP tools not available
- **Solution:** Restart Cursor and verify MCP configuration

## CRITICAL: Git Workflow

### Branch Strategy
- **Current Branch:** `mvp` (for MVP development)
- **Feature Branches:** Create new branches for features
- **Commit Strategy:** Roll-up commits with descriptive messages
- **Push Permission:** Use "push to github" token

### Commit Pattern
```bash
git add .
git commit -m "Feature: Descriptive summary of changes"
# Use "push to github" command to trigger remote push
```

## CRITICAL: Performance Metrics

### Bundle Size Targets
- **Current Size:** ~22.9KB
- **Target:** < 50KB
- **Monitoring:** Track size changes

### Performance Targets
- **API Response Time:** < 5000ms
- **Table Update Time:** < 3000ms
- **UI Interaction Time:** < 500ms
- **Memory Usage:** < 100MB

## CRITICAL: AI Agent Best Practices

### Before Starting Work
1. **Read Context:** Always read `@cursor-context.md` first
2. **Verify Files:** Check that referenced files actually exist
3. **Test Environment:** Ensure local development environment is ready
4. **Run Tests:** Execute relevant tests to understand current state

### During Development
1. **Follow Patterns:** Use established code patterns and conventions
2. **Test Changes:** Run relevant tests after modifications
3. **Update Documentation:** Keep changelog and documentation current
4. **Use MVP Commands:** Always use established PowerShell scripts

### After Completing Work
1. **Run Tests:** Execute comprehensive test suites
2. **Update Changelog:** Document changes in `changelog-mvp.md`
3. **Commit Changes:** Use descriptive commit messages
4. **Verify Integration:** Ensure all components work together

## CRITICAL: Memory Management

### Key Memories to Maintain
- **MVP Migration Complete:** All "mvp-" prefixes removed, files migrated
- **Chrome MCP Integration:** Testing framework with browser automation
- **Enterprise Configs:** CSU, CCC, DEMO configurations available
- **Git Workflow:** Feature branches, roll-up commits, "push to github" token
- **Testing Structure:** Comprehensive test suite with Chrome MCP integration
- **Database:** JSON file storage (no MySQL)

### Context Files Priority
1. **`@cursor-context.md`** - Primary context file
2. **`@changelog-mvp.md`** - Recent changes
3. **`@project-rules.md`** - Project standards
4. **`@best-practices.md`** - Development guidelines
5. **`@reports-architecture.md`** - System architecture
6. **`@BROWSERTOOLS_MCP_INTEGRATION.md`** - Chrome MCP setup
