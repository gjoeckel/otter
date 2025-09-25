# Cursor Context Guide for Otter Project 🦦

## Project Overview

**Otter** is a comprehensive enterprise reporting system with MVP (Minimum Viable Product) architecture, supporting multiple enterprises (CSU, CCC, DEMO) with unified configuration management and Chrome MCP testing integration.

## Essential Context Files (Review First!)

Before starting any work, the AI agent MUST review these files to understand the project:

- **Project Setup & Commands:** `@README.md`
- **MVP System Documentation:** `@changelog-mvp.md`
- **Project Rules & Standards:** `@project-rules.md`
- **Best Practices:** `@best-practices.md`
- **Reports Architecture:** `@reports-architecture.md`
- **Chrome MCP Integration:** `@BROWSERTOOLS_MCP_INTEGRATION.md`

## Core Technical Stack

### Backend
- **Language:** PHP 8.x
- **Database:** JSON file storage (no MySQL - uses `passwords.json` and cache files)
- **Session Management:** PHP sessions with enterprise-specific handling
- **API Architecture:** RESTful APIs with internal/external separation
- **Configuration:** JSON-based enterprise configs (`config/*.config`)

### Frontend
- **JavaScript:** ES6+ modules with ESBuild bundling
- **Build System:** npm scripts with `build:mvp` and `dev:mvp` commands
- **Testing:** Chrome MCP integration for browser automation
- **UI Framework:** Custom CSS with enterprise-specific styling

### Development Environment
- **Local Server:** PHP built-in server (port 8000)
- **PowerShell Scripts:** Windows-specific automation (`*.ps1`)
- **Git Workflow:** Feature branches with roll-up commits
- **Testing Framework:** Custom TestBase class with Chrome MCP integration

## Project Structure & Conventions

### Directory Organization
```
otter/
├── config/                 # Enterprise configurations
├── lib/                    # Core PHP libraries
├── reports/                # Reports system (main application)
│   ├── js/                # JavaScript modules
│   ├── dist/              # Built bundles
│   └── *.php              # Reports pages and APIs
├── tests/                  # Comprehensive test suite
│   ├── chrome-mcp/        # Chrome MCP integration tests
│   ├── performance/       # Performance testing
│   ├── e2e/              # End-to-end tests
│   └── mvp/              # MVP-specific tests
├── admin/                  # Admin interface
├── scripts/               # PowerShell automation
└── browsertools-mcp/      # Chrome MCP server
```

### File Naming Conventions
- **MVP Files:** No "mvp-" prefix (migrated to standard names)
- **Test Files:** `*_test.php` suffix
- **Config Files:** `*.config` extension
- **PowerShell Scripts:** `*.ps1` extension
- **JavaScript Modules:** ES6 modules with `.js` extension

## Development Workflow

### Starting Development
1. **Use MVP Commands:** Always use `mvp-local.ps1` for local development
2. **Check Enterprise Config:** Verify correct enterprise config is loaded
3. **Build Bundle:** Run `npm run build:mvp` or `npm run dev:mvp`
4. **Start Server:** Use PowerShell scripts for consistent environment

### Code Standards
- **PHP:** Follow PSR standards, use type hints, proper error handling
- **JavaScript:** ES6+ modules, proper imports/exports, async/await
- **CSS:** Enterprise-specific styling, avoid inline styles
- **Testing:** Use TestBase class, Chrome MCP for frontend tests

### Git Workflow
- **Branch Strategy:** Feature branches (e.g., `mvp`, `investigate-broken-functionality`)
- **Commit Messages:** Roll-up commits with descriptive summaries
- **Push Permission:** Use "push to github" token for remote pushes
- **Changelog:** Update `changelog-mvp.md` for MVP changes

## Enterprise Configuration

### Supported Enterprises
- **CSU:** California State University system
- **CCC:** California Community Colleges
- **DEMO:** Demo/testing environment

### Configuration Files
- `config/csu.config` - CSU-specific settings
- `config/ccc.config` - CCC-specific settings  
- `config/demo.config` - Demo environment settings

### Key Configuration Elements
- **start_date:** Enterprise start date for data filtering
- **organizations:** List of organizations with admin credentials
- **cache_ttl:** Cache time-to-live settings
- **enterprise_code:** Unique identifier for each enterprise

## MVP System Architecture

### Core Components
- **Unified Data Service:** `reports/js/unified-data-service.js`
- **Table Updater:** `reports/js/unified-table-updater.js`
- **Reports Entry:** `reports/js/reports-entry.js`
- **Date Range Picker:** `reports/js/date-range-picker.js`
- **Messaging System:** `reports/js/reports-messaging.js`

### API Endpoints
- **External API:** `reports/reports_api.php`
- **Internal API:** `reports/reports_api_internal.php`
- **MVP APIs:** `reports/mvp_reports_api*.php`

### Bundle Management
- **Entry Point:** `reports/js/reports-entry.js`
- **Output:** `reports/dist/reports.bundle.js`
- **Build Commands:** `npm run build:mvp` or `npm run dev:mvp`

## Testing Framework

### Test Categories
- **Unit Tests:** `tests/unit/` - Individual component testing
- **Integration Tests:** `tests/integration/` - Component interaction testing
- **Chrome MCP Tests:** `tests/chrome-mcp/` - Browser automation testing
- **Performance Tests:** `tests/performance/` - Performance metrics
- **E2E Tests:** `tests/e2e/` - End-to-end user journey testing
- **MVP Tests:** `tests/mvp/` - MVP-specific validation

### Test Execution
```bash
# Run all tests
php tests/run_comprehensive_tests.php

# Run Chrome MCP tests
php tests/chrome-mcp/run_chrome_mcp_tests.php

# Run MVP validation
php tests/mvp/mvp_reports_validation_test.php

# Run specific enterprise tests
php tests/test_all_enterprises.php
```

### Chrome MCP Integration
- **TestBase Class:** Enhanced with Chrome MCP methods
- **Browser Automation:** Screenshot capture, element interaction
- **Performance Monitoring:** Bundle size, response times, memory usage
- **Error Detection:** Console errors, network monitoring

## Common Tasks & Solutions

### Starting Local Development
```powershell
# Use MVP local environment
.\mvp-local.ps1

# Or use individual commands
.\scripts\start-mvp-testing.ps1
```

### Building JavaScript Bundle
```bash
# Production build
npm run build:mvp

# Development build with watch
npm run dev:mvp
```

### Testing MVP System
```bash
# Validate MVP components
php tests/mvp/mvp_reports_validation_test.php

# Test frontend integration
php tests/chrome-mcp/mvp_frontend_integration_test.php

# Performance testing
php tests/performance/mvp_bundle_performance_test.php
```

### Enterprise Configuration
```php
// Initialize enterprise
TestBase::initEnterprise('csu');

// Get enterprise config
$config = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate();
```

## Troubleshooting Guide

### Common Issues
1. **Bundle Not Found:** Run `npm run build:mvp`
2. **Session Issues:** Check enterprise config and admin authentication
3. **Date Range Picker Broken:** Verify `date-range-picker.js` is imported
4. **Apply Button Not Working:** Check `window.handleApplyClick` function
5. **Chrome MCP Not Available:** Restart Cursor and verify MCP configuration

### Debug Commands
```bash
# Check bundle exists
ls reports/dist/reports.bundle.js

# Test enterprise config
php tests/test_all_enterprises.php

# Validate MVP system
php tests/mvp/mvp_reports_validation_test.php
```

## Memory & Context Management

### Key Memories to Maintain
- **MVP Migration Complete:** All "mvp-" prefixes removed, files migrated
- **Chrome MCP Integration:** Testing framework with browser automation
- **Enterprise Configs:** CSU, CCC, DEMO configurations available
- **Git Workflow:** Feature branches, roll-up commits, "push to github" token
- **Testing Structure:** Comprehensive test suite with Chrome MCP integration

### Context Files to Reference
- **Recent Changes:** `@changelog-mvp.md`
- **Test Results:** `@tests/README.md`
- **Chrome MCP Setup:** `@BROWSERTOOLS_MCP_INTEGRATION.md`
- **Project Rules:** `@project-rules.md`

## Best Practices for AI Agent

### Before Starting Work
1. **Review Context:** Always read the essential context files listed above
2. **Check Current State:** Understand what's been done recently
3. **Verify Environment:** Ensure local development environment is ready
4. **Test Current System:** Run relevant tests to understand current state

### During Development
1. **Follow Conventions:** Use established naming and structure patterns
2. **Test Changes:** Run relevant tests after making changes
3. **Update Documentation:** Keep changelog and documentation current
4. **Use MVP Commands:** Always use established PowerShell scripts

### After Completing Work
1. **Run Tests:** Execute relevant test suites
2. **Update Changelog:** Document changes in `changelog-mvp.md`
3. **Commit Changes:** Use descriptive commit messages
4. **Verify Integration:** Ensure all components work together

## Quick Reference Commands

```bash
# Development
.\mvp-local.ps1                    # Start MVP environment
npm run build:mvp                  # Build JavaScript bundle
npm run dev:mvp                    # Development build with watch

# Testing
php tests/run_comprehensive_tests.php           # All tests
php tests/chrome-mcp/run_chrome_mcp_tests.php   # Chrome MCP tests
php tests/mvp/mvp_reports_validation_test.php   # MVP validation

# Git
git add .                          # Stage changes
git commit -m "Descriptive message" # Commit with message
# Use "push to github" token for remote push
```

---

**Remember:** This project has a sophisticated MVP architecture with Chrome MCP testing integration. Always review the context files and follow established patterns for consistent, reliable development.
