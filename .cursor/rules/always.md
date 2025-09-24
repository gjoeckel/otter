---
alwaysApply: true
---

# Otter Project - Core Context & Rules

**Before you begin, you MUST review and adhere to this context.**

## 1. Key Documentation (Review First!)

Your top priority is to understand the project's structure and standards. Use the `@` command to read these files:

- **Project Overview:** `@cursor-context.md`
- **MVP System:** `@changelog-mvp.md`
- **Project Rules:** `@project-rules.md`
- **Best Practices:** `@best-practices.md`
- **Reports Architecture:** `@reports-architecture.md`
- **Chrome MCP Integration:** `@BROWSERTOOLS_MCP_INTEGRATION.md`

## 2. Core Technical Rules

- **Backend:** PHP 8.x with unified database management
- **Frontend:** ES6+ modules with ESBuild bundling (`npm run build:mvp`)
- **Testing:** Chrome MCP integration with TestBase class
- **Enterprise Configs:** JSON-based configs in `config/*.config`
- **Git Workflow:** Feature branches with roll-up commits
- **Push Permission:** Use "push to github" token for remote pushes

## 3. MVP System Architecture

- **Core Files:** `reports/js/unified-data-service.js`, `reports/js/unified-table-updater.js`
- **Entry Point:** `reports/js/reports-entry.js`
- **Bundle Output:** `reports/dist/reports.bundle.js`
- **API Endpoints:** `reports/reports_api.php`, `reports/mvp_reports_api*.php`
- **No "mvp-" Prefix:** All MVP files migrated to standard names

## 4. Development Workflow

- **Start Development:** Always use `.\mvp-local.ps1`
- **Build Bundle:** `npm run build:mvp` or `npm run dev:mvp`
- **Test System:** `php tests/run_comprehensive_tests.php`
- **Chrome MCP Tests:** `php tests/chrome-mcp/run_chrome_mcp_tests.php`

## 5. Enterprise Configuration

- **Supported:** CSU, CCC, DEMO enterprises
- **Config Files:** `config/csu.config`, `config/ccc.config`, `config/demo.config`
- **Key Elements:** start_date, organizations, cache_ttl, enterprise_code

## 6. Testing Framework

- **TestBase Class:** Enhanced with Chrome MCP methods
- **Test Categories:** Unit, Integration, Chrome MCP, Performance, E2E, MVP
- **Browser Automation:** Screenshot capture, element interaction, performance monitoring

## 7. File Structure Conventions

- **Reports:** `reports/` (main application)
- **Tests:** `tests/` with organized subdirectories
- **Configs:** `config/` for enterprise configurations
- **Scripts:** `scripts/` for PowerShell automation
- **Chrome MCP:** `browsertools-mcp/` for browser automation

## 8. Critical Memories

- **MVP Migration Complete:** All "mvp-" prefixes removed
- **Chrome MCP Integration:** Testing framework with browser automation
- **Enterprise Configs:** CSU, CCC, DEMO configurations available
- **Git Workflow:** Feature branches, roll-up commits
- **Testing Structure:** Comprehensive test suite with Chrome MCP integration

## 9. Common Issues & Solutions

- **Bundle Not Found:** Run `npm run build:mvp`
- **Session Issues:** Check enterprise config and admin authentication
- **Date Range Picker Broken:** Verify `date-range-picker.js` is imported
- **Apply Button Not Working:** Check `window.handleApplyClick` function
- **Chrome MCP Not Available:** Restart Cursor and verify MCP configuration

## 10. Quick Reference Commands

```bash
# Development
.\mvp-local.ps1                    # Start MVP environment
npm run build:mvp                  # Build JavaScript bundle

# Testing
php tests/run_comprehensive_tests.php           # All tests
php tests/chrome-mcp/run_chrome_mcp_tests.php   # Chrome MCP tests
php tests/mvp/mvp_reports_validation_test.php   # MVP validation

# Git
git add .                          # Stage changes
git commit -m "Descriptive message" # Commit with message
# Use "push to github" token for remote push
```

**Remember:** This project has a sophisticated MVP architecture with Chrome MCP testing integration. Always review the context files and follow established patterns for consistent, reliable development.
