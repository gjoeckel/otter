<?php
// refresh.php - Endpoint for refreshing data
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_data_service.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Verify enterprise code matches session
if (!isset($_SESSION['enterprise_code']) || $_SESSION['enterprise_code'] !== UnifiedEnterpriseConfig::getEnterpriseCode()) {
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid enterprise session']);
    exit;
}

// Get cache manager for enterprise-specific cache directory
$cacheManager = EnterpriseCacheManager::getInstance();

// Add after session and enterprise code validation
$logFile = $cacheManager->getCacheFilePath('refresh_debug.log');
function log_refresh($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Create data service and refresh all data
$dataService = new EnterpriseDataService();
$result = $dataService->refreshAllData(true); // Force refresh

// After $result = $dataService->refreshAllData(true);
$expectedFiles = [
    $cacheManager->getCacheFilePath('all-registrants-data.json'),
    $cacheManager->getCacheFilePath('all-submissions-data.json'),
];
$missingOrEmpty = [];
foreach ($expectedFiles as $file) {
    if (!file_exists($file) || filesize($file) === 0) {
        $missingOrEmpty[] = basename($file);
    }
}
if (!empty($missingOrEmpty)) {
    $result['warning'] = 'Some cache files are missing or empty: ' . implode(', ', $missingOrEmpty);
    log_refresh("WARNING: Missing/empty cache files: " . implode(', ', $missingOrEmpty) . ", user: {$_SESSION['admin_authenticated']}");
} else {
    log_refresh("SUCCESS: All cache files refreshed, user: {$_SESSION['admin_authenticated']}");
}

header('Content-Type: application/json');
echo json_encode($result); 