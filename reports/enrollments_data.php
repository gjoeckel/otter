<?php
ini_set('display_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/abbreviation_utils.php';

// Abbreviate organization names using prioritized, single-abbreviation logic
function abbreviateLinkText($name) {
    return abbreviateOrganizationName($name);
}

// Helper: Validate MM-DD-YY
function is_valid_mmddyy($date) {
    return preg_match('/^\d{2}-\d{2}-\d{2}$/', $date);
}

// Get date range and mode from GET
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';
$enrollmentMode = (isset($_GET['enrollment_mode']) && $_GET['enrollment_mode'] === 'by-registration') ? 'by-registration' : 'by-tou';
$validRange = is_valid_mmddyy($start) && is_valid_mmddyy($end);

// Initialize enterprise cache manager
$cacheManager = EnterpriseCacheManager::getInstance();

// Load data using the same approach as the API
$enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
$enrollmentsData = $enrollmentsCache ?? [];

// Load registrants data for enrollment processing (same as API)
$registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
$registrantsData = $registrantsCache['data'] ?? [];

// Use DataProcessor for consistent enrollment processing
require_once __DIR__ . '/../lib/data_processor.php';

// Get the minimum start date from configuration
$minStartDate = UnifiedEnterpriseConfig::getStartDate();

// Filter by submitted date in range
function in_range($date, $start, $end) {
    $d = DateTime::createFromFormat('m-d-y', $date);
    $s = DateTime::createFromFormat('m-d-y', $start);
    $e = DateTime::createFromFormat('m-d-y', $end);
    if (!$d || !$s || !$e) return false;
    return $d >= $s && $d <= $e;
}

$filtered = [];
$reportCaption = '';

if ($validRange) {
    // Map enrollment mode to DataProcessor mode
    $processorMode = ($enrollmentMode === 'by-registration') ? 'registration_date' : 'tou_completion';
    
    // Use DataProcessor for consistent enrollment processing
    $enrollmentResult = DataProcessor::processEnrollmentsData($enrollmentsData, $start, $end, $registrantsData, $processorMode);
    $filtered = is_array($enrollmentResult) && isset($enrollmentResult['data']) ? $enrollmentResult['data'] : $enrollmentResult;
    
    // Set report caption based on mode
    if ($enrollmentMode === 'by-registration') {
        $reportCaption = "Enrollees by Registration Date | {$start} - {$end}";
    } else {
        $reportCaption = "Enrollees by TOU Completion Date | {$start} - {$end}";
    }

    // Sort by Enrolled date (descending) then by Last Name (ascending)
    usort($filtered, function($a, $b) {
        $enrolledA = $a[2] ?? ''; // Enrolled (index 2)
        $enrolledB = $b[2] ?? ''; // Enrolled (index 2)
        
        // Parse MM-DD-YY dates for comparison
        $dateA = DateTime::createFromFormat('m-d-y', $enrolledA);
        $dateB = DateTime::createFromFormat('m-d-y', $enrolledB);
        
        // If both have valid dates, compare them (descending)
        if ($dateA && $dateB) {
            $dateCmp = $dateB <=> $dateA; // Descending order
            if ($dateCmp !== 0) return $dateCmp;
        }
        
        // If dates are equal or invalid, sort by Last Name (ascending)
        return strcmp($a[6] ?? '', $b[6] ?? ''); // Last (index 6)
    });
}