# Unused and Legacy Files Analysis

## Analysis Framework

### Unused Files Criteria
- Files not referenced in any other files (no imports, includes, requires)
- Files not linked in HTML/configuration files
- Files not called by any scripts or processes
- Test files that are no longer relevant
- Backup files and temporary files
- Old documentation that's been superseded

### Legacy Files Criteria
- Files using deprecated functions or libraries
- Files with outdated coding patterns
- Files with TODO comments indicating planned replacement
- Files with version numbers in names suggesting old versions
- Files using old API endpoints or deprecated methods
- Files with outdated dependencies

## Analysis Results

### Unused Files

#### PHP Files
**High Confidence Unused:**
- `test_demo_settings_direct.php` - Temporary test file for demo settings validation
- `test_demo_settings.php` - Temporary test file for demo settings validation  
- `test_demo_transformation.php` - Temporary test file for demo transformation validation
- `mvp_skeleton/` directory - Complete skeleton implementation that appears to be superseded by main system

**Medium Confidence Unused:**
- `admin/test.php` - Test file in admin directory
- `videos/index.php` - Videos directory with single file, unclear if referenced

#### JavaScript Files
**High Confidence Unused:**
- `test_data_loading.js` - Manual testing script for data loading (browser console tool)
- `reports_diagnostic.js` - Diagnostic script for reports debugging (browser console tool)
- `debug_console.js` - Empty debug console file
- `test_cohort_logic.js` - Empty test file for cohort logic

**Medium Confidence Unused:**
- `mvp_skeleton/reports/js/simple-messaging.js` - Part of skeleton implementation

#### CSS Files
**All CSS files appear to be in use** - Referenced in HTML files and PHP templates

#### Configuration Files
**Backup Files (Safe to Delete):**
- `config/passwords.json.backup.20250925_180922`
- `config/demo.config.backup.20250925_180922`
- `config/passwords.json.backup.2025-09-25-23-05-20`
- `config/passwords.json.backup.enterprise-fix.2025-09-25-23-06-47`
- `backup_original_apis/reports_api_internal.php.backup`
- `backup_original_apis/reports_api.php.backup`
- `admin/index.php.backup`

#### Documentation Files
**Potentially Unused:**
- `agent-eval.md` - Agent evaluation document
- `chrome_mcp_testing_guide.md` - Testing guide (may be superseded)
- `WINDOWS_11_CHROME_PATH_FIX.md` - Windows-specific fix documentation
- `BROWSERTOOLS_MCP_INTEGRATION.md` - Integration documentation

#### Test Files
**Temporary Test Files (Safe to Delete):**
- All files matching pattern `test_*.php` in root directory
- All files matching pattern `debug_*.php` in root directory
- Various HTML test files in `tests/` directory that appear to be temporary

### Legacy Files

#### Deprecated Functions Usage
**Files with Legacy Code Patterns:**
- `reports/js/reports-data.js` - Contains multiple legacy function references and comments about legacy support
- `lib/unified_database.php` - Contains legacy support comments for organizations array
- `reports/reports_api.php` - Contains legacy endpoint comments and old registration logic
- `lib/reports_service.php` - Contains placeholder functions for cohort-based enrollments

#### Outdated Patterns
**Files with TODO/FIXME Comments:**
- `logging-implementation-summary.md` - Contains TODO items for deprecated function warnings
- `reports/js/reports-data.js` - Contains legacy function removal comments
- `lib/enterprise_cache_manager.php` - Contains old session file cleanup logic

**Files with Version Numbers in Names:**
- `config/passwords.json.backup.20250925_180922` - Backup with timestamp
- `config/demo.config.backup.20250925_180922` - Backup with timestamp
- Multiple backup files with timestamps indicating old versions

#### Old Dependencies
**Files Using Deprecated Methods:**
- `reports/js/reports-data.js` - References to legacy variables and functions
- `lib/unified_database.php` - Legacy support for organizations array structure
- `reports/reports_api.php` - Legacy endpoint handling for groups data table operations

## Risk Assessment

### High Risk (Do not delete without careful review)
- `mvp_skeleton/` directory - Complete skeleton implementation, may be referenced in documentation or used for reference
- `videos/index.php` - May be referenced in admin interface or documentation
- `chrome-extension/` directory - Browser extension files, may be actively used
- `browsertools-mcp/` directory - MCP testing tools, actively referenced in project rules

### Medium Risk (Review before deletion)
- `admin/test.php` - Test file in admin directory, may be used for admin testing
- `agent-eval.md` - Agent evaluation document, may be used for AI agent training
- `chrome_mcp_testing_guide.md` - Testing guide, may be referenced by developers
- `WINDOWS_11_CHROME_PATH_FIX.md` - Windows-specific documentation, may be needed for troubleshooting

### Low Risk (Safe to delete)
- All backup files (`.backup.*` pattern)
- Temporary test files (`test_*.php`, `debug_*.php` in root)
- Empty JavaScript files (`debug_console.js`, `test_cohort_logic.js`)
- Browser console diagnostic scripts (`test_data_loading.js`, `reports_diagnostic.js`)
- Documentation files that appear superseded

## Implementation Phases

### Phase 1: Low Risk Deletions (15 files)
**Status:** Ready for execution
**Files to Delete:**
- **Backup Files (7):** All `.backup.*` files
- **Temporary Test Files (3):** `test_*.php` in root directory
- **Empty/Diagnostic JS Files (4):** `debug_console.js`, `reports_diagnostic.js`, `test_cohort_logic.js`, `test_data_loading.js`
- **Debug HTML Files (1):** `debug_bundle_path.html`

**Testing Strategy:**
- **MCP-Driven Tests:** Full application flow validation using Chrome MCP tools
- **Manual Spot Checks:** 2-3 random functionality tests
- **Rollback Plan:** Git branch allows immediate rollback if issues found

### Phase 2: Medium Risk Deletions (4 files)
**Status:** Pending Phase 1 validation
**Files to Review:**
- `admin/test.php` - Test file in admin directory
- `agent-eval.md` - Agent evaluation document
- `chrome_mcp_testing_guide.md` - Testing guide
- `WINDOWS_11_CHROME_PATH_FIX.md` - Windows-specific documentation

**Testing Strategy:**
- **Extended MCP Tests:** Comprehensive functionality testing
- **Manual Testing:** More thorough manual validation
- **Integration Tests:** Run full test suite

### Phase 3: High Risk Deletions (4 items)
**Status:** Pending Phase 2 validation
**Items to Evaluate:**
- `mvp_skeleton/` directory - Complete skeleton implementation
- `videos/index.php` - Videos directory file
- `chrome-extension/` directory - Browser extension files
- `browsertools-mcp/` directory - MCP testing tools

**Testing Strategy:**
- **Complete System Validation:** Full MCP testing suite
- **Comprehensive Manual Testing:** Full user workflow testing
- **Performance Tests:** Verify no performance degradation

## Testing Infrastructure

### MCP-Driven Testing Tools Available
- **Chrome MCP Tools:** Navigation, snapshots, JavaScript evaluation, console monitoring
- **Server Status:** PHP server running on localhost:8000 with health checks
- **Built-in Tests:** `php run_tests.php` for comprehensive validation
- **Health Checks:** All systems operational (4/4 checks passing)

### Testing Workflow After Each Phase
1. **Immediate MCP Validation:** Automated testing using Chrome MCP tools
2. **Manual Spot Checks:** Human validation of key functionality
3. **Issue Detection:** Console error monitoring and functionality verification
4. **Rollback Capability:** Git branch allows immediate rollback if issues found

## Recommendations

### Immediate Actions (Phase 1 - Low Risk)
1. **Delete backup files** - All `.backup.*` files can be safely removed
2. **Clean temporary test files** - Remove `test_*.php` and `debug_*.php` from root directory
3. **Remove empty files** - Delete `debug_console.js` and `test_cohort_logic.js`
4. **Clean diagnostic scripts** - Remove browser console testing scripts

### Medium-term Actions (Phase 2 - Medium Risk)
1. **Review admin test file** - Check if `admin/test.php` is referenced anywhere
2. **Consolidate documentation** - Review and merge or remove duplicate documentation files
3. **Clean up test directory** - Review HTML test files in `tests/` directory

### Long-term Actions (Phase 3 - High Risk)
1. **Evaluate skeleton implementation** - Determine if `mvp_skeleton/` is still needed
2. **Review browser extension** - Assess if `chrome-extension/` is actively used
3. **Audit MCP tools** - Ensure `browsertools-mcp/` is still relevant

### Code Quality Improvements
1. **Remove legacy code comments** - Clean up TODO/FIXME comments in production code
2. **Consolidate legacy support** - Remove or update legacy function references
3. **Update documentation** - Ensure all documentation reflects current implementation
