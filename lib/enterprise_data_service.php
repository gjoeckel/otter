<?php
/**
 * Enterprise Data Service
 * Handles all Google Sheets data retrieval and caching operations
 */

require_once __DIR__ . '/unified_enterprise_config.php';
require_once __DIR__ . '/enterprise_cache_manager.php';

class EnterpriseDataService {
    private $config;
    private $cacheManager;
    private $cacheTtl;
    private $apiKey;

    public function __construct() {
        $this->config = UnifiedEnterpriseConfig::getGoogleSheets();
        $this->cacheManager = EnterpriseCacheManager::getInstance();
        $this->cacheTtl = UnifiedEnterpriseConfig::getCacheTtl();
        $this->apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
        
        // Log which enterprise is being used
        $enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
        $apiKeyPrefix = substr($this->apiKey, 0, 10) . '...';
        
        // Log to refresh debug log
        $logFile = $this->cacheManager->getCacheFilePath('refresh_debug.log');
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] DEBUG: EnterpriseDataService initialized for enterprise: $enterpriseCode, API key: $apiKeyPrefix\n", FILE_APPEND);
    }

    /**
     * Refresh all enterprise data from Google Sheets
     * @param bool $forceRefresh Whether to bypass cache and force refresh
     * @return array Summary of refreshed data
     */
    public function refreshAllData($forceRefresh = false) {
        try {
            // Fetch and cache registrants data
            $registrantsData = $this->fetchRegistrantsData($forceRefresh);
            if (isset($registrantsData['error'])) {
                return ['error' => $registrantsData['error']];
            }

            // Fetch and cache submissions data
            $submissionsData = $this->fetchSubmissionsData($forceRefresh);
            if (isset($submissionsData['error'])) {
                return ['error' => $submissionsData['error']];
            }

            // Generate derived cache files
            $this->generateDerivedData($registrantsData, $submissionsData);

            // Return summary
            return [
                'registrations' => strval(count($this->getRegistrations())),
                'enrollments' => strval(count($this->getEnrollments())),
                'certificates' => strval(count($this->getCertificates()))
            ];

        } catch (Exception $e) {
            require_once __DIR__ . '/error_messages.php';
            return ['error' => ErrorMessages::getTechnicalDifficulties()];
        }
    }

    /**
     * Check if any cache files are stale
     * @return bool True if data is stale or missing
     */
    public function isDataStale() {
        $cacheFiles = [
            $this->cacheManager->getRegistrantsCachePath(),
            $this->cacheManager->getSubmissionsCachePath()
        ];

        foreach ($cacheFiles as $file) {
            if (!file_exists($file)) {
                return true;
            }

            $cacheAge = time() - filemtime($file);
            if ($cacheAge > $this->cacheTtl) {
                return true;
            }
        }

        return false;
    }

    /**
     * Fetch registrants data from Google Sheets
     * @param bool $forceRefresh Whether to bypass cache
     * @return array Registrants data or error
     */
    private function fetchRegistrantsData($forceRefresh = false) {
        $cacheFile = $this->cacheManager->getRegistrantsCachePath();

        // Check cache first
        if (!$forceRefresh && file_exists($cacheFile)) {
            $json = json_decode(file_get_contents($cacheFile), true);
            $cacheTimestamp = isset($json['global_timestamp']) ? $json['global_timestamp'] : null;

            if ($cacheTimestamp) {
                $dt = DateTime::createFromFormat('m-d-y \a\t g:i A', $cacheTimestamp, new DateTimeZone('America/Los_Angeles'));
                if ($dt !== false) {
                    $now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
                    $diff = $now->getTimestamp() - $dt->getTimestamp();
                    if ($diff < $this->cacheTtl) {
                        return isset($json['data']) ? $json['data'] : [];
                    }
                }
            }
        }

        // Fetch from Google Sheets
        $registrantsConfig = $this->config['registrants'];
        $data = $this->fetchSheetData(
            $registrantsConfig['workbook_id'],
            $registrantsConfig['sheet_name'],
            $registrantsConfig['start_row']
        );

        if (isset($data['error'])) {
            return $data;
        }

        // Cache the data with timestamp
        $this->cacheData($cacheFile, $data);

        return $data;
    }

    /**
     * Fetch submissions data from Google Sheets
     * @param bool $forceRefresh Whether to bypass cache
     * @return array Submissions data or error
     */
    private function fetchSubmissionsData($forceRefresh = false) {
        $cacheFile = $this->cacheManager->getSubmissionsCachePath();

        // Check cache first
        if (!$forceRefresh && file_exists($cacheFile)) {
            $json = json_decode(file_get_contents($cacheFile), true);
            $cacheTimestamp = isset($json['global_timestamp']) ? $json['global_timestamp'] : null;

            if ($cacheTimestamp) {
                $dt = DateTime::createFromFormat('m-d-y \a\t g:i A', $cacheTimestamp, new DateTimeZone('America/Los_Angeles'));
                if ($dt !== false) {
                    $now = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
                    $diff = $now->getTimestamp() - $dt->getTimestamp();
                    if ($diff < $this->cacheTtl) {
                        return isset($json['data']) ? $json['data'] : [];
                    }
                }
            }
        }

        // Fetch from Google Sheets
        $submissionsConfig = $this->config['submissions'];
        $data = $this->fetchSheetData(
            $submissionsConfig['workbook_id'],
            $submissionsConfig['sheet_name'],
            $submissionsConfig['start_row']
        );

        if (isset($data['error'])) {
            return $data;
        }

        // Cache the data with timestamp
        $this->cacheData($cacheFile, $data);

        return $data;
    }

    /**
     * Fetch data from Google Sheets
     * @param string $workbookId Google Sheets workbook ID
     * @param string $sheetName Sheet name
     * @param int $startRow Starting row number
     * @return array Sheet data or error
     */
    private function fetchSheetData($workbookId, $sheetName, $startRow) {
        if (empty($this->apiKey)) {
            require_once __DIR__ . '/error_messages.php';
            return ['error' => ErrorMessages::getTechnicalDifficulties()];
        }

        $url = sprintf(
            'https://sheets.googleapis.com/v4/spreadsheets/%s/values/%s!A:Z?key=%s',
            $workbookId,
            urlencode($sheetName),
            $this->apiKey
        );

        // Log the URL being requested (without API key for security)
        $logUrl = preg_replace('/key=[^&]+/', 'key=***', $url);
        
        // Log to refresh debug log instead of error_log
        $cacheManager = EnterpriseCacheManager::getInstance();
        $logFile = $cacheManager->getCacheFilePath('refresh_debug.log');
        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] DEBUG: Attempting to fetch Google Sheets data from: $logUrl\n", FILE_APPEND);

        $response = @file_get_contents($url);
        if ($response === false) {
            // Get the actual PHP error that was suppressed
            $error = error_get_last();
            $errorMessage = $error ? $error['message'] : 'Unknown error';
            $errorType = $error ? $error['type'] : 'Unknown type';
            
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] DEBUG: file_get_contents failed. Error: $errorMessage (Type: $errorType)\n", FILE_APPEND);
            file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] DEBUG: URL attempted: $logUrl\n", FILE_APPEND);
            
            // Check if it's a Google service issue (503, 500, connection timeout, etc.)
            if (strpos($errorMessage, '503') !== false || 
                strpos($errorMessage, '500') !== false || 
                strpos($errorMessage, 'Service Unavailable') !== false ||
                strpos($errorMessage, 'HTTP request failed') !== false) {
                require_once __DIR__ . '/error_messages.php';
                return ['error' => ErrorMessages::getGoogleServicesIssue()];
            }
            
            require_once __DIR__ . '/error_messages.php';
            return ['error' => ErrorMessages::getTechnicalDifficulties()];
        }

        $data = json_decode($response, true);
        if (isset($data['error'])) {
            require_once __DIR__ . '/error_messages.php';
            return ['error' => ErrorMessages::getTechnicalDifficulties()];
        }

        if (!isset($data['values'])) {
            require_once __DIR__ . '/error_messages.php';
            return ['error' => ErrorMessages::getTechnicalDifficulties()];
        }

        $rows = $data['values'];
        $out = [];
        for ($i = $startRow - 1; $i < count($rows); $i++) {
            $out[] = array_map('trim', $rows[$i]);
        }

        return $out;
    }

    /**
     * Cache data with timestamp
     * @param string $cacheFile Cache file path
     * @param array $data Data to cache
     */
    private function cacheData($cacheFile, $data) {
        $dt = new DateTime('now', new DateTimeZone('America/Los_Angeles'));
        $formatted = $dt->format('m-d-y');
        $hour = $dt->format('g');
        $minute = $dt->format('i');
        $ampm = $dt->format('A');
        $time = $hour . ':' . $minute . ' ' . $ampm;
        $global_timestamp = $formatted . ' at ' . $time;

        $dataWithTimestamp = [
            'global_timestamp' => $global_timestamp,
            'data' => $data
        ];

        file_put_contents($cacheFile, json_encode($dataWithTimestamp));
    }

    /**
     * Generate derived data files (registrations, enrollments, certificates)
     * @param array $registrantsData Registrants data
     * @param array $submissionsData Submissions data
     */
    private function generateDerivedData($registrantsData, $submissionsData) {
        // Use hardcoded Google Sheets column indices for reliable data processing
        $idxRegEnrolled = 2;      // Google Sheets Column C (Enrolled)
        $idxRegCertificate = 10;  // Google Sheets Column K (Certificate)
        $idxRegIssued = 11;       // Google Sheets Column L (Issued)

        // Generate registrations data (ALL submissions data, no date filtering for cache)
        $registrations = [];
        foreach ($submissionsData as $row) {
            $registrations[] = array_map('strval', $row);
        }
        file_put_contents($this->cacheManager->getRegistrationsCachePath(), json_encode($registrations));

        // Generate enrollments data
        // Track ALL registrations that are also enrolled (no date range filtering for cache)
        $enrollments = [];
        foreach ($registrantsData as $row) {
            $enrolled = isset($row[$idxRegEnrolled]) ? $row[$idxRegEnrolled] : '';
            if ($enrolled === 'Yes') {
                $enrollments[] = array_map('strval', $row);
            }
        }
        file_put_contents($this->cacheManager->getEnrollmentsCachePath(), json_encode($enrollments));

        // Generate certificates data (ALL certificates, no date filtering for cache)
        $certificates = [];
        foreach ($registrantsData as $row) {
            $certificate = isset($row[$idxRegCertificate]) ? $row[$idxRegCertificate] : '';
            if ($certificate === 'Yes') {
                $certificates[] = array_map('strval', $row);
            }
        }
        file_put_contents($this->cacheManager->getCertificatesCachePath(), json_encode($certificates));
    }

    /**
     * Get registrations data
     * @return array Registrations data
     */
    public function getRegistrations() {
        $file = $this->cacheManager->getRegistrationsCachePath();
        return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    /**
     * Get enrollments data
     * @return array Enrollments data
     */
    public function getEnrollments() {
        $file = $this->cacheManager->getEnrollmentsCachePath();
        return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    /**
     * Get certificates data
     * @return array Certificates data
     */
    public function getCertificates() {
        $file = $this->cacheManager->getCertificatesCachePath();
        return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
    }

    /**
     * Check if date is valid MM-DD-YY format
     * @param string $date Date string
     * @return bool True if valid
     */
    private function isValidDate($date) {
        return preg_match('/^\d{2}-\d{2}-\d{2}$/', $date);
    }

    /**
     * Check if date is in range
     * @param string $date Date to check
     * @param string $start Start date
     * @param string $end End date
     * @return bool True if in range
     */
    private function isInRange($date, $start, $end) {
        $d = DateTime::createFromFormat('m-d-y', $date);
        $s = DateTime::createFromFormat('m-d-y', $start);
        $e = DateTime::createFromFormat('m-d-y', $end);
        if (!$d || !$s || !$e) return false;
        return $d >= $s && $d <= $e;
    }


}