<?php
require_once __DIR__ . '/../lib/output_buffer.php';
startJsonResponse();

// refresh.php - Endpoint for refreshing data
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/unified_refresh_service.php';

// Initialize enterprise and environment from single source of truth
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Check if enterprise detection failed
if (isset($context['error'])) {
    sendJsonError();
}

require_once __DIR__ . '/../lib/session.php';
initializeSession();
if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    sendJsonError();
}

// Verify enterprise code matches session
if (!isset($_SESSION['enterprise_code']) || $_SESSION['enterprise_code'] !== UnifiedEnterpriseConfig::getEnterpriseCode()) {
    session_destroy();
    sendJsonError();
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

sendJsonResponse($result);