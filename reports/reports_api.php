<?php
// reports_api.php - MVP reporting API for organization
ob_start();
header('Content-Type: application/json');

// Start session first
if (session_status() === PHP_SESSION_NONE) session_start();

// Load enterprise configuration and cache manager
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/cache_utils.php';
require_once __DIR__ . '/../lib/data_processor.php';
require_once __DIR__ . '/../lib/enterprise_features.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();
$cacheManager = EnterpriseCacheManager::getInstance();

// --- Config paths ---
define('CACHE_DIR', $cacheManager->getEnterpriseCacheDir());
define('GLOBAL_CACHE_DIR', $cacheManager->getEnterpriseCacheDir());
define('REGISTRANTS_CACHE_FILE', $cacheManager->getRegistrantsCachePath());
define('SUBMISSIONS_CACHE_FILE', $cacheManager->getSubmissionsCachePath());
define('REGISTRATIONS_FILE', $cacheManager->getRegistrationsCachePath());
define('ENROLLMENTS_FILE', $cacheManager->getEnrollmentsCachePath());
define('CERTIFICATES_FILE', $cacheManager->getCertificatesCachePath());
define('CACHE_TTL', 6 * 60 * 60); // 6 hours

// Get enterprise code for dynamic config paths
$enterprise_code = UnifiedEnterpriseConfig::getEnterpriseCode();

$registrantsFile = "../../config/$enterprise_code/registrants.json";
$submissionsFile = "../../config/$enterprise_code/submissions.json";
$googleApiKeyFile = "../../config/$enterprise_code/google_api_key.txt";

// Validate enterprise configuration
if (!in_array($enterprise_code, ['csu', 'ccc', 'demo'])) {
    ob_end_clean();
    echo json_encode(['error' => 'Invalid enterprise configuration']);
    exit;
}

// --- Helpers ---
if (!function_exists('trim_row')) {
    function trim_row($row) {
        return array_map('trim', $row);
    }
}

/**
 * Checks if a cohort/year combination falls within a date range.
 * @param string $cohort Two-digit month string (MM)
 * @param string $year Two-digit year string (YY)
 * @param string $startDate Start date in MM-DD-YY format
 * @param string $endDate End date in MM-DD-YY format
 * @return bool True if cohort/year is within range
 */
if (!function_exists('isCohortYearInRange')) {
    function isCohortYearInRange($cohort, $year, $startDate, $endDate) {
    // Convert start and end dates to MM-YY format
    $startMM = substr($startDate, 0, 2);
    $startYY = substr($startDate, 6, 2);
    $endMM = substr($endDate, 0, 2);
    $endYY = substr($endDate, 6, 2);
    
    // Convert cohort and year to integers for comparison
    $cohortNum = intval($cohort);
    $yearNum = intval($year);
    $startMMNum = intval($startMM);
    $startYYNum = intval($startYY);
    $endMMNum = intval($endMM);
    $endYYNum = intval($endYY);
    
    // Check if cohort/year is within range
    if ($yearNum < $startYYNum || $yearNum > $endYYNum) {
        return false;
    }
    
    if ($yearNum == $startYYNum && $cohortNum < $startMMNum) {
        return false;
    }
    
    if ($yearNum == $endYYNum && $cohortNum > $endMMNum) {
        return false;
    }
    
    return true;
    }
}

// --- Google Sheets API ---
if (!function_exists('fetch_sheet_data')) {
    function fetch_sheet_data($workbook_id, $sheet_name, $start_row) {
    $api_key = UnifiedEnterpriseConfig::getGoogleApiKey();
    
    if (empty($api_key)) {
        return ['error' => 'Google API key not configured'];
    }
    
    $url = "https://sheets.googleapis.com/v4/spreadsheets/$workbook_id/values/$sheet_name!A$start_row:Z";
    $url .= "?key=$api_key";
    
    $context = stream_context_create([
        'http' => [
            'timeout' => 30,
            'user_agent' => 'Mozilla/5.0 (compatible; Enterprise API)'
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    if ($response === false) {
        return ['error' => 'Failed to fetch data from Google Sheets'];
    }
    
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'Invalid JSON response from Google Sheets'];
    }
    
    if (!isset($data['values'])) {
        return ['error' => 'No values found in Google Sheets response'];
    }
    
    return $data['values'];
    }
}

// --- Load config files ---
$registrantsSheetConfig = UnifiedEnterpriseConfig::getSheetConfig('registrants');
$submissionsSheetConfig = UnifiedEnterpriseConfig::getSheetConfig('submissions');

if (!$registrantsSheetConfig || !$submissionsSheetConfig) {
    ob_end_clean();
    echo json_encode(['error' => 'Configuration files not found or invalid']);
    exit;
}

// --- Extract config values ---
$regCols = $registrantsSheetConfig['columns'];
$regStartRow = $registrantsSheetConfig['start_row'];
$regWbId = $registrantsSheetConfig['workbook_id'];
$regSheet = $registrantsSheetConfig['sheet_name'];
$subCols = $submissionsSheetConfig['columns'];
$subStartRow = $submissionsSheetConfig['start_row'];
$subWbId = $submissionsSheetConfig['workbook_id'];
$subSheet = $submissionsSheetConfig['sheet_name'];

// --- Get date range ---
$start = isset($_REQUEST['start_date']) ? trim($_REQUEST['start_date']) : '';
$end = isset($_REQUEST['end_date']) ? trim($_REQUEST['end_date']) : '';

if (!CacheUtils::isValidMMDDYY($start) || !CacheUtils::isValidMMDDYY($end)) {
    ob_end_clean();
    echo json_encode(['error' => 'Invalid or missing date range. Use MM-DD-YY.']);
    exit;
}

// --- Registrants data (cache or fetch) ---
$useRegCache = false;
$forceRefresh = isset($_REQUEST['force_refresh']) && $_REQUEST['force_refresh'] === '1';

if (!$forceRefresh && CacheUtils::isCacheFresh($cacheManager, 'all-registrants-data.json')) {
    $json = $cacheManager->readCacheFile('all-registrants-data.json');
    $registrantsData = isset($json['data']) ? $json['data'] : [];
    $useRegCache = true;
}

if (!$useRegCache) {
    $registrantsData = fetch_sheet_data($regWbId, $regSheet, $regStartRow);
    
    if (isset($registrantsData['error'])) {
        ob_end_clean();
        echo json_encode(['error' => $registrantsData['error']]);
        exit;
    }
    
    if (!is_dir(CACHE_DIR)) {
        mkdir(CACHE_DIR, 0777, true);
    }
    
    // Ensure all data is trimmed and stringified
    $registrantsData = array_map('trim_row', $registrantsData);
    
    // Add global_timestamp using utility
    $registrantsDataWithTimestamp = CacheUtils::createTimestampedData($registrantsData);
    
    $cacheManager->writeCacheFile('all-registrants-data.json', $registrantsDataWithTimestamp);
}

// --- Submissions data (cache or fetch) ---
$useSubCache = false;

if (!$forceRefresh && CacheUtils::isCacheFresh($cacheManager, 'all-submissions-data.json')) {
    $json = $cacheManager->readCacheFile('all-submissions-data.json');
    $submissionsData = isset($json['data']) ? $json['data'] : [];
    $useSubCache = true;
}

if (!$useSubCache) {
    $submissionsData = fetch_sheet_data($subWbId, $subSheet, $subStartRow);
    if (isset($submissionsData['error'])) {
        ob_end_clean();
        echo json_encode(['error' => $submissionsData['error']]);
        exit;
    }
    
    // Ensure all data is trimmed and stringified
    $submissionsData = array_map('trim_row', $submissionsData);
    
    // Add global_timestamp using utility
    $submissionsDataWithTimestamp = CacheUtils::createTimestampedData($submissionsData);
    
    $cacheManager->writeCacheFile('all-submissions-data.json', $submissionsDataWithTimestamp);
}

// --- Process data for date range using DataProcessor ---
$response = [];

// Process registrants data using utility
$processedData = DataProcessor::processRegistrantsData($registrantsData, $start, $end);
$registrations = $processedData['registrations'];
$enrollments = $processedData['enrollments'];
$certificates = $processedData['certificates'];

// Process submissions data using utility
$submissions = DataProcessor::processSubmissionsData($submissionsData, $start, $end);

// Cache processed data
$cacheManager->writeCacheFile('registrations.json', $registrations);
$cacheManager->writeCacheFile('enrollments.json', $enrollments);
$cacheManager->writeCacheFile('certificates.json', $certificates);

// Build response
$response['registrations'] = $registrations;
$response['enrollments'] = $enrollments;
$response['certificates'] = $certificates;
$response['submissions'] = $submissions;

// Add organization data if requested
if (isset($_REQUEST['organization_data'])) {
    // If the requested range is 'all', use the new shared function
    $minStartDate = UnifiedEnterpriseConfig::getStartDate();
    $isAllRange = (isset($_REQUEST['start_date'], $_REQUEST['end_date']) &&
        $_REQUEST['start_date'] === $minStartDate &&
        $_REQUEST['end_date'] === date('m-d-y'));
    
    if ($isAllRange) {
        require_once __DIR__ . '/../lib/api/organizations_api.php';
        $organizationData = OrganizationsAPI::getAllOrganizationsDataAllRange();
        $response['organization_data'] = $organizationData;
    } else {
        $organizationFiles = [
            $cacheManager->getRegistrationsCachePath(),
            $cacheManager->getEnrollmentsCachePath(),
            $cacheManager->getCertificatesCachePath()
        ];
        
        foreach ($organizationFiles as $file) {
            if (!file_exists($file)) {
                ob_end_clean();
                echo json_encode(['error' => 'Organization data unavailable: missing cache file ' . basename($file)]);
                exit;
            }
        }
        
        $registrationsRows = json_decode(file_get_contents($organizationFiles[0]), true);
        $enrollmentsRows = json_decode(file_get_contents($organizationFiles[1]), true);
        $certificatesRows = json_decode(file_get_contents($organizationFiles[2]), true);
        
        // Process organization data using utility
        $organizationData = DataProcessor::processOrganizationData($registrationsRows, $enrollmentsRows, $certificatesRows);
        $response['organization_data'] = $organizationData;
    }
}

// Groups Data Table Operations (only for enterprises that support groups)
$supportsGroups = EnterpriseFeatures::supportsGroups();

if ($supportsGroups && isset($_REQUEST['groups_data'])) {
    $enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
    $groupsFile = __DIR__ . "/../config/groups/{$enterpriseCode}.json";
    
    if (!file_exists($groupsFile)) {
        ob_end_clean();
        echo json_encode(['error' => 'Groups mapping unavailable: missing groups.json']);
        exit;
    }
    
    $groupsMap = json_decode(file_get_contents($groupsFile), true);
    
    $groupsData = [];
    $groupsCounts = [];
    
    // Build a college-to-group lookup
    $collegeToGroup = [];
    foreach ($groupsMap as $group => $colleges) {
        foreach ($colleges as $college) {
            $collegeToGroup[$college] = $group;
        }
    }
    
    // Helper to increment counts
    function add_group_count(&$arr, $row, $colIdx, $collegeToGroup) {
        if (!isset($row[$colIdx])) return;
        $college = trim($row[$colIdx]);
        if ($college === '' || !isset($collegeToGroup[$college])) return;
        $group = $collegeToGroup[$college];
        if (!isset($arr[$group])) $arr[$group] = 0;
        $arr[$group]++;
    }
    
    $collegeIdx = 9; // College/Organization column index
    
    $regCounts = [];
    $enrCounts = [];
    $certCounts = [];
    
    foreach ($registrations as $row) {
        add_group_count($regCounts, $row, $collegeIdx, $collegeToGroup);
    }
    foreach ($enrollments as $row) {
        add_group_count($enrCounts, $row, $collegeIdx, $collegeToGroup);
    }
    foreach ($certificates as $row) {
        add_group_count($certCounts, $row, $collegeIdx, $collegeToGroup);
    }
    
    // Build groups data array
    foreach ($groupsMap as $group => $colleges) {
        $groupsData[] = [
            'group' => $group,
            'registrations' => isset($regCounts[$group]) ? $regCounts[$group] : 0,
            'enrollments' => isset($enrCounts[$group]) ? $enrCounts[$group] : 0,
            'certificates' => isset($certCounts[$group]) ? $certCounts[$group] : 0
        ];
    }
    
    $response['groups_data'] = $groupsData;
}

ob_end_clean();
echo json_encode($response);