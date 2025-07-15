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

class OrganizationsAPI {
    private static $registrants = null;
    private static $cacheLoaded = false;
    private static $globalTimestamp = null;
    private static $cacheManager = null;

    /**
     * Get column index from configuration
     * @param string $columnName - Column name from config
     * @return int|null Column index or null if not found
     */
    private static function getColumnIndex($columnName) {
        $config = UnifiedEnterpriseConfig::getGoogleSheets();
        return $config['registrants']['columns'][$columnName]['index'] ?? null;
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
        return [
            'api_retrieval_timestamp' => $timestamp,
            'enrollment' => self::processEnrollment($orgName),
            'enrolled' => self::processEnrolled($orgName),
            'invited' => self::processInvited($orgName)
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
     * Reads minStartDate from UnifiedEnterpriseConfig and uses today's date as end.
     */
    public static function getAllOrganizationsDataAllRange() {
        $minStartDate = UnifiedEnterpriseConfig::getStartDate();
        $endDate = date('m-d-y');
        self::loadCache();
        $orgCounts = [];
        $isAllRange = true; // This function is specifically for "all" range
        
        // Get column indices from configuration
        $orgIdx = self::getColumnIndex('Organization'); // Google Sheets Column J (9)
        $regDateIdx = self::getColumnIndex('Invited'); // Google Sheets Column B (1)
        $enrolledIdx = self::getColumnIndex('Enrolled'); // Google Sheets Column C (2)
        $certificateIdx = self::getColumnIndex('Certificate'); // Google Sheets Column K (10)
        $issuedIdx = self::getColumnIndex('Issued'); // Google Sheets Column L (11)
        
        foreach (self::$registrants as $row) {
            // Date columns: Registration (B/1), Enrolled (C/2), Certificate (K/10), Issued (L/11), Organization (J/9)
            $org = isset($row[$orgIdx]) ? trim($row[$orgIdx]) : '';
            if ($org === '') continue;
            $regDate = isset($row[$regDateIdx]) ? trim($row[$regDateIdx]) : '';
            $enrolled = isset($row[$enrolledIdx]) && $row[$enrolledIdx] === 'Yes';
            $certificate = isset($row[$certificateIdx]) && $row[$certificateIdx] === 'Yes';
            $issuedDate = isset($row[$issuedIdx]) ? trim($row[$issuedIdx]) : '';
            
            // Check if organization has any activity in the range
            $hasActivity = false;
            
            // Check registration activity
            if (self::inRange($regDate, $minStartDate, $endDate)) {
                $hasActivity = true;
            }
            
            // Check enrollment activity (if enrolled and registration date in range)
            if ($enrolled && self::inRange($regDate, $minStartDate, $endDate)) {
                $hasActivity = true;
            }
            
            // Check certificate activity (if certificate exists and issued date in range)
            if ($certificate && self::inRange($issuedDate, $minStartDate, $endDate)) {
                $hasActivity = true;
            }
            
            // Skip if no activity in range
            if (!$hasActivity) continue;
            
            if (!isset($orgCounts[$org])) {
                $orgCounts[$org] = [
                    'organization' => $org,
                    'organization_display' => abbreviateOrganizationName($org),
                    'registrations' => 0,
                    'enrollments' => 0,
                    'certificates' => 0
                ];
            }
            $orgCounts[$org]['registrations']++;
            if ($enrolled) $orgCounts[$org]['enrollments']++;
            // Certificates: for 'All' range, count all 'Yes'; for others, check issued date
            if ($isAllRange) {
                if ($certificate) {
                    $orgCounts[$org]['certificates']++;
                }
            } else {
                if ($certificate && self::inRange($issuedDate, $minStartDate, $endDate)) {
                    $orgCounts[$org]['certificates']++;
                }
            }
        }
        
        // Ensure ALL organizations from the config are included, even if they have no data
        $config = UnifiedEnterpriseConfig::getFullConfig();
        if (isset($config['organizations']) && is_array($config['organizations'])) {
            foreach ($config['organizations'] as $configOrg) {
                if (!isset($orgCounts[$configOrg])) {
                    $orgCounts[$configOrg] = [
                        'organization' => $configOrg,
                        'organization_display' => abbreviateOrganizationName($configOrg),
                        'registrations' => 0,
                        'enrollments' => 0,
                        'certificates' => 0
                    ];
                }
            }
        }
        
        // Note: Organizations with all zero values are included to support client-side data display options
        // The client-side filtering logic in data-display-options.js handles showing/hiding based on user selection
        
        // Sort orgs alphabetically
        usort($orgCounts, function($a, $b) {
            return strcasecmp($a['organization'], $b['organization']);
        });
        return array_values($orgCounts);
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
     */
    public static function getAllCertificatesEarnedRowsAllRange($orgName) {
        self::loadCache();
        
        // Get column indices from configuration
        $orgIdx = self::getColumnIndex('Organization'); // Google Sheets Column J (9)
        $certificateIdx = self::getColumnIndex('Certificate'); // Google Sheets Column K (10)
        $cohortIdx = self::getColumnIndex('Cohort'); // Google Sheets Column D (3)
        $yearIdx = self::getColumnIndex('Year'); // Google Sheets Column E (4)
        $firstIdx = self::getColumnIndex('First'); // Google Sheets Column F (5)
        $lastIdx = self::getColumnIndex('Last'); // Google Sheets Column G (6)
        $emailIdx = self::getColumnIndex('Email'); // Google Sheets Column H (7)
        
        $rows = [];
        foreach (self::$registrants as $row) {
            if (isset($row[$orgIdx], $row[$certificateIdx]) && $row[$orgIdx] === $orgName && trim($row[$certificateIdx]) === 'Yes') {
                $rows[] = [
                    'cohort' => $row[$cohortIdx],
                    'year' => $row[$yearIdx],
                    'first' => $row[$firstIdx],
                    'last' => $row[$lastIdx],
                    'email' => $row[$emailIdx]
                ];
            }
        }
        return $rows;
    }
} 