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

// Load registrants data from cache (this contains all certificate earners)
$registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
$registrantsData = $registrantsCache['data'] ?? [];

// Get the minimum start date from configuration
$minStartDate = UnifiedEnterpriseConfig::getStartDate();

// Filter by issue date in range
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

    // Column indices from the registrants data
    $cohortIdx = 3;      // Cohort
    $yearIdx = 4;        // Year
    $firstIdx = 5;       // First
    $lastIdx = 6;        // Last
    $emailIdx = 7;       // Email
    $orgIdx = 9;         // Organization
    $certificateIdx = 10; // Certificate
    $issuedIdx = 11;     // Issued

    if ($isAllRange) {
        // For 'All', include all Certificate == 'Yes'
        $filtered = array_filter($registrantsData, function($row) use ($certificateIdx) {
            return isset($row[$certificateIdx]) && trim($row[$certificateIdx]) === 'Yes';
        });
    } else {
        // For other ranges, filter by Issued in range and Certificate == 'Yes'
        $filtered = array_filter($registrantsData, function($row) use ($start, $end, $certificateIdx, $issuedIdx) {
            return isset($row[$certificateIdx], $row[$issuedIdx]) &&
                   trim($row[$certificateIdx]) === 'Yes' &&
                   preg_match('/^\d{2}-\d{2}-\d{2}$/', $row[$issuedIdx]) &&
                   in_range($row[$issuedIdx], $start, $end);
        });
    }

    // Custom sort: no Issued first, then with Issued (desc), both sorted by Org, Last, First
    $noIssued = [];
    $withIssued = [];
    foreach ($filtered as $row) {
        $issuedVal = $row[$issuedIdx] ?? '';
        // Only treat as 'withIssued' if matches MM-DD-YY format
        if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $issuedVal)) {
            $withIssued[] = $row;
        } else {
            $noIssued[] = $row;
        }
    }

    // Sort noIssued: Organization, Last, First (all ascending)
    usort($noIssued, function($a, $b) use ($orgIdx, $lastIdx, $firstIdx) {
        $orgCmp = strcmp($a[$orgIdx] ?? '', $b[$orgIdx] ?? '');
        if ($orgCmp !== 0) return $orgCmp;
        $lastCmp = strcmp($a[$lastIdx] ?? '', $b[$lastIdx] ?? '');
        if ($lastCmp !== 0) return $lastCmp;
        return strcmp($a[$firstIdx] ?? '', $b[$firstIdx] ?? '');
    });

    // Sort withIssued: YY desc, MM desc, DD desc, then Organization, Last, First (all ascending)
    usort($withIssued, function($a, $b) use ($issuedIdx, $orgIdx, $lastIdx, $firstIdx) {
        // Parse MM-DD-YY
        list($mmA, $ddA, $yyA) = array_map('intval', explode('-', $a[$issuedIdx]));
        list($mmB, $ddB, $yyB) = array_map('intval', explode('-', $b[$issuedIdx]));
        if ($yyA !== $yyB) return $yyB - $yyA;
        if ($mmA !== $mmB) return $mmB - $mmA;
        if ($ddA !== $ddB) return $ddB - $ddA;
        $orgCmp = strcmp($a[$orgIdx] ?? '', $b[$orgIdx] ?? '');
        if ($orgCmp !== 0) return $orgCmp;
        $lastCmp = strcmp($a[$lastIdx] ?? '', $b[$lastIdx] ?? '');
        if ($lastCmp !== 0) return $lastCmp;
        return strcmp($a[$firstIdx] ?? '', $b[$firstIdx] ?? '');
    });

    // Merge for display
    $filtered = array_merge($noIssued, $withIssued);
}
