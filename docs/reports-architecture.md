# Reports Architecture and Logic Guide

**Document Status**: Active and up-to-date.

This document provides a comprehensive overview of the architecture, data flow, and business logic for the reporting system.

## AI Agent Implementation Plan

### Phase 1: Foundation Analysis & Validation
- **Review cursor rules** to understand project scope, tools, and patterns
- **MANDATORY: Start local server** using `./mvp-local.sh` (REQUIRED before ANY testing)
- **MANDATORY: Authenticate first** - navigate to `http://localhost:8000/login.php` before accessing any other page
- **MANDATORY: Initialize Chrome MCP** for browser automation and testing
- **MANDATORY: Initialize Memory MCP** for maintaining context across sessions
- **MANDATORY: Initialize Filesystem MCP** for enhanced file operations
- **MANDATORY: Initialize Source Control** for local git operations
- **Analyze validated working state** from commit 83b4ba5 (2025-09-11)
- **Document current broken state** and identify specific failure points
- **Establish baseline** for comparison between working and broken implementations

### Phase 2: Core Component Restoration
- **MANDATORY: Use Chrome MCP** for all testing - take screenshots and validate UI functionality
- **MANDATORY: Use Memory MCP** to maintain implementation patterns across sessions
- **Restore systemwide widget functionality** with active radio buttons for registrations (by-date/by-cohort) and enrollments (by-tou/by-registration)
- **Implement working cohort selection dropdown** populated from actual data
- **Restore auto-switch logic** that automatically switches from TOU to registration mode when TOU count is 0
- **Fix data display** ensuring all three tables (systemwide, organizations, groups) update correctly
- **Verify enterprise compatibility** across CSU, CCC, and DEMO configurations

### Phase 3: Data Service Integration
- **MANDATORY: Use Chrome MCP** to monitor network requests and API responses
- **MANDATORY: Use Memory MCP** to maintain implementation patterns across sessions
- **Restore unified data service** with proper API integration using `unified-data-service.js`
- **Implement cohort mode support** with `&cohort_mode=true` parameter when needed (per memory 9125479)
- **Update method signatures** to `updateAllTables(start, end, enrollmentMode, cohortMode=false)` (per memory 9125477)
- **Fix table update coordination** ensuring all tables update together
- **Restore proper error handling** and user feedback mechanisms

### Phase 4: User Interface Restoration
- **MANDATORY: Use Chrome MCP** for UI interaction testing and screenshot validation
- **MANDATORY: Use Memory MCP** to maintain implementation patterns across sessions
- **Restore functional date range picker** with presets and validation
- **Implement dynamic report links** that update based on current selections
- **Fix apply button functionality** ensuring it triggers proper updates
- **Restore proper loading states** and user feedback
- **Test enterprise-specific date ranges** using UnifiedEnterpriseConfig start dates

### Phase 5: Module Loading & Integration
- **MANDATORY: Use Filesystem MCP** to verify module file structure and dependencies
- **MANDATORY: Use Memory MCP** to maintain implementation patterns across sessions
- **Use direct ES6 module imports** (consistent with validated state - no bundle system)
- **Implement proper ES6 module imports** in the main page
- **Fix module dependencies** ensuring all required modules are loaded
- **Restore proper initialization sequence** for all components
- **Integrate with unified data service** patterns from established MVP architecture

### Phase 6: Testing & Validation
- **MANDATORY: Use Chrome MCP** for all testing to validate functionality
- **MANDATORY: Use Memory MCP** to maintain test patterns and context across sessions
- **MANDATORY: Use Source Control** for version management of test results
- **Test all user interactions** including radio button changes, date selection, and apply button
- **Validate data loading** across all three table sections
- **Test enterprise compatibility** ensuring CSU, CCC, and DEMO configurations work
- **Performance testing** using Chrome MCP performance monitoring

### Phase 7: Documentation & Cleanup
- **MANDATORY: Use Source Control** to commit changes with descriptive messages
- **MANDATORY: Use Memory MCP** to store successful implementation patterns
- **Update architecture documentation** with restored functionality
- **Document any changes** made during restoration process
- **Clean up any temporary code** or debugging artifacts
- **Ensure code follows project standards** for DRY, simple, and reliable patterns

### Implementation Guidelines
- **MANDATORY: Follow cursor rules** for development workflow and testing protocols
- **MANDATORY: Start local server** with `./mvp-local.sh` before ANY testing
- **MANDATORY: Authenticate first** - always navigate to login.php before accessing reports
- **MANDATORY: Use Chrome MCP tools** for browser automation and testing
- **MANDATORY: Use Memory MCP** to maintain context and patterns across sessions
- **MANDATORY: Use Filesystem MCP** for enhanced file operations
- **MANDATORY: Use Source Control** for local git operations and commit patterns
- **Maintain enterprise compatibility** across all configurations (CSU, CCC, DEMO)
- **Implement proper error handling** with user-friendly feedback
- **Follow established MVP patterns** from unified data service architecture
- **Test thoroughly** before considering any phase complete
- **Document changes** in the architecture documentation

## 1. Core Business Logic

The reporting system is designed to display registration and enrollment data, which can be viewed in two primary modes: "by date" and "by cohort". The logic is governed by the following rules:

-   **Registrations Radio is Authoritative**: The selection on the Registrations widget (`by-date` vs. `by-cohort`) determines the primary dataset for both the Registrations and Enrollments tables.
-   **Enrollments Radio is Secondary**: The selection on the Enrollments widget (`by-tou` vs. `by-registration`) only affects the Enrollments data and does not change the primary mode (date/cohort).
-   **"ALL" Date Range Safeguard**: When the "ALL" date range is selected, the system automatically forces the "by-date" mode and disables the "by-cohort" option to ensure performance and clarity.

### Core Datasets

The backend (`reports_api.php`) generates six distinct datasets to support the various display modes on the frontend:

-   `registrations_submissions`: Registrations filtered by submission date.
-   `registrations_cohort`: Registrations filtered by cohort/year.
-   `submissions_enrollments_tou`: Enrollments based on "by-date" registrations, counted by Term of Use completion date.
-   `submissions_enrollments_registrations`: Enrollments based on "by-date" registrations, counted by registration date.
-   `cohort_enrollments_tou`: Enrollments based on "by-cohort" registrations, counted by Term of Use completion date.
-   `cohort_enrollments_registrations`: Enrollments based on "by-cohort" registrations, counted by registration date.

## 2. System Architecture

The reporting system is composed of four key components that work together to fetch, manage, and display the data, enhanced with MCP ecosystem integration.

-   **`reports_api.php` (Backend)**: A PHP script that serves as the single API endpoint for all reporting data. It fetches raw data from Google Sheets, processes it, and returns a unified JSON object containing all six core datasets. Enhanced with enterprise configuration support.
-   **`unified-data-service.js` (Frontend Service)**: A JavaScript class (`MvpReportsDataService`) that acts as the central point for all data fetching on the frontend. It makes a single call to `reports_api.php` and manages the application state (e.g., current date range, selected modes, cohort mode). Enhanced with Memory MCP integration for context maintenance.
-   **`unified-table-updater.js` (Frontend UI)**: A JavaScript class (`MvpUnifiedTableUpdater`) that takes the data from the `MvpReportsDataService` and is responsible for rendering it into the various HTML tables on the reports page. Enhanced with Chrome MCP integration for testing.
-   **`reports-data.js` (Frontend Orchestrator)**: The main JavaScript file that orchestrates the entire process. It handles user interactions (like changing the date range or display mode), initializes the data service and table updater, and triggers data refreshes. Enhanced with enterprise configuration integration.

## 3. Data Flow

The data flows through the system in the following sequence, enhanced with MCP ecosystem integration:

1.  **User Interaction**: The process begins when a user changes the date range or selects a different display mode on the reports page.
2.  **Enterprise Configuration**: The system loads enterprise-specific settings (CSU, CCC, DEMO) using UnifiedEnterpriseConfig.
3.  **Orchestration (`reports-data.js`)**: The event listeners in `reports-data.js` capture the user's action. The `getCurrentModes()` function determines the currently selected display modes and cohort mode (per memory 9125474).
4.  **Data Fetching (`unified-data-service.js`)**: The `updateAllTables(start, end, enrollmentMode, cohortMode=false)` function calls the `MvpReportsDataService`, which constructs the appropriate URL and makes a single `fetch` request to `reports_api.php`. The request includes the date range, selected modes, and cohort mode as URL parameters (per memory 9125479).
5.  **Backend Processing (`reports_api.php`)**: The PHP script receives the request, fetches the raw data from Google Sheets (utilizing a cache for performance), and processes it to generate all six core datasets. It then returns a single JSON object containing these datasets.
6.  **UI Update (`unified-table-updater.js`)**: Once the data is returned to the frontend, the `MvpReportsDataService` passes it to the `MvpUnifiedTableUpdater`. This class then updates the HTML for the system-wide, organizations, and groups tables with the appropriate data based on the user's selections.
7.  **MCP Integration**: Throughout the process, Memory MCP maintains context, Chrome MCP monitors performance, Filesystem MCP manages files, and Source Control tracks changes.

## 4. Race Condition Handling

To prevent race conditions caused by rapid user input (e.g., a user typing quickly in the date range fields), the system employs a **debouncing** mechanism.

-   **How it Works**: When a user triggers a data refresh, the application does not send the API request immediately. Instead, it waits for a brief period (e.g., 200ms). If the user triggers another refresh within that window, the timer is reset. The API request is only sent after the user has paused their input for the specified duration.
-   **Implementation**: This is handled in `reports-data.js` and `unified-data-service.js` using `setTimeout` and `clearTimeout`.
-   **Benefit**: This is a simple and reliable solution that ensures only a single, final API request is sent, preventing unnecessary server load and avoiding potential conflicts from multiple, simultaneous data requests.

This architecture was implemented to refactor a previous version that made multiple, parallel API calls, leading to inefficiencies and code complexity. The current unified system is more performant, reliable, and easier to maintain.

## Validated (Working State - Commit 83b4ba5)

The validated working state from commit 83b4ba5 (2025-09-11) represents a fully functional Reports page with the following architecture:

### Core Components

#### 1. **reports/index.php** - Main Page Structure
```php
// Key features from validated state (commit 83b4ba5):
- Complete HTML structure with all three sections (systemwide, organizations, groups)
- Active systemwide widget with radio buttons for registrations (by-date/by-cohort) and enrollments (by-tou/by-registration)
- Cohort selection dropdown for both registrations and enrollments by-cohort modes
- Direct module loading system using ES6 imports
- No bundle system - direct ES6 module imports used
- Working fieldset elements with proper radio button groups
```

#### 2. **reports/js/reports-data.js** - Data Orchestration
```javascript
// Key functions from validated state (commit 83b4ba5):
- wireSystemwideWidgetRadios() - Active widget wiring for registrations
- wireSystemwideEnrollmentsWidgetRadios() - Active widget wiring for enrollments  
- populateCohortSelectFromData() - Populates cohort dropdown from actual data
- populateEnrollmentsCohortSelectFromData() - Populates enrollments cohort dropdown
- wireWidgetRadiosGeneric() - Generic function for widget radio button behavior
- updateSystemwideCountAndLink() - Updates counts and report links
- updateSystemwideEnrollmentsCountAndLink() - Updates enrollment counts and links
- resetWidgetsToDefaults() - Resets widgets when date range changes
- updateCountAndLinkGeneric() - Generic count and link update function
- Complete auto-switch logic and cohort mode disable for "ALL" ranges
```

#### 3. **reports/js/date-range-picker.js** - Date Range Management
```javascript
// Key functionality from validated state:
- Complete date range picker with preset options
- Apply button logic with validation
- Integration with fetchAndUpdateAllTables
- Reset functionality for widgets
- Report link updates
```

#### 4. **reports/reports_api.php** - Backend API
```php
// Key features from validated state:
- Single API endpoint for all data requests
- Support for enrollment_mode parameter
- Organization and groups data processing
- Proper error handling and JSON responses
- Cache management for performance
```

### Working Features in Validated State (Commit 83b4ba5)

1. **Systemwide Widget**: Fully functional radio buttons for registrations (by-date/by-cohort) and enrollments (by-tou/by-registration)
2. **Cohort Selection**: Working dropdown populated from actual data for both registrations and enrollments
3. **Auto-switch Logic**: Automatic switching from TOU to registration mode when TOU count is 0
4. **Data Display**: All three tables (systemwide, organizations, groups) updating correctly
5. **Date Range Picker**: Complete functionality with presets and validation
6. **Report Links**: Dynamic links updating based on current selections
7. **Widget Reset**: Automatic reset to defaults when date range changes
8. **Generic Functions**: DRY implementation with reusable widget functions
9. **Status Messages**: Real-time status updates for user feedback

## Current (Broken State - Current Branch)

The current state shows significant architectural changes that have broken the Reports page functionality. The widgets were disabled in commit 10ebee7 (2025-09-24) during the "MVP System Launch" when the system was simplified:

### Major Changes Made

#### 1. **reports/index.php** - Simplified Structure
```php
// Current state changes:
- Systemwide widget radio buttons are COMMENTED OUT (lines 242-269)
- Systemwide toggle button is COMMENTED OUT (line 276)
- Direct module loading (same as validated state - no bundle system)
- Missing active widget controls
```

#### 2. **reports/js/reports-data.js** - Disabled Functions
```javascript
// Current state - many functions are DISABLED:
// DISABLED: function wireSystemwideWidgetRadios() {
// DISABLED: function wireSystemwideEnrollmentsWidgetRadios() {
// DISABLED: function resetWidgetsToDefaults() {
// DISABLED: function updateSystemwideCountAndLink() {
// DISABLED: function updateSystemwideEnrollmentsCountAndLink() {

// Current approach uses hardcoded modes:
function getCurrentModes() {
  return { 
    registrationsMode: 'by-date', 
    enrollmentMode: 'by-tou', 
    cohortMode: false 
  };
}
```

#### 3. **New Architecture Components**
```javascript
// Current state introduces new unified system:
- ReportsDataService class for centralized data management
- UnifiedTableUpdater class for table updates
- Enhanced logging system
- Debouncing mechanisms
```

### What's Broken in Current State

1. **No User Controls**: Systemwide widget radio buttons are commented out, removing user interaction
2. **Hardcoded Modes**: No way for users to switch between by-date/by-cohort or by-tou/by-registration
3. **Missing Widget Logic**: All widget wiring functions are disabled
4. **No Cohort Selection**: Cohort dropdown functionality is removed
5. **Simplified UI**: Toggle buttons and interactive elements are commented out

## Optimized Implementation Plan (Approach A)

**Recommendation**: Restore validated functionality from commit 83b4ba5 into the current unified architecture, preserving both proven working patterns and modern architectural benefits.

### Implementation Strategy

**Phase 1: Foundation Restoration (Low Risk)**
- Uncomment HTML widgets in `reports/index.php` from validated state
- Restore basic widget structure without complex logic
- Test with Chrome MCP to ensure UI renders correctly

**Phase 2: Core Function Restoration (Medium Risk)**
- Uncomment and adapt widget functions from validated state
- Integrate with new `getCurrentModes()` function to detect user selections
- Update `ReportsDataService` to accept `cohortMode` parameter (per memory 9125477)

**Phase 3: Advanced Features (Medium Risk)**
- Restore cohort selection dropdown functionality
- Implement auto-switch logic using new unified service
- Add cohort mode disable for "ALL" date ranges

**Phase 4: Integration & Testing (High Value)**
- Comprehensive Chrome MCP testing across all enterprises
- Performance validation using new monitoring tools
- Memory MCP integration for pattern preservation

### Key Integration Points

#### 1. **Mode Detection Integration**
```javascript
// Update getCurrentModes() to read from actual UI (from validated state)
function getCurrentModes() {
  const regRadios = document.querySelectorAll('input[name="systemwide-data-display"]');
  const registrationsMode = Array.from(regRadios).find(r => r.checked)?.value || 'by-date';
  const cohortMode = registrationsMode === 'by-cohort';
  
  const enrollmentRadios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
  const enrollmentMode = Array.from(enrollmentRadios).find(r => r.checked)?.value || 'by-tou';
  
  return { registrationsMode, enrollmentMode, cohortMode };
}
```

#### 2. **Service Integration**
```javascript
// Update ReportsDataService to support cohort mode (per memory 9125477)
async updateAllTables(start, end, enrollmentMode, cohortMode = false) {
  this.currentRegistrationsCohortMode = cohortMode;
  return this.updateAllTablesInternal(start, end, enrollmentMode, cohortMode);
}
```

#### 3. **Restore User Interface Controls**
```php
// In reports/index.php - uncomment and restore:
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
</fieldset>

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

#### 4. **Restore Widget Logic Functions**
```javascript
// In reports/js/reports-data.js - restore these functions:
function wireSystemwideWidgetRadios() {
  wireWidgetRadiosGeneric('systemwide-data-display', 'cohort-select', 'systemwide', 'registrations', 'by-date', updateSystemwideCountAndLink);
}

function wireSystemwideEnrollmentsWidgetRadios() {
  // Restore the complete enrollment widget logic from validated state
}

function resetWidgetsToDefaults() {
  // Restore widget reset functionality
}

async function updateSystemwideCountAndLink() {
  await updateCountAndLinkGeneric('systemwide-data-display', setSystemwideRegistrationsCell, updateRegistrantsReportLink);
}

function updateSystemwideEnrollmentsCountAndLink() {
  // Restore enrollment count and link updates
}
```

#### 5. **Integrate with New Architecture** (Per Memory 9125474)
```javascript
// In reports/js/reports-data.js - implement cohort mode detection:
function getCurrentModes() {
  // Determine Registrations mode and pass cohort flag
  const regRadios = document.querySelectorAll('input[name="systemwide-data-display"]');
  const registrationsMode = Array.from(regRadios).find(r => r.checked)?.value || 'by-date';
  const cohortMode = registrationsMode === 'by-cohort';
  
  // Get enrollment mode
  const enrollmentRadios = document.querySelectorAll('input[name="systemwide-enrollments-display"]');
  const enrollmentMode = Array.from(enrollmentRadios).find(r => r.checked)?.value || 'by-tou';
  
  return { registrationsMode, enrollmentMode, cohortMode };
}

// Update fetchAndUpdateAllTablesInternal to pass cohort mode:
async function fetchAndUpdateAllTablesInternal(start, end, enrollmentMode) {
  // Determine Registrations mode and pass cohort flag
  const regRadios = document.querySelectorAll('input[name="systemwide-data-display"]');
  const registrationsMode = Array.from(regRadios).find(r => r.checked)?.value || 'by-date';
  const cohortMode = registrationsMode === 'by-cohort';
  
  // Single service call updates all tables
  await window.reportsDataService.updateAllTables(start, end, enrollmentMode, cohortMode);
}
```

#### 6. **Update Unified System Integration** (Per Memories 9125477-9125482)
```javascript
// In reports/js/unified-data-service.js - update method signatures:
class MvpReportsDataService {
  constructor() {
    this.currentRegistrationsCohortMode = false;
  }
  
  async updateAllTables(start, end, enrollmentMode, cohortMode = false) {
    this.currentRegistrationsCohortMode = cohortMode;
    return this.updateAllTablesInternal(start, end, enrollmentMode, cohortMode);
  }
  
  async updateAllTablesInternal(start, end, enrollmentMode, cohortMode = false) {
    this.currentRegistrationsCohortMode = cohortMode;
    const data = await this.fetchAllData(start, end, enrollmentMode, cohortMode);
    // Process data...
  }
  
  async fetchAllData(start, end, enrollmentMode, cohortMode = false) {
    // Append cohort_mode parameter when needed (per memory 9125479)
    const url = `reports_api.php?start=${start}&end=${end}&enrollment_mode=${enrollmentMode}&all_tables=1${cohortMode ? '&cohort_mode=true' : ''}`;
    // Make API call...
  }
}

// In reports/js/unified-table-updater.js - handle enrollment mode changes:
class MvpUnifiedTableUpdater {
  handleEnrollmentModeChange(newMode) {
    // No change needed other than ensuring ReportsDataService remembers currentRegistrationsCohortMode
    // so updateAllTables continues to pass the existing cohortMode when only enrollment mode changes (per memory 9125481)
    this.currentEnrollmentMode = newMode;
    this.refreshAllTables();
  }
}
```

### Benefits of This Approach

#### **Why Approach A is Optimal:**

1. **Proven Functionality**: Commit 83b4ba5 has working cohort selection, auto-switch logic, and complete widget functionality
2. **Business Logic Preservation**: Contains important business logic (cohort mode disable for "ALL" ranges, auto-switching)
3. **User Experience**: Provides complete, intuitive interface that users expect
4. **Architecture Benefits**: Preserves new unified system benefits while restoring proven UI patterns

#### **Preserve New Architecture Benefits**
- Keep the unified data service for performance
- Maintain the enhanced logging system
- Preserve debouncing mechanisms
- Keep the modular ES6 import system
- Maintain the unified table updater for consistency
- Integrate with MCP ecosystem (Memory, Filesystem, Source Control, Chrome MCP)
- Support enterprise configurations (CSU, CCC, DEMO)
- Maintain cohort mode functionality as specified in project memories

### Risk Mitigation

1. **Incremental Testing**: Test each phase with Chrome MCP before proceeding
2. **Rollback Plan**: Keep current working state as fallback
3. **Memory MCP**: Store successful patterns for future reference
4. **Enterprise Testing**: Validate across CSU, CCC, DEMO configurations
5. **Performance Monitoring**: Use Chrome MCP to track performance metrics
6. **Source Control**: Commit changes with descriptive messages for easy rollback

## MCP Ecosystem Integration

### Chrome MCP Integration
- **Mandatory Testing Protocol**: Always start with `./mvp-local.sh` and authenticate via `login.php`
- **Browser Automation**: Use Chrome MCP tools for UI testing and screenshot validation
- **Performance Monitoring**: Track bundle loading, API response times, and UI interaction performance
- **Error Detection**: Monitor console errors and network requests for debugging

### Memory MCP Integration
- **Context Maintenance**: Preserve implementation patterns and successful approaches across sessions
- **Pattern Recognition**: Store working code patterns from validated state for reuse
- **Performance Baselines**: Track performance metrics and trends over time
- **Enterprise Preferences**: Remember enterprise-specific testing configurations

### Filesystem MCP Integration
- **Enhanced File Access**: Direct access to project files and configuration directories
- **Module Management**: Streamlined access to JavaScript modules and dependencies
- **Configuration Validation**: Automated checking of enterprise configuration files
- **Test Asset Organization**: Better management of screenshots and test artifacts

### Source Control Integration
- **Commit Patterns**: Consistent commit messages and roll-up commits
- **Branch Management**: Feature branch organization and merge strategies
- **Change Tracking**: Version control for all implementation changes
- **Conflict Resolution**: Automated support for merge conflicts

## Enterprise Configuration Integration

### Supported Enterprises
- **CSU**: California State University system with specific start dates and organizations
- **CCC**: California Community Colleges with district-level administration
- **DEMO**: Demo environment for testing and validation

### Enterprise-Specific Implementation
```php
// Initialize enterprise configuration
TestBase::initEnterprise('csu'); // or 'ccc', 'demo'
$config = UnifiedEnterpriseConfig::getEnterprise();
$startDate = UnifiedEnterpriseConfig::getStartDate();
```

### Enterprise Testing Strategy
- **Cross-Enterprise Validation**: Test functionality across all enterprise configurations
- **Configuration Loading**: Verify enterprise-specific settings load correctly
- **Data Filtering**: Ensure data is filtered by enterprise start dates
- **Organization Management**: Test enterprise-specific organization handling

This optimized approach leverages the proven working code from commit 83b4ba5 while preserving the architectural improvements made in the current system, enhanced with comprehensive MCP ecosystem integration and enterprise configuration support, resulting in a Reports page that is both functional and well-architected.

## Summary

**Validated Working State**: Commit 83b4ba5 (2025-09-11) - Contains fully functional systemwide widgets with radio buttons, cohort selection, and complete business logic.

**Current Broken State**: Current HEAD - Widgets disabled in commit 10ebee7 (2025-09-24) during MVP System Launch simplification.

**Recommended Solution**: Approach A - Restore validated functionality from 83b4ba5 into current unified architecture, preserving both proven working patterns and modern architectural benefits through phased implementation with comprehensive testing.

## Critical Documentation Gaps (Simple, Reliable, DRY Focus)

### 1. Authentication Flow Documentation

**Current Gap**: Missing detailed authentication implementation patterns

**Required Documentation**:
- Session management patterns using `$_SESSION['organization_authenticated']` and `$_SESSION['admin_authenticated']`
- Enterprise-specific authentication flows for CSU, CCC, DEMO configurations
- Authentication redirects and error handling patterns
- Chrome MCP authentication testing procedures

**Implementation Pattern**:
```php
// Standard authentication check pattern
if (!isset($_SESSION['organization_authenticated']) && !isset($_SESSION['admin_authenticated'])) {
    header('Location: /login.php');
    exit;
}
```

### 2. API Endpoint Documentation

**Current Gap**: Incomplete API structure documentation

**Required Documentation**:
- Complete parameter documentation for `reports_api.php` and `reports_api_internal.php`
- The six core datasets: `registrations_submissions`, `registrations_cohort`, `submissions_enrollments_tou`, `submissions_enrollments_registrations`, `cohort_enrollments_tou`, `cohort_enrollments_registrations`
- Error response formats and HTTP status codes
- Existing cache management patterns

**API Parameter Pattern**:
```php
// Standard API parameter handling
$start_date = $_GET['start_date'] ?? null;
$end_date = $_GET['end_date'] ?? null;
$enrollment_mode = $_GET['enrollment_mode'] ?? 'by-tou';
$cohort_mode = $_GET['cohort_mode'] ?? false;
```

### 3. Error Handling and Recovery Patterns

**Current Gap**: Error handling not comprehensively documented

**Required Documentation**:
- Existing error recovery strategies for API failures
- User-facing error message patterns
- Existing retry mechanisms and debouncing
- Error logging patterns

**Error Handling Pattern**:
```javascript
// Standard error handling pattern
try {
    const response = await fetch(url);
    if (!response.ok) {
        throw new Error(`API Error: ${response.status}`);
    }
    return await response.json();
} catch (error) {
    console.error('API Error:', error);
    showUserError('Unable to load data. Please try again.');
}
```

### 4. Data Validation and Sanitization

**Current Gap**: Data validation patterns not documented

**Required Documentation**:
- Existing input validation strategies for all API endpoints
- Data sanitization patterns for enterprise data
- Data integrity checks for the six core datasets
- Validation of enterprise-specific data constraints

**Validation Pattern**:
```php
// Standard data validation pattern
function validateDateRange($start_date, $end_date) {
    if (!preg_match('/^\d{2}-\d{2}-\d{2}$/', $start_date)) {
        throw new InvalidArgumentException('Invalid start date format');
    }
    // Additional validation logic
}
```

### 5. Memory MCP Integration (Simplified)

**Current Gap**: Essential memory patterns not documented

**Required Documentation**:
- Essential Memory MCP patterns for context maintenance across sessions
- Basic memory cleanup strategies
- Performance baseline storage patterns
- **Avoid**: Over-engineering or complex memory management

**Memory Pattern**:
```javascript
// Essential memory pattern for context maintenance
const contextKey = `reports_${enterprise}_${dateRange}`;
// Store essential patterns only, avoid complex state management
```

### 6. Performance Monitoring (Simplified)

**Current Gap**: Performance implementation details missing

**Required Documentation**:
- Existing performance metric collection patterns
- Basic performance alerting patterns
- Performance regression detection
- **Avoid**: Complex monitoring systems or new performance tools

**Performance Pattern**:
```javascript
// Simple performance monitoring pattern
const startTime = performance.now();
// ... operation ...
const duration = performance.now() - startTime;
if (duration > 5000) {
    console.warn(`Slow operation: ${duration}ms`);
}
```

## Implementation Priority

### High Priority (Core Documentation):
1. **Authentication Flow Documentation** - Essential for reliability
2. **API Endpoint Documentation** - Single source of truth
3. **Error Handling Patterns** - Standardizes existing error handling
4. **Data Validation Documentation** - Documents existing validation

### Medium Priority (Simplified):
5. **Memory MCP Integration (Simplified)** - Essential patterns only
6. **Performance Monitoring (Simplified)** - Existing metrics only

## Core Principle Alignment

These documentation additions follow the project's established pattern of:
- **Simple**: Document existing patterns, don't create new complexity
- **Reliable**: Focus on proven patterns from validated state
- **DRY**: Leverage existing unified architecture (unified-data-service, unified-table-updater)

**Focus**: Document and standardize **existing working patterns** rather than adding new complexity or edge case handling.