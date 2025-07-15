<?php
/**
 * Data Processor
 * Centralized data processing utilities for reports pages
 *
 * GOOGLE SHEETS COLUMN NUMBERING:
 * - Google Sheets uses 1-based column indexing (A=1, B=2, C=3, etc.)
 * - Array indices are 0-based (0, 1, 2, etc.)
 * - Mapping: Google Sheets Column A ΓåÆ Array Index 0, Column B ΓåÆ Array Index 1, etc.
 *
 * KEY COLUMN MAPPINGS (from config/csu.config):
 * - Registration Date: Google Sheets Column B (1) ΓåÆ Array Index 1
 * - Enrollment Status: Google Sheets Column C (2) ΓåÆ Array Index 2
 * - Organization: Google Sheets Column J (9) ΓåÆ Array Index 9
 * - Certificate Status: Google Sheets Column K (10) ΓåÆ Array Index 10
 * - Certificate Issued Date: Google Sheets Column L (11) ΓåÆ Array Index 11
 * - Submission Date: Google Sheets Column P (15) ΓåÆ Array Index 15
 * - Status: Google Sheets Column Q (16) ΓåÆ Array Index 16
 * 
 * ENROLLMENT LOGIC:
 * - Enrollments track registrations that are also enrolled
 * - Uses same date range logic as registrations (Invited date)
 * - Additional condition: Enrolled column must be "Yes"
 */

require_once __DIR__ . '/abbreviation_utils.php';

class DataProcessor {
    private static $orgIdx = 9; // Organization column index (Google Sheets Column J)

    /**
     * Get column index from configuration
     * @param string $sheetType - 'registrants' or 'submissions'
     * @param string $columnName - Column name from config
     * @return int|null Column index or null if not found
     */
    private static function getColumnIndex($sheetType, $columnName) {
        $config = UnifiedEnterpriseConfig::getGoogleSheets();
        return $config[$sheetType]['columns'][$columnName]['index'] ?? null;
    }

    /**
     * Process registrants data for date range
     * @param array $registrantsData - Raw registrants data
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return array Processed data with registrations, enrollments, and certificates
     */
    public static function processRegistrantsData($registrantsData, $start, $end) {
        // Get column indices from configuration
        $regDateIdx = self::getColumnIndex('registrants', 'Invited'); // Google Sheets Column B (1)
        $enrolledIdx = self::getColumnIndex('registrants', 'Enrolled'); // Google Sheets Column C (2)
        $certificateIdx = self::getColumnIndex('registrants', 'Certificate'); // Google Sheets Column K (10)
        $issuedDateIdx = self::getColumnIndex('registrants', 'Issued'); // Google Sheets Column L (11)

        $registrations = [];
        $enrollments = [];
        $certificates = [];

        $processedCount = 0;
        $registrationCount = 0;
        $enrollmentCount = 0;
        $certificateCount = 0;

        foreach ($registrantsData as $rowIndex => $row) {
            $processedCount++;

            if (!is_array($row)) {
                continue;
            }

            // Use configuration-based column indices
            $regDate = isset($row[$regDateIdx]) ? trim($row[$regDateIdx]) : '';
            $enrolled = isset($row[$enrolledIdx]) && $row[$enrolledIdx] === 'Yes';
            $certificate = isset($row[$certificateIdx]) && $row[$certificateIdx] === 'Yes';
            $issuedDate = isset($row[$issuedDateIdx]) ? trim($row[$issuedDateIdx]) : '';

            // Note: Cohort and year are no longer used for enrollment calculation
            // Enrollments now use the same date range logic as registrations

            if (self::inRange($regDate, $start, $end)) {
                $registrations[] = $row;
                $registrationCount++;
            }

            // Enrollments: Track registrations that are also enrolled
            // Use same logic to determine registrations in date range, then add condition that Enrolled column must be "Yes"
            if (self::inRange($regDate, $start, $end) && $enrolled) {
                $enrollments[] = $row;
                $enrollmentCount++;
            }

            if ($certificate && self::inRange($issuedDate, $start, $end)) {
                $certificates[] = $row;
                $certificateCount++;
            }
        }

        return [
            'registrations' => $registrations,
            'enrollments' => $enrollments,
            'certificates' => $certificates
        ];
    }

    /**
     * Process submissions data for date range
     * @param array $submissionsData - Raw submissions data
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return array Processed submissions data
     */
    public static function processSubmissionsData($submissionsData, $start, $end) {
        // Get column index from configuration
        $submittedDateIdx = self::getColumnIndex('submissions', 'Token'); // Google Sheets Column B (1)

        $submissions = [];
        $processedCount = 0;
        $submissionCount = 0;

        foreach ($submissionsData as $rowIndex => $row) {
            $processedCount++;

            if (!is_array($row)) {
                continue;
            }

            // Use configuration-based column index
            $submittedDate = isset($row[$submittedDateIdx]) ? trim($row[$submittedDateIdx]) : '';

            if (self::inRange($submittedDate, $start, $end)) {
                $submissions[] = $row;
                $submissionCount++;
            }
        }

        return $submissions;
    }

    /**
     * Process organization data from cached files
     * @param array $registrationsRows - Registration data
     * @param array $enrollmentsRows - Enrollment data
     * @param array $certificatesRows - Certificate data
     * @return array Processed organization data
     */
    public static function processOrganizationData($registrationsRows, $enrollmentsRows, $certificatesRows) {
        // Get organization column index from configuration
        $orgIdx = self::getColumnIndex('registrants', 'Organization'); // Google Sheets Column J (9)

        // Get ALL organizations from config FIRST (not just those with data)
        $config = UnifiedEnterpriseConfig::getFullConfig();
        $configOrgs = $config['organizations'] ?? [];

        // Start with ALL organizations from config, ensuring they're all included
        $organizationSet = [];
        foreach ($configOrgs as $orgName) {
            $organizationSet[trim($orgName)] = true;
        }

        // Also add any organizations found in the data (in case there are new ones not in config)
        foreach ($registrationsRows as $rowIndex => $row) {
            if (isset($row[$orgIdx])) {
                $organization = trim($row[$orgIdx]);
                if ($organization !== '') {
                    $organizationSet[$organization] = true;
                }
            }
        }

        foreach ($enrollmentsRows as $rowIndex => $row) {
            if (isset($row[$orgIdx])) {
                $organization = trim($row[$orgIdx]);
                if ($organization !== '') {
                    $organizationSet[$organization] = true;
                }
            }
        }

        foreach ($certificatesRows as $rowIndex => $row) {
            if (isset($row[$orgIdx])) {
                $organization = trim($row[$orgIdx]);
                if ($organization !== '') {
                    $organizationSet[$organization] = true;
                }
            }
        }

        // Convert to array and sort
        $organizations = array_keys($organizationSet);
        sort($organizations);

        // Process each organization
        $organizationData = [];
        foreach ($organizations as $orgName) {
            $registrations = 0;
            $enrollments = 0;
            $certificates = 0;

            // Count registrations for this organization
            foreach ($registrationsRows as $row) {
                if (isset($row[$orgIdx]) && trim($row[$orgIdx]) === $orgName) {
                    $registrations++;
                }
            }

            // Count enrollments for this organization
            foreach ($enrollmentsRows as $row) {
                if (isset($row[$orgIdx]) && trim($row[$orgIdx]) === $orgName) {
                    $enrollments++;
                }
            }

            // Count certificates for this organization
            foreach ($certificatesRows as $row) {
                if (isset($row[$orgIdx]) && trim($row[$orgIdx]) === $orgName) {
                    $certificates++;
                }
            }

            // Add organization data with abbreviated name
            // Note: Organizations with all zero values are included to support client-side data display options
            $organizationData[] = [
                'organization' => $orgName,
                'organization_display' => abbreviateOrganizationName($orgName),
                'registrations' => $registrations,
                'enrollments' => $enrollments,
                'certificates' => $certificates
            ];
        }

        return $organizationData;
    }

    /**
     * Check if date is within range
     * @param string $date - Date in MM-DD-YY format
     * @param string $start - Start date in MM-DD-YY format
     * @param string $end - End date in MM-DD-YY format
     * @return bool True if date is within range
     */
    private static function inRange($date, $start, $end) {
        if (empty($date)) {
            return false;
        }

        $d = DateTime::createFromFormat('m-d-y', $date);
        $s = DateTime::createFromFormat('m-d-y', $start);
        $e = DateTime::createFromFormat('m-d-y', $end);

        if (!$d || !$s || !$e) {
            return false;
        }

        return $d >= $s && $d <= $e;
    }


}
