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
$mode = (isset($_GET['mode']) && $_GET['mode'] === 'cohort') ? 'cohort' : 'date';
$cohortParam = $_GET['cohort'] ?? '';
$validRange = is_valid_mmddyy($start) && is_valid_mmddyy($end);

// Initialize enterprise cache manager
$cacheManager = EnterpriseCacheManager::getInstance();

// Load submissions data using DRY service (this contains all registrations from "Filtered" sheet)
$submissionsData = CacheDataLoader::loadSubmissionsData();

// Transform organization names for demo enterprise using DRY service
$submissionsData = DemoTransformationService::transformOrganizationNames($submissionsData);

// Get the minimum start date from configuration
$minStartDate = UnifiedEnterpriseConfig::getStartDate();

// Helper function removed - now using DataProcessor::inRange()

// Build inclusive list of cohort keys (MM-YY) from start..end
function build_cohort_keys_from_range($start, $end) {
    $keys = [];
    $sMM = (int)substr($start, 0, 2);
    $sYY = (int)substr($start, 6, 2);
    $eMM = (int)substr($end, 0, 2);
    $eYY = (int)substr($end, 6, 2);
    $mm = $sMM; $yy = $sYY;
    while ($yy < $eYY || ($yy === $eYY && $mm <= $eMM)) {
        $keys[] = sprintf('%02d-%02d', $mm, $yy);
        $mm += 1;
        if ($mm > 12) { $mm = 1; $yy += 1; }
    }
    return $keys;
}

// Build unique cohort-year keys (MM-YY) from data rows
function build_unique_cohort_keys_from_rows($rows, $cohortIdx, $yearIdx) {
    $map = [];
    foreach ($rows as $row) {
        if (!isset($row[$cohortIdx], $row[$yearIdx])) continue;
        $key = sprintf('%02d-%02d', (int)$row[$cohortIdx], (int)$row[$yearIdx]);
        $map[$key] = true;
    }
    return array_keys($map);
}

// Format a cohort key (MM-YY) to label like "Aug 25"
function format_cohort_label($key) {
    $months = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
    $parts = explode('-', $key);
    if (count($parts) !== 2) return $key;
    $idx = max(1, min(12, (int)$parts[0])) - 1;
    return $months[$idx] . ' ' . $parts[1];
}

$filtered = [];
$reportCaption = '';
if ($validRange) {
    $isAllRange = ($start === $minStartDate && $end === date('m-d-y'));

    // Use DRY service for column indices
    $submittedIdx = GoogleSheetsColumns::SUBMISSIONS['SUBMITTED'];
    $cohortIdx = GoogleSheetsColumns::SUBMISSIONS['COHORT'];
    $yearIdx = GoogleSheetsColumns::SUBMISSIONS['YEAR'];

    if ($mode === 'cohort') {
        // Build cohort filter
        if ($cohortParam === 'ALL') {
            // First, restrict to rows whose Submitted date is within the selected date range
            if ($isAllRange) {
                $inRange = $submissionsData;
            } else {
                // Use DRY service for date range filtering
                $inRange = DataProcessor::filterByDateRange($submissionsData, $start, $end, $submittedIdx);
            }

            // Build the unique cohort-year keys present in the in-range data (data-driven, not calendar-driven)
            $keys = array_flip(build_unique_cohort_keys_from_rows($inRange, $cohortIdx, $yearIdx));

            // Keep rows that match any cohort-year present in the in-range data
            $filtered = array_filter($inRange, function($row) use ($keys, $cohortIdx, $yearIdx) {
                if (!isset($row[$cohortIdx], $row[$yearIdx])) return false;
                $key = sprintf('%02d-%02d', (int)$row[$cohortIdx], (int)$row[$yearIdx]);
                return isset($keys[$key]);
            });
            // Sort: Year desc, Cohort desc, Submitted desc
            usort($filtered, function($a, $b) use ($yearIdx, $cohortIdx, $submittedIdx) {
                $ya = isset($a[$yearIdx]) ? (int)$a[$yearIdx] : -1;
                $yb = isset($b[$yearIdx]) ? (int)$b[$yearIdx] : -1;
                if ($yb !== $ya) return $yb <=> $ya;
                $ma = isset($a[$cohortIdx]) ? (int)$a[$cohortIdx] : -1;
                $mb = isset($b[$cohortIdx]) ? (int)$b[$cohortIdx] : -1;
                if ($mb !== $ma) return $mb <=> $ma;
                $da = isset($a[$submittedIdx]) ? DateTime::createFromFormat('m-d-y', (string)$a[$submittedIdx]) : false;
                $db = isset($b[$submittedIdx]) ? DateTime::createFromFormat('m-d-y', (string)$b[$submittedIdx]) : false;
                $ta = $da ? (int)$da->format('U') : -1;
                $tb = $db ? (int)$db->format('U') : -1;
                return $tb <=> $ta;
            });
            $reportCaption = "Registrations for All Cohorts | {$start} - {$end}";
        } elseif (preg_match('/^\d{2}-\d{2}$/', $cohortParam)) {
            $filtered = array_filter($submissionsData, function($row) use ($cohortParam, $cohortIdx, $yearIdx) {
                if (!isset($row[$cohortIdx], $row[$yearIdx])) return false;
                $key = sprintf('%02d-%02d', (int)$row[$cohortIdx], (int)$row[$yearIdx]);
                return $key === $cohortParam;
            });
            // Sort: Year desc, Cohort desc, Submitted desc
            usort($filtered, function($a, $b) use ($yearIdx, $cohortIdx, $submittedIdx) {
                $ya = isset($a[$yearIdx]) ? (int)$a[$yearIdx] : -1;
                $yb = isset($b[$yearIdx]) ? (int)$b[$yearIdx] : -1;
                if ($yb !== $ya) return $yb <=> $ya;
                $ma = isset($a[$cohortIdx]) ? (int)$a[$cohortIdx] : -1;
                $mb = isset($b[$cohortIdx]) ? (int)$b[$cohortIdx] : -1;
                if ($mb !== $ma) return $mb <=> $ma;
                $da = isset($a[$submittedIdx]) ? DateTime::createFromFormat('m-d-y', (string)$a[$submittedIdx]) : false;
                $db = isset($b[$submittedIdx]) ? DateTime::createFromFormat('m-d-y', (string)$b[$submittedIdx]) : false;
                $ta = $da ? (int)$da->format('U') : -1;
                $tb = $db ? (int)$db->format('U') : -1;
                return $tb <=> $ta;
            });
            $reportCaption = 'Registrations for ' . format_cohort_label($cohortParam) . " Cohort | {$start} - {$end}";
        } else {
            // Fallback to by date if no valid cohort provided
            if ($isAllRange) {
                $filtered = $submissionsData;
            } else {
                // Use DRY service for date range filtering
                $filtered = DataProcessor::filterByDateRange($submissionsData, $start, $end, $submittedIdx);
            }
            $reportCaption = "Registrations by Date | {$start} - {$end}";
        }
    } else {
        // By date (existing logic)
        if ($isAllRange) {
            $filtered = $submissionsData;
        } else {
            // Use DRY service for date range filtering
            $filtered = DataProcessor::filterByDateRange($submissionsData, $start, $end, $submittedIdx);
        }
        $reportCaption = "Registrations by Date | {$start} - {$end}";
    }
}