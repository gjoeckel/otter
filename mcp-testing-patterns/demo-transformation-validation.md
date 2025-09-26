# MCP Testing Pattern - Demo Transformation Validation

## Overview
This is a comprehensive testing sequence specifically for validating demo transformation functionality on the Reports page. This pattern was successfully executed during the demo transformation fix validation to ensure organization names display correctly with " Demo" suffix.

**Note**: This document uses OTTER shorthand notation as defined in `OTTER-shorthand.md`:
- **OTRS** = Otter Test and Regeneration Sequence
- **PR** = Preset Ranges, **PM** = Past Month, **A** = All
- **ADR** = Active Date Range, **EDR** = Edit Date Range
- **T** = Tables, **S** = Systemwide, **O** = Organizations, **G** = Groups
- **R** = Registrations, **E** = Enrollments, **C** = Certificates

## Pattern: Demo Transformation Validation

### Objective
Validate that demo transformation functionality works correctly on the Reports page, specifically ensuring organization names display with proper " Demo" suffix instead of generic "Demo Organization" names.

### Prerequisites
- Local PHP server running on `localhost:8000`
- Chrome with debugging enabled on port 9222
- Demo enterprise configuration active
- Demo transformation service updated (DemoTransformationService)
- Cache files cleared and regenerated

### Steps

#### Phase 1: Cache Management
1. **Delete legacy cache files**
   ```bash
   rm -f cache/ccc/certificates.json cache/ccc/enrollments.json cache/ccc/registrations.json
   rm -f cache/csu/certificates.json cache/csu/enrollments.json cache/csu/registrations.json
   rm -f cache/demo/certificates.json cache/demo/enrollments.json cache/demo/registrations.json
   ```

2. **Verify cache cleanup**
   ```bash
   find cache -name "*.json" -type f
   ```
   - Expected: Only source files (`all-registrants-data.json`, `all-submissions-data.json`)

3. **Regenerate cache**
   ```bash
   php reports/reports_api_internal.php
   ```

4. **Verify cache regeneration**
   ```bash
   find cache -name "*.json" -type f
   ```
   - Expected: Source files regenerated, no derived files created

#### Phase 2: Browser Testing Setup
1. **Start Chrome with debugging**
   ```bash
   start chrome --remote-debugging-port=9222 --user-data-dir="C:\temp\chrome-debug"
   ```

2. **Create new page**
   - Tool: `mcp_chrome-devtools_new_page`
   - URL: `http://localhost:8000/reports/index.php`

3. **Navigate to login**
   - Tool: `mcp_chrome-devtools_navigate_page`
   - URL: `http://localhost:8000/login.php`

4. **Login as demo admin**
   - Tool: `mcp_chrome-devtools_fill`
   - UID: `[password_field_uid]`
   - Value: `8888`
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[login_button_uid]`

5. **Navigate to Reports**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[reports_link_uid]`

#### Phase 3: Date Range Testing (PR)
1. **Test A (All) date range**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[A_radio_uid]`
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: ADR shows full range (e.g., "08-06-22 to 09-26-25")

2. **Test PM (Past Month) date range**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[PM_radio_uid]`
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[apply_button_uid]`
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: ADR updates and data values change

#### Phase 4: Data Validation (T)
1. **Scroll to T (Tables)**
   - Tool: `mcp_chrome-devtools_evaluate_script`
   - Function: `() => { window.scrollTo(0, 1500); return "Scrolled down"; }`

2. **Expand O (Organizations) T**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[O_show_button_uid]`

3. **Validate organization names**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Names like "Allan Hancock College Demo", "Bakersfield College Demo"
   - Not expected: Generic "Demo Organization" names

4. **Verify data consistency (R, E, C)**
   - Check that R, E, C show numeric values
   - Verify totals are reasonable (e.g., 2593 R, 2498 E, 1248 C)

#### Phase 5: Console and Network Monitoring
1. **Check console messages**
   - Tool: `mcp_chrome-devtools_list_console_messages`
   - Expected: No JavaScript errors, successful initialization messages

2. **Monitor network requests**
   - Tool: `mcp_chrome-devtools_list_network_requests`
   - Expected: All requests return 200 status, enterprise API call succeeds

3. **Verify enterprise configuration**
   - Tool: `mcp_chrome-devtools_get_network_request`
   - URL: `../lib/api/enterprise_api.php?ent=demo`
   - Expected: Successful response with demo configuration

### Expected Results

#### Cache Management
- ✅ Legacy derived cache files deleted
- ✅ Only source files remain after cleanup
- ✅ Cache regeneration successful
- ✅ No derived files created (on-demand processing working)

#### Browser Testing
- ✅ Login successful with demo admin password
- ✅ Reports page loads correctly
- ✅ Date range selection works
- ✅ Data updates based on date range

#### Data Validation
- ✅ Organization names show with " Demo" suffix
- ✅ Specific organization names preserved (e.g., "Bakersfield College Demo")
- ✅ No generic "Demo Organization" names
- ✅ Data values are numeric and reasonable
- ✅ 220+ organizations displayed correctly

#### System Health
- ✅ No JavaScript errors in console
- ✅ All network requests successful
- ✅ Enterprise API responds correctly
- ✅ Page performance acceptable

### Troubleshooting

#### Cache Issues
- If cache files not deleted: Check file permissions
- If regeneration fails: Check PHP errors, verify API keys
- If derived files created: Verify DRY refactoring is complete

#### Browser Issues
- If Chrome not connecting: Restart Chrome with debugging flags
- If login fails: Verify password and check enterprise configuration
- If page not loading: Check local server status and network requests

#### Data Issues
- If organization names wrong: Verify demo transformation service is updated
- If data not updating: Check date range selection and Apply button
- If tables not visible: Scroll down and click show/hide buttons

### Success Criteria
- All cache operations complete successfully
- Browser testing shows correct organization names
- Data validation passes all checks
- No errors in console or network requests
- System demonstrates proper demo transformation functionality

## Pattern Usage
This pattern should be run after:
- Demo transformation service updates
- Changes to DemoTransformationService.php
- Cache regeneration for demo enterprise
- Any changes affecting demo organization name display
- DRY refactoring that impacts demo transformation logic

## Time Estimate
- Cache management: 2-3 minutes
- Browser testing: 5-7 minutes
- Data validation: 3-5 minutes
- **Total: 10-15 minutes**
