<?php
require_once __DIR__ . '/data_processor.php';
require_once __DIR__ . '/enterprise_data_service.php';
require_once __DIR__ . '/unified_enterprise_config.php';
require_once __DIR__ . '/reports_data_spec.php'; // Added

class ReportsService {
    private $dataProcessor;
    private $enterpriseDataService;
    private $googleSheetsConfig;
    
    public function __construct() {
        $this->dataProcessor = new DataProcessor();
        $this->enterpriseDataService = new EnterpriseDataService();
        $this->googleSheetsConfig = UnifiedEnterpriseConfig::getGoogleSheets();
    }
    
    public function trimRow($row) {
        return array_map('trim', $row);
    }
    
    public function isCohortYearInRange($cohort, $year, $startDate, $endDate) {
        $cohortDate = DateTime::createFromFormat('m-y', $cohort . '-' . $year);
        $start = DateTime::createFromFormat('m-d-y', $startDate);
        $end = DateTime::createFromFormat('m-d-y', $endDate);
        
        return $cohortDate >= $start && $cohortDate <= $end;
    }
    
    public function fetchSheetData($sheetName) {
        $config = $this->googleSheetsConfig[$sheetName];
        return $this->enterpriseDataService->fetchSheetData(
            $config['workbook_id'],
            $config['sheet_name'],
            $config['start_row']
        );
    }
    
    public function processReportsData($params) {
        $enterprise = $params['enterprise'] ?? null;
        $startDate = $params['start_date'] ?? null;
        $endDate = $params['end_date'] ?? null;
        $mode = $params['mode'] ?? 'date';
        $enrollmentMode = $params['enrollment_mode'] ?? 'tou'; // Added enrollment mode

        if (!$enterprise || !$startDate || !$endDate) {
            throw new InvalidArgumentException('Missing required parameters');
        }
        
        $spec = new ReportsDataSpec($mode, $enrollmentMode);
        $result = [];

        // Fetch raw data once
        $registrantsData = $this->fetchSheetData('registrants');
        $submissionsData = $this->fetchSheetData('submissions');

        // Conditionally process and add datasets based on spec
        if ($spec->needsDataset('registrations_submissions')) {
            $result['registrations_submissions'] = $this->dataProcessor->processRegistrationsData(
                $submissionsData, $startDate, $endDate
            );
        }
        
        if ($spec->needsDataset('registrations_cohort')) {
            // This would require specific cohort processing logic in DataProcessor
            // For now, it's a placeholder or can reuse date-based if applicable
            $result['registrations_cohort'] = []; 
        }

        if ($spec->needsDataset('submissions_enrollments_tou')) {
            $enrollmentsTouResult = $this->dataProcessor->processEnrollmentsData(
                null, $startDate, $endDate, $registrantsData, 'tou_completion'
            );
            $result['submissions_enrollments_tou'] = $enrollmentsTouResult['data'];
        }

        if ($spec->needsDataset('submissions_enrollments_registrations')) {
            $enrollmentsRegResult = $this->dataProcessor->processEnrollmentsData(
                null, $startDate, $endDate, $registrantsData, 'registration_date'
            );
            $result['submissions_enrollments_registrations'] = $enrollmentsRegResult['data'];
        }

        if ($spec->needsDataset('cohort_enrollments_tou')) {
            // Placeholder for cohort-based enrollments
            $result['cohort_enrollments_tou'] = [];
        }

        if ($spec->needsDataset('cohort_enrollments_registrations')) {
            // Placeholder for cohort-based enrollments
            $result['cohort_enrollments_registrations'] = [];
        }
        
        return $result;
    }
}
