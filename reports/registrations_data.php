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

// Load registrants data from cache (this contains all registrations)
$registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
$registrantsData = $registrantsCache['data'] ?? [];

// Get the minimum start date from configuration
$minStartDate = UnifiedEnterpriseConfig::getStartDate();

// Filter by invited date in range
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

    // Column indices from the registrants data (based on config)
    $invitedIdx = 1;     // Invited (Google Sheets Column B)
    $cohortIdx = 3;      // Cohort (Google Sheets Column D)
    $yearIdx = 4;        // Year (Google Sheets Column E)
    $firstIdx = 5;       // First (Google Sheets Column F)
    $lastIdx = 6;        // Last (Google Sheets Column G)
    $emailIdx = 7;       // Email (Google Sheets Column H)
    $orgIdx = 9;         // Organization (Google Sheets Column J)

    if ($isAllRange) {
        // For 'All', include all registrations
        $filtered = $registrantsData;
    } else {
        // For other ranges, filter by Invited in range
        $filtered = array_filter($registrantsData, function($row) use ($start, $end, $invitedIdx) {
            return isset($row[$invitedIdx]) &&
                   preg_match('/^\d{2}-\d{2}-\d{2}$/', $row[$invitedIdx]) &&
                   in_range($row[$invitedIdx], $start, $end);
        });
    }

    // Custom sort: no Invited first, then with Invited (desc), both sorted by Org, Last, First
    $noInvited = [];
    $withInvited = [];
    foreach ($filtered as $row) {
        $invitedVal = $row[$invitedIdx] ?? '';
        // Only treat as 'withInvited' if matches MM-DD-YY format
        if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $invitedVal)) {
            $withInvited[] = $row;
        } else {
            $noInvited[] = $row;
        }
    }

    // Sort noInvited: Organization, Last, First (all ascending)
    usort($noInvited, function($a, $b) use ($orgIdx, $lastIdx, $firstIdx) {
        $orgCmp = strcmp($a[$orgIdx] ?? '', $b[$orgIdx] ?? '');
        if ($orgCmp !== 0) return $orgCmp;
        $lastCmp = strcmp($a[$lastIdx] ?? '', $b[$lastIdx] ?? '');
        if ($lastCmp !== 0) return $lastCmp;
        return strcmp($a[$firstIdx] ?? '', $b[$firstIdx] ?? '');
    });

    // Sort withInvited: YY desc, MM desc, DD desc, then Organization, Last, First (all ascending)
    usort($withInvited, function($a, $b) use ($invitedIdx, $orgIdx, $lastIdx, $firstIdx) {
        // Parse MM-DD-YY
        list($mmA, $ddA, $yyA) = array_map('intval', explode('-', $a[$invitedIdx]));
        list($mmB, $ddB, $yyB) = array_map('intval', explode('-', $b[$invitedIdx]));
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
    $filtered = array_merge($noInvited, $withInvited);
} 