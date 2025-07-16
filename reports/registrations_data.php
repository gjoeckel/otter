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

// Get date range from GET only
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';
$validRange = is_valid_mmddyy($start) && is_valid_mmddyy($end);

// Initialize enterprise cache manager
$cacheManager = EnterpriseCacheManager::getInstance();

// Load submissions data from cache (this contains all registrations from "Filtered" sheet)
$submissionsCache = $cacheManager->readCacheFile('all-submissions-data.json');
$submissionsData = $submissionsCache['data'] ?? [];

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
if ($validRange) {
    $isAllRange = ($start === $minStartDate && $end === date('m-d-y'));

    // Column indices from the submissions data (based on config)
    $submittedIdx = 15;  // Submitted (Google Sheets Column P)

    if ($isAllRange) {
        // For 'All', include all submissions in original order
        $filtered = $submissionsData;
    } else {
        // For other ranges, filter by Submitted in range, preserving original order
        $filtered = array_filter($submissionsData, function($row) use ($start, $end, $submittedIdx) {
            return isset($row[$submittedIdx]) &&
                   preg_match('/^\d{2}-\d{2}-\d{2}$/', $row[$submittedIdx]) &&
                   in_range($row[$submittedIdx], $start, $end);
        });
    }
}