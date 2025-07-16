# Registrations Refactor Documentation

## Overview
This document identifies all files and code currently used to return values for the **Registration column** on the reports page. The registration logic is a critical component that determines how registrations are counted and displayed across the system.

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

## Testing

### Test Files:
- `tests/systemwide_table_fix_test.php` - Validates systemwide table data structure
- `tests/integration/javascript_simulation_test.php` - Simulates JavaScript data processing
- `tests/integration/simple_reports_validation_test.php` - Validates reports data processing
- `tests/test_organization_processing.php` - Tests organization-level data processing

### Key Test Validations:
1. API returns registrations as arrays
2. JavaScript correctly counts array lengths
3. Data consistency across date ranges
4. Logical relationships (enrollments ≤ registrations, certificates ≤ enrollments)

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

# Proposed Refactoring: Using Submissions for Registration Logic

## Overview
This section documents the proposed refactoring to:
1. Replace the current Registration logic with Submissions logic for counting registrations
2. Preserve the existing Registration logic as "Invitations" for future use

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

## Expected Outcomes

### Data Changes:
- Registration counts will likely increase (submissions include duplicates, previous cohort members, etc.)
- Organization-level data may show different patterns
- Date range filtering will use submission dates instead of invitation dates

### System Behavior:
- All existing functionality preserved
- New registration logic uses submissions data
- Invitations logic available for future use
- API responses include both data types

### Future Considerations:
- Data cleanup logic can be added to submissions processing
- Invitations logic preserved for potential future use cases
- Clear separation between invitation and registration concepts

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