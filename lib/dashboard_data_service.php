<?php
/**
 * Dashboard Data Service
 * Unified data processing service for all dashboard components
 * 
 * This service consolidates all dashboard data processing logic into a single,
 * reliable, DRY implementation that eliminates code duplication and fixes
 * broken enrollment logic across dashboard components.
 *
 * GOOGLE SHEETS COLUMN MAPPING (0-based array indices):
 * - Column A (0): DaysToClose
 * - Column B (1): Invited (MM-DD-YY format)
 * - Column C (2): Enrolled (MM-DD-YY format or "-"/blank)
 * - Column D (3): Cohort (MM format)
 * - Column E (4): Year (YY format)
 * - Column F (5): First (first name)
 * - Column G (6): Last (last name)
 * - Column H (7): Email
 * - Column I (8): Role
 * - Column J (9): Organization (organization name)
 * - Column K (10): Certificate ("Yes" or other)
 * - Column L (11): Issued
 * - Column M (12): ClosingDate
 * - Column N (13): Completed
 * - Column O (14): ID
 * - Column P (15): Submitted
 * - Column Q (16): Status
 */

require_once __DIR__ . '/unified_enterprise_config.php';
require_once __DIR__ . '/enterprise_cache_manager.php';

class DashboardDataService {
    private static $registrants = null;
    private static $cacheLoaded = false;
    private static $cacheManager = null;

    // Centralized column index definitions
    private static function getColumnIndex($columnName) {
        $indices = [
            'DaysToClose' => 0,    // Column A (0)
            'Invited' => 1,        // Column B (1)
            'Enrolled' => 2,       // Column C (2)
            'Cohort' => 3,         // Column D (3)
            'Year' => 4,           // Column E (4)
            'First' => 5,          // Column F (5)
            'Last' => 6,           // Column G (6)
            'Email' => 7,          // Column H (7)
            'Role' => 8,           // Column I (8)
            'Organization' => 9,   // Column J (9)
            'Certificate' => 10,   // Column K (10)
            'Issued' => 11,        // Column L (11)
            'ClosingDate' => 12,   // Column M (12)
            'Completed' => 13,     // Column N (13)
            'ID' => 14,            // Column O (14)
            'Submitted' => 15,     // Column P (15)
            'Status' => 16         // Column Q (16)
        ];
        return $indices[$columnName] ?? 0;
    }

    private static function getCacheManager() {
        if (self::$cacheManager === null) {
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
        $json = $cacheManager->readCacheFile('all-registrants-data.json');
        
        self::$registrants = isset($json['data']) ? $json['data'] : [];
        self::$cacheLoaded = true;
    }

    /**
     * Get all dashboard data for a specific organization
     * Dashboard pages are ALWAYS for the "All" date range with no date filtering
     * 
     * @param string $organizationName - Organization name to filter by
     * @return array Complete dashboard data structure
     */
    public static function getOrganizationDashboardData($organizationName) {
        self::loadCache();
        
        return [
            'enrollment_summary' => self::getEnrollmentSummary($organizationName),
            'enrolled_participants' => self::getEnrolledParticipants($organizationName),
            'invited_participants' => self::getInvitedParticipants($organizationName),
            'certificates_earned' => self::getCertificatesEarned($organizationName)
        ];
    }

    /**
     * 1. Enrollment Summary Table
     * Show all Cohort-Year combinations that have at least one enrollment
     * Sort: Year descending, then Cohort descending
     */
    private static function getEnrollmentSummary($organizationName) {
        $orgIdx = self::getColumnIndex('Organization');
        $cohortIdx = self::getColumnIndex('Cohort');
        $yearIdx = self::getColumnIndex('Year');
        $enrolledIdx = self::getColumnIndex('Enrolled');
        $completedIdx = self::getColumnIndex('Completed');
        $certificateIdx = self::getColumnIndex('Certificate');

        $grouped = [];
        
        foreach (self::$registrants as $row) {
            // Filter by organization
            if (!isset($row[$orgIdx]) || $row[$orgIdx] !== $organizationName) {
                continue;
            }
            
            $cohort = $row[$cohortIdx] ?? '';
            $year = $row[$yearIdx] ?? '';
            $enrolled = $row[$enrolledIdx] ?? '';
            
            // Only include groups where at least one row has enrollment (not "-" and not blank)
            if (empty($cohort) || empty($year) || (empty($enrolled) || $enrolled === '-')) {
                continue;
            }
            
            $key = $cohort . '-' . $year;
            
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'cohort' => $cohort,
                    'year' => $year,
                    'enrollments' => 0,
                    'completed' => 0,
                    'certificates' => 0
                ];
            }
            
            // Count enrollments, completions, and certificates
            $grouped[$key]['enrollments'] += ($enrolled !== '-' && !empty($enrolled)) ? 1 : 0;
            $grouped[$key]['completed'] += (($row[$completedIdx] ?? '') === 'Yes') ? 1 : 0;
            $grouped[$key]['certificates'] += (($row[$certificateIdx] ?? '') === 'Yes') ? 1 : 0;
        }

        // Convert to array and sort
        $summary = array_values($grouped);
        
        // Sort: Year descending, then Cohort descending
        usort($summary, function($a, $b) {
            $yearDiff = strcmp($b['year'], $a['year']);
            if ($yearDiff !== 0) return $yearDiff;
            return strcmp($b['cohort'], $a['cohort']);
        });

        return $summary;
    }

    /**
     * 2. Enrolled Participants
     * Show all registrants who are actively enrolled (not closed)
     * Sort: Year descending, Cohort descending, Last ascending, First ascending
     */
    private static function getEnrolledParticipants($organizationName) {
        $orgIdx = self::getColumnIndex('Organization');
        $daysToCloseIdx = self::getColumnIndex('DaysToClose');
        $cohortIdx = self::getColumnIndex('Cohort');
        $yearIdx = self::getColumnIndex('Year');
        $firstIdx = self::getColumnIndex('First');
        $lastIdx = self::getColumnIndex('Last');
        $emailIdx = self::getColumnIndex('Email');
        $completedIdx = self::getColumnIndex('Completed');
        $certificateIdx = self::getColumnIndex('Certificate');

        $enrolled = [];
        
        foreach (self::$registrants as $row) {
            // Filter by organization and not closed
            if (!isset($row[$orgIdx]) || $row[$orgIdx] !== $organizationName) {
                continue;
            }
            
            $daysToClose = $row[$daysToCloseIdx] ?? '';
            
            // Include only rows where DaysToClose is NOT closed
            if ($daysToClose === 'closed' || empty($daysToClose)) {
                continue;
            }
            
            $enrolled[] = [
                'daystoclose' => $daysToClose,
                'cohort' => $row[$cohortIdx] ?? '',
                'year' => $row[$yearIdx] ?? '',
                'first' => $row[$firstIdx] ?? '',
                'last' => $row[$lastIdx] ?? '',
                'email' => $row[$emailIdx] ?? '',
                'completed' => ($row[$completedIdx] ?? '') === 'Yes' ? '1' : '0',
                'certificate' => ($row[$certificateIdx] ?? '') === 'Yes' ? '1' : '0'
            ];
        }

        // Sort: Year descending, Cohort descending, Last ascending, First ascending
        usort($enrolled, function($a, $b) {
            $yearDiff = strcmp($b['year'] ?? '', $a['year'] ?? '');
            if ($yearDiff !== 0) return $yearDiff;
            
            $cohortDiff = strcmp($b['cohort'] ?? '', $a['cohort'] ?? '');
            if ($cohortDiff !== 0) return $cohortDiff;
            
            $lastDiff = strcmp($a['last'] ?? '', $b['last'] ?? '');
            if ($lastDiff !== 0) return $lastDiff;
            
            return strcmp($a['first'] ?? '', $b['first'] ?? '');
        });

        return $enrolled;
    }

    /**
     * 3. Invited Participants
     * Show all registrants who are invited but not yet enrolled
     * Sort: Year descending, Cohort descending, Invited descending, Last ascending, First ascending
     */
    private static function getInvitedParticipants($organizationName) {
        $orgIdx = self::getColumnIndex('Organization');
        $invitedIdx = self::getColumnIndex('Invited');
        $enrolledIdx = self::getColumnIndex('Enrolled');
        $cohortIdx = self::getColumnIndex('Cohort');
        $yearIdx = self::getColumnIndex('Year');
        $firstIdx = self::getColumnIndex('First');
        $lastIdx = self::getColumnIndex('Last');
        $emailIdx = self::getColumnIndex('Email');

        $invited = [];
        
        foreach (self::$registrants as $row) {
            // Filter by organization
            if (!isset($row[$orgIdx]) || $row[$orgIdx] !== $organizationName) {
                continue;
            }
            
            $enrolled = $row[$enrolledIdx] ?? '';
            
            // Include rows where Enrolled is "-" or blank
            if (!empty($enrolled) && $enrolled !== '-') {
                continue;
            }
            
            $invited[] = [
                'invited' => $row[$invitedIdx] ?? '',
                'cohort' => $row[$cohortIdx] ?? '',
                'year' => $row[$yearIdx] ?? '',
                'first' => $row[$firstIdx] ?? '',
                'last' => $row[$lastIdx] ?? '',
                'email' => $row[$emailIdx] ?? ''
            ];
        }

        // Sort: Year descending, Cohort descending, Invited descending, Last ascending, First ascending
        usort($invited, function($a, $b) {
            $yearDiff = strcmp($b['year'] ?? '', $a['year'] ?? '');
            if ($yearDiff !== 0) return $yearDiff;
            
            $cohortDiff = strcmp($b['cohort'] ?? '', $a['cohort'] ?? '');
            if ($cohortDiff !== 0) return $cohortDiff;
            
            // Invited date descending (string comparison for MM-DD-YY format)
            $invitedDiff = strcmp($b['invited'] ?? '', $a['invited'] ?? '');
            if ($invitedDiff !== 0) return $invitedDiff;
            
            $lastDiff = strcmp($a['last'] ?? '', $b['last'] ?? '');
            if ($lastDiff !== 0) return $lastDiff;
            
            return strcmp($a['first'] ?? '', $b['first'] ?? '');
        });

        return $invited;
    }

    /**
     * 4. Certificates Earned
     * Show all registrants who have earned certificates
     * Sort: Year descending, Cohort descending, Last ascending, First ascending
     */
    private static function getCertificatesEarned($organizationName) {
        $orgIdx = self::getColumnIndex('Organization');
        $certificateIdx = self::getColumnIndex('Certificate');
        $cohortIdx = self::getColumnIndex('Cohort');
        $yearIdx = self::getColumnIndex('Year');
        $firstIdx = self::getColumnIndex('First');
        $lastIdx = self::getColumnIndex('Last');
        $emailIdx = self::getColumnIndex('Email');

        $certificates = [];
        
        foreach (self::$registrants as $row) {
            // Filter by organization and certificate earned
            if (!isset($row[$orgIdx]) || $row[$orgIdx] !== $organizationName) {
                continue;
            }
            
            $certificate = $row[$certificateIdx] ?? '';
            
            // Include rows where Certificate is "Yes"
            if ($certificate !== 'Yes') {
                continue;
            }
            
            $certificates[] = [
                'cohort' => $row[$cohortIdx] ?? '',
                'year' => $row[$yearIdx] ?? '',
                'first' => $row[$firstIdx] ?? '',
                'last' => $row[$lastIdx] ?? '',
                'email' => $row[$emailIdx] ?? ''
            ];
        }

        // Sort: Year descending, Cohort descending, Last ascending, First ascending
        usort($certificates, function($a, $b) {
            $yearDiff = strcmp($b['year'] ?? '', $a['year'] ?? '');
            if ($yearDiff !== 0) return $yearDiff;
            
            $cohortDiff = strcmp($b['cohort'] ?? '', $a['cohort'] ?? '');
            if ($cohortDiff !== 0) return $cohortDiff;
            
            $lastDiff = strcmp($a['last'] ?? '', $b['last'] ?? '');
            if ($lastDiff !== 0) return $lastDiff;
            
            return strcmp($a['first'] ?? '', $b['first'] ?? '');
        });

        return $certificates;
    }

    /**
     * Get raw registrants data for an organization
     * Used for backward compatibility and custom processing
     */
    public static function getOrganizationRawData($organizationName) {
        self::loadCache();
        
        $orgIdx = self::getColumnIndex('Organization');
        $rawData = [];
        
        foreach (self::$registrants as $row) {
            if (isset($row[$orgIdx]) && $row[$orgIdx] === $organizationName) {
                $rawData[] = $row;
            }
        }
        
        return $rawData;
    }

    /**
     * Get all organizations data for reports
     * Maintains compatibility with existing reports system
     */
    public static function getAllOrganizationsData() {
        self::loadCache();
        
        $orgIdx = self::getColumnIndex('Organization');
        $orgCounts = [];
        
        // Get all organizations from config first
        $config = UnifiedEnterpriseConfig::getFullConfig();
        $configOrgs = $config['organizations'] ?? [];
        
        foreach ($configOrgs as $orgName) {
            $orgCounts[$orgName] = [
                'organization' => $orgName,
                'organization_display' => $orgName, // Will be abbreviated by calling code if needed
                'registrations' => 0,
                'enrollments' => 0,
                'certificates' => 0
            ];
        }
        
        // Count data for each organization
        foreach (self::$registrants as $row) {
            $org = $row[$orgIdx] ?? '';
            if (empty($org)) continue;
            
            if (!isset($orgCounts[$org])) {
                $orgCounts[$org] = [
                    'organization' => $org,
                    'organization_display' => $org,
                    'registrations' => 0,
                    'enrollments' => 0,
                    'certificates' => 0
                ];
            }
            
            $orgCounts[$org]['registrations']++;
            
            // Count enrollments (Enrolled column not "-" and not blank)
            $enrolled = $row[self::getColumnIndex('Enrolled')] ?? '';
            if (!empty($enrolled) && $enrolled !== '-') {
                $orgCounts[$org]['enrollments']++;
            }
            
            // Count certificates (Certificate column is "Yes")
            $certificate = $row[self::getColumnIndex('Certificate')] ?? '';
            if ($certificate === 'Yes') {
                $orgCounts[$org]['certificates']++;
            }
        }
        
        // Sort alphabetically
        usort($orgCounts, function($a, $b) {
            return strcasecmp($a['organization'], $b['organization']);
        });
        
        return array_values($orgCounts);
    }
}
