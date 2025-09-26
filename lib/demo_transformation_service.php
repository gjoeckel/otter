<?php

require_once __DIR__ . '/unified_enterprise_config.php';

/**
 * DemoTransformationService - Single source of truth for demo organization name transformation
 * 
 * This class consolidates all demo transformation logic, eliminating 7 duplicate functions
 * across the codebase. Applied to ORGANIZATION column (index 9) for BOTH registrants 
 * and submissions data to facilitate easier Google Sheets updates.
 * 
 * @package Otter\Lib
 */
class DemoTransformationService {
    
    /**
     * Transform organization names for demo enterprise
     * 
     * Appends " Demo" suffix to organization names for the demo enterprise.
     * This preserves specific organization identity while clearly marking them as demo data.
     * 
     * @param array $data The data array to transform
     * @return array The transformed data array
     */
    public static function transformOrganizationNames($data) {
        // Only apply transformation for demo enterprise
        if (!self::shouldTransform()) {
            return $data;
        }
        
        $organizationIndex = 9; // Column J (zero-based index)
        $transformedData = [];
        
        foreach ($data as $row) {
            if (isset($row[$organizationIndex]) && !empty($row[$organizationIndex])) {
                $orgName = trim($row[$organizationIndex]);
                // Append " Demo" suffix if not already present
                if (!str_ends_with($orgName, ' Demo')) {
                    $row[$organizationIndex] = $orgName . ' Demo';
                }
            }
            $transformedData[] = $row;
        }
        
        return $transformedData;
    }
    
    /**
     * Check if demo transformation should be applied
     * 
     * @return bool True if transformation should be applied (demo enterprise)
     */
    public static function shouldTransform() {
        try {
            return UnifiedEnterpriseConfig::getEnterpriseCode() === 'demo';
        } catch (Exception $e) {
            // If enterprise config is not available, default to no transformation
            return false;
        }
    }
    
    /**
     * Transform organization names for registrants data
     * 
     * @param array $data The registrants data array
     * @return array The transformed registrants data array
     */
    public static function transformRegistrantsData($data) {
        return self::transformOrganizationNames($data);
    }
    
    /**
     * Transform organization names for submissions data
     * 
     * @param array $data The submissions data array
     * @return array The transformed submissions data array
     */
    public static function transformSubmissionsData($data) {
        return self::transformOrganizationNames($data);
    }
    
    /**
     * Get the demo organization suffix used in transformations
     * 
     * @return string The demo organization suffix
     */
    public static function getDemoOrganizationSuffix() {
        return ' Demo';
    }
}
