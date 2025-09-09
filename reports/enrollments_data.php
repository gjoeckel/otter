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

// Load enrollments data from cache (pre-filtered enrollments)
$enrollmentsCache = $cacheManager->readCacheFile('enrollments.json');
$enrollmentsData = $enrollmentsCache ?? [];

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

    // Column indices from the enrollments data (based on config)
    $submittedIdx = 15;  // Submitted (Google Sheets Column P)
    $cohortIdx = 3;      // Cohort (Google Sheets Column D)
    $yearIdx = 4;        // Year (Google Sheets Column E)
    $firstIdx = 5;       // First (Google Sheets Column F)
    $lastIdx = 6;        // Last (Google Sheets Column G)
    $emailIdx = 7;       // Email (Google Sheets Column H)
    $orgIdx = 9;         // Organization (Google Sheets Column J)

    if ($isAllRange) {
        // For 'All', include all enrollments (already filtered in cache)
        $filtered = $enrollmentsData;
    } else {
        // For other ranges, filter by Submitted in range
        $filtered = array_filter($enrollmentsData, function($row) use ($start, $end, $submittedIdx) {
            return isset($row[$submittedIdx]) &&
                   preg_match('/^\d{2}-\d{2}-\d{2}$/', $row[$submittedIdx]) &&
                   in_range($row[$submittedIdx], $start, $end);
        });
    }

    // Custom sort: no Submitted first, then with Submitted (desc), both sorted by Org, Last, First
    $noSubmitted = [];
    $withSubmitted = [];
    foreach ($filtered as $row) {
        $submittedVal = $row[$submittedIdx] ?? '';
        // Only treat as 'withSubmitted' if matches MM-DD-YY format
        if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $submittedVal)) {
            $withSubmitted[] = $row;
        } else {
            $noSubmitted[] = $row;
        }
    }

    // Sort noSubmitted: Organization, Last, First (all ascending)
    usort($noSubmitted, function($a, $b) use ($orgIdx, $lastIdx, $firstIdx) {
        $orgCmp = strcmp($a[$orgIdx] ?? '', $b[$orgIdx] ?? '');
        if ($orgCmp !== 0) return $orgCmp;
        $lastCmp = strcmp($a[$lastIdx] ?? '', $b[$lastIdx] ?? '');
        if ($lastCmp !== 0) return $lastCmp;
        return strcmp($a[$firstIdx] ?? '', $b[$firstIdx] ?? '');
    });

    // Sort withSubmitted: YY desc, MM desc, DD desc, then Organization, Last, First (all ascending)
    usort($withSubmitted, function($a, $b) use ($submittedIdx, $orgIdx, $lastIdx, $firstIdx) {
        // Parse MM-DD-YY
        list($mmA, $ddA, $yyA) = array_map('intval', explode('-', $a[$submittedIdx]));
        list($mmB, $ddB, $yyB) = array_map('intval', explode('-', $b[$submittedIdx]));
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
    $filtered = array_merge($noSubmitted, $withSubmitted);
}