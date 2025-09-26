# Cache System Analysis

## 1. Current JSON Files Being Used

### **Primary Cache Files (Per Enterprise)**
Each enterprise (CSU, CCC, DEMO) has its own cache directory with identical file structure:

```
cache/{enterprise}/
├── all-registrants-data.json     # Source data from Google Sheets (Registrants)
├── all-submissions-data.json     # Source data from Google Sheets (Submissions) 
├── registrations.json            # Derived: All submissions data
├── enrollments.json              # Derived: Enrolled participants from registrants
├── certificates.json             # Derived: Certificate earners from registrants
└── refresh_debug.log             # Debug logging
```

### **Session Files (Global)**
```
cache/
├── sess_*                        # PHP session files (100+ files)
```

### **File Usage Patterns**

#### **Source Files (Google Sheets Data)**
- **`all-registrants-data.json`**: Raw data from Google Sheets "Registrants" tab
  - Contains: Invitations, enrollments, certificates, participant details
  - Structure: `{"data": [...], "global_timestamp": "..."}`
  - Used by: Dashboard, Organizations API, Data Processing

- **`all-submissions-data.json`**: Raw data from Google Sheets "Submissions" tab  
  - Contains: Registration submissions, cohort data
  - Structure: `{"data": [...], "global_timestamp": "..."}`
  - Used by: Reports API, Registration processing

#### **Derived Files (Generated from Source)**
- **`registrations.json`**: All submissions data (no date filtering)
  - Generated from: `all-submissions-data.json`
  - Used by: Reports, Registration data endpoints

- **`enrollments.json`**: Enrolled participants only
  - Generated from: `all-registrants-data.json` (filtered by enrollment status)
  - Used by: Reports, Enrollment data endpoints

- **`certificates.json`**: Certificate earners only
  - Generated from: `all-registrants-data.json` (filtered by certificate status)
  - Used by: Reports, Certificate data endpoints

## 2. Architecture Evaluation for Streamlined Processes

### **Current Architecture Issues**

#### **❌ Redundant Data Storage**
- **Problem**: Same data stored in multiple formats
  - `all-submissions-data.json` → `registrations.json` (identical data)
  - `all-registrants-data.json` → `enrollments.json` + `certificates.json` (filtered views)
- **Impact**: 3x storage overhead, cache invalidation complexity

#### **❌ Inconsistent Cache Management**
- **Problem**: Multiple cache generation patterns
  - Some files use `EnterpriseCacheManager::writeCacheFile()`
  - Others use direct `file_put_contents()`
  - Inconsistent error handling and locking

#### **❌ Complex Data Flow**
- **Problem**: Circular dependencies and multiple data paths
  - APIs generate derived files that other APIs consume
  - No clear separation between source and derived data
  - Demo transformation applied inconsistently

#### **❌ Cache Invalidation Issues**
- **Problem**: No clear cache invalidation strategy
  - Derived files not updated when source files change
  - Manual cache clearing required for consistency
  - No cache versioning or dependency tracking

### **Recommended Architecture Changes**

#### **✅ Simplified Cache Structure**
```
cache/{enterprise}/
├── source/
│   ├── registrants.json          # Raw Google Sheets data
│   └── submissions.json          # Raw Google Sheets data
├── processed/
│   ├── registrations.json        # Date-filtered submissions
│   ├── enrollments.json          # Date-filtered enrollments  
│   └── certificates.json         # Date-filtered certificates
└── metadata/
    ├── cache_version.json        # Cache versioning
    └── last_refresh.json         # Refresh timestamps
```

#### **✅ Unified Cache Manager**
- Single `CacheManager` class with consistent methods
- Automatic cache invalidation and dependency tracking
- Enterprise-specific isolation with shared utilities

#### **✅ Lazy Loading with Smart Caching**
- Generate derived files only when requested
- Cache computed results with TTL
- Invalidate dependent caches automatically

## 3. DRY Principles Assessment

### **❌ Code Duplication Issues**

#### **Demo Transformation Duplication**
- **Problem**: `transformDemoOrganizationNames()` function duplicated in 7 files
- **Files**: `reports_api.php`, `reports_api_internal.php`, `enrollments_data.php`, `registrations_data.php`, `certificates_data.php`
- **Impact**: Maintenance nightmare, inconsistent behavior

#### **Cache Reading Duplication**
- **Problem**: Similar cache reading patterns across multiple files
- **Pattern**: `$cacheManager->readCacheFile()` + `isset($json['data']) ? $json['data'] : []`
- **Files**: All data processing files

#### **Data Processing Duplication**
- **Problem**: Similar data filtering and processing logic
- **Examples**: 
  - Certificate filtering: `if ($certificate === 'Yes')`
  - Enrollment filtering: `if ($enrolled === 'Yes')`
  - Date range filtering: Multiple implementations

#### **Column Index Duplication**
- **Problem**: Hardcoded column indices repeated across files
- **Examples**: `$idxRegCertificate = 10`, `$idxRegEnrolled = 2`
- **Files**: Multiple processing files

### **✅ DRY Improvements Needed**

#### **Centralized Transformation Service**
```php
class DemoTransformationService {
    public static function transformOrganizationNames($data) {
        // Single implementation
    }
    
    public static function shouldTransform() {
        return UnifiedEnterpriseConfig::getEnterpriseCode() === 'demo';
    }
}
```

#### **Unified Data Processing**
```php
class CacheDataProcessor {
    public static function loadCacheData($filename) {
        // Single cache loading pattern
    }
    
    public static function filterByColumn($data, $columnIndex, $value) {
        // Single filtering implementation
    }
}
```

#### **Column Index Constants**
```php
class GoogleSheetsColumns {
    const REGISTRANTS = [
        'ENROLLED' => 2,
        'CERTIFICATE' => 10,
        'ORGANIZATION' => 9,
        // ...
    ];
}
```

## 4. Recommendations

### **Immediate Actions (High Priority)**

1. **Consolidate Demo Transformation**
   - Create single `DemoTransformationService` class
   - Remove duplicate functions from all files
   - Apply transformation at cache manager level

2. **Standardize Cache Operations**
   - Use `EnterpriseCacheManager` for all cache operations
   - Remove direct `file_put_contents()` calls
   - Implement consistent error handling

3. **Eliminate Redundant Files**
   - Remove `registrations.json` (use `all-submissions-data.json` directly)
   - Generate `enrollments.json` and `certificates.json` on-demand
   - Implement smart caching with TTL

### **Medium-term Improvements**

1. **Implement Cache Versioning**
   - Add cache version metadata
   - Automatic cache invalidation
   - Dependency tracking between files

2. **Create Unified Data Service**
   - Single service for all data operations
   - Consistent data transformation
   - Centralized column index management

3. **Optimize Storage**
   - Compress large cache files
   - Implement cache compression
   - Add cache size monitoring

### **Long-term Architecture**

1. **Move to Database Caching**
   - Replace file-based cache with database
   - Implement proper indexing
   - Add cache query optimization

2. **Implement Cache Warming**
   - Pre-generate commonly used data
   - Background cache refresh
   - Predictive cache loading

## 5. Impact Assessment

### **Current Issues**
- **Storage**: ~3x overhead due to redundant files
- **Maintenance**: 7 duplicate transformation functions
- **Performance**: Multiple file I/O operations
- **Reliability**: Inconsistent cache management

### **After Improvements**
- **Storage**: ~60% reduction in cache file size
- **Maintenance**: Single source of truth for transformations
- **Performance**: Reduced I/O, faster data access
- **Reliability**: Consistent cache behavior across all endpoints

## 6. Detailed Phased Implementation Plan

### **Phase 1: Create DRY Foundation Services**

#### **1.1 Create GoogleSheetsColumns Class**
- **File**: `lib/google_sheets_columns.php`
- **Purpose**: Centralize all column index constants
- **Implementation**: Define constants for all Google Sheets column mappings

#### **1.2 Create DemoTransformationService Class**
- **File**: `lib/demo_transformation_service.php`
- **Purpose**: Single source of truth for demo organization name transformation
- **Implementation**: Consolidate all demo transformation logic

#### **1.3 Create CacheDataLoader Service**
- **File**: `lib/cache_data_loader.php`
- **Purpose**: Centralized data loading with automatic demo transformation
- **Implementation**: Unified cache loading with consistent transformation

#### **1.4 Enhance DataProcessor with DRY Methods**
- **File**: `lib/data_processor.php`
- **Purpose**: Add centralized filtering methods
- **New Methods**: `filterCertificates()`, `filterEnrollments()`, enhanced date processing

### **Phase 2: Update Core API Files**

#### **2.1 Update reports_api.php**
- **Remove**: All duplicate `transformDemoOrganizationNames()` functions
- **Remove**: Derived cache file generation
- **Remove**: Hardcoded column indices
- **Replace**: Cache loading with `CacheDataLoader` methods
- **Replace**: Certificate filtering with `DataProcessor::filterCertificates()`
- **Replace**: Enrollment processing with `DataProcessor::filterEnrollments()`

#### **2.2 Update reports_api_internal.php**
- **Remove**: All duplicate `transformDemoOrganizationNames()` functions
- **Remove**: Derived cache file generation
- **Remove**: Hardcoded column indices
- **Replace**: Cache loading with `CacheDataLoader` methods
- **Replace**: All filtering logic with DRY methods

#### **2.3 Update Individual Data Files**
- **Files**: `reports/certificates_data.php`, `reports/enrollments_data.php`, `reports/registrations_data.php`
- **Remove**: All duplicate functions and hardcoded indices
- **Replace**: All logic with DRY methods

### **Phase 3: Update Supporting Files**

#### **3.1 Update Dashboard Data Service**
- **Replace**: Hardcoded column indices with `GoogleSheetsColumns` constants
- **Replace**: Certificate filtering with `DataProcessor::filterCertificates()`
- **Replace**: Cache loading with `CacheDataLoader` methods

#### **3.2 Update Organizations API**
- **Replace**: Hardcoded column indices with `GoogleSheetsColumns` constants
- **Replace**: Cache loading with `CacheDataLoader` methods
- **Remove**: Duplicate `inRange()` method

#### **3.3 Update Enterprise Data Service**
- **Remove**: Derived cache file generation methods
- **Replace**: All filtering logic with DRY methods
- **Replace**: Hardcoded column indices with `GoogleSheetsColumns` constants

### **Phase 4: Clean Up Cache Management**

#### **4.1 Update Enterprise Cache Manager**
- **Remove**: Methods for derived cache files
- **Update**: `clearAllCache()` to only clear source files
- **Update**: `getCacheFileInfo()` to only handle source files

#### **4.2 Delete Derived Cache Files**
- **Remove**: All `cache/*/registrations.json` files
- **Remove**: All `cache/*/enrollments.json` files
- **Remove**: All `cache/*/certificates.json` files
- **Keep**: Only `all-registrants-data.json` and `all-submissions-data.json`

#### **4.3 Update Cache Directory Structure**
- **Verify**: Only source files remain in cache directories
- **Clean**: Any orphaned derived cache files
- **Document**: New simplified cache structure

### **Phase 5: Update Test Files**

#### **5.1 Update Test Files with DRY Methods**
- **Files**: All test files in `tests/` directory
- **Replace**: Duplicate functions with DRY methods
- **Replace**: Hardcoded column indices with `GoogleSheetsColumns` constants
- **Replace**: Cache loading with `CacheDataLoader` methods

#### **5.2 Update Test Data Generation**
- **Remove**: Tests that depend on derived cache files
- **Update**: Tests to work with on-demand data generation
- **Add**: Tests for new DRY methods

### **Phase 6: Documentation and Validation**

#### **6.1 Update Documentation**
- **Update**: `cache-system-analysis.md` with new architecture
- **Update**: `dry-patterns-analysis.md` with implementation results
- **Create**: `cache-architecture.md` documenting new simplified structure
- **Update**: API documentation to reflect on-demand processing

#### **6.2 Comprehensive Testing**
- **Run**: All existing tests to ensure no regressions
- **Test**: Demo transformation across all endpoints
- **Test**: Date range filtering with new DRY methods
- **Test**: Certificate and enrollment processing
- **Test**: Cache loading and data consistency

#### **6.3 Performance Validation**
- **Measure**: Cache file size reduction
- **Measure**: Data processing performance
- **Measure**: Memory usage improvements
- **Validate**: No performance regressions

### **Phase 7: Final Cleanup**

#### **7.1 Remove Dead Code**
- **Search**: For any remaining references to derived cache files
- **Remove**: Unused imports and includes
- **Clean**: Any remaining duplicate functions
- **Verify**: No orphaned code references

#### **7.2 Code Review and Optimization**
- **Review**: All modified files for consistency
- **Optimize**: Any remaining performance bottlenecks
- **Validate**: DRY principles are properly implemented
- **Ensure**: Error handling is consistent across all files

#### **7.3 Deployment Preparation**
- **Create**: Migration script to clean up existing derived cache files
- **Document**: Deployment steps and rollback procedures
- **Prepare**: Monitoring for cache system health
- **Validate**: All enterprises (CSU, CCC, DEMO) work correctly

### **Success Criteria**

#### **Code Quality Metrics**
- **Zero duplicate functions** across the codebase
- **Single source of truth** for all data processing logic
- **Consistent error handling** across all cache operations
- **Unified column index management**

#### **Performance Metrics**
- **60% reduction** in cache file storage
- **Elimination** of derived cache file I/O operations
- **Faster data processing** through direct source access
- **Reduced memory usage** from eliminated data duplication

#### **Reliability Metrics**
- **Fixed enrollment processing bug** (proper date checking)
- **Consistent demo transformation** across all endpoints
- **Eliminated cache invalidation issues**
- **Improved data consistency** across all data views

#### **Maintainability Metrics**
- **Single point of change** for all data processing logic
- **Simplified cache management** with only source files
- **Reduced code complexity** through DRY implementation
- **Easier debugging** with centralized data processing
