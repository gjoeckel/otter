# DRY Patterns Analysis for Cache Elimination

## **Identified DRY Code Patterns**

### **1. Date Range Filtering (Most Critical)**

**❌ Current Duplication:**
- `in_range()` function duplicated in **6 files**:
  - `reports/certificates_data.php`
  - `reports/enrollments_data.php` 
  - `reports/registrations_data.php`
  - `tests/root_tests/certificate_tests.php`
  - `tests/test_certificates_page.php`

**✅ DRY Solution:**
- **`DataProcessor::inRange()`** already exists and is used in `lib/data_processor.php`
- **`CacheUtils::inRange()`** also exists in `lib/cache_utils.php`
- **`OrganizationsAPI::inRange()`** exists in `lib/api/organizations_api.php`

**Recommendation:** Use `DataProcessor::inRange()` as the single source of truth.

### **2. Certificate Filtering Logic**

**❌ Current Duplication:**
```php
// Pattern repeated in 8+ files:
if ($certificate === 'Yes') {
    // Process certificate
}
```

**Files with this pattern:**
- `reports/certificates_data.php` (lines 79, 85)
- `reports/reports_api.php` (line 372)
- `reports/reports_api_internal.php` (line 346)
- `lib/dashboard_data_service.php` (lines 145, 200, 307, 412)
- `lib/data_processor.php` (line 71)
- `lib/enterprise_data_service.php` (line 302)
- Multiple test files

**✅ DRY Solution:**
Create a centralized certificate filtering method in `DataProcessor`:

```php
public static function filterCertificates($data, $start = null, $end = null) {
    $certificateIdx = 10; // Column K
    $issuedIdx = 11;      // Column L
    
    return array_filter($data, function($row) use ($start, $end, $certificateIdx, $issuedIdx) {
        $certificate = $row[$certificateIdx] ?? '';
        if ($certificate !== 'Yes') return false;
        
        // If date range specified, filter by issued date
        if ($start && $end) {
            $issuedDate = $row[$issuedIdx] ?? '';
            return self::isValidMMDDYY($issuedDate) && self::inRange($issuedDate, $start, $end);
        }
        
        return true; // All certificates if no date range
    });
}
```

### **3. Enrollment Filtering Logic**

**❌ Current Duplication:**
```php
// Pattern repeated in multiple files:
$enrolled = isset($row[$idxRegEnrolled]) ? $row[$idxRegEnrolled] : '';
if ($enrolled === 'Yes') { // ❌ WRONG! Should check for date or non-empty
    // Process enrollment
}
```

**✅ DRY Solution:**
Create a centralized enrollment filtering method:

```php
public static function filterEnrollments($data, $start = null, $end = null, $mode = 'tou_completion') {
    $enrolledIdx = 2;     // Column C
    $submittedIdx = 15;   // Column P
    
    return array_filter($data, function($row) use ($start, $end, $enrolledIdx, $submittedIdx, $mode) {
        $enrolled = $row[$enrolledIdx] ?? '';
        
        // Check if enrolled (has date, not "-")
        if ($enrolled === '-' || empty($enrolled)) return false;
        
        // If date range specified, filter by appropriate date
        if ($start && $end) {
            if ($mode === 'registration_date') {
                $submitted = $row[$submittedIdx] ?? '';
                return self::isValidMMDDYY($submitted) && self::inRange($submitted, $start, $end);
            } else { // tou_completion
                return self::isValidMMDDYY($enrolled) && self::inRange($enrolled, $start, $end);
            }
        }
        
        return true; // All enrollments if no date range
    });
}
```

### **4. Column Index Constants**

**❌ Current Duplication:**
```php
// Hardcoded indices repeated everywhere:
$idxRegEnrolled = 2;      // Google Sheets Column C
$idxRegCertificate = 10;  // Google Sheets Column K
$idxRegIssued = 11;       // Google Sheets Column L
$submittedIdx = 15;       // Google Sheets Column P
```

**✅ DRY Solution:**
Create a centralized column index class:

```php
class GoogleSheetsColumns {
    // Registrants sheet columns
    const REGISTRANTS = [
        'DAYS_TO_CLOSE' => 0,    // Column A
        'INVITED' => 1,          // Column B  
        'ENROLLED' => 2,         // Column C
        'COHORT' => 3,           // Column D
        'YEAR' => 4,             // Column E
        'FIRST' => 5,            // Column F
        'LAST' => 6,             // Column G
        'EMAIL' => 7,            // Column H
        'ROLE' => 8,             // Column I
        'ORGANIZATION' => 9,     // Column J
        'CERTIFICATE' => 10,     // Column K
        'ISSUED' => 11,          // Column L
        'CLOSING_DATE' => 12,    // Column M
        'COMPLETED' => 13,       // Column N
        'ID' => 14,              // Column O
        'SUBMITTED' => 15,       // Column P
        'STATUS' => 16           // Column Q
    ];
    
    // Submissions sheet columns (if different)
    const SUBMISSIONS = [
        // Define as needed
    ];
}
```

### **5. Data Loading and Transformation**

**❌ Current Duplication:**
```php
// Pattern repeated in 7+ files:
$cacheManager = EnterpriseCacheManager::getInstance();
$registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
$registrantsData = $registrantsCache['data'] ?? [];

// Transform organization names for demo enterprise
$enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();
if ($enterprise_code === 'demo') {
    $registrantsData = transformDemoOrganizationNames($registrantsData);
}
```

**✅ DRY Solution:**
Create a centralized data loading service:

```php
class CacheDataLoader {
    public static function loadRegistrantsData() {
        $cacheManager = EnterpriseCacheManager::getInstance();
        $cache = $cacheManager->readCacheFile('all-registrants-data.json');
        $data = $cache['data'] ?? [];
        
        // Apply demo transformation if needed
        if (UnifiedEnterpriseConfig::getEnterpriseCode() === 'demo') {
            $data = DemoTransformationService::transformOrganizationNames($data);
        }
        
        return $data;
    }
    
    public static function loadSubmissionsData() {
        $cacheManager = EnterpriseCacheManager::getInstance();
        $cache = $cacheManager->readCacheFile('all-submissions-data.json');
        $data = $cache['data'] ?? [];
        
        // Apply demo transformation if needed
        if (UnifiedEnterpriseConfig::getEnterpriseCode() === 'demo') {
            $data = DemoTransformationService::transformOrganizationNames($data);
        }
        
        return $data;
    }
}
```

## **Implementation Strategy**

### **Phase 1: Create DRY Services (1-2 days)**

1. **Create `GoogleSheetsColumns` class**
2. **Create `DemoTransformationService` class** 
3. **Create `CacheDataLoader` class**
4. **Add filtering methods to `DataProcessor`**

### **Phase 2: Update Data Processing (2-3 days)**

1. **Update `reports_api.php`** to use DRY services
2. **Update `reports_api_internal.php`** to use DRY services
3. **Update individual data files** (`certificates_data.php`, `enrollments_data.php`, `registrations_data.php`)
4. **Remove derived cache file generation**

### **Phase 3: Clean Up (1 day)**

1. **Remove duplicate functions** from all files
2. **Delete derived cache files** (`enrollments.json`, `registrations.json`, `certificates.json`)
3. **Update cache manager** to remove derived file methods
4. **Run comprehensive tests**

## **Benefits of DRY Implementation**

### **Code Reduction:**
- **Eliminate 6 duplicate `in_range()` functions**
- **Eliminate 8+ duplicate certificate filtering patterns**
- **Eliminate 7+ duplicate data loading patterns**
- **Eliminate hardcoded column indices everywhere**

### **Maintenance Benefits:**
- **Single source of truth** for all data processing logic
- **Consistent behavior** across all endpoints
- **Easier debugging** and testing
- **Simplified cache management**

### **Performance Benefits:**
- **Eliminate 3x storage overhead** from derived cache files
- **Reduce I/O operations** (no more writing derived files)
- **Faster data processing** (direct from source)
- **Better memory usage** (no duplicate data in memory)

### **Reliability Benefits:**
- **Fix enrollment bug** (proper date checking instead of "Yes" string)
- **Consistent demo transformation** across all data
- **Eliminate cache invalidation issues**
- **Reduce data inconsistency risks**

## **Estimated Impact**

- **Files to modify**: ~15 files
- **Lines of code to remove**: ~200+ lines
- **Storage reduction**: ~60% (eliminate derived cache files)
- **Maintenance complexity**: ~70% reduction
- **Bug fixes**: Enrollment processing bug resolved

**Total estimated effort**: 4-6 days for complete DRY implementation and cache elimination.
