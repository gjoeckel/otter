# MCP Testing Patterns - Reports Page

## Overview
This document contains step-by-step testing patterns for the Reports page using MCP Chrome DevTools integration. These patterns are derived from actual testing sessions and can be reused for automated testing.

**Note**: This document uses OTTER shorthand notation as defined in `OTTER-shorthand.md`:
- **PR** = Preset Ranges, **PM** = Past Month, **A** = All
- **ADR** = Active Date Range, **EDR** = Edit Date Range
- **T** = Tables, **S** = Systemwide, **O** = Organizations, **G** = Groups
- **R** = Registrations, **E** = Enrollments, **C** = Certificates

## Pattern 1: Basic Reports Page Access and Login

### Objective
Test basic access to the Reports page and login functionality.

### Steps
1. **Start Chrome with debugging enabled**
   ```bash
   start chrome --remote-debugging-port=9222 --user-data-dir="C:\temp\chrome-debug"
   ```

2. **Create new page**
   - Tool: `mcp_chrome-devtools_new_page`
   - URL: `http://localhost:8000/reports/index.php`

3. **Take initial snapshot**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Purpose: Verify page loaded correctly

4. **Navigate to login page**
   - Tool: `mcp_chrome-devtools_navigate_page`
   - URL: `http://localhost:8000/login.php`

5. **Take login page snapshot**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Purpose: Verify login form is present

6. **Fill password field**
   - Tool: `mcp_chrome-devtools_fill`
   - UID: `[password_field_uid]`
   - Value: `8888` (demo admin password)

7. **Click login button**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[login_button_uid]`

8. **Verify successful login**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Should show "DEMO Admin" interface

9. **Navigate to Reports page**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[reports_link_uid]`

### Expected Results
- Login page loads correctly
- Password field accepts input
- Login button works
- Successfully redirected to admin interface
- Reports page accessible

---

## Pattern 2: Date Range Selection Testing (PR)

### Objective
Test PR functionality on the Reports page.

### Steps
1. **Take initial reports page snapshot**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Purpose: Verify page loaded with PR controls

2. **Select A (All) date range**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[A_radio_button_uid]`

3. **Verify ADR display**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Should show "08-06-22 to 09-26-25" or similar

4. **Select PM (Past Month) date range**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[PM_radio_button_uid]`

5. **Verify ADR update**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Should show updated ADR like "08-01-25 to 08-31-25"

6. **Click Apply button**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[apply_button_uid]`

7. **Wait for data update**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Purpose: Verify data has been filtered

### Expected Results
- Date range radio buttons work correctly
- Date range display updates appropriately
- Apply button triggers data refresh
- Data values change based on selected range

---

## Pattern 3: Data Tables Visibility Testing (T)

### Objective
Test that T are present and can be expanded to show data.

### Steps
1. **Scroll down to find T**
   - Tool: `mcp_chrome-devtools_evaluate_script`
   - Function: `() => { window.scrollTo(0, 1500); return "Scrolled down"; }`

2. **Take snapshot to verify T are present**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Should see "S Data", "O Data", "G Data" sections

3. **Click "Show table filter and data rows" for O Data**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[O_show_button_uid]`

4. **Verify T expansion**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Should see expanded T with organization names and data

5. **Scroll through T data**
   - Tool: `mcp_chrome-devtools_evaluate_script`
   - Function: `() => { window.scrollTo(0, 2000); return "Scrolled down more"; }`

6. **Take final snapshot**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Purpose: Verify all data is visible

### Expected Results
- Data tables are present and collapsed by default
- Show/hide buttons work correctly
- Table data displays properly when expanded
- Organization names show with correct formatting (e.g., "Demo" suffix)

---

## Pattern 4: Console and Network Monitoring

### Objective
Monitor console messages and network requests during Reports page testing.

### Steps
1. **Check console messages**
   - Tool: `mcp_chrome-devtools_list_console_messages`
   - Purpose: Identify any JavaScript errors or warnings

2. **Monitor network requests**
   - Tool: `mcp_chrome-devtools_list_network_requests`
   - Parameters: `pageSize: 50`
   - Purpose: Verify all resources load correctly

3. **Check for failed requests**
   - Tool: `mcp_chrome-devtools_list_network_requests`
   - Parameters: `pageIdx: 1, pageSize: 50`
   - Purpose: Look for any failed HTTP requests

4. **Verify enterprise API call**
   - Tool: `mcp_chrome-devtools_get_network_request`
   - URL: `../lib/api/enterprise_api.php?ent=demo`
   - Purpose: Ensure enterprise configuration loads correctly

### Expected Results
- No JavaScript errors in console
- All network requests return 200 status
- Enterprise API call succeeds
- CSS and JS files load correctly

---

## Pattern 5: Data Validation Testing

### Objective
Validate that the displayed data matches expected values and formatting.

### Steps
1. **Expand Organizations Data table**
   - Tool: `mcp_chrome-devtools_click`
   - UID: `[organizations_show_button_uid]`

2. **Take snapshot of expanded table**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Purpose: Capture organization names and data

3. **Verify organization name formatting**
   - Expected: Names should end with " Demo" (e.g., "Allan Hancock College Demo")
   - Not expected: Generic "Demo Organization" names

4. **Check data consistency**
   - Verify registrations, enrollments, and certificates columns have numeric values
   - Verify totals match expected ranges

5. **Test table filtering (if available)**
   - Tool: `mcp_chrome-devtools_fill`
   - UID: `[filter_input_uid]`
   - Value: `"Bakersfield"`
   - Purpose: Test search functionality

6. **Verify filtered results**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Only organizations containing "Bakersfield" should be visible

### Expected Results
- Organization names display with proper " Demo" suffix
- Data values are numeric and reasonable
- Filtering works correctly
- No generic "Demo Organization" names appear

---

## Pattern 6: Performance and Error Handling

### Objective
Test page performance and error handling scenarios.

### Steps
1. **Measure page load time**
   - Tool: `mcp_chrome-devtools_performance_start_trace`
   - Parameters: `reload: true, autoStop: true`

2. **Wait for trace completion**
   - Tool: `mcp_chrome-devtools_performance_stop_trace`

3. **Test with invalid date range**
   - Tool: `mcp_chrome-devtools_fill`
   - UID: `[start_date_uid]`
   - Value: `"invalid-date"`

4. **Verify error handling**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Should show appropriate error message or validation

5. **Test page refresh**
   - Tool: `mcp_chrome-devtools_evaluate_script`
   - Function: `() => { window.location.reload(); return "Page reloaded"; }`

6. **Verify page recovers correctly**
   - Tool: `mcp_chrome-devtools_take_snapshot`
   - Expected: Page should reload and function normally

### Expected Results
- Page loads within acceptable time limits
- Invalid inputs are handled gracefully
- Page refresh works correctly
- No JavaScript errors occur

---

## Common UID Patterns

### Login Page Elements
- Password field: Usually `[number]_3` (e.g., `24_3`, `25_3`)
- Login button: Usually `[number]_4` (e.g., `24_4`, `25_4`)

### Reports Page Elements
- Date range radios: Usually `[number]_13` for "All", `[number]_12` for "Past Month"
- Apply button: Usually `[number]_20`
- Show table buttons: Usually `[number]_7`, `[number]_28`, `[number]_34`

### Table Elements
- Organization names: Usually `[number]_44`, `[number]_48`, etc.
- Data values: Usually follow pattern `[number]_45`, `[number]_46`, `[number]_47`

## Notes
- UID values change with each page load/snapshot
- Always take fresh snapshots before interacting with elements
- Use `mcp_chrome-devtools_list_pages` to manage multiple tabs
- Console messages provide valuable debugging information
- Network monitoring helps identify loading issues

## Troubleshooting
- If elements not found: Take fresh snapshot and verify UIDs
- If page not loading: Check network requests for failed resources
- If data not updating: Verify date range selection and Apply button click
- If login fails: Check password value and button UID
