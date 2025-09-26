<?php

require_once __DIR__ . '/unified_enterprise_config.php';

/**
 * DemoTransformationService - Single source of truth for demo data transformation
 * 
 * This class consolidates all demo transformation logic, eliminating 7 duplicate functions
 * across the codebase. Applied to multiple columns for BOTH registrants and submissions 
 * data to facilitate easier Google Sheets updates and maintain data privacy.
 * 
 * Transformations applied:
 * - Last (index 6): All values replaced with "Demo"
 * - Email (index 7): All values before @ replaced with "demo"
 * - Organization (index 9): All values append suffix " Demo"
 * 
 * @package Otter\Lib
 */
class DemoTransformationService {
    
    /**
     * Transform data for demo enterprise
     * 
     * Applies multiple transformations to maintain data privacy and clearly mark as demo data:
     * - Last (index 6): All values replaced with "Demo"
     * - Email (index 7): All values before @ replaced with "demo"
     * - Organization (index 9): All values append suffix " Demo"
     * 
     * @param array $data The data array to transform
     * @return array The transformed data array
     */
    public static function transformData($data) {
        // Only apply transformation for demo enterprise
        if (!self::shouldTransform()) {
            return $data;
        }
        
        $lastIndex = 6;        // Column G (zero-based index)
        $emailIndex = 7;       // Column H (zero-based index)
        $organizationIndex = 9; // Column J (zero-based index)
        $transformedData = [];
        
        foreach ($data as $row) {
            // Transform Last name (index 6) - replace with "Demo"
            if (isset($row[$lastIndex]) && !empty($row[$lastIndex])) {
                $row[$lastIndex] = 'Demo';
            }
            
            // Transform Email (index 7) - replace part before @ with "demo"
            if (isset($row[$emailIndex]) && !empty($row[$emailIndex])) {
                $email = trim($row[$emailIndex]);
                if (strpos($email, '@') !== false) {
                    $parts = explode('@', $email, 2);
                    $row[$emailIndex] = 'demo@' . $parts[1];
                }
            }
            
            // Transform Organization (index 9) - append " Demo" suffix
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
     * Transform organization names for demo enterprise (legacy method for backward compatibility)
     * 
     * @param array $data The data array to transform
     * @return array The transformed data array
     */
    public static function transformOrganizationNames($data) {
        return self::transformData($data);
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
     * Transform registrants data for demo enterprise
     * 
     * @param array $data The registrants data array
     * @return array The transformed registrants data array
     */
    public static function transformRegistrantsData($data) {
        return self::transformData($data);
    }
    
    /**
     * Transform submissions data for demo enterprise
     * 
     * @param array $data The submissions data array
     * @return array The transformed submissions data array
     */
    public static function transformSubmissionsData($data) {
        return self::transformData($data);
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
