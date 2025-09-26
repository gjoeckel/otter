# Admin to Home Migration Guide

## Overview
Transform the "Admin" functionality into a "Home" page system with updated navigation, paths, and styling.

## MCP-Driven Discovery Process
This migration guide was enhanced through comprehensive MCP-driven codebase analysis using:
- **Codebase Search**: Semantic search for admin-related functionality
- **Grep Analysis**: Pattern matching for specific strings (admin.css, admin_authenticated, back-btn, etc.)
- **Dependency Validation**: Complete CSS and JavaScript dependency mapping
- **File Structure Analysis**: Comprehensive file discovery across all directories

**Key Discoveries:**
- Additional test files with admin references
- .cursor rules files containing session variable references
- Legacy documentation files with admin/test.php references
- CSS asset path variations (assets/css/admin.css)
- Comprehensive session variable usage across 12+ files

## Migration Tasks

### Phase 1: Directory and File Rename
```bash
# Rename admin directory to home
mv admin/ home/
```

### Phase 2: Path Updates (admin/ → home/)

#### Core Application Files
- **`reports/index.php`**
  - Line 167: `../admin/index.php?auth=1` → `../home/index.php?auth=1`
  
- **`videos/index.php`**
  - Line 45: `../admin/index.php?auth=1` → `../home/index.php?auth=1`
  
- **`settings/index.php`**
  - Line 335: `../admin/index.php?auth=1` → `../home/index.php?auth=1`
  
- **`login.php`**
  - Line 83: `admin/index.php?login=1` → `home/index.php?login=1`
  
- **`lib/unified_enterprise_config.php`**
  - Line 485: `admin/index.php?auth=1` → `home/index.php?auth=1`

#### Test Files
- **`tests/chrome-mcp/mvp_frontend_integration_test.php`**
  - Line 50: `/admin/index.php` → `/home/index.php`
  
- **`tests/root_tests/test_admin_flow.php`**
  - Multiple admin/ references → home/
  
- **`mvp_skeleton/login.php`**
  - Line 20: `admin/index.php` → `home/index.php`

#### Additional Test Files (Previously Missing)
- **`tests/integration/target_folder_url_test.php`**
  - Line 44: `admin/index.php?auth=1` → `home/index.php?auth=1`
  
- **`tests/css_path_validation_test.php`**
  - Line 143: `admin/index.php` → `home/index.php`
  
- **`tests/test_login_flow.php`**
  - Lines 67, 79, 158, 161, 164, 167: Multiple admin references → home/
  
- **`tests/es6_module_validation_test.php`**
  - Line 94: `admin/index.php` → `home/index.php`
  
- **`tests/integration/environment_migration_test.php`**
  - Line 26: `admin/index.php` → `home/index.php`

#### Additional Test Files (Comprehensive Coverage)
- **`tests/run_enterprise_tests.php`**
  - Lines 194, 197: `admin_authenticated` → `home_authenticated`
  
- **`tests/root_tests/test_admin_flow.php`**
  - Lines 92, 97, 164, 204, 213, 230: Multiple `admin_authenticated` references → `home_authenticated`
  
- **`tests/test_login_flow.php`**
  - Lines 113, 133: `admin_authenticated` → `home_authenticated`
  
- **`tests/integration/login_test.php`**
  - Lines 47, 50: `admin_authenticated` → `home_authenticated`
  
- **`tests/path_info_fix_validation_test.php`**
  - Line 93: `admin.css` → `home.css`
  
- **`tests/root_tests/login_tests.php`**
  - Line 103: `admin.css` → `home.css`
  
- **`tests/integration/settings_dashboard_workflow_test.php`**
  - Lines 177, 233, 270: `admin.css` → `home.css`

#### Additional Files Discovered via MCP Codebase Review
- **`unused-legacy-files.md`**
  - Lines 33, 57, 107, 137, 183: Multiple `admin/test.php` references → `home/test.php`
  
- **`lib/unified_enterprise_config.php`**
  - Line 485: `admin/index.php?auth=1` → `home/index.php?auth=1`
  
- **`tests/integration/target_folder_url_test.php`**
  - Line 19: `css/admin.css` → `css/home.css`
  
- **`tests/test_login_flow.php`**
  - Lines 67, 79, 158, 161, 164, 167: Multiple `assets/css/admin.css` references → `assets/css/home.css`

### Phase 3: Session Variable Updates (admin_authenticated → home_authenticated)

#### Core Application Files
- **`home/index.php`** (formerly admin/index.php)
  - Line 32: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`
  - Line 74: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`
  
- **`videos/index.php`**
  - Line 7: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`
  
- **`reports/reports_api.php`**
  - Line 45: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`
  - Lines 47-48: Update error logging references
  
- **`login.php`**
  - Line 74: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`

#### Test Files
- **`tests/test_integration.php`**
  - Line 10: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`
  
- **`tests/run_comprehensive_tests.php`**
  - Line 197: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`
  - Line 201: Update authentication check logic
  
- **`mvp_skeleton/login.php`**
  - Line 17: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`

#### Additional Session Variable Files (Previously Missing)
- **`lib/session.php`**
  - Lines 38, 43, 48: `admin_authenticated` → `home_authenticated`
  
- **`mvp_skeleton/lib/reports_data_service.php`**
  - Line 51: `admin_authenticated` → `home_authenticated`
  
- **`enterprise_system_refactoring.md`**
  - Lines 31, 115, 276, 331, 342, 377, 1098, 1142: Multiple `admin_authenticated` references → `home_authenticated`
  
- **`enterprise_system_analysis.md`**
  - Line 241: `admin_authenticated` → `home_authenticated`
  
- **`reports-architecture.md`**
  - Lines 517, 525: `admin_authenticated` → `home_authenticated`

#### Additional Session Variable Files (MCP Discovered)
- **`.cursor/rules/enterprise.md`**
  - Line 247: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`
  
- **`.cursor/rules/chrome-mcp.md`**
  - Line 26: `$_SESSION['admin_authenticated']` → `$_SESSION['home_authenticated']`

### Phase 4: CSS Class Updates (back-btn → home-btn)

#### CSS Files
- **`css/buttons.css`**
  - Line 79: `.back-btn` → `.home-btn`
  
- **`css/settings.css`**
  - Lines 385-395: `#back-btn` → `#home-btn`
  
- **`css/settings2.css`**
  - Lines 385-395: `#back-btn` → `#home-btn`

#### HTML Files
- **`reports/index.php`**
  - Line 167: `id="back-btn" class="btn back-btn"` → `id="home-btn" class="btn home-btn"`
  
- **`videos/index.php`**
  - Line 45: `id="back-btn" class="link"` → `id="home-btn" class="link"`
  
- **`settings/index.php`**
  - Line 335: `id="back-btn" class="link"` → `id="home-btn" class="link"`

### Phase 5: CSS File Rename and References

#### CSS File Rename
- **`css/admin.css`** → **`css/home.css`**
  - Rename file and update all references

#### CSS Reference Updates
- **`home/index.php`** (formerly admin/index.php)
  - Line 163: `../css/admin.css` → `../css/home.css`

#### CSS Class Updates (back-btn → home-btn)
- **`css/buttons.css`**
  - Line 79: `.back-btn` → `.home-btn`
  
- **`css/settings.css`**
  - Lines 385-395: `#back-btn` → `#home-btn`
  
- **`css/settings2.css`**
  - Lines 385-395: `#back-btn` → `#home-btn`

#### CSS Class Updates (admin-home → home-home)
- **`css/settings.css`**
  - Line 169: `.container.admin-home` → `.container.home-home`
  
- **`css/settings2.css`**
  - Line 169: `.container.admin-home` → `.container.home-home`
  
- **`css/print.css`**
  - Lines 288, 331: `.admin-home` → `.home-home`

#### CSS Class Updates (admin-btn-row → home-btn-row)
- **`css/admin.css`** (will become `css/home.css`)
  - Lines 229, 238-239: `.admin-btn-row` → `.home-btn-row`
  
- **`admin/home.css`** (will become `home/home.css`)
  - Lines 696, 705-706: `.admin-btn-row` → `.home-btn-row`
  
- **`admin/index.php`** (will become `home/index.php`)
  - Line 195: `class="admin-btn-row"` → `class="home-btn-row"`

### Phase 6: Header Layout Changes

#### CSS Updates
- **`reports/css/reports-main.css`**
  - Update nav positioning to move home button to left side
  - Maintain same whitespace between button and page edge

### Phase 7: Text Updates
- **`reports/index.php`**
  - Line 167: `Admin` → `Home`
  
- **`videos/index.php`**
  - Line 45: `Admin` → `Home`
  
- **`settings/index.php`**
  - Line 335: `Admin` → `Home`

### Phase 8: Configuration Files Updates

#### URL Pattern Configuration
- **`config/dashboards.json`**
  - Lines 8, 16, 24, 48: `admin/index.php` → `home/index.php`
  - Lines 8, 24: `admin/index.php?auth=1` → `home/index.php?auth=1`

#### MCP Configuration
- **`browsertools-mcp/config.json`**
  - Line 38: `admin/index.php` → `home/index.php`

### Phase 9: Documentation Updates

#### Main Documentation
- **`README.md`**
  - Line 33: `admin/index.php` → `home/index.php`
  - Update references from "Admin Interface" to "Home Interface"
  
- **`reports-architecture.md`**
  - Update authentication patterns documentation
  
- **`project-rules.md`**
  - Update CSS path references

#### System Documentation
- **`enterprise_system_refactoring.md`**
  - Lines 280, 380, 1144: `admin/index.php` → `home/index.php`
  
- **`enterprise_system_analysis.md`**
  - Line 23: `admin/index.php` → `home/index.php`

#### Testing Documentation
- **`browsertools-mcp/TESTING_GUIDE.md`**
  - Line 117: `admin/index.php` → `home/index.php`

## Implementation Commands

### MCP Tool Usage Pattern
```javascript
// For each file modification:
1. read_file(target_file)
2. search_replace(file_path, old_string, new_string)
3. read_lints(paths) // Check for errors
4. commit changes
```

### Batch Operations
```bash
# Directory rename
git mv admin/ home/

# CSS file rename
git mv css/admin.css css/home.css

# Commit each phase separately
git add [files]
git commit -m "Phase X: [Description]"
```

### Phase-Specific Commands
```bash
# Phase 1: Directory rename
git mv admin/ home/

# Phase 2: Path updates (use search_replace for each file)
# Phase 3: Session variable updates (use search_replace for each file)
# Phase 4: CSS class updates (use search_replace for each file)

# Phase 5: CSS file rename and references
git mv css/admin.css css/home.css
# Update home/index.php CSS reference

# Phase 6: Header layout changes
# Update reports/css/reports-main.css

# Phase 7: Text updates
# Update button text in HTML files

# Phase 8: Configuration files
# Update config/dashboards.json and browsertools-mcp/config.json

# Phase 9: Documentation updates
# Update all documentation files
```

## Validation Steps

### 1. Path Validation
- Verify all `admin/` references updated to `home/`
- Test navigation links work correctly
- Confirm authentication redirects function

### 2. Session Validation
- Test login flow with new session variables
- Verify authentication checks work
- Confirm session persistence

### 3. UI Validation
- Verify home button appears on left side of header
- Test button styling and hover states
- Confirm consistent spacing

### 4. Functionality Testing
- Test Reports page navigation
- Test Videos page navigation  
- Test Settings page navigation
- Verify all authentication flows

## Files Summary
- **Total Files to Modify:** ~60+ files (updated from MCP codebase review)
- **Total Changes:** ~120+ individual updates (updated from comprehensive analysis)
- **Critical Files:** reports/index.php, login.php, home/index.php
- **CSS Files:** 6 files (admin.css→home.css, buttons.css, settings.css, settings2.css, print.css, admin/home.css→home/home.css)
- **Test Files:** 18+ files (comprehensive test coverage including all session variables and CSS references)
- **Configuration Files:** 2 files (dashboards.json, browsertools-mcp/config.json)
- **Documentation Files:** 8 files (README.md, system docs, testing guides, .cursor rules)
- **Library Files:** 3 files (session.php, unified_database.php, enterprise_resolver.php)
- **Session Variable Files:** 12+ files (all admin_authenticated references including .cursor rules)
- **Legacy Files:** 1 file (unused-legacy-files.md with admin/test.php references)

## Rollback Plan
If issues arise:
1. `git mv home/ admin/`
2. Revert session variable changes
3. Revert CSS class changes
4. Revert path updates
5. Test functionality

## Notes
- Keep `admin_passwords` in config files unchanged (internal configuration)
- Maintain backward compatibility during transition
- Update MCP testing patterns to reflect new structure
- Consider updating changelog.md with migration details

## Critical Dependencies
- **CSS File Reference**: `home/index.php` must reference `../css/home.css` (not `../css/admin.css`)
- **Test Coverage**: All test files must be updated to prevent test failures
- **Configuration Files**: URL patterns in `config/dashboards.json` affect routing
- **MCP Configuration**: `browsertools-mcp/config.json` affects automated testing

## Migration Validation Checklist
- [ ] All 60+ files identified and updated (MCP-validated comprehensive list)
- [ ] Directory rename completed (`admin/` → `home/`)
- [ ] CSS file rename completed (`css/admin.css` → `css/home.css`)
- [ ] All path references updated (`admin/` → `home/`)
- [ ] All session variables updated (`admin_authenticated` → `home_authenticated`)
- [ ] All CSS classes updated (`back-btn` → `home-btn`, `admin-home` → `home-home`, `admin-btn-row` → `home-btn-row`)
- [ ] All configuration files updated
- [ ] All documentation files updated (including .cursor rules)
- [ ] All test files updated (18+ files)
- [ ] All library files updated (session.php, etc.)
- [ ] All CSS references updated (including print.css)
- [ ] Legacy file references updated (unused-legacy-files.md)
- [ ] Navigation functionality tested
- [ ] Authentication flows tested
- [ ] CSS styling verified
- [ ] All admin_passwords references remain unchanged (internal config)

