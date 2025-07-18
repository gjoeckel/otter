<?php
ob_start();
header('Content-Type: application/json');

// Load enterprise configuration and cache manager
require_once __DIR__ . '/../lib/unified_enterprise_config.php';
require_once __DIR__ . '/../lib/enterprise_cache_manager.php';

// Initialize enterprise configuration
$context = UnifiedEnterpriseConfig::initializeFromRequest();

// Check if enterprise detection failed
if (isset($context['error'])) {
    ob_clean();
    echo json_encode(['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.']);
    exit;
}
$cacheManager = EnterpriseCacheManager::getInstance();

// Set session path to enterprise-specific cache directory
ini_set('session.save_path', $cacheManager->getEnterpriseCacheDir());
if (session_status() === PHP_SESSION_NONE) session_start();

// Check both session and URL parameter for admin status
$is_admin = (isset($_SESSION['user']) && $_SESSION['user']['type'] === 'admin') ||
            (isset($_GET['auth']) && $_GET['auth'] === '1');

if (!$is_admin) {
    ob_clean();
    http_response_code(403);
    echo json_encode(['error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.']);
    exit;
}

$registrants_cache_file = $cacheManager->getRegistrantsCachePath();

$response = [
    'exists' => file_exists($registrants_cache_file),
    'enterprise' => UnifiedEnterpriseConfig::getEnterpriseCode(),
    'cache_dir' => $cacheManager->getEnterpriseCacheDir(),
    'cache_info' => $cacheManager->getCacheFileInfo('all-registrants-data.json')
];

ob_clean();
echo json_encode($response);
exit;