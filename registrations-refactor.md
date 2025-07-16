# Registrations Refactor Documentation

## Implementation Status

### Phase Completion Status
| Phase | Description | Status | Notes |
|-------|-------------|--------|-------|
| 1 | Create backup of current state | ⏳ **PENDING** | Need to document current registration counts |
| 2 | Update documentation | ✅ **COMPLETE** | This document created |
| 3 | Rename existing registration logic as "Invitations" | ⏳ **PENDING** | Method renaming in data_processor.php |
| 4 | Update API endpoints to preserve invitations data | ⏳ **PENDING** | API response structure changes |
| 5 | Update configuration comments | ⏳ **PENDING** | Enterprise config file updates |
| 6 | Create new registration processing method | ⏳ **PENDING** | New method in data_processor.php |
| 7 | Update API endpoints to use new registration logic | ⏳ **PENDING** | Switch to submissions data source |
| 8 | Update organization data processing | ⏳ **PENDING** | Organization counting logic |
| 9 | Update JavaScript data processing | ⏳ **PENDING** | Frontend data handling |
| 10 | Update reports pages | ⏳ **PENDING** | Reports display updates |
| 11 | Update groups/districts logic | ⏳ **PENDING** | Groups data processing |
| 12 | Create comprehensive tests | ⏳ **PENDING** | Test coverage for new logic |
| 13 | Data validation | ⏳ **PENDING** | Compare old vs new results |
| 14 | Update configuration files | ⏳ **PENDING** | Final config updates |
| 15 | Update documentation | ⏳ **PENDING** | Post-implementation docs |
| 16 | Deploy changes | ⏳ **PENDING** | Production deployment |
| 17 | Post-deployment validation | ⏳ **PENDING** | Monitoring and validation |

### Current Blockers/Issues
- **None identified** - Ready to begin Phase 1 implementation
- **Data source validation needed** - Verify submissions "Filtered" sheet accessibility
- **Baseline data collection** - Document current registration counts before changes

### Next Steps
1. **Phase 1**: Document current registration counts for all enterprises
2. **Phase 3**: Begin method renaming in `lib/data_processor.php`
3. **Phase 6**: Implement new registration processing using submissions data

---

## Overview
This document identifies all files and code currently used to return values for the **Registration column** on the reports page. The registration logic is a critical component that determines how registrations are counted and displayed across the system.

**Business Context**: This refactor addresses data completeness issues by switching from registrants sheet data to submissions sheet data, capturing the full picture of invited vs. actual submissions.

## Business Justification

### Current Registration Logic (Registrants Sheet) - Problems
**Advantages:**
- ✅ Duplicates removed
- ✅ Identified past enrollees removed  
- ✅ Bot submissions removed
- ✅ Clean, validated data

**Disadvantages:**
- ❌ **Rows for invited registrants who didn't convert to enrollees are purged when cohort closes**
- ❌ **Total submissions and submission-to-enrollment ratios are not accurately captured**
- ❌ Missing data for people who were invited but never enrolled

### Proposed Solution (Submissions "Filtered" Sheet) - Benefits
**Phase 1 Goal:** Switch to using submissions workbook "Filtered" sheet (manually validated)
**Future Phase:** Add validation, de-duplication, past enrollee identification, etc.

**Key Insight:** This is a **data completeness** improvement - capturing the full picture of invited vs. actual submissions, even if it means temporarily including some duplicates or past enrollees.

**Expected Outcomes:**
- **Registration counts will increase** (this is GOOD - captures missing data)
- **More accurate ratios** (submission-to-enrollment ratios will be more realistic)
- **Complete historical data** (no more lost data from closed cohorts)
- **Better business insights** (full picture of invitation effectiveness)

## Core Data Processing Logic

### 1. Primary Data Processor
**File**: `lib/data_processor.php`  
**Method**: `DataProcessor::processRegistrantsData()`  
**Lines**: 47-85

**Logic**:
```php
// Get column indices from configuration
$regDateIdx = self::getColumnIndex('registrants', 'Invited'); // Google Sheets Column B (1)

$registrations = [];
$registrationCount = 0;

foreach ($registrantsData as $rowIndex => $row) {
    $regDate = isset($row[$regDateIdx]) ? trim($row[$regDateIdx]) : '';
    
    if (self::inRange($regDate, $start, $end)) {
        $registrations[] = $row;
        $registrationCount++;
    }
}

return [
    'registrations' => $registrations,
    'enrollments' => $enrollments,
    'certificates' => $certificates
];
```

**Key**: Uses "Invited" column (index 1) from registrants sheet to determine registrations in date range

### 2. API Endpoints

#### Main Reports API
**File**: `reports/reports_api.php`  
**Lines**: 151-242

**Logic**:
```php
// Process registrants data using utility
$processedData = DataProcessor::processRegistrantsData($registrantsData, $start, $end);
$registrations = $processedData['registrations'];

// Build response
$response['registrations'] = $registrations;
```

#### Internal Reports API
**File**: `reports/reports_api_internal.php`  
**Lines**: 145-220

**Logic**: Same processing as main API

### 3. Enterprise Data Service
**File**: `lib/enterprise_data_service.php`  
**Lines**: 231-311

**Logic**:
```php
// Generate registrations data
$registrations = [];
foreach ($submissionsData as $row) {
    $submitted = isset($row[$idxSubSubmitted]) ? trim($row[$idxSubSubmitted]) : '';
    if ($this->isValidDate($submitted) && $this->isInRange($submitted, $startDate, $endDate)) {
        $registrations[] = array_map('strval', array_map('trim', $row));
    }
}
```

## Frontend Display Logic

### 4. JavaScript Data Processing
**File**: `reports/js/reports-data.js`  
**Lines**: 31-48

**Logic**:
```javascript
function updateSystemwideTable(start, end, data) {
    const tbody = document.querySelector('#systemwide-data tbody');
    
    if (!tbody) {
        return;
    }
    
    // Count the arrays to get summary numbers
    const registrationsCount = Array.isArray(data.registrations) ? data.registrations.length : 0;
    const enrollmentsCount = Array.isArray(data.enrollments) ? data.enrollments.length : 0;
    const certificatesCount = Array.isArray(data.certificates) ? data.certificates.length : 0;
    
    const html = `<tr><td>${start}</td><td>${end}</td><td>${registrationsCount}</td><td>${enrollmentsCount}</td><td>${certificatesCount}</td></tr>`;
    
    tbody.innerHTML = html;
}
```

### 5. Organization Data Processing
**File**: `lib/data_processor.php`  
**Method**: `DataProcessor::processOrganizationData()`  
**Lines**: 140-233

**Logic**:
```php
// Count registrations for this organization
foreach ($registrationsRows as $row) {
    if (isset($row[$orgIdx]) && trim($row[$orgIdx]) === $orgName) {
        $registrations++;
    }
}

// Add organization data with abbreviated name
$organizationData[] = [
    'organization' => $orgName,
    'organization_display' => abbreviateOrganizationName($orgName),
    'registrations' => $registrations,
    'enrollments' => $enrollments,
    'certificates' => $certificates
];
```

### 6. Organizations API
**File**: `lib/api/organizations_api.php`  
**Lines**: 263-310

**Logic**: Processes organization-specific registration counts

## Report-Specific Logic

### 7. Registrants Report
**File**: `reports/registrations_data.php`  
**Lines**: 46-107

**Logic**:
```php
// Column indices from the registrants data (based on config)
$invitedIdx = 1;     // Invited (Google Sheets Column B)

// Filter by Invited in range
$filtered = array_filter($registrantsData, function($row) use ($start, $end, $invitedIdx) {
    return isset($row[$invitedIdx]) &&
           preg_match('/^\d{2}-\d{2}-\d{2}$/', $row[$invitedIdx]) &&
           in_range($row[$invitedIdx], $start, $end);
});
```

### 8. Groups Data Processing
**File**: `reports/reports_api.php`  
**Lines**: 281-344

**Logic**: Processes registration counts for district/group level

## Configuration Files

### 9. Enterprise Configurations
**Files**: 
- `config/ccc.config`
- `config/csu.config` 
- `config/demo.config`

**Key Mapping**:
```json
"Invited": {
    "index": 1,
    "type": "string", 
    "_sheets_column": "B",
    "_description": "Registration date (MM-DD-YY format)"
}
```

## HTML Structure

### 10. Reports Page Template
**File**: `reports/index.php`  
**Lines**: 217-254

**Structure**: Systemwide table with "Registrations" column header

## Data Flow Summary

### Current Registration Logic Flow:

1. **Data Source**: Registrants Google Sheet, "Invited" column (index 1)
2. **Date Filtering**: Uses `inRange()` function to check if "Invited" date falls within selected date range
3. **Processing**: `DataProcessor::processRegistrantsData()` method counts rows where "Invited" date is in range
4. **Display**: JavaScript counts array length and displays the number in the Systemwide table
5. **Organization Level**: Same logic applied per organization for Organizations and Groups tables

### Key Components:

- **Primary Logic**: `lib/data_processor.php` - `processRegistrantsData()`
- **API Layer**: `reports/reports_api.php` and `reports/reports_api_internal.php`
- **Frontend**: `reports/js/reports-data.js` - `updateSystemwideTable()`
- **Configuration**: Enterprise config files define column mappings
- **Reports**: `reports/registrations_data.php` for detailed registrants report

### Data Consistency:

The system consistently uses the **"Invited" column from the registrants sheet** as the source for registration dates across all components. This ensures data consistency between:

- Systemwide summary table
- Organizations table
- Groups/Districts table (where applicable)
- Detailed registrants report

### Recent Changes:

- **Submission Logic Refactor**: Updated to use "Filtered" sheet and "Submitted" column (index 15) instead of "Token" column
- **Configuration Updates**: All enterprise configs now use "Filtered" sheet name for submissions
- **DataProcessor Updates**: Modified to use correct column indices from configuration

## Testing Strategy

### Testing Approach
**This refactor changes only the data source, not the core logic.** The submissions processing has been validated and works correctly. Testing should focus on **critical data integrity issues** rather than comprehensive validation.

### AI Agent Testing Scope
**LIMITED TO CRITICAL ISSUES ONLY:**
- Data source accessibility (can we reach the submissions "Filtered" sheet?)
- API response structure (does the new data flow through correctly?)
- Basic data format validation (are dates in expected MM-DD-YY format?)
- Configuration mapping accuracy (are column indices correct?)

### User Testing Requirements
**USER SHOULD TEST WHEN APPROPRIATE:**
- **Phase 7**: After API endpoints updated - test reports page functionality
- **Phase 9**: After frontend updates - verify table displays correctly
- **Phase 13**: After data validation - confirm registration counts make sense
- **Phase 16**: After deployment - validate production data accuracy

### Critical Test Validations

#### Data Source Validation (AI Agent)
1. **Submissions Sheet Accessibility**
   - Verify submissions "Filtered" sheet can be accessed
   - Confirm "Submitted" column (index 15) exists and contains data
   - Validate date format consistency (MM-DD-YY)

2. **API Response Structure**
   - API returns both `invitations` and `registrations` arrays
   - No PHP errors in API responses
   - Data flows through to frontend without JavaScript errors

3. **Configuration Accuracy**
   - Enterprise config files have correct submissions mapping
   - Column indices match actual Google Sheets structure
   - Sheet names and workbook IDs are correct

#### Data Quality Checks (User Validation)
1. **Registration Count Changes**
   - Registration counts increase (expected and desired)
   - Organization-level data is consistent
   - Date range filtering works correctly

2. **Business Logic Validation**
   - Invitations ≥ registrations (logical relationship)
   - Enrollments ≤ registrations (logical relationship)
   - Data makes business sense to users

### Test Files for Critical Issues

#### Existing Test Files (Reuse)
- `tests/integration/simple_reports_validation_test.php` - Validate API responses
- `tests/test_organization_processing.php` - Test organization data consistency

#### New Test Files (Minimal)
- `tests/integration/submissions_data_source_test.php` - Verify submissions sheet accessibility
- `tests/integration/api_response_structure_test.php` - Validate new API structure

### Test Validation Checklist

#### Pre-Implementation (AI Agent)
- [ ] Submissions "Filtered" sheet accessible
- [ ] "Submitted" column contains valid date data
- [ ] Enterprise configs have correct submissions mapping
- [ ] Existing tests passing

#### During Implementation (AI Agent)
- [ ] API returns both invitations and registrations
- [ ] No PHP errors in API responses
- [ ] Configuration changes applied correctly
- [ ] Basic data format validation passes

#### Post-Implementation (User Testing)
- [ ] Reports page loads without errors
- [ ] Registration counts display correctly
- [ ] Date range filtering works
- [ ] Organization data is consistent
- [ ] Data makes business sense

### Test Data Requirements

#### Minimal Test Data
1. **Current Registration Counts**: Document baseline before changes
2. **Submissions Data Sample**: Verify data quality in submissions sheet
3. **Date Range Validation**: Test with known date ranges

#### No Complex Test Environment Needed
- **No isolated testing environment required**
- **No performance testing needed** (same data processing logic)
- **No comprehensive edge case testing** (focus on critical issues only)

## Future Considerations

When refactoring registration logic, consider:

1. **Data Source Consistency**: Ensure all components use the same data source
2. **Date Range Logic**: Maintain consistent date filtering across all tables
3. **Performance**: Consider caching strategies for large datasets
4. **Configuration**: Use configuration-driven column mappings for maintainability
5. **Testing**: Maintain comprehensive test coverage for data processing logic

---

# Submissions Logic Documentation

## Overview
This section documents all files and code currently used to return values for **Submissions** in the system. The submissions logic was recently refactored to use the "Filtered" sheet and "Submitted" column instead of the "Token" column.

**Key Insight**: The submissions "Filtered" sheet has been **manually validated** and will be used as the new data source for registrations in Phase 1 of the refactor.

## Core Data Processing Logic

### 1. Primary Data Processor
**File**: `lib/data_processor.php`  
**Method**: `DataProcessor::processSubmissionsData()`  
**Lines**: 90-120

**Logic**:
```php
/**
 * Process submissions data for date range
 * @param array $submissionsData - Raw submissions data
 * @param string $start - Start date in MM-DD-YY format
 * @param string $end - End date in MM-DD-YY format
 * @return array Processed submissions data
 */
public static function processSubmissionsData($submissionsData, $start, $end) {
    // Get column index from configuration
    $submittedDateIdx = self::getColumnIndex('submissions', 'Submitted'); // Google Sheets Column P (15)

    $submissions = [];
    $processedCount = 0;
    $submissionCount = 0;

    foreach ($submissionsData as $rowIndex => $row) {
        $processedCount++;

        if (!is_array($row)) {
            continue;
        }

        // Use configuration-based column index
        $submittedDate = isset($row[$submittedDateIdx]) ? trim($row[$submittedDateIdx]) : '';

        if (self::inRange($submittedDate, $start, $end)) {
            $submissions[] = $row;
            $submissionCount++;
        }
    }

    return $submissions;
}
```

**Key**: Uses "Submitted" column (index 15) from submissions sheet to determine submissions in date range

### 2. API Endpoints

#### Main Reports API
**File**: `reports/reports_api.php`  
**Lines**: 201-242

**Logic**:
```php
// Process submissions data using utility
$submissions = DataProcessor::processSubmissionsData($submissionsData, $start, $end);

// Build response
$response['registrations'] = $registrations;
$response['enrollments'] = $enrollments;
$response['certificates'] = $certificates;
$response['submissions'] = $submissions;
```

#### Internal Reports API
**File**: `reports/reports_api_internal.php`  
**Lines**: 201-220

**Logic**: Same processing as main API

### 3. Enterprise Data Service
**File**: `lib/enterprise_data_service.php`  
**Lines**: 129-200

**Logic**:
```php
/**
 * Fetch submissions data from Google Sheets
 * @param bool $forceRefresh Whether to bypass cache
 * @return array Submissions data or error
 */
private function fetchSubmissionsData($forceRefresh = false) {
    $cacheFile = $this->cacheManager->getSubmissionsCachePath();
    
    // Check cache first
    if (!$forceRefresh && file_exists($cacheFile)) {
        $json = json_decode(file_get_contents($cacheFile), true);
        $cacheTimestamp = isset($json['global_timestamp']) ? $json['global_timestamp'] : null;
        
        if ($cacheTimestamp) {
            $dt = DateTime::createFromFormat('m-d-y \a\t g:i A', $cacheTimestamp, new DateTimeZone('America/Los_Angeles'));
            if ($dt !== false) {
                $now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
                $diff = $now->getTimestamp() - $dt->getTimestamp();
                if ($diff < $this->cacheTtl) {
                    return isset($json['data']) ? $json['data'] : [];
                }
            }
        }
    }
    
    // Fetch from Google Sheets
    $submissionsConfig = $this->config['submissions'];
    $data = $this->fetchSheetData(
        $submissionsConfig['workbook_id'],
        $submissionsConfig['sheet_name'],
        $submissionsConfig['start_row']
    );
    
    if (isset($data['error'])) {
        return $data;
    }
    
    // Cache the data with timestamp
    $this->cacheData($cacheFile, $data);
    
    return $data;
}
```

## Configuration Files

### 4. Enterprise Configurations
**Files**: 
- `config/ccc.config`
- `config/csu.config` 
- `config/demo.config`

**Key Mapping**:
```json
"submissions": {
    "workbook_id": "1LwR4j62XKlaHYsRRB2MtdQUPU0MTj5ynWW_5VpkPqIg",
    "sheet_name": "Filtered",
    "start_row": 2,
    "_comment": "Column mapping for submissions data. Key dates: Token (B/1), Submitted (P/15)",
    "columns": {
        "Submitted": {
            "index": 15,
            "type": "string",
            "_sheets_column": "P",
            "_description": "Submission date (MM-DD-YY format)"
        }
    }
}
```

## Data Flow Summary

### Current Submissions Logic Flow:

1. **Data Source**: Submissions Google Sheet, "Filtered" sheet, "Submitted" column (index 15)
2. **Date Filtering**: Uses `inRange()` function to check if "Submitted" date falls within selected date range
3. **Processing**: `DataProcessor::processSubmissionsData()` method counts rows where "Submitted" date is in range
4. **API Response**: Submissions data included in API responses for systemwide data
5. **Caching**: Submissions data cached in `all-submissions-data.json`

### Key Components:

- **Primary Logic**: `lib/data_processor.php` - `processSubmissionsData()`
- **API Layer**: `reports/reports_api.php` and `reports/reports_api_internal.php`
- **Data Service**: `lib/enterprise_data_service.php` - `fetchSubmissionsData()`
- **Configuration**: Enterprise config files define submissions sheet and column mappings
- **Cache**: `all-submissions-data.json` for performance optimization

### Recent Refactoring Changes:

#### Before Refactor:
- **Sheet Name**: "Sheet1"
- **Date Column**: "Token" (index 1)
- **Logic**: Used token column for date filtering

#### After Refactor:
- **Sheet Name**: "Filtered" 
- **Date Column**: "Submitted" (index 15)
- **Logic**: Uses "Submitted" column for date filtering in MM-DD-YY format

### Configuration Updates Made:

**All three enterprise config files updated**:
```json
"submissions": {
    "sheet_name": "Filtered",  // Changed from "Sheet1"
    "columns": {
        "Submitted": {
            "index": 15,       // Uses correct column index
            "_sheets_column": "P",
            "_description": "Submission date (MM-DD-YY format)"
        }
    }
}
```

## Testing

### Test Files for Submissions:
- `tests/root_tests/data_tests.php` - Validates submissions data processing
- `tests/integration/simple_reports_validation_test.php` - Tests submissions API responses

### Key Test Validations:
1. Submissions data loads from "Filtered" sheet
2. "Submitted" column (index 15) correctly mapped
3. Date filtering works with MM-DD-YY format
4. API returns submissions data in expected format

## Data Consistency

The submissions system now consistently uses:
- **Sheet**: "Filtered" (instead of "Sheet1")
- **Date Column**: "Submitted" (index 15) 
- **Date Format**: MM-DD-YY (e.g., "07-16-25")
- **Processing**: Configuration-driven column mapping

## Integration Points

### Submissions vs Registrations:
- **Submissions**: Use submissions sheet ("Filtered") with "Submitted" column
- **Registrations**: Use registrants sheet with "Invited" column
- **Different Data Sources**: Submissions and registrations come from different Google Sheets
- **Different Purposes**: 
  - Submissions track actual submission dates
  - Registrations track invitation/registration dates

### API Response Structure:
```json
{
    "registrations": [...],  // From registrants sheet
    "enrollments": [...],    // From registrants sheet  
    "certificates": [...],   // From registrants sheet
    "submissions": [...]     // From submissions sheet
}
```

## Future Considerations for Submissions

When working with submissions logic, consider:

1. **Sheet Consistency**: Ensure all enterprises use "Filtered" sheet name
2. **Column Mapping**: Verify "Submitted" column is correctly mapped to index 15
3. **Date Format**: Maintain MM-DD-YY format consistency
4. **Performance**: Submissions data is cached separately from registrants data
5. **Testing**: Validate submissions processing independently from registrations

---

# Naming Convention Strategy

## Current Naming Issues
The current code has misleading names that don't reflect the actual data being processed:

### Method Names
- **Current**: `processRegistrantsData()` 
- **Problem**: Processes "Invited" dates, not actual registrations
- **Proposed**: `processInvitationsData()`

### API Response Keys
- **Current**: `"registrations"` (contains invitation data)
- **Problem**: Misleading - contains invitation dates, not actual registrations
- **Proposed**: 
  ```json
  {
      "invitations": [...],     // From registrants sheet "Invited" column
      "registrations": [...],   // From submissions sheet "Submitted" column
      "enrollments": [...],     // From registrants sheet
      "certificates": [...]     // From registrants sheet
  }
  ```

### Variable Names
- **Current**: `$registrations`, `$registrationCount`
- **Proposed**: `$invitations`, `$invitationCount` (for old logic)
- **New**: `$registrations`, `$registrationCount` (for new submissions logic)

### Configuration Comments
- **Current**: `"_description": "Registration date (MM-DD-YY format)"`
- **Proposed**: `"_description": "Invitation date (MM-DD-YY format)"`

## UI Display Remains Unchanged
- **Table Headers**: "Registrants" (business terminology stays the same)
- **User Experience**: No change to what users see
- **Business Context**: "Registrants" is the established business term

## Code Logic Reflects Data Reality
- **Invitations**: People who were invited (registrants sheet "Invited" column)
- **Registrations**: People who actually submitted (submissions sheet "Submitted" column)
- **Clear Separation**: Code names match the actual data being processed

## Benefits of Consistent Naming
1. **Code Clarity**: Method names reflect what they actually do
2. **Data Accuracy**: API responses clearly distinguish between invitations and actual registrations
3. **Maintainability**: Future developers understand the data flow
4. **Business Continuity**: UI remains unchanged for users
5. **Analytics Potential**: Can now track invitation-to-registration conversion rates

---

# File Organization Strategy

## Keep Logic in Existing Files

### Rationale for Current File Structure
**DO NOT** move logic to new files. Here's why:

#### DRY Principle Benefits
- ✅ **Shared Logic**: Both invitations and registrations use identical date filtering patterns
- ✅ **Code Re-use**: Same `inRange()` function, same validation patterns
- ✅ **Maintainability**: Related logic stays together in `DataProcessor` class

#### Consistency Benefits
- ✅ **Existing Patterns**: Follows current code organization in `lib/data_processor.php`
- ✅ **Configuration Access**: Both use same `self::getColumnIndex()` patterns
- ✅ **Error Handling**: Both use same validation and error patterns

#### Testing Benefits
- ✅ **Related Tests**: Easier to test related functionality together
- ✅ **Integration Testing**: Test both logic paths in same test files
- ✅ **Maintenance**: Single file to update for related changes

### Recommended File Structure
```
lib/data_processor.php
├── processInvitationsData()     // Old logic (renamed)
├── processRegistrationsData()   // New logic (new method)
├── processEnrollmentsData()     // Existing logic
└── processCertificatesData()    // Existing logic
```

### Method Organization Strategy
1. **Group Related Methods**: Keep all data processing methods together
2. **Clear Naming**: Use descriptive method names that reflect actual data
3. **Shared Utilities**: Extract common date processing logic to private methods
4. **Configuration-Driven**: Both methods use same configuration access patterns

### Benefits of This Approach
1. **Maintainability**: Single file for all data processing logic
2. **Code Re-use**: Shared utilities between related methods
3. **Testing**: Comprehensive testing of related functionality
4. **Documentation**: Clear relationship between different data types
5. **Future Extensions**: Easy to add new data processing methods

---

# Proposed Refactoring: Using Submissions for Registration Logic

## Overview
This section documents the proposed refactoring to:
1. **Replace the current Registration logic with Submissions logic** for counting registrations
2. **Preserve the existing Registration logic as "Invitations"** for future use
3. **Address data completeness issues** by capturing all invited registrants, not just those who enrolled

## Business Context

### Problem Statement
The current registration logic using the registrants sheet has a critical flaw: **rows for invited registrants who didn't convert to enrollees are purged when cohort closes**. This means:
- Total submissions are not accurately captured
- Submission-to-enrollment ratios are incomplete
- Historical data is lost for non-converting registrants

### Solution Strategy
**Phase 1**: Switch to submissions "Filtered" sheet (manually validated) for registration counting
**Phase 2**: Add data quality improvements (de-duplication, past enrollee filtering, bot detection)

### Expected Benefits
- **Data completeness**: All invited registrants captured, including non-enrollees
- **Accurate ratios**: Proper submission-to-enrollment ratio analysis
- **Historical preservation**: No more lost data from closed cohorts
- **Better insights**: Full picture of invitation effectiveness

## Revised Implementation Strategy

### Phase 1: Data Source Switch (Current Focus)
**Goal**: Switch from registrants "Invited" to submissions "Filtered" sheet

**Core Changes**:
1. **Data source switch**: Registrants "Invited" → Submissions "Filtered"
2. **Method rename**: `processRegistrantsData()` → `processInvitationsData()`
3. **New method**: `processRegistrationsData()` using submissions
4. **API updates**: Include both invitations and registrations
5. **Frontend updates**: Display new registration counts

**No Phase 1 Changes**:
- ❌ De-duplication logic
- ❌ Past enrollee filtering
- ❌ Bot detection
- ❌ Complex data validation

### Phase 2: Data Quality Enhancement (Future)
**Goal**: Add automated data quality improvements

**Future Changes**:
- ✅ Implement automated de-duplication
- ✅ Add past enrollee identification
- ✅ Remove bot submissions
- ✅ Achieve both completeness AND purity

## Implementation Details and Configuration Updates

### Configuration Migration Strategy

#### Enterprise Config Files Updates
**Files to Update**:
- `config/ccc.config`
- `config/csu.config` 
- `config/demo.config`

#### Current Configuration Structure
```json
"registrants": {
    "columns": {
        "Invited": {
            "index": 1,
            "type": "string",
            "_sheets_column": "B",
            "_description": "Registration date (MM-DD-YY format)"  // OLD
        }
    }
}
```

#### Updated Configuration Structure
```json
"registrants": {
    "columns": {
        "Invited": {
            "index": 1,
            "type": "string",
            "_sheets_column": "B",
            "_description": "Invitation date (MM-DD-YY format)"  // NEW
        }
    }
},
"submissions": {
    "workbook_id": "1LwR4j62XKlaHYsRRB2MtdQUPU0MTj5ynWW_5VpkPqIg",
    "sheet_name": "Filtered",
    "start_row": 2,
    "columns": {
        "Submitted": {
            "index": 15,
            "type": "string",
            "_sheets_column": "P",
            "_description": "Registration submission date (MM-DD-YY format)"  // NEW
        }
    }
}
```

### Validation and Testing Strategy

#### Pre-Implementation Validation
1. **Data Quality Assessment**
   - Compare current registration counts across all enterprises
   - Document expected submission counts for same date ranges
   - Identify any data quality issues in submissions data
   - Validate organization name consistency between sheets

2. **System Preparation**
   - Ensure all tests are passing before changes
   - Create database backups
   - Document current performance metrics
   - Prepare rollback procedures

3. **Stakeholder Communication**
   - Notify stakeholders of planned changes
   - Explain expected impact on metrics (registration counts will increase)
   - Plan for user training if needed
   - Prepare communication for data discrepancies

#### Implementation Validation Steps
1. **Side-by-Side Comparison**
   - Run old and new logic simultaneously
   - Compare registration counts between old and new logic
   - Document expected differences (submissions may have duplicates, etc.)
   - Verify organization-level data is consistent

2. **API Response Validation**
   - Verify API returns both `invitations` and `registrations`
   - Test data consistency across all tables and reports
   - Validate date range filtering accuracy
   - Check for any data quality issues

3. **Frontend Display Validation**
   - Ensure reports display correctly
   - Verify data makes business sense
   - Test all date range selections
   - Validate organization and groups tables

#### Post-Implementation Validation
1. **Data Validation**
   - Compare new registration counts with expected submissions
   - Verify organization-level data consistency
   - Check for any data quality issues
   - Validate date range filtering accuracy

2. **System Validation**
   - All tests passing
   - Performance within acceptable limits
   - No errors in logs
   - Cache files properly generated

3. **User Validation**
   - Reports display correctly
   - Data makes business sense
   - No user-reported issues
   - Stakeholder acceptance of new metrics

### Rollback Strategy

#### Quick Rollback Procedures
1. **Code Rollback**
   - Revert method names back to original
   - Restore original API response structure
   - Rollback configuration changes

2. **Data Rollback**
   - Restore from database backups
   - Clear cache files to force fresh data
   - Verify original registration counts

3. **Configuration Rollback**
   - Restore original enterprise config files
   - Verify column mappings are correct
   - Test with original data sources

#### Monitoring and Alerts
1. **Performance Monitoring**
   - Monitor API response times
   - Track cache hit rates
   - Watch for memory usage spikes

2. **Data Quality Monitoring**
   - Monitor for unexpected data changes
   - Track registration count trends
   - Alert on data quality issues

3. **User Experience Monitoring**
   - Monitor for user-reported issues
   - Track report generation success rates
   - Alert on frontend errors

## Proposed Steps and Order of Operations

### Phase 1: Preparation and Documentation
1. **Create backup of current state**
   - Document current registration counts for all enterprises
   - Create test snapshots of current data processing
   - Verify all tests are passing before changes

2. **Update documentation**
   - Add section to `registrations-refactor.md` documenting the planned changes
   - Create migration checklist
   - Document expected data differences between current and new logic

### Phase 2: Preserve Current Registration Logic as "Invitations"
3. **Rename and comment existing registration logic**
   - In `lib/data_processor.php`: Rename `processRegistrantsData()` to `processInvitationsData()`
   - Add comprehensive inline comments explaining this is preserved for future use
   - Update method documentation to clarify it processes "invitations" not "registrations"

4. **Update API endpoints to preserve invitations data**
   - In `reports/reports_api.php`: Add invitations processing alongside new registration logic
   - In `reports/reports_api_internal.php`: Same updates
   - Modify response structure to include both `invitations` and `registrations`

5. **Update configuration comments**
   - In all enterprise config files: Update comments to clarify "Invited" column is for invitations
   - Add documentation about the distinction between invitations and registrations

### Phase 3: Implement New Registration Logic Using Submissions
6. **Create new registration processing method**
   - In `lib/data_processor.php`: Create `processRegistrationsData()` method
   - Use submissions data source and "Submitted" column (index 15)
   - Implement same date range filtering logic as submissions

7. **Update API endpoints to use new registration logic**
   - In `reports/reports_api.php`: Replace registration processing with new method
   - In `reports/reports_api_internal.php`: Same updates
   - Ensure API responses include both `invitations` and `registrations`

8. **Update organization data processing**
   - In `lib/data_processor.php`: Modify `processOrganizationData()` to use new registration data
   - Update organization counting logic to use submissions-based registrations
   - Preserve invitations counting for future use

### Phase 4: Update Frontend and Reports
9. **Update JavaScript data processing**
   - In `reports/js/reports-data.js`: Modify `updateSystemwideTable()` to use new registration data
   - Update organization table processing to use new registration counts
   - Ensure invitations data is available for future use

10. **Update reports pages**
    - In `reports/index.php`: Update table headers if needed
    - In `reports/registrations_data.php`: Update to use submissions data source
    - Ensure registrants report shows actual submissions, not invitations

### Phase 5: Update Groups/Districts Logic
11. **Update groups data processing**
    - In `reports/reports_api.php`: Update groups counting to use new registration logic
    - Ensure district-level data reflects submissions-based registrations

### Phase 6: Testing and Validation
12. **Create comprehensive tests**
    - Test new registration logic with submissions data
    - Verify invitations logic still works correctly
    - Test data consistency across all tables and reports
    - Validate API responses include both data types

13. **Data validation**
    - Compare registration counts between old and new logic
    - Document expected differences (submissions may have duplicates, etc.)
    - Verify organization-level data is consistent

### Phase 7: Configuration and Documentation
14. **Update configuration files**
    - Add comments explaining the new registration logic
    - Document the distinction between invitations and registrations
    - Update any hardcoded references to registration logic

15. **Update documentation**
    - Update `registrations-refactor.md` with new logic
    - Document the migration and expected data changes
    - Create troubleshooting guide for data discrepancies

### Phase 8: Deployment and Monitoring
16. **Deploy changes**
    - Deploy to test environment first
    - Monitor for any issues with data processing
    - Verify all reports and tables display correctly

17. **Post-deployment validation**
    - Monitor registration counts for expected changes
    - Document any data quality issues discovered
    - Plan for future data cleanup logic

## Success Criteria

### Technical Success:
- [ ] Registration counts increase (expected and desired)
- [ ] All invited registrants captured (including non-enrollees)
- [ ] System continues to function without errors
- [ ] Both invitations and registrations available in API

### Business Success:
- [ ] More accurate submission-to-enrollment ratios
- [ ] Complete historical data preservation
- [ ] Better understanding of invitation effectiveness
- [ ] Stakeholder acceptance of higher registration numbers

## Risk Assessment

### Reduced Technical Risk:
- **Manual validation**: "Filtered" sheet reduces data quality concerns
- **Proven data source**: Submissions processing already works
- **Incremental approach**: Can add quality improvements later

### Expected Data Changes (Positive):
- **Registration counts will increase**: This is GOOD - captures missing data
- **More accurate ratios**: Submission-to-enrollment ratios will be more realistic
- **Complete historical data**: No more lost data from closed cohorts
- **Better business insights**: Full picture of invitation effectiveness

## Optimal Order Rationale

### Why This Order:
1. **Preservation First**: Save current logic before making changes to prevent data loss
2. **Parallel Development**: Keep both systems running during transition
3. **Incremental Testing**: Test each component as it's updated
4. **Rollback Capability**: Maintain ability to revert if issues arise
5. **Data Validation**: Compare old vs new results at each step

### Critical Dependencies:
- Step 3 must complete before Step 6 (preserve invitations before creating new registrations)
- Step 6 must complete before Step 7 (new logic must exist before API updates)
- Step 7 must complete before Step 9 (API must work before frontend updates)
- All backend changes must complete before frontend updates

### Risk Mitigation:
- Each phase can be tested independently
- Rollback points at each major phase
- Both old and new logic available during transition
- Comprehensive testing at each step

## Git Branch Strategy

### Recommended Approach:
**Use a single feature branch** for the entire process:

```bash
# Create and switch to feature branch
git checkout -b feature/registration-submissions-refactor

# Make changes following the 17-step plan
# Commit after each major phase
git add .
git commit -m "feat: [phase description]"

# Push branch for backup and potential collaboration
git push origin feature/registration-submissions-refactor

# When complete, create pull request
# After review and testing, merge to main
```

### Branch Naming:
```bash
git checkout -b registrations-refactor
```

### Commit Strategy:
- **Phase-based commits**: One commit per major phase
- **Descriptive messages**: Clear explanation of what changed
- **Atomic commits**: Each commit should be a complete, testable unit

### Example Commit Structure:
```
feat: preserve current registration logic as invitations
feat: implement new registration logic using submissions data
feat: update API endpoints for new registration logic
feat: update frontend to use submissions-based registrations
feat: update organization and groups data processing
feat: update configuration and documentation
test: add comprehensive tests for new registration logic
docs: update documentation for registration refactor
```

### Testing Strategy:
- **Test each commit**: Ensure each phase works before proceeding
- **Compare data**: Run tests to compare old vs new registration counts
- **Integration testing**: Test complete workflow after each major phase

### Merge Strategy:
- **Pull request**: Create PR for code review
- **Squash merge**: Combine all commits into one clean merge
- **Deploy to staging**: Test in staging environment before production

## Critical Reasons for Using a Git Branch:

### **1. Risk Management**
- **Safe experimentation**: You can test changes without affecting the main codebase
- **Easy rollback**: If issues arise, you can quickly revert to the working state
- **No production impact**: Main branch remains stable during development

### **2. Complex Multi-Phase Changes**
- **17 steps** across multiple files and systems
- **Multiple API endpoints** need coordinated updates
- **Frontend and backend** changes must be synchronized
- **Configuration changes** across all enterprise configs

### **3. Data Logic Changes**
- **Fundamental change** in how registrations are calculated
- **Potential data discrepancies** that need investigation
- **Testing required** at each phase
- **Documentation updates** throughout the process

### **4. Team Collaboration**
- **Code review**: Others can review changes before merging
- **Parallel development**: Other features can continue on main branch
- **Clear history**: Git history shows the complete refactoring process

## Pre-Implementation Checklist

### Data Validation:
- [ ] Compare current registration counts across all enterprises
- [ ] Document expected submission counts for same date ranges
- [ ] Identify any data quality issues in submissions data
- [ ] Validate organization name consistency between sheets

### System Preparation:
- [ ] Ensure all tests are passing
- [ ] Create database backups
- [ ] Document current performance metrics
- [ ] Prepare rollback procedures

### Stakeholder Communication:
- [ ] Notify stakeholders of planned changes
- [ ] Explain expected impact on metrics (registration counts will increase)
- [ ] Plan for user training if needed
- [ ] Prepare communication for data discrepancies

## Post-Implementation Validation

### Data Validation:
- [ ] Compare new registration counts with expected submissions
- [ ] Verify organization-level data consistency
- [ ] Check for any data quality issues
- [ ] Validate date range filtering accuracy

### System Validation:
- [ ] All tests passing
- [ ] Performance within acceptable limits
- [ ] No errors in logs
- [ ] Cache files properly generated

### User Validation:
- [ ] Reports display correctly
- [ ] Data makes business sense
- [ ] No user-reported issues
- [ ] Stakeholder acceptance of new metrics 