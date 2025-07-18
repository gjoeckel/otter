<?php
// reports_api_internal.php - Internal API for data refresh without headers
// This version is designed to be included in other pages without sending JSON headers

// Start session first
if (session_status() === PHP_SESSION_NONE) session_start();

// Load enterprise configuration and cache manager
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/cache_utils.php';
require_once __DIR__ . '/../lib/data_processor.php';

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
    return ['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.'];
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
        return ['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.'];
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
        // Get the actual PHP error that was suppressed
        $error = error_get_last();
        $errorMessage = $error ? $error['message'] : 'Unknown error';
        
        // Check if it's a Google service issue (503, 500, connection timeout, etc.)
        if (strpos($errorMessage, '503') !== false || 
            strpos($errorMessage, '500') !== false || 
            strpos($errorMessage, 'Service Unavailable') !== false ||
            strpos($errorMessage, 'HTTP request failed') !== false) {
            return ['error' => 'We are experiencing issues connecting to Google services. Please wait a few minutes and then retry. If problem persists, contact accessibledocs@webaim.org for support.'];
        }
        
        return ['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.'];
    }

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.'];
    }

    if (!isset($data['values'])) {
        return ['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.'];
    }

    return $data['values'];
    }
}

// --- Load config files ---
$registrantsSheetConfig = UnifiedEnterpriseConfig::getSheetConfig('registrants');
$submissionsSheetConfig = UnifiedEnterpriseConfig::getSheetConfig('submissions');

if (!$registrantsSheetConfig || !$submissionsSheetConfig) {
    return ['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.'];
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
    return ['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.'];
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
        return ['error' => $registrantsData['error']];
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
        return ['error' => $submissionsData['error']];
    }

    // Ensure all data is trimmed and stringified
    $submissionsData = array_map('trim_row', $submissionsData);

    // Add global_timestamp using utility
    $submissionsDataWithTimestamp = CacheUtils::createTimestampedData($submissionsData);

    $cacheManager->writeCacheFile('all-submissions-data.json', $submissionsDataWithTimestamp);
}

// --- Process data ---
$invitationsProcessed = DataProcessor::processInvitationsData($registrantsData, $start, $end);
$registrationsProcessed = DataProcessor::processRegistrationsData($submissionsData, $start, $end);
// Load cached enrollments data and process for date range
$enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
$enrollmentsData = $enrollmentsCache ?? [];

$enrollmentsProcessed = DataProcessor::processEnrollmentsData($enrollmentsData, $start, $end);
$submissionsProcessed = DataProcessor::processSubmissionsData($submissionsData, $start, $end);
$organizationData = DataProcessor::processOrganizationData(
    $registrationsProcessed, // Use new registrations data for organization processing
    $enrollmentsProcessed,
    $invitationsProcessed['certificates']
);

// Return success (no output, just return)
return ['success' => true, 'data' => [
    'invitations' => $invitationsProcessed,
    'registrations' => $registrationsProcessed,
    'submissions' => $submissionsProcessed,
    'organizations' => $organizationData
]];