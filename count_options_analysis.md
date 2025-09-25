# Count Options Code Analysis

## Overview
This document analyzes all code related to count options functionality in the reports system. The count options system allows users to choose between different counting modes for registrations and enrollments.

## Files with Count Options Logic

### 1. **reports/index.php**
**Count Options Features:**
- HTML radio buttons for systemwide data display options
- HTML radio buttons for systemwide enrollments display options
- Toggle buttons for expanding/collapsing table controls
- Status message containers for displaying current mode

**Key Code Sections:**
```html
<!-- Systemwide Registration Count Options -->
<fieldset id="systemwide-data-display" class="fieldset-box fieldset-stack">
  <legend>Systemwide Registrations Count Options</legend>
  <div class="systemwide-data-display-options">
    <label class="systemwide-data-display-label">
      <input type="radio" name="systemwide-data-display" value="by-date" class="systemwide-data-display-radio" checked> count registrations by submission date
    </label>
    <label class="systemwide-data-display-label">
      <input type="radio" name="systemwide-data-display" value="by-cohort" class="systemwide-data-display-radio"> count registrations by cohort(s)
    </label>
    <select id="cohort-select" class="cohort-select" aria-label="Select cohort" disabled>
      <option value="">Select cohort</option>
    </select>
  </div>
  <div class="message-container">
    <div id="systemwide-data-display-message" class="date-range-status" aria-live="polite"></div>
  </div>
</fieldset>

<!-- Systemwide Enrollments Count Options -->
<fieldset id="systemwide-enrollments-display" class="fieldset-box fieldset-stack">
  <legend>Systemwide Enrollments Count Options</legend>
  <div class="systemwide-enrollments-display-options">
    <label class="systemwide-enrollments-display-label">
      <input type="radio" name="systemwide-enrollments-display" value="by-tou" class="systemwide-enrollments-display-radio" checked> count enrollments by TOU completion date
    </label>
    <label class="systemwide-enrollments-display-label">
      <input type="radio" name="systemwide-enrollments-display" value="by-registration" class="systemwide-enrollments-display-radio"> count enrollments by registration date
    </label>
  </div>
</fieldset>
```

### 2. **reports/js/reports-data.js**
**Count Options Features:**
- Mode detection from radio buttons
- Auto-switching logic when TOU count is 0
- Widget wiring for radio button event listeners
- Cohort filtering and date range logic
- Count and link update functions

**Key Functions:**
- `getCurrentModes()` - Detects current radio button selections
- `checkEnrollmentCountsAndAutoSwitch()` - Auto-switches modes based on data (ACTIVE)
- `wireSystemwideWidgetRadios()` - Wires up radio button event listeners
- `wireSystemwideEnrollmentsWidgetRadios()` - Wires enrollment radio buttons
- `updateSystemwideCountAndLink()` - Updates counts and report links
- `updateSystemwideEnrollmentsCountAndLink()` - Updates enrollment counts
- `filterCohortDataByDateRange()` - Filters data by cohort keys
- `getCohortKeysFromRange()` - Computes cohort keys from date range
- `populateCohortSelectFromData()` - Populates cohort dropdown from data (ACTIVE)
- `setupCohortModeDisableForAllRange()` - Disables cohort mode for "ALL" ranges (ACTIVE)
- `formatCohortLabel()` - Formats cohort keys to display labels (ACTIVE)

### 3. **reports/js/unified-data-service.js**
**Count Options Features:**
- Mode parameter handling in API calls
- Cohort mode support
- Current mode tracking

**Key Code:**
```javascript
async updateAllTables(start, end, enrollmentMode = null, cohortMode = false, options = {}) {
  // Handles enrollmentMode and cohortMode parameters
}

async fetchAllData(start, end, enrollmentMode, cohortMode = false) {
  // Passes modes to API calls with default cohortMode = false
}
```

### 4. **reports/js/unified-table-updater.js**
**Count Options Features:**
- Table updates based on mode parameters
- Lock registrations option
- Mode change handling

**Key Functions:**
- `handleEnrollmentModeChange()` - Handles enrollment mode changes
- `updateAllTables()` - Updates tables with mode-specific data

### 5. **reports/js/data-display-options.js**
**Count Options Features:**
- Display mode handling for organizations and groups
- Mode-specific table updates

### 6. **reports/js/data-display-utility.js**
**Count Options Features:**
- Status message display system
- Mode-specific messaging

### 7. **reports/js/date-range-picker.js**
**Count Options Features:**
- Date range change handling
- Cohort mode disable for "ALL" ranges

### 8. **Recent Data Structure Fixes (v1.2.0)**
**Count Options Features:**
- Fixed legacy function data structure mismatch
- Updated functions to use `__lastSummaryData.systemwide.registrations_count`
- Updated functions to use `__lastSummaryData.systemwide.enrollments_count`
- Resolved systemwide table showing 0 values issue

## Count Options Modes

### Registration Count Modes:
- **by-date**: Count registrations by submission date
- **by-cohort**: Count registrations by cohort (month-year combinations)

### Enrollment Count Modes:
- **by-tou**: Count enrollments by TOU completion date
- **by-registration**: Count enrollments by registration date

## Complex Logic Features

### 1. **Auto-Switching Logic**
- Checks TOU completion count
- If 0, automatically switches to registration date mode
- Disables TOU mode and shows status message

### 2. **Cohort Filtering**
- Converts date ranges to cohort keys (MM-YY format)
- Filters data based on cohort combinations
- Handles "ALL" date ranges specially

### 3. **Widget Wiring**
- Event listeners for radio button changes
- Status message updates
- Report link updates
- Data refresh triggers

### 4. **Mode Persistence**
- Tracks current modes across page interactions
- Maintains mode state during data updates
- Handles mode changes gracefully

### 5. **Current Status Messages**
- "Showing data for all registrations submitted in date range - count by cohorts disabled"
- "Showing data for all TOU completions in the date range"
- "Showing data for all registrations submitted for cohort(s) in the date range"

## Simplification Strategy

### What to Remove:
1. All radio button HTML and CSS
2. **Cohort select dropdown** (`<select id="cohort-select">`)
3. Mode detection logic
4. Auto-switching functionality (`checkEnrollmentCountsAndAutoSwitch()`)
5. Cohort filtering and computation (`filterCohortDataByDateRange()`, `getCohortKeysFromRange()`)
6. **Cohort dropdown population** (`populateCohortSelectFromData()`)
7. **Cohort formatting** (`formatCohortLabel()`)
8. **Cohort mode disable logic** (`setupCohortModeDisableForAllRange()`)
9. Widget wiring and event listeners
10. Dynamic status messages
11. Mode change handling

### What to Keep:
1. Basic table structure
2. Data fetching (with default modes)
3. Simple table updates
4. Report links (with default parameters)

### Default Mode Values on Page Load:
- **Registration Mode**: "by-date" (default selection)
- **Enrollment Mode**: "by-tou" (default selection)  
- **Cohort Mode**: false (default, but will use updated counting logic when enabled)
- **Status Messages**: Static, simplified messages

## Current State Before Changes

**IMPORTANT:** This analysis reflects the CURRENT state of the codebase as of v1.2.0. The following functions and features are currently ACTIVE and will need to be removed/refactored:

### Currently Active Functions:
- `checkEnrollmentCountsAndAutoSwitch()` - Called in `fetchAndUpdateAllTablesInternal()`
- `populateCohortSelectFromData()` - Called in `fetchAndUpdateAllTablesInternal()`
- `setupCohortModeDisableForAllRange()` - Called in `wireSystemwideWidgetRadios()`
- `formatCohortLabel()` - Used by `populateCohortSelectFromData()`

### Currently Active HTML:
- Cohort select dropdown (`<select id="cohort-select">`)
- Cohort-related status messages
- Cohort mode disable functionality

### Recently Fixed Issues:
- Data structure mismatch resolved (v1.2.0)
- Systemwide table now shows correct values (7235 registrations, 3281 enrollments)
- Legacy functions updated to use correct `__lastSummaryData.systemwide.*` structure
