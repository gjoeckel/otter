<?php

require_once __DIR__ . '/enterprise_cache_manager.php';
require_once __DIR__ . '/unified_enterprise_config.php';
require_once __DIR__ . '/demo_transformation_service.php';

/**
 * CacheDataLoader - Centralized data loading with automatic demo transformation
 * 
 * This class provides unified cache loading with consistent transformation,
 * eliminating 7+ duplicate data loading patterns across the codebase.
 * 
 * @package Otter\Lib
 */
class CacheDataLoader {
    
    /**
     * Load registrants data with automatic demo transformation
     * 
     * @return array The registrants data array
     * @throws Exception If cache file cannot be read
     */
    public static function loadRegistrantsData() {
        try {
            $cacheManager = EnterpriseCacheManager::getInstance();
            $cache = $cacheManager->readCacheFile('all-registrants-data.json');
            $data = $cache['data'] ?? [];
            
            // Apply demo transformation if needed
            if (DemoTransformationService::shouldTransform()) {
                $data = DemoTransformationService::transformRegistrantsData($data);
            }
            
            return $data;
        } catch (Exception $e) {
            throw new Exception("Failed to load registrants data: " . $e->getMessage());
        }
    }
    
    /**
     * Load submissions data with automatic demo transformation
     * 
     * @return array The submissions data array
     * @throws Exception If cache file cannot be read
     */
    public static function loadSubmissionsData() {
        try {
            $cacheManager = EnterpriseCacheManager::getInstance();
            $cache = $cacheManager->readCacheFile('all-submissions-data.json');
            $data = $cache['data'] ?? [];
            
            // Apply demo transformation if needed
            if (DemoTransformationService::shouldTransform()) {
                $data = DemoTransformationService::transformSubmissionsData($data);
            }
            
            return $data;
        } catch (Exception $e) {
            throw new Exception("Failed to load submissions data: " . $e->getMessage());
        }
    }
    
    /**
     * Load registrants data without transformation
     * 
     * @return array The raw registrants data array
     * @throws Exception If cache file cannot be read
     */
    public static function loadRawRegistrantsData() {
        try {
            $cacheManager = EnterpriseCacheManager::getInstance();
            $cache = $cacheManager->readCacheFile('all-registrants-data.json');
            return $cache['data'] ?? [];
        } catch (Exception $e) {
            throw new Exception("Failed to load raw registrants data: " . $e->getMessage());
        }
    }
    
    /**
     * Load submissions data without transformation
     * 
     * @return array The raw submissions data array
     * @throws Exception If cache file cannot be read
     */
    public static function loadRawSubmissionsData() {
        try {
            $cacheManager = EnterpriseCacheManager::getInstance();
            $cache = $cacheManager->readCacheFile('all-submissions-data.json');
            return $cache['data'] ?? [];
        } catch (Exception $e) {
            throw new Exception("Failed to load raw submissions data: " . $e->getMessage());
        }
    }
    
    /**
     * Load enrollments data with automatic demo transformation
     * On-demand processing: filters registrants data for enrolled participants
     * 
     * @return array The enrollments data array
     * @throws Exception If cache file cannot be read
     */
    public static function loadEnrollmentsData() {
        try {
            $registrantsData = self::loadRegistrantsData();
            
            // Use DRY service for enrollment filtering
            require_once __DIR__ . '/data_processor.php';
            return DataProcessor::filterEnrollments($registrantsData);
        } catch (Exception $e) {
            throw new Exception("Failed to load enrollments data: " . $e->getMessage());
        }
    }
    
    /**
     * Load certificates data with automatic demo transformation
     * On-demand processing: filters registrants data for certificate earners
     * 
     * @return array The certificates data array
     * @throws Exception If cache file cannot be read
     */
    public static function loadCertificatesData() {
        try {
            $registrantsData = self::loadRegistrantsData();
            
            // Use DRY service for certificate filtering
            require_once __DIR__ . '/data_processor.php';
            return DataProcessor::filterCertificates($registrantsData);
        } catch (Exception $e) {
            throw new Exception("Failed to load certificates data: " . $e->getMessage());
        }
    }
    
    /**
     * Load registrations data with automatic demo transformation
     * On-demand processing: uses submissions data directly
     * 
     * @return array The registrations data array
     * @throws Exception If cache file cannot be read
     */
    public static function loadRegistrationsData() {
        try {
            return self::loadSubmissionsData();
        } catch (Exception $e) {
            throw new Exception("Failed to load registrations data: " . $e->getMessage());
        }
    }
    
    /**
     * Check if cache files exist and are readable
     * 
     * @return array Status of cache files ['registrants' => bool, 'submissions' => bool]
     */
    public static function checkCacheStatus() {
        $status = [
            'registrants' => false,
            'submissions' => false
        ];
        
        try {
            $cacheManager = EnterpriseCacheManager::getInstance();
            
            // Check registrants cache
            $registrantsCache = $cacheManager->readCacheFile('all-registrants-data.json');
            $status['registrants'] = isset($registrantsCache['data']) && is_array($registrantsCache['data']);
            
            // Check submissions cache
            $submissionsCache = $cacheManager->readCacheFile('all-submissions-data.json');
            $status['submissions'] = isset($submissionsCache['data']) && is_array($submissionsCache['data']);
            
        } catch (Exception $e) {
            // Cache files don't exist or are not readable
        }
        
        return $status;
    }
    
    /**
     * Get cache file information
     * 
     * @return array Cache file information including timestamps and sizes
     */
    public static function getCacheInfo() {
        $info = [
            'registrants' => null,
            'submissions' => null
        ];
        
        try {
            $cacheManager = EnterpriseCacheManager::getInstance();
            
            // Get registrants cache info
            $registrantsInfo = $cacheManager->getCacheFileInfo('all-registrants-data.json');
            if ($registrantsInfo) {
                $info['registrants'] = $registrantsInfo;
            }
            
            // Get submissions cache info
            $submissionsInfo = $cacheManager->getCacheFileInfo('all-submissions-data.json');
            if ($submissionsInfo) {
                $info['submissions'] = $submissionsInfo;
            }
            
        } catch (Exception $e) {
            // Cache files don't exist or are not readable
        }
        
        return $info;
    }
}
