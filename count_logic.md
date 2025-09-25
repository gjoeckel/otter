# Count Logic - AI Agent Reference

## Overview
This document defines the count logic requirements for both Reports and Dashboard systems. It serves as a reference for AI agents implementing count functionality.

---

## 🚀 **IMPLEMENTATION READY - Complete Understanding Confirmed:**

### **Dashboard Requirements:**
1. **Always "All" date range** - no date filtering
2. **No enrollment count options** - single consistent logic
3. **Four distinct data views** with specific filtering and sorting logic
4. **All data pre-validated** as trimmed strings

### **Implementation Ready:**
I now have everything needed to create the optimal DRY implementation:

1. **Unified Data Processing Service** - Single source of truth for all dashboard data
2. **Consistent Column Index Management** - Centralized index definitions
3. **Standardized Data Structure** - Unified output format
4. **Eliminate Code Duplication** - Consolidate the 4+ different processing approaches

## 🚀 **Ready to Implement:**

The DRY solution will:
- **Fix the broken enrollment logic** by implementing the correct filtering rules
- **Eliminate code duplication** across `dashboard.php`, `organizations_api.php`, `data_processor.php`, and `reports_api.php`
- **Create a single, reliable data processing service** for all dashboard components
- **Maintain consistent sorting and filtering** across all four data views

**No additional information needed** - I can proceed with the implementation using the column indices and logic rules you've provided.

---

# Reports Count Logic

## Reports System Overview
The Reports system allows users to choose between different counting modes for registrations and enrollments with dynamic date range filtering.

## Reports Count Options Modes

### Registration Count Modes:
- **by-date**: Count registrations by submission date
- **by-cohort**: Count registrations by cohort (month-year combinations)

### Enrollment Count Modes:
- **by-tou**: Count enrollments by TOU completion date
- **by-registration**: Count enrollments by registration date

## Reports Complex Logic Features

### 1. **Auto-Switching Logic**
- Checks TOU completion count
- If 0, automatically switches to registration date mode
- Disables TOU mode and shows status message

### 2. **Cohort Filtering**
- Converts date ranges to cohort keys (MM-YY format)
- Filters data based on cohort combinations
- Handles "ALL" date ranges specially

### 3. **Mode Persistence**
- Tracks current modes across page interactions
- Maintains mode state during data updates
- Handles mode changes gracefully

### 4. **Current Status Messages**
- "Showing data for all registrations submitted in date range - count by cohorts disabled"
- "Showing data for all TOU completions in the date range"
- "Showing data for all registrations submitted for cohort(s) in the date range"

## Reports Implementation Status

### Currently Active Features:
- Auto-switching functionality when TOU count is 0
- Cohort mode disable for "ALL" ranges
- Dynamic status messages based on mode selection
- Mode change handling and data refresh triggers

### Recent Fixes (v1.2.0):
- Data structure mismatch resolved
- Systemwide table now shows correct values
- Legacy functions updated to use correct data structure

---

# Dashboard Count Logic

## Dashboard System Overview
Dashboard pages are always for the "All" date range with no enrollment count options - they use consistent, simple logic for displaying organization-specific data.

## Data Source
- **Source**: `all-registrants-data.json` (cached enterprise data)
- **Format**: All data are trimmed strings that are already validated
- **Date Range**: Dashboard pages are **ALWAYS** for the "All" date range (no date filtering)
- **Organization**: Filtered by specific organization name

## Column Index Reference
Based on Google Sheets column mapping (0-based array indices):
- **Column A (0)**: DaysToClose
- **Column B (1)**: Invited (MM-DD-YY format)
- **Column C (2)**: Enrolled (MM-DD-YY format or "-"/blank)
- **Column D (3)**: Cohort (MM format)
- **Column E (4)**: Year (YY format)
- **Column F (5)**: First (first name)
- **Column G (6)**: Last (last name)
- **Column J (9)**: Organization (organization name)
- **Column K (10)**: Certificate ("Yes" or other)

## Dashboard Data Views

### 1. Enrollment Summary Table
**Purpose**: Show all Cohort-Year combinations that have at least one enrollment
**Logic**:
- Group by `row[3]` (Cohort) + `row[4]` (Year)
- Include only groups where **at least one row** has `row[2]` (Enrolled) ≠ "-" and ≠ blank
- Count enrollments, completions, and certificates for each group
- **Sort**: Year descending, then Cohort descending

### 2. Enrolled Participants
**Purpose**: Show all registrants who are actively enrolled (not closed)
**Logic**:
- Include rows where `row[0]` (DaysToClose) is **NOT closed**
- Filter by organization: `row[9]` === organization name
- **Sort**: Year descending, Cohort descending, Last ascending, First ascending

### 3. Invited Participants
**Purpose**: Show all registrants who are invited but not yet enrolled
**Logic**:
- Include rows where `row[2]` (Enrolled) is "-" or blank
- Filter by organization: `row[9]` === organization name
- **Sort**: Year descending, Cohort descending, Invited descending (`row[1]`), Last ascending, First ascending

### 4. Certificates Earned
**Purpose**: Show all registrants who have earned certificates
**Logic**:
- Include rows where `row[10]` (Certificate) === "Yes"
- Filter by organization: `row[9]` === organization name
- **Sort**: Year descending, Cohort descending, Last ascending, First ascending

## Key Implementation Rules

### 1. Data Filtering
- **Organization Filter**: Always filter by `row[9]` === organization name
- **No Date Filtering**: Dashboard always shows ALL data (no date range constraints)
- **String Comparisons**: Use exact string matching (data already trimmed)

### 2. Enrollment Status Logic
- **Enrolled**: `row[2]` (Enrolled) is not "-" and not blank
- **Invited**: `row[2]` (Enrolled) is "-" or blank
- **Closed**: `row[0]` (DaysToClose) === "closed" (or similar closed status)
- **Certificate Earned**: `row[10]` (Certificate) === "Yes"

### 3. Sorting Rules
- **Primary Sort**: Year descending (newest first)
- **Secondary Sort**: Cohort descending (highest month first)
- **Tertiary Sort**: Last name ascending, First name ascending (alphabetical)
- **Exception**: Invited Participants use Invited date descending as tertiary sort

### 4. Grouping Logic
- **Enrollment Summary**: Group by Cohort-Year combination
- **Other Views**: Individual rows (no grouping)

## DRY Implementation Requirements

### 1. Unified Data Processing
- **Single Source**: Create one service to process all dashboard data
- **Consistent Logic**: Use same filtering and sorting rules across all views
- **Reusable Functions**: Extract common filtering and sorting logic

### 2. Column Index Management
- **Centralized**: Define column indices in one place
- **Consistent**: Use same indices across all dashboard processing
- **Maintainable**: Easy to update when column structure changes

### 3. Data Structure Standardization
- **Unified Output**: All dashboard data should use consistent structure
- **Predictable**: Same field names and formats across all views
- **Extensible**: Easy to add new dashboard views

## Current Issues to Fix

### 1. Code Duplication
- Multiple files processing the same data differently
- Inconsistent enrollment logic across components
- Scattered column index definitions

### 2. Broken Enrollment Logic
- Dashboard uses different enrollment logic than reports
- Inconsistent data interpretation across components
- Missing unified enrollment determination rules

### 3. Non-DRY Implementation
- Hardcoded indices in multiple files
- Duplicate data processing logic
- Inconsistent data structure expectations

## Expected Benefits of DRY Implementation

### 1. Reliability
- Consistent enrollment logic across all components
- Single source of truth for data processing
- Eliminates inconsistencies between dashboard and reports

### 2. Maintainability
- Single place to update dashboard logic
- Centralized column index management
- Reduced code duplication

### 3. Performance
- Eliminate duplicate processing
- Optimized data filtering and sorting
- Consistent caching strategy

### 4. Accuracy
- Unified enrollment counting logic
- Consistent data interpretation
- Reliable dashboard data display
