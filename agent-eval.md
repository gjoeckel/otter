# Otter Project Architecture Evaluation

**Evaluator**: Claude (AI Assistant)  
**Date**: September 23, 2025  
**Project**: Multi-Enterprise Web Application (Otter)  
**Scope**: Comprehensive code architecture analysis

## Executive Summary

The Otter project is a well-architected multi-enterprise web application built with PHP and JavaScript. It demonstrates strong engineering practices, comprehensive testing, and clear separation of concerns. The architecture supports California State University (CSU), California Community Colleges (CCC), and Demo environments with a unified codebase.

**Overall Architecture Quality: 8.5/10**

### Key Strengths
- Multi-enterprise architecture with clean isolation
- Configuration-driven design
- Comprehensive testing framework (100% pass rates)
- Modern development practices with automated builds
- WCAG 2.1 AA compliance
- JSON-based storage without MySQL dependencies

### Key Areas for Improvement
- API function duplication (documented but architectural debt)
- Potential for code splitting in frontend bundles
- Opportunity for service layer pattern implementation

## Data Flow Patterns Analysis

### 1. Reports System Data Flow (Primary Pattern)

The reports system demonstrates a sophisticated unified data flow pattern that replaced a previous fragmented approach:

#### Architecture Components
```
User Interaction → reports-data.js → unified-data-service.js → reports_api.php
                                                           ↓
HTML Tables ← unified-table-updater.js ← JSON Response ← Data Processing
```

#### Detailed Flow Analysis

**Stage 1: User Interaction Capture**
- Event listeners in `reports-data.js` capture user actions (date changes, display mode toggles)
- `getCurrentModes()` function determines selected display modes
- Debouncing mechanism (200ms) prevents race conditions from rapid user input

**Stage 2: Data Service Orchestration**
- `ReportsDataService` in `unified-data-service.js` acts as central data coordinator
- Single API call to `reports_api.php` with consolidated parameters
- Manages application state (current date range, selected modes)
- Implements retry logic and error handling

**Stage 3: Backend Data Processing**
- `reports_api.php` serves as unified API endpoint
- Fetches from Google Sheets with intelligent caching
- Processes into six distinct datasets:
  - `registrations_submissions` (by submission date)
  - `registrations_cohort` (by cohort/year)
  - `submissions_enrollments_tou` (ToU completion date)
  - `submissions_enrollments_registrations` (registration date)
  - `cohort_enrollments_tou` (cohort + ToU date)
  - `cohort_enrollments_registrations` (cohort + registration date)

**Stage 4: UI Rendering**
- `UnifiedTableUpdater` class renders data into HTML tables
- Supports multiple display modes without additional API calls
- Updates system-wide, organizations, and groups tables simultaneously

#### Pattern Strengths
1. **Single Source of Truth**: One API call generates all required datasets
2. **Race Condition Prevention**: Debouncing eliminates conflicting requests
3. **Performance Optimization**: Reduced from multiple parallel API calls to one
4. **State Management**: Centralized application state handling

#### Pattern Weaknesses
1. **Large Payload**: Single API response contains all datasets (potential over-fetching)
2. **Coupling**: Frontend tightly coupled to specific backend data structure
3. **Cache Invalidation**: Complex caching strategy with 6-hour TTL

### 2. Enterprise Data Flow Pattern

#### Multi-Enterprise Detection Flow
```
URL/Session/Password → UnifiedEnterpriseConfig → Enterprise Detection → Configuration Loading
```

**Detection Priority:**
1. URL Parameter (`?ent=csu`)
2. Session (`$_SESSION['enterprise_code']`)
3. Organization Password (4-digit lookup)
4. Default fallback (`csu`)

**Configuration Loading:**
- Enterprise-specific config files (`config/{enterprise}.config`)
- JSON-based configuration with validation
- Features detection (`supportsGroups()`, `supportsQuarterlyPresets()`)

#### Cache Management Flow
```
Request → Cache Check → [Fresh: Return Cache] OR [Stale: Fetch → Process → Cache → Return]
```

**Cache Strategy:**
- Enterprise-specific cache directories (`cache/{enterprise}/`)
- Hierarchical caching with TTL management
- Manual refresh capabilities through admin interface
- Automatic cleanup of old session files

### 3. Authentication Data Flow

#### Login Process Flow
```
login.php → Password Validation → Enterprise Detection → Session Creation → Redirect
```

**Authentication Patterns:**
- Unified login for all enterprises
- Password-based organization identification
- Session-based state management
- Secure session handling with enterprise context

### 4. Dashboard Data Flow

#### Organization Dashboard Pattern
```
dashboard.php → Organization Data → Cache Check → Display Preparation → HTML Rendering
```

**Data Loading:**
- Organization-specific data retrieval
- Real-time data with 3-hour TTL
- Loading overlay during data operations
- Auto-refresh capabilities

### 5. API Architecture Patterns

#### External vs Internal API Pattern
The project implements a deliberate separation to prevent output buffering conflicts:

**External APIs** (`reports_api.php`):
- JSON endpoints for browser AJAX requests
- Set `Content-Type: application/json` headers
- Use output buffering for clean JSON output
- Always output JSON and exit

**Internal APIs** (`reports_api_internal.php`):
- Called by PHP via `require_once` includes
- NO HTTP headers (prevents "headers already sent" errors)
- NO output buffering (prevents JSON corruption)
- Return data arrays instead of outputting JSON

**Critical Issue Identified:**
This pattern results in significant code duplication. While documented as necessary for race condition prevention, it represents technical debt that could be addressed through a service layer pattern.

### 6. Error Handling Data Flow

#### Centralized Error Management
```
Error Detection → ErrorMessages Class → Centralized Response → User Feedback
```

**Error Patterns:**
- Centralized error messages in `lib/error_messages.php`
- Consistent error handling across all components
- Graceful degradation for API failures
- User-friendly error messaging

## Data Flow Recommendations

### High Priority
1. **Implement Service Layer Pattern** to eliminate API duplication
2. **Add GraphQL endpoint** for more efficient data fetching
3. **Implement proper event sourcing** for better state management

### Medium Priority
1. **Add data validation layer** between API and database
2. **Implement circuit breaker pattern** for external API calls
3. **Add comprehensive logging** throughout data flow

### Low Priority
1. **Consider implementing CQRS pattern** for read/write separation
2. **Add real-time data updates** via WebSockets
3. **Implement data streaming** for large datasets

## Technical Debt in Data Flow

### Documented Technical Debt
- API function duplication (justified but architectural debt)
- Complex date range handling scattered across multiple files
- Mixed terminal usage patterns in development workflow

### Hidden Technical Debt
- Large JSON payloads in unified API responses
- Tight coupling between frontend and backend data structures
- Complex caching invalidation logic

## Critical Data Flow Issues Identified

### 1. API Duplication Anti-Pattern (High Priority)

**Current State**: `reports_api.php` and `reports_api_internal.php` contain duplicated logic
**Root Cause**: Output buffering conflicts between HTTP responses and PHP includes
**Impact**: Maintenance burden, violation of DRY principle

**Recommended Solution**:
```php
class ReportsService {
    public function processReportsData($params) {
        // Shared business logic
        return $this->dataProcessor->process($params);
    }
}

class HttpReportsController {
    public function handleApiRequest() {
        $service = new ReportsService();
        $data = $service->processReportsData($_GET);
        
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}

class InternalReportsGateway {
    public function getReportsData($params) {
        $service = new ReportsService();
        return $service->processReportsData($params);
    }
}
```

### 2. Over-Fetching in Unified Data Pattern (Medium Priority)

**Current State**: Single API call generates 6 datasets regardless of need
**Impact**: Unnecessary data processing and bandwidth usage
**Recommendation**: Implement selective data fetching based on user context

### 3. Enterprise Detection Security Concern (Medium Priority)

**Issue**: Password-based enterprise detection exposes system structure
**Current Flow**: Password → Organization lookup → Enterprise inference
**Security Risk**: Enumeration attacks possible
**Recommendation**: Direct enterprise authentication or encrypted tokens

### 4. Cache Invalidation Strategy Gap (Low Priority)

**Current State**: Time-based TTL only
**Missing**: Event-driven invalidation, dependency tracking
**Impact**: Stale data potential during high-change periods

## Performance Analysis

### Bottlenecks Identified

1. **Single Point of Failure**: `reports_api.php` processes all report requests
2. **Synchronous Processing**: No async data fetching capabilities
3. **Large JSON Payloads**: 6 datasets returned regardless of usage
4. **Google Sheets API**: External dependency for all data operations

### Scalability Concerns

1. **Cache Storage**: No apparent size limits or cleanup strategies
2. **Concurrent Users**: No rate limiting or request queuing
3. **Enterprise Isolation**: Shared resources across enterprises

## Security Assessment

### Strengths
- Session management with enterprise context
- Input validation and sanitization
- WCAG compliance implementation
- Centralized error handling prevents information disclosure

### Vulnerabilities
- Enterprise enumeration through password attempts
- No apparent rate limiting on authentication
- Cache files stored in predictable locations
- Session management complexity across enterprises

## Maintainability Score

**Current Score: 7.5/10**

**Strengths** (+2.5):
- Comprehensive testing (100% pass rates)
- Excellent documentation
- Recent DRY improvements (session management, error handling)
- Clear separation of concerns

**Weaknesses** (-2.5):
- API duplication technical debt
- Complex enterprise detection logic
- Mixed architectural patterns
- Large monolithic API responses

## Conclusion

The data flow patterns in the Otter project demonstrate mature architectural thinking with clear separation of concerns and well-documented design decisions. The unified data service pattern is particularly well-implemented, showing significant improvement over previous iterations. 

However, the API duplication pattern represents significant technical debt that should be addressed. The codebase shows evidence of continuous improvement with recent DRY implementations and comprehensive testing, indicating a healthy development culture.

## DRY Principle Violations Analysis

### Critical DRY Violations

**1. API Function Duplication (High Impact)**
Location: `reports_api.php` vs `reports_api_internal.php`
```php
// Duplicated functions with function_exists() guards
function trim_row() { /* identical logic */ }
function isCohortYearInRange() { /* identical logic */ }
function fetch_sheet_data() { /* identical logic */ }
```
**Impact**: 3 core functions duplicated, maintenance burden
**Fix Priority**: Immediate - extract to shared service class

**2. Session Management Pattern (RESOLVED)**
The changelog shows this was addressed through universal session management:
```php
// Before: 50+ instances of
if (session_status() === PHP_SESSION_NONE) session_start();

// After: Centralized
require_once __DIR__ . '/lib/session.php';
initializeSession();
```
**Status**: ✅ Successfully refactored - good example of DRY improvement

**3. Output Buffering Pattern (RESOLVED)**
Similarly addressed through centralized utility:
```php
// Before: 20+ instances of ob_start(); header(); patterns
// After: Centralized in lib/output_buffer.php
startJsonResponse();
sendJsonResponse($data);
```
**Status**: ✅ Successfully refactored

### Simplicity Violations

**1. Complex Enterprise Detection Logic**
```php
// Current: 4-step fallback chain
$enterprise = detectFromUrl() ?? 
              detectFromSession() ?? 
              detectFromPassword() ?? 
              'csu'; // hardcoded fallback
```
**Simplification**: Direct enterprise parameter required, no fallbacks
**Benefit**: Eliminates ambiguity and security issues

**2. Over-Engineered Reports Data Flow**
```javascript
// Current: Single API returns 6 datasets
{
  registrations_submissions: [...],
  registrations_cohort: [...],
  submissions_enrollments_tou: [...],
  submissions_enrollments_registrations: [...],
  cohort_enrollments_tou: [...],
  cohort_enrollments_registrations: [...]
}
```
**Simplification**: Generate only requested dataset
**Benefit**: Reduced payload size, clearer intent

### Reliability Issues

**1. Hardcoded Fallback Values**
Multiple instances of hardcoded 'csu' defaults violate multi-enterprise principle
**Risk**: Silent failures, incorrect data routing
**Fix**: Explicit error handling instead of fallbacks

**2. Complex Cache TTL Logic**
6-hour TTL with manual refresh creates complexity
**Simplification**: Event-driven cache invalidation
**Benefit**: Fresher data, simpler logic

**3. Mixed Error Handling Patterns**
Despite centralized ErrorMessages class, some files still use custom error handling
**Fix**: Audit remaining custom error patterns

## Immediate DRY Improvement Opportunities

### High-Impact, Low-Risk Fixes

**1. Extract Shared Validation Logic**
```php
// Currently scattered across files
class InputValidator {
    public static function validateEnterpriseCode($code) {
        return preg_match('/^[a-z]{3,4}$/', $code);
    }
    
    public static function validateDateRange($start, $end) {
        // Centralized date validation
    }
    
    public static function validatePassword($password) {
        return preg_match('/^\d{4}$/', $password);
    }
}
```

**2. Consolidate Database Connection Logic**
Multiple files create database connections independently
```php
// Extract to DatabaseFactory
class DatabaseFactory {
    public static function createConnection($enterprise) {
        // Centralized connection logic
    }
}
```

**3. Standardize API Response Format**
```php
class ApiResponse {
    public static function success($data) {
        return ['status' => 'success', 'data' => $data];
    }
    
    public static function error($message) {
        return ['status' => 'error', 'message' => $message];
    }
}
```

### Medium-Impact Improvements

**1. Extract Enterprise Configuration Logic**
```php
class EnterpriseConfigLoader {
    private static $configs = [];
    
    public static function load($enterprise) {
        if (!isset(self::$configs[$enterprise])) {
            self::$configs[$enterprise] = json_decode(
                file_get_contents("config/{$enterprise}.config"), 
                true
            );
        }
        return self::$configs[$enterprise];
    }
}
```

**2. Centralize File Path Management**
```php
class PathResolver {
    public static function getConfigPath($enterprise) {
        return "config/{$enterprise}.config";
    }
    
    public static function getCachePath($enterprise) {
        return "cache/{$enterprise}/";
    }
}
```

## Simplicity Improvement Roadmap

### Phase 1: Eliminate API Duplication (1-2 weeks)
1. Extract shared functions to service class
2. Update both API files to use service
3. Add tests for service class
4. Gradually remove duplicated functions

### Phase 2: Simplify Enterprise Detection (1 week)
1. Remove hardcoded fallbacks
2. Require explicit enterprise parameter
3. Add proper error handling
4. Update all entry points

### Phase 3: Streamline Data Flow (2-3 weeks)
1. Implement selective data fetching
2. Reduce API payload sizes
3. Simplify frontend data handling
4. Remove unused datasets

## Detailed Implementation Plans with Code Samples

### 1. API Function Duplication Elimination (High Priority)

**Current Problem**: Three functions duplicated between `reports_api.php` and `reports_api_internal.php`

**Step 1: Create Shared Service Class**
Create `lib/reports_service.php`:

```php
<?php
require_once __DIR__ . '/data_processor.php';
require_once __DIR__ . '/enterprise_data_service.php';

class ReportsService {
    private $dataProcessor;
    private $enterpriseDataService;
    
    public function __construct() {
        $this->dataProcessor = new DataProcessor();
        $this->enterpriseDataService = new EnterpriseDataService();
    }
    
    public function trimRow($row) {
        // Move logic from duplicated trim_row() function
        return array_map('trim', $row);
    }
    
    public function isCohortYearInRange($cohort, $year, $startDate, $endDate) {
        // Move logic from duplicated isCohortYearInRange() function
        $cohortDate = DateTime::createFromFormat('m-y', $cohort . '-' . $year);
        $start = DateTime::createFromFormat('m-d-y', $startDate);
        $end = DateTime::createFromFormat('m-d-y', $endDate);
        
        return $cohortDate >= $start && $cohortDate <= $end;
    }
    
    public function fetchSheetData($enterprise, $sheetName) {
        // Move logic from duplicated fetch_sheet_data() function
        return $this->enterpriseDataService->fetchSheetData($enterprise, $sheetName);
    }
    
    public function processReportsData($params) {
        $enterprise = $params['enterprise'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $mode = $params['mode'] ?? 'date';
        
        if (!$enterprise || !$startDate || !$endDate) {
            throw new InvalidArgumentException('Missing required parameters');
        }
        
        // Centralized processing logic
        $registrantsData = $this->fetchSheetData($enterprise, 'registrants');
        $submissionsData = $this->fetchSheetData($enterprise, 'submissions');
        
        return $this->dataProcessor->processAllTables(
            $registrantsData,
            $submissionsData,
            $startDate,
            $endDate,
            $mode
        );
    }
}
```

**Step 2: Update External API**
Update `reports/reports_api.php`:

```php
<?php
require_once __DIR__ . '/../lib/output_buffer.php';
require_once __DIR__ . '/../lib/reports_service.php';
require_once __DIR__ . '/../lib/error_messages.php';

startJsonResponse();

try {
    $reportsService = new ReportsService();
    $data = $reportsService->processReportsData($_GET);
    sendJsonResponse($data);
} catch (Exception $e) {
    sendJsonError(ErrorMessages::getTechnicalDifficulties());
}
```

**Step 3: Update Internal API**
Update `reports/reports_api_internal.php`:

```php
<?php
require_once __DIR__ . '/../lib/reports_service.php';

function getReportsDataInternal($params) {
    $reportsService = new ReportsService();
    return $reportsService->processReportsData($params);
}

// Remove all duplicated functions - they're now in ReportsService
```

**Step 4: Update Callers**
Update files that include `reports_api_internal.php`:

```php
// Before
require_once __DIR__ . '/reports_api_internal.php';
$data = processReportsData($params);

// After
require_once __DIR__ . '/reports_api_internal.php';
$data = getReportsDataInternal($params);
```

### 2. Enterprise Detection Simplification

**Current Problem**: Complex 4-step fallback chain with hardcoded defaults

**Step 1: Create Explicit Enterprise Resolver**
Create `lib/enterprise_resolver.php`:

```php
<?php
require_once __DIR__ . '/session.php';

class EnterpriseResolver {
    private static $validEnterprises = ['csu', 'ccc', 'demo'];
    
    public static function resolve($sources = []) {
        // Try URL parameter first
        if (isset($_GET['ent']) && self::isValid($_GET['ent'])) {
            return $_GET['ent'];
        }
        
        // Try session
        initializeSession();
        if (isset($_SESSION['enterprise_code']) && self::isValid($_SESSION['enterprise_code'])) {
            return $_SESSION['enterprise_code'];
        }
        
        // Try provided sources (for password lookup)
        foreach ($sources as $enterprise) {
            if (self::isValid($enterprise)) {
                return $enterprise;
            }
        }
        
        // NO FALLBACK - require explicit enterprise
        throw new InvalidArgumentException(
            'Enterprise code required. Valid options: ' . implode(', ', self::$validEnterprises)
        );
    }
    
    public static function resolveFromPassword($password) {
        $passwordsConfig = json_decode(file_get_contents(__DIR__ . '/../config/passwords.json'), true);
        
        foreach ($passwordsConfig['organizations'] as $org) {
            if ($org['password'] === $password) {
                return $org['enterprise'];
            }
        }
        
        throw new InvalidArgumentException('Invalid password provided');
    }
    
    private static function isValid($enterprise) {
        return in_array($enterprise, self::$validEnterprises);
    }
    
    public static function getValidEnterprises() {
        return self::$validEnterprises;
    }
}
```

**Step 2: Update Login Logic**
Update `login.php`:

```php
<?php
require_once __DIR__ . '/lib/enterprise_resolver.php';
require_once __DIR__ . '/lib/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    
    if (empty($password)) {
        $error = 'Password is required';
    } else {
        try {
            $enterprise = EnterpriseResolver::resolveFromPassword($password);
            
            initializeSession();
            $_SESSION['enterprise_code'] = $enterprise;
            $_SESSION['organization_password'] = $password;
            
            header('Location: dashboard.php');
            exit;
        } catch (InvalidArgumentException $e) {
            $error = 'Invalid password';
        }
    }
}
```

**Step 3: Update Enterprise Detection Points**
Update all files that currently use enterprise detection:

```php
// Before
$enterprise = detectFromUrl() ?? detectFromSession() ?? detectFromPassword() ?? 'csu';

// After
try {
    $enterprise = EnterpriseResolver::resolve();
} catch (InvalidArgumentException $e) {
    // Handle error appropriately for each context
    http_response_code(400);
    die('Enterprise required: ' . $e->getMessage());
}
```

### 3. Selective Data Fetching Implementation

**Current Problem**: Reports API returns 6 datasets regardless of need

**Step 1: Create Data Request Specification**
Create `lib/reports_data_spec.php`:

```php
<?php

class ReportsDataSpec {
    private $requestedDatasets = [];
    
    public function __construct($mode = 'date', $enrollmentMode = 'tou') {
        $this->determineRequiredDatasets($mode, $enrollmentMode);
    }
    
    private function determineRequiredDatasets($mode, $enrollmentMode) {
        // Only generate what's actually needed
        if ($mode === 'date') {
            $this->requestedDatasets[] = 'registrations_submissions';
            $this->requestedDatasets[] = $enrollmentMode === 'tou' 
                ? 'submissions_enrollments_tou' 
                : 'submissions_enrollments_registrations';
        } elseif ($mode === 'cohort') {
            $this->requestedDatasets[] = 'registrations_cohort';
            $this->requestedDatasets[] = $enrollmentMode === 'tou'
                ? 'cohort_enrollments_tou'
                : 'cohort_enrollments_registrations';
        }
    }
    
    public function getRequestedDatasets() {
        return $this->requestedDatasets;
    }
    
    public function needsDataset($datasetName) {
        return in_array($datasetName, $this->requestedDatasets);
    }
}
```

**Step 2: Update Reports Service**
Update `lib/reports_service.php`:

```php
<?php
require_once __DIR__ . '/reports_data_spec.php';

class ReportsService {
    // ... existing code ...
    
    public function processReportsData($params) {
        $enterprise = $params['enterprise'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $mode = $params['mode'] ?? 'date';
        $enrollmentMode = $params['enrollment_mode'] ?? 'tou';
        
        $spec = new ReportsDataSpec($mode, $enrollmentMode);
        $result = [];
        
        // Only fetch and process requested datasets
        if ($spec->needsDataset('registrations_submissions')) {
            $result['registrations_submissions'] = $this->processRegistrationsBySubmission(
                $enterprise, $startDate, $endDate
            );
        }
        
        if ($spec->needsDataset('registrations_cohort')) {
            $result['registrations_cohort'] = $this->processRegistrationsByCohort(
                $enterprise, $startDate, $endDate
            );
        }
        
        if ($spec->needsDataset('submissions_enrollments_tou')) {
            $result['submissions_enrollments_tou'] = $this->processEnrollmentsByTou(
                $enterprise, $startDate, $endDate, 'submissions'
            );
        }
        
        // Add other datasets only if needed...
        
        return $result;
    }
    
    private function processRegistrationsBySubmission($enterprise, $startDate, $endDate) {
        // Extracted processing logic
        $submissionsData = $this->fetchSheetData($enterprise, 'submissions');
        return $this->dataProcessor->filterBySubmissionDate($submissionsData, $startDate, $endDate);
    }
    
    private function processRegistrationsByCohort($enterprise, $startDate, $endDate) {
        // Extracted processing logic
        $registrantsData = $this->fetchSheetData($enterprise, 'registrants');
        return $this->dataProcessor->filterByCohortRange($registrantsData, $startDate, $endDate);
    }
    
    // Add other processing methods...
}
```

**Step 3: Update Frontend Request**
Update `reports/js/unified-data-service.js`:

```javascript
class ReportsDataService {
    async fetchData(dateRange, modes) {
        const params = new URLSearchParams({
            enterprise: window.ENTERPRISE_CODE,
            start_date: dateRange.start,
            end_date: dateRange.end,
            mode: modes.registrationMode,
            enrollment_mode: modes.enrollmentMode
        });
        
        const response = await fetch(`reports_api.php?${params}`);
        
        if (!response.ok) {
            throw new Error(`API request failed: ${response.status}`);
        }
        
        return await response.json();
    }
    
    // Remove logic for handling 6 datasets - now dynamic
}
```

### 4. Input Validation Centralization

**Step 1: Create Validation Service**
Create `lib/input_validator.php`:

```php
<?php

class InputValidator {
    public static function validateEnterpriseCode($code) {
        if (!is_string($code) || !preg_match('/^[a-z]{3,4}$/', $code)) {
            throw new InvalidArgumentException('Enterprise code must be 3-4 lowercase letters');
        }
        return $code;
    }
    
    public static function validatePassword($password) {
        if (!is_string($password) || !preg_match('/^\d{4}$/', $password)) {
            throw new InvalidArgumentException('Password must be exactly 4 digits');
        }
        return $password;
    }
    
    public static function validateDateFormat($date) {
        if (!preg_match('/^\d{2}-\d{2}-\d{2}$/', $date)) {
            throw new InvalidArgumentException('Date must be in MM-DD-YY format');
        }
        
        $dateObj = DateTime::createFromFormat('m-d-y', $date);
        if (!$dateObj || $dateObj->format('m-d-y') !== $date) {
            throw new InvalidArgumentException('Invalid date provided');
        }
        
        return $date;
    }
    
    public static function validateDateRange($startDate, $endDate) {
        $start = self::validateDateFormat($startDate);
        $end = self::validateDateFormat($endDate);
        
        $startObj = DateTime::createFromFormat('m-d-y', $start);
        $endObj = DateTime::createFromFormat('m-d-y', $end);
        
        if ($startObj > $endObj) {
            throw new InvalidArgumentException('Start date must be before end date');
        }
        
        return [$start, $end];
    }
    
    public static function validateMode($mode) {
        $validModes = ['date', 'cohort'];
        if (!in_array($mode, $validModes)) {
            throw new InvalidArgumentException('Mode must be: ' . implode(', ', $validModes));
        }
        return $mode;
    }
}
```

**Step 2: Update All Input Points**
Replace scattered validation with centralized calls:

```php
// Before (scattered across files)
if (!preg_match('/^\d{4}$/', $password)) {
    $error = 'Invalid password format';
}

// After (centralized)
try {
    $password = InputValidator::validatePassword($_POST['password']);
} catch (InvalidArgumentException $e) {
    $error = $e->getMessage();
}
```

### 5. Implementation Timeline

**Week 1: API Duplication Elimination**
- Days 1-2: Create ReportsService class
- Days 3-4: Update external and internal APIs
- Day 5: Test and validate changes

**Week 2: Enterprise Detection Simplification** 
- Days 1-2: Create EnterpriseResolver class
- Days 3-4: Update all detection points
- Day 5: Remove hardcoded fallbacks and test

**Week 3: Selective Data Fetching**
- Days 1-3: Implement ReportsDataSpec and update service
- Days 4-5: Update frontend and test performance improvements

**Week 4: Input Validation Consolidation**
- Days 1-3: Create InputValidator and update all input points
- Days 4-5: Test validation consistency across all forms

### Risk Mitigation

**Backward Compatibility**
- Keep old functions temporarily with deprecation warnings
- Implement feature flags for gradual rollout
- Maintain existing API responses during transition

**Testing Strategy**
- Unit tests for all new service classes
- Integration tests for API endpoints
- Performance tests for data fetching improvements
- Regression tests for existing functionality

**Rollback Plan**
- Git branches for each phase
- Database/cache backup before changes
- Ability to quickly revert to old API patterns
- Monitoring for error rate increases