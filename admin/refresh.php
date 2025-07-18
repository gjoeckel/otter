<?php
ob_start();
// refresh.php - Endpoint for refreshing data
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_refresh_service.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

// Verify enterprise code matches session
if (!isset($_SESSION['enterprise_code']) || $_SESSION['enterprise_code'] !== UnifiedEnterpriseConfig::getEnterpriseCode()) {
    session_destroy();
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid enterprise session']);
    exit;
}

// Log the current enterprise and session info
$enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
$apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
$apiKeyPrefix = substr($apiKey, 0, 10) . '...';

log_refresh("DEBUG: Refresh started for enterprise: $enterpriseCode, API key: $apiKeyPrefix");

// Use unified refresh service
$refreshService = UnifiedRefreshService::getInstance();
$result = $refreshService->forceRefresh();

// Log the refresh operation
$cacheManager = EnterpriseCacheManager::getInstance();
$logFile = $cacheManager->getCacheFilePath('refresh_debug.log');
function log_refresh($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

if (isset($result['error'])) {
    log_refresh("ERROR: " . $result['error'] . ", user: {$_SESSION['admin_authenticated']}");
} elseif (isset($result['warning'])) {
    log_refresh("WARNING: " . $result['warning'] . ", user: {$_SESSION['admin_authenticated']}");
} else {
    log_refresh("SUCCESS: All cache files refreshed, user: {$_SESSION['admin_authenticated']}");
}

ob_clean();
header('Content-Type: application/json');
echo json_encode($result);