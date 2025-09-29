<?php
ini_set('display_errors', '0');
error_reporting(0);

require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';
require_once __DIR__ . '/../lib/abbreviation_utils.php';

// Load DRY services
require_once __DIR__ . '/../lib/google_sheets_columns.php';
require_once __DIR__ . '/../lib/demo_transformation_service.php';
require_once __DIR__ . '/../lib/cache_data_loader.php';
require_once __DIR__ . '/../lib/data_processor.php';

// Helper function removed - now using DemoTransformationService

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

// Load registrants data using DRY service (this contains all certificate earners)
$registrantsData = CacheDataLoader::loadRegistrantsData();

// Transform organization names for demo enterprise using DRY service
$registrantsData = DemoTransformationService::transformOrganizationNames($registrantsData);

// Get the minimum start date from configuration
$minStartDate = UnifiedEnterpriseConfig::getStartDate();

// Helper function removed - now using DataProcessor::inRange()

$filtered = [];
if ($validRange) {
    $isAllRange = ($start === $minStartDate && $end === date('m-d-y'));

    // Column indices from the registrants data
    $cohortIdx = 3;      // Cohort
    $yearIdx = 4;        // Year
    $firstIdx = 5;       // First
    $lastIdx = 6;        // Last
    $emailIdx = 7;       // Email
    // Use DRY service for column indices
    $orgIdx = GoogleSheetsColumns::REGISTRANTS['ORGANIZATION'];
    $certificateIdx = GoogleSheetsColumns::REGISTRANTS['CERTIFICATE'];
    $issuedIdx = GoogleSheetsColumns::REGISTRANTS['ISSUED'];

    if ($isAllRange) {
        // For 'All', use DRY service for certificate filtering
        $filtered = DataProcessor::filterCertificates($registrantsData);
    } else {
        // For other ranges, use DRY service for certificate filtering with date range
        $filtered = DataProcessor::filterCertificates($registrantsData, $start, $end);
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

    // Sort withIssued: Issued desc, Year desc, Cohort desc, Last asc, First asc
    usort($withIssued, function($a, $b) use ($issuedIdx, $yearIdx, $cohortIdx, $lastIdx, $firstIdx) {
        // First compare by Issued date (descending)
        $issuedA = $a[$issuedIdx] ?? '';
        $issuedB = $b[$issuedIdx] ?? '';
        if ($issuedA !== $issuedB) {
            // Parse MM-DD-YY for date comparison
            if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $issuedA) && preg_match('/^\d{2}-\d{2}-\d{2}$/', $issuedB)) {
                list($mmA, $ddA, $yyA) = array_map('intval', explode('-', $issuedA));
                list($mmB, $ddB, $yyB) = array_map('intval', explode('-', $issuedB));
                if ($yyA !== $yyB) return $yyB - $yyA;
                if ($mmA !== $mmB) return $mmB - $mmA;
                if ($ddA !== $ddB) return $ddB - $ddA;
            } else {
                // If dates don't match format, do string comparison
                return strcmp($issuedB, $issuedA); // descending
            }
        }

        // Then compare by Year (descending)
        $yearA = $a[$yearIdx] ?? '';
        $yearB = $b[$yearIdx] ?? '';
        if ($yearA !== $yearB) {
            return strcmp($yearB, $yearA); // descending
        }

        // Then compare by Cohort (descending)
        $cohortA = $a[$cohortIdx] ?? '';
        $cohortB = $b[$cohortIdx] ?? '';
        if ($cohortA !== $cohortB) {
            return strcmp($cohortB, $cohortA); // descending
        }

        // Then compare by Last name (ascending)
        $lastA = $a[$lastIdx] ?? '';
        $lastB = $b[$lastIdx] ?? '';
        if ($lastA !== $lastB) {
            return strcmp($lastA, $lastB); // ascending
        }

        // Finally compare by First name (ascending)
        $firstA = $a[$firstIdx] ?? '';
        $firstB = $b[$firstIdx] ?? '';
        return strcmp($firstA, $firstB); // ascending
    });

    // Merge for display
    $filtered = array_merge($noIssued, $withIssued);
}
