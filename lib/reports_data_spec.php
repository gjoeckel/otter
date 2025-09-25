<?php

class ReportsDataSpec {
    private $requestedDatasets = [];
    
    public function __construct($mode = 'date', $enrollmentMode = 'tou') {
        $this->determineRequiredDatasets($mode, $enrollmentMode);
    }
    
    private function determineRequiredDatasets($mode, $enrollmentMode) {
        // Only generate what's actually needed
        if ($mode === 'date') {
            $this->requestedDatasets[] = 'registrations_submissions';
            $this->requestedDatasets[] = $enrollmentMode === 'tou' 
                ? 'submissions_enrollments_tou' 
                : 'submissions_enrollments_registrations';
        } elseif ($mode === 'cohort') {
            $this->requestedDatasets[] = 'registrations_cohort';
            $this->requestedDatasets[] = $enrollmentMode === 'tou'
                ? 'cohort_enrollments_tou'
                : 'cohort_enrollments_registrations';
        }
    }
    
    public function getRequestedDatasets() {
        return $this->requestedDatasets;
    }
    
    public function needsDataset($datasetName) {
        return in_array($datasetName, $this->requestedDatasets);
    }
}
