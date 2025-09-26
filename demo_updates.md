# Demo Mirror Organization Updates - AI Agent Implementation Guide

## Overview
This document outlines the complete implementation plan for updating the demo mirror organization naming system. The goal is to ensure all demo organizations have " Demo" suffix in their names and that Google Sheets API calls append " Demo" to organization names in column J for demo enterprise data.

## Implementation Priority
**CRITICAL**: All updates must be implemented in the correct order to maintain system consistency and avoid breaking changes.

## 1. Configuration File Updates

### 1.1 Update `config/passwords.json` metadata
**File**: `config/passwords.json`
**Lines**: 3513-3524 (metadata section)

**Changes Required**:
```json
"metadata": {
    "last_updated": "2025-01-27",
    "total_organizations": 450,  // Update to reflect actual count
    "enterprises": [
        "super",
        "csu", 
        "ccc",
        "demo"
    ],
    "version": "1.1"
}
```

**AI Agent Instructions**:
- Count total organizations in the file
- Update `total_organizations` with actual count
- Update `last_updated` to current date
- Increment version to "1.1"

### 1.2 Update `config/demo.config` organizations list
**File**: `config/demo.config`
**Lines**: 7-220+ (organizations array)

**Changes Required**:
- Add " Demo" suffix to ALL organization names
- Update metadata section with new count and timestamp

**Example Transformation**:
```json
// BEFORE
"Allan Hancock College",
"Allan Hancock Joint Community College District",

// AFTER  
"Allan Hancock College Demo",
"Allan Hancock Joint Community College District Demo",
```

**AI Agent Instructions**:
- Process each organization name in the array
- Append " Demo" to each name (ensure proper spacing)
- Update metadata `total_organizations` count
- Update metadata `last_updated` timestamp

## 2. Google Sheets API Integration Updates

### 2.1 Update `reports/reports_api.php`
**Function**: `fetch_sheet_data()`
**Lines**: 139-189

**Changes Required**:
Add demo organization name transformation logic:

```php
function fetch_sheet_data($workbook_id, $sheet_name, $start_row) {
    // ... existing code ...
    
    if (!isset($data['values'])) {
        require_once __DIR__ . '/../lib/error_messages.php';
        return ['error' => ErrorMessages::getTechnicalDifficulties()];
    }

    // NEW: Transform organization names for demo enterprise
    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
    if ($enterprise_code === 'demo') {
        $data['values'] = transformDemoOrganizationNames($data['values']);
    }

    return $data['values'];
}

// NEW: Helper function to transform demo organization names
function transformDemoOrganizationNames($data) {
    foreach ($data as &$row) {
        if (isset($row[9]) && !empty($row[9])) { // Organization column (index 9, Column J)
            $orgName = trim($row[9]);
            if (!str_ends_with($orgName, ' Demo')) {
                $row[9] = $orgName . ' Demo';
            }
        }
    }
    return $data;
}
```

### 2.2 Update `reports/reports_api_internal.php`
**Lines**: 247-260 (submissions data processing)

**Changes Required**:
Apply same transformation logic to internal API:

```php
if (!$useSubCache) {
    $submissionsData = fetch_sheet_data($subWbId, $subSheet, $subStartRow);
    if (isset($submissionsData['error'])) {
        return ['error' => $submissionsData['error']];
    }

    // NEW: Transform organization names for demo enterprise
    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
    if ($enterprise_code === 'demo') {
        $submissionsData = transformDemoOrganizationNames($submissionsData);
    }

    // Ensure all data is trimmed and stringified
    $submissionsData = array_map('trim_row', $submissionsData);
    // ... rest of existing code ...
}
```

## 3. Data Processing Updates

### 3.1 Update `lib/data_processor.php`
**Function**: `processOrganizationData()`
**Lines**: 247-257

**Changes Required**:
Add demo organization name handling:

```php
public static function processOrganizationData($registrationsRows, $enrollmentsRows, $certificatesRows, $enrollmentMode = null) {
    // Use unified Dashboard Data Service for consistent data processing
    $orgData = DashboardDataService::getAllOrganizationsData();
    
    // NEW: Handle demo organization naming
    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
    
    // Apply abbreviation to organization display names
    foreach ($orgData as &$org) {
        // For demo enterprise, ensure organization names have " Demo" suffix
        if ($enterprise_code === 'demo' && !str_ends_with($org['organization'], ' Demo')) {
            $org['organization'] = $org['organization'] . ' Demo';
        }
        
        $org['organization_display'] = abbreviateOrganizationName($org['organization']);
    }
    
    return $orgData;
}
```

### 3.2 Update `lib/dashboard_data_service.php`
**Function**: `getAllOrganizationsData()`
**Lines**: 360-382

**Changes Required**:
Add demo organization name consistency check:

```php
public static function getAllOrganizationsData() {
    self::loadCache();
    
    $orgIdx = self::getColumnIndex('Organization');
    $orgCounts = [];
    
    // Get all organizations from config first
    $config = UnifiedEnterpriseConfig::getFullConfig();
    $configOrgs = $config['organizations'] ?? [];
    
    $enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
    
    foreach ($configOrgs as $orgName) {
        // NEW: For demo enterprise, ensure config orgs have " Demo" suffix
        if ($enterprise_code === 'demo' && !str_ends_with($orgName, ' Demo')) {
            $orgName = $orgName . ' Demo';
        }
        
        $orgCounts[$orgName] = [
            'organization' => $orgName,
            'organization_display' => $orgName, // Will be abbreviated by calling code if needed
            'registrations' => 0,
            'enrollments' => 0,
            'certificates' => 0
        ];
    }
    
    // Count data for each organization
    foreach (self::$registrants as $row) {
        // ... existing counting logic ...
    }
    
    return array_values($orgCounts);
}
```

## 4. Cache Management Updates

### 4.1 Clear Demo Cache Files
**Action Required**: Clear existing demo cache files to force refresh

**Files to Clear**:
- `cache/demo/all-registrants-data.json`
- `cache/demo/all-submissions-data.json`
- `cache/demo/registrations.json`
- `cache/demo/enrollments.json`
- `cache/demo/certificates.json`

**AI Agent Instructions**:
```bash
# Clear demo cache files
rm -f cache/demo/*.json
# Or create empty cache files to force refresh
```

## 5. Testing Updates

### 5.1 Update Test Files
**Files to Update**:
- `tests/enterprise_config_validation_test.php`
- `tests/integration/reports_tables_validation_test.php`
- Any other tests that validate demo organization names

**Changes Required**:
- Update test expectations to include " Demo" suffix
- Verify demo organization naming consistency
- Test Google Sheets API transformation

## 6. Implementation Checklist

### Phase 1: Configuration Updates
- [ ] Update `config/passwords.json` metadata
- [ ] Update `config/demo.config` organizations list
- [ ] Verify JSON syntax is valid

### Phase 2: API Integration Updates  
- [ ] Update `reports/reports_api.php` fetch_sheet_data function
- [ ] Update `reports/reports_api_internal.php` submissions processing
- [ ] Add helper function for organization name transformation

### Phase 3: Data Processing Updates
- [ ] Update `lib/data_processor.php` processOrganizationData method
- [ ] Update `lib/dashboard_data_service.php` getAllOrganizationsData method
- [ ] Verify organization counting logic

### Phase 4: Cache Management
- [ ] Clear existing demo cache files
- [ ] Test cache refresh with new naming

### Phase 5: Testing & Validation
- [ ] Update test files
- [ ] Run comprehensive tests
- [ ] Verify demo enterprise functionality

## 7. Validation Steps

### 7.1 Configuration Validation
```bash
# Validate JSON syntax
php -l config/passwords.json
php -l config/demo.config

# Check organization counts match
```

### 7.2 API Testing
```bash
# Test demo enterprise API calls
php tests/debug_google_api.php demo

# Verify organization names in responses
```

### 7.3 Frontend Testing
- Navigate to demo enterprise reports
- Verify organization names display with " Demo" suffix
- Check organization filtering and search functionality

## 8. Rollback Plan

If issues occur:
1. Revert configuration file changes
2. Restore original API functions
3. Clear cache files to force refresh
4. Test with original naming

## 9. Success Criteria

- [ ] All demo organizations have " Demo" suffix in config files
- [ ] Google Sheets API calls append " Demo" to organization names
- [ ] Frontend displays properly named demo organizations
- [ ] All tests pass with new naming convention
- [ ] Cache files refresh with correct organization names
- [ ] No breaking changes to non-demo enterprises

## 10. AI Agent Implementation Notes

**Critical Points**:
1. Always check enterprise code before applying transformations
2. Preserve existing functionality for CSU and CCC enterprises
3. Use consistent string checking (`str_ends_with()` for PHP 8+)
4. Clear cache files after configuration changes
5. Test thoroughly before considering implementation complete

**Error Handling**:
- Validate JSON syntax after configuration changes
- Handle edge cases where organization names might be empty
- Ensure backward compatibility with existing data

**Performance Considerations**:
- Organization name transformation is minimal overhead
- Cache clearing forces one-time refresh cost
- Long-term performance impact is negligible

---

**Implementation Status**: Ready for AI Agent execution
**Estimated Time**: 2-3 hours for complete implementation
**Risk Level**: Low (isolated to demo enterprise only)
