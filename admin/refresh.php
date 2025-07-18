<?php
ob_start();
header('Content-Type: application/json');

// refresh.php - Endpoint for refreshing data
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_refresh_service.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Check if enterprise detection failed
if (isset($context['error'])) {
    ob_clean();
    echo json_encode(['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.']);
    exit;
}

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    ob_clean();
    echo json_encode(['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.']);
    exit;
}

// Verify enterprise code matches session
if (!isset($_SESSION['enterprise_code']) || $_SESSION['enterprise_code'] !== UnifiedEnterpriseConfig::getEnterpriseCode()) {
    session_destroy();
    ob_clean();
    echo json_encode(['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.']);
    exit;
}

// Define logging function first
function log_refresh($message) {
    $cacheManager = EnterpriseCacheManager::getInstance();
    $logFile = $cacheManager->getCacheFilePath('refresh_debug.log');
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
}

// Log the current enterprise and session info
$enterpriseCode = UnifiedEnterpriseConfig::getEnterpriseCode();
$apiKey = UnifiedEnterpriseConfig::getGoogleApiKey();
$apiKeyPrefix = substr($apiKey, 0, 10) . '...';

log_refresh("DEBUG: Refresh started for enterprise: $enterpriseCode, API key: $apiKeyPrefix");

// Use unified refresh service
$refreshService = UnifiedRefreshService::getInstance();
$result = $refreshService->forceRefresh();

if (isset($result['error'])) {
    log_refresh("ERROR: " . $result['error'] . ", user: {$_SESSION['admin_authenticated']}");
} elseif (isset($result['warning'])) {
    log_refresh("WARNING: " . $result['warning'] . ", user: {$_SESSION['admin_authenticated']}");
} else {
    log_refresh("SUCCESS: All cache files refreshed, user: {$_SESSION['admin_authenticated']}");
}

ob_clean();
echo json_encode($result);
exit;