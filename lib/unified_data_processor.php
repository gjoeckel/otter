<?php
/**
 * Unified Data Processor
 * Centralized data processing for all report tables with consistent enrollment mode handling
 * 
 * This class provides a unified approach to processing data for all report tables,
 * ensuring consistent enrollment mode behavior across Systemwide, Organizations, and Groups tables.
 */

require_once __DIR__ . '/unified_enterprise_config.php';
require_once __DIR__ . '/enterprise_features.php';
require_once __DIR__ . '/abbreviation_utils.php';

class UnifiedDataProcessor {
    
    /**
     * Process all tables with consistent enrollment mode handling
     * 
     * @param array $registrations - Processed registrations data
     * @param array $enrollments - Processed enrollments data  
     * @param array $certificates - Processed certificates data
     * @param string $enrollmentMode - Enrollment mode ('tou_completion' or 'registration_date')
     * @return array Unified data for all tables
     */
    public static function processAllTables($registrations, $enrollments, $certificates, $enrollmentMode) {
        // Log data pipeline start
        error_log("[DATA-PIPELINE] Starting unified data processing with enrollment mode: {$enrollmentMode}");
        
        $startTime = microtime(true);
        
        $systemwide = self::processSystemwideData($registrations, $enrollments, $certificates, $enrollmentMode);
        error_log("[DATA-PIPELINE] Systemwide data processed: " . json_encode($systemwide));
        
        $organizations = self::processOrganizationsData($registrations, $enrollments, $certificates, $enrollmentMode);
        error_log("[DATA-PIPELINE] Organizations data processed: " . count($organizations) . " organizations");
        
        $groups = self::processGroupsData($registrations, $enrollments, $certificates, $enrollmentMode);
        error_log("[DATA-PIPELINE] Groups data processed: " . count($groups) . " groups");
        
        $duration = (microtime(true) - $startTime) * 1000; // Convert to milliseconds
        error_log("[DATA-PIPELINE] Total processing time: {$duration}ms");
        
        return [
            'systemwide' => $systemwide,
            'organizations' => $organizations,
            'groups' => $groups
        ];
    }

    /**
     * Process systemwide data with enrollment mode context
     * 
     * @param array $registrations - Processed registrations data
     * @param array $enrollments - Processed enrollments data
     * @param array $certificates - Processed certificates data
     * @param string $enrollmentMode - Enrollment mode for context
     * @return array Systemwide data
     */
    private static function processSystemwideData($registrations, $enrollments, $certificates, $enrollmentMode) {
        return [
            'registrations_count' => count($registrations),
            'enrollments_count' => count($enrollments),
            'certificates_count' => count($certificates),
            'enrollment_mode' => $enrollmentMode
        ];
    }

    /**
     * Process organizations data with enrollment mode awareness
     * 
     * @param array $registrations - Processed registrations data
     * @param array $enrollments - Processed enrollments data
     * @param array $certificates - Processed certificates data
     * @param string $enrollmentMode - Enrollment mode for context
     * @return array Organizations data
     */
    private static function processOrganizationsData($registrations, $enrollments, $certificates, $enrollmentMode) {
        $orgIdx = 9; // Google Sheets Column J
        
        // Get all organizations from config
        $config = UnifiedEnterpriseConfig::getFullConfig();
        $configOrgs = $config['organizations'] ?? [];
        
        error_log("[DATA-PIPELINE] Processing organizations with enrollment mode: {$enrollmentMode}, found " . count($configOrgs) . " organizations");
        
        $organizations = [];
        foreach ($configOrgs as $orgName) {
            $orgData = [
                'organization' => $orgName,
                'organization_display' => abbreviateOrganizationName($orgName),
                'registrations' => self::countForOrganization($registrations, $orgIdx, $orgName),
                'enrollments' => self::countForOrganization($enrollments, $orgIdx, $orgName),
                'certificates' => self::countForOrganization($certificates, $orgIdx, $orgName)
            ];
            $organizations[] = $orgData;
        }
        
        error_log("[DATA-PIPELINE] Organizations processing complete: " . count($organizations) . " organizations processed");
        
        return $organizations;
    }

    /**
     * Process groups data with enrollment mode awareness
     * 
     * @param array $registrations - Processed registrations data
     * @param array $enrollments - Processed enrollments data
     * @param array $certificates - Processed certificates data
     * @param string $enrollmentMode - Enrollment mode for context
     * @return array Groups data
     */
    private static function processGroupsData($registrations, $enrollments, $certificates, $enrollmentMode) {
        if (!EnterpriseFeatures::supportsGroups()) {
            return [];
        }
        
        $enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
        $groupsFile = __DIR__ . "/../config/groups/{$enterpriseCode}.json";
        
        if (!file_exists($groupsFile)) {
            return [];
        }
        
        $groupsMap = json_decode(file_get_contents($groupsFile), true);
        $collegeToGroup = [];
        
        // Build college-to-group lookup
        foreach ($groupsMap as $group => $colleges) {
            foreach ($colleges as $college) {
                $collegeToGroup[$college] = $group;
            }
        }
        
        $groups = [];
        foreach ($groupsMap as $group => $colleges) {
            $groups[] = [
                'group' => $group,
                'registrations' => self::countForGroup($registrations, $collegeToGroup, $group),
                'enrollments' => self::countForGroup($enrollments, $collegeToGroup, $group),
                'certificates' => self::countForGroup($certificates, $collegeToGroup, $group)
            ];
        }
        
        return $groups;
    }

    /**
     * Count records for a specific organization
     * 
     * @param array $data - Data to count from
     * @param int $orgIdx - Organization column index
     * @param string $orgName - Organization name to count
     * @return int Count of records
     */
    private static function countForOrganization($data, $orgIdx, $orgName) {
        $count = 0;
        foreach ($data as $row) {
            if (isset($row[$orgIdx]) && $row[$orgIdx] === $orgName) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Count records for a specific group
     * 
     * @param array $data - Data to count from
     * @param array $collegeToGroup - College to group mapping
     * @param string $group - Group name to count
     * @return int Count of records
     */
    private static function countForGroup($data, $collegeToGroup, $group) {
        $count = 0;
        $orgIdx = 9; // Google Sheets Column J
        
        foreach ($data as $row) {
            if (isset($row[$orgIdx])) {
                $college = $row[$orgIdx];
                if (isset($collegeToGroup[$college]) && $collegeToGroup[$college] === $group) {
                    $count++;
                }
            }
        }
        return $count;
    }
}
