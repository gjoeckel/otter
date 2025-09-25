<?php
/**
 * organizations_api.php
 *
 * Provides dashboard data for an organization using enterprise-specific cache file:
 *   - cache/{enterprise_code}/all-registrants-data.json
 *
 * Output structure matches SheetAPI for drop-in replacement.
 *
 * MVP, simple, reliable, accurate, WCAG compliant.
 *
 * Data fields are mapped based on api.md mapping.
 *
 * GOOGLE SHEETS COLUMN NUMBERING:
 * - Google Sheets uses 1-based column indexing (A=1, B=2, C=3, etc.)
 * - Array indices are 0-based (0, 1, 2, etc.)
 * - Mapping: Google Sheets Column A → Array Index 0, Column B → Array Index 1, etc.
 *
 * KEY COLUMN MAPPINGS (from config/csu.config):
 * - Registration Date: Google Sheets Column B (1) → Array Index 1
 * - Enrollment Status: Google Sheets Column C (2) → Array Index 2
 * - Organization: Google Sheets Column J (9) → Array Index 9
 * - Certificate Status: Google Sheets Column K (10) → Array Index 10
 * - Certificate Issued Date: Google Sheets Column L (11) → Array Index 11
 * - Submission Date: Google Sheets Column P (15) → Array Index 15
 * - Status: Google Sheets Column Q (16) → Array Index 16
 */

require_once __DIR__ . '/../unified_enterprise_config.php';
require_once __DIR__ . '/../enterprise_cache_manager.php';
require_once __DIR__ . '/../abbreviation_utils.php';
require_once __DIR__ . '/../dashboard_data_service.php';

class OrganizationsAPI {
    private static $registrants = null;
    private static $cacheLoaded = false;
    private static $globalTimestamp = null;
    private static $cacheManager = null;

    /**
     * Get hardcoded column index for Google Sheets integration
     * @param string $columnName - Column name for reference
     * @return int Hardcoded Google Sheets column index (0-based)
     */
    private static function getColumnIndex($columnName) {
        // Always use hardcoded indices for Google Sheets integration
        // This is the best practice for reliable Google Sheets data processing
        $indices = [
            'DaysToClose' => 0,    // Google Sheets Column A (0)
            'Invited' => 1,        // Google Sheets Column B (1)
            'Enrolled' => 2,       // Google Sheets Column C (2)
            'Cohort' => 3,         // Google Sheets Column D (3)
            'Year' => 4,           // Google Sheets Column E (4)
            'First' => 5,          // Google Sheets Column F (5)
            'Last' => 6,           // Google Sheets Column G (6)
            'Email' => 7,          // Google Sheets Column H (7)
            'Role' => 8,           // Google Sheets Column I (8)
            'Organization' => 9,   // Google Sheets Column J (9)
            'Certificate' => 10,   // Google Sheets Column K (10)
            'Issued' => 11,        // Google Sheets Column L (11)
            'ClosingDate' => 12,   // Google Sheets Column M (12)
            'Completed' => 13,     // Google Sheets Column N (13)
            'ID' => 14,            // Google Sheets Column O (14)
            'Submitted' => 15,     // Google Sheets Column P (15)
            'Status' => 16         // Google Sheets Column Q (16)
        ];

        return $indices[$columnName] ?? 0;
    }

    private static function getCacheManager() {
        if (self::$cacheManager === null) {
            // Initialize EnterpriseConfig if not already done
            if (UnifiedEnterpriseConfig::getEnterpriseCode() === null) {
                UnifiedEnterpriseConfig::init();
            }
            self::$cacheManager = EnterpriseCacheManager::getInstance();
        }
        return self::$cacheManager;
    }

    private static function loadCache() {
        if (self::$cacheLoaded) return;

        $cacheManager = self::getCacheManager();
        $regPath = $cacheManager->getRegistrantsCachePath();

        $json = $cacheManager->readCacheFile('all-registrants-data.json');

        self::$registrants = isset($json['data']) ? $json['data'] : [];
        self::$globalTimestamp = isset($json['global_timestamp']) ? $json['global_timestamp'] : null;
        self::$cacheLoaded = true;
    }

    public static function getOrgData($selectedOrg, $globalTimestamp = null) {
        self::loadCache();
        $orgName = $selectedOrg;
        $timestamp = $globalTimestamp !== null ? $globalTimestamp : self::$globalTimestamp;
        
        // Use unified Dashboard Data Service for consistent data processing
        $dashboardData = DashboardDataService::getOrganizationDashboardData($orgName);
        $rawData = DashboardDataService::getOrganizationRawData($orgName);
        
        return [
            'api_retrieval_timestamp' => $timestamp,
            'enrollment' => $rawData, // Raw data for backward compatibility
            'enrolled' => $dashboardData['enrolled_participants'],
            'invited' => $dashboardData['invited_participants']
        ];
    }

    private static function findLatestTimestamp($orgName) {
        // Get column indices from configuration
        $orgIdx = self::getColumnIndex('Organization'); // Google Sheets Column J (9)
        $statusIdx = self::getColumnIndex('Status'); // Google Sheets Column Q (16)

        $latest = null;
        foreach (self::$registrants as $row) {
            if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName && isset($row[$statusIdx])) {
                if ($latest === null || $row[$statusIdx] > $latest) {
                    $latest = $row[$statusIdx];
                }
            }
        }
        return $latest;
    }

    private static function processEnrollment($orgName) {
        // Get column indices from configuration
        $daysToCloseIdx = self::getColumnIndex('DaysToClose'); // Google Sheets Column A (0)
        $regDateIdx = self::getColumnIndex('Invited'); // Google Sheets Column B (1)
        $enrolledIdx = self::getColumnIndex('Enrolled'); // Google Sheets Column C (2)
        $cohortIdx = self::getColumnIndex('Cohort'); // Google Sheets Column D (3)
        $yearIdx = self::getColumnIndex('Year'); // Google Sheets Column E (4)
        $firstIdx = self::getColumnIndex('First'); // Google Sheets Column F (5)
        $lastIdx = self::getColumnIndex('Last'); // Google Sheets Column G (6)
        $emailIdx = self::getColumnIndex('Email'); // Google Sheets Column H (7)
        $roleIdx = self::getColumnIndex('Role'); // Google Sheets Column I (8)
        $orgIdx = self::getColumnIndex('Organization'); // Google Sheets Column J (9)
        $certificateIdx = self::getColumnIndex('Certificate'); // Google Sheets Column K (10)
        $issuedIdx = self::getColumnIndex('Issued'); // Google Sheets Column L (11)
        $closingDateIdx = self::getColumnIndex('ClosingDate'); // Google Sheets Column M (12)
        $completedIdx = self::getColumnIndex('Completed'); // Google Sheets Column N (13)
        $idIdx = self::getColumnIndex('ID'); // Google Sheets Column O (14)
        $submittedIdx = self::getColumnIndex('Submitted'); // Google Sheets Column P (15)
        $statusIdx = self::getColumnIndex('Status'); // Google Sheets Column Q (16)

        $enrollment = [];
        foreach (self::$registrants as $row) {
            if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName) {
                $enrollment[] = [
                    'daystoclose' => $row[$daysToCloseIdx] ?? '',
                    'invited' => $row[$regDateIdx],
                    'enrolled' => $row[$enrolledIdx],
                    'cohort' => $row[$cohortIdx],
                    'year' => $row[$yearIdx],
                    'first' => $row[$firstIdx],
                    'last' => $row[$lastIdx],
                    'email' => $row[$emailIdx],
                    'role' => $row[$roleIdx],
                    'organization' => $row[$orgIdx],
                    'certificate' => $row[$certificateIdx],
                    'issued' => $row[$issuedIdx],
                    'closing_date' => $row[$closingDateIdx],
                    'completed' => $row[$completedIdx],
                    'id' => $row[$idIdx],
                    'submitted' => $row[$submittedIdx],
                    'token' => $row[$statusIdx]
                ];
            }
        }
        return $enrollment;
    }

    private static function processEnrolled($orgName) {
        // Get column indices from configuration
        $daysToCloseIdx = self::getColumnIndex('DaysToClose'); // Google Sheets Column A (0)
        $regDateIdx = self::getColumnIndex('Invited'); // Google Sheets Column B (1)
        $enrolledIdx = self::getColumnIndex('Enrolled'); // Google Sheets Column C (2)
        $cohortIdx = self::getColumnIndex('Cohort'); // Google Sheets Column D (3)
        $yearIdx = self::getColumnIndex('Year'); // Google Sheets Column E (4)
        $firstIdx = self::getColumnIndex('First'); // Google Sheets Column F (5)
        $lastIdx = self::getColumnIndex('Last'); // Google Sheets Column G (6)
        $emailIdx = self::getColumnIndex('Email'); // Google Sheets Column H (7)
        $roleIdx = self::getColumnIndex('Role'); // Google Sheets Column I (8)
        $orgIdx = self::getColumnIndex('Organization'); // Google Sheets Column J (9)
        $certificateIdx = self::getColumnIndex('Certificate'); // Google Sheets Column K (10)
        $issuedIdx = self::getColumnIndex('Issued'); // Google Sheets Column L (11)
        $closingDateIdx = self::getColumnIndex('ClosingDate'); // Google Sheets Column M (12)
        $completedIdx = self::getColumnIndex('Completed'); // Google Sheets Column N (13)
        $idIdx = self::getColumnIndex('ID'); // Google Sheets Column O (14)
        $submittedIdx = self::getColumnIndex('Submitted'); // Google Sheets Column P (15)
        $statusIdx = self::getColumnIndex('Status'); // Google Sheets Column Q (16)

        $enrolled = [];
        foreach (self::$registrants as $row) {
            if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName && isset($row[$enrolledIdx]) && $row[$enrolledIdx] === 'Yes') {
                $enrolled[] = [
                    'daystoclose' => $row[$daysToCloseIdx] ?? '',
                    'invited' => $row[$regDateIdx],
                    'enrolled' => $row[$enrolledIdx],
                    'cohort' => $row[$cohortIdx],
                    'year' => $row[$yearIdx],
                    'first' => $row[$firstIdx],
                    'last' => $row[$lastIdx],
                    'email' => $row[$emailIdx],
                    'role' => $row[$roleIdx],
                    'organization' => $row[$orgIdx],
                    'certificate' => $row[$certificateIdx],
                    'issued' => $row[$issuedIdx],
                    'closing_date' => $row[$closingDateIdx],
                    'completed' => $row[$completedIdx],
                    'id' => $row[$idIdx],
                    'submitted' => $row[$submittedIdx],
                    'token' => $row[$statusIdx]
                ];
            }
        }
        return $enrolled;
    }

    private static function processInvited($orgName) {
        // Get column indices from configuration
        $regDateIdx = self::getColumnIndex('Invited'); // Google Sheets Column B (1)
        $enrolledIdx = self::getColumnIndex('Enrolled'); // Google Sheets Column C (2)
        $cohortIdx = self::getColumnIndex('Cohort'); // Google Sheets Column D (3)
        $yearIdx = self::getColumnIndex('Year'); // Google Sheets Column E (4)
        $firstIdx = self::getColumnIndex('First'); // Google Sheets Column F (5)
        $lastIdx = self::getColumnIndex('Last'); // Google Sheets Column G (6)
        $emailIdx = self::getColumnIndex('Email'); // Google Sheets Column H (7)
        $orgIdx = self::getColumnIndex('Organization'); // Google Sheets Column J (9)

        $invited = [];
        foreach (self::$registrants as $row) {
            if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName && (!isset($row[$enrolledIdx]) || $row[$enrolledIdx] !== 'Yes')) {
                $invited[] = [
                    'invited' => $row[$regDateIdx],
                    'cohort' => $row[$cohortIdx],
                    'year' => $row[$yearIdx],
                    'first' => $row[$firstIdx],
                    'last' => $row[$lastIdx],
                    'email' => $row[$emailIdx]
                ];
            }
        }
        return $invited;
    }

    /**
     * Get all organizations data for the "all" range.
     * Uses unified Dashboard Data Service for consistent data processing.
     */
    public static function getAllOrganizationsDataAllRange() {
        // Use unified Dashboard Data Service for consistent data processing
        $orgData = DashboardDataService::getAllOrganizationsData();
        
        // Apply abbreviation to organization display names
        foreach ($orgData as &$org) {
            $org['organization_display'] = abbreviateOrganizationName($org['organization']);
        }
        
        return $orgData;
    }

    // Helper: MM-DD-YY in range
    private static function inRange($date, $start, $end) {
        $dt = DateTime::createFromFormat('m-d-y', $date);
        $dtStart = DateTime::createFromFormat('m-d-y', $start);
        $dtEnd = DateTime::createFromFormat('m-d-y', $end);
        if (!$dt || !$dtStart || !$dtEnd) return false;
        return $dt >= $dtStart && $dt <= $dtEnd;
    }

    /**
     * Checks if a cohort/year combination falls within a date range.
     * @param string $cohort Two-digit month string (MM)
     * @param string $year Two-digit year string (YY)
     * @param string $startDate Start date in MM-DD-YY format
     * @param string $endDate End date in MM-DD-YY format
     * @return bool True if cohort/year is within range
     */
    private static function isCohortYearInRange($cohort, $year, $startDate, $endDate) {
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

    /**
     * Returns all participant rows for the given org where Certificate == 'Yes' (no date validation).
     * Used for the Certificates Earned table for the 'All' preset.
     * Uses unified Dashboard Data Service for consistent data processing.
     */
    public static function getAllCertificatesEarnedRowsAllRange($orgName) {
        // Use unified Dashboard Data Service for consistent data processing
        $dashboardData = DashboardDataService::getOrganizationDashboardData($orgName);
        return $dashboardData['certificates_earned'];
    }
}
