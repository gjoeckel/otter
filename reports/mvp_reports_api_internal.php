<?php
/**
 * MVP reports_api_internal.php - Internal API endpoint (simplified to 15 lines)
 * Replaces 400+ line complex internal API with simple service call
 */
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/mvp_config.php';
require_once __DIR__ . '/../lib/mvp_utils.php';
require_once __DIR__ . '/../lib/mvp_reports_data_service.php';

initializeSession();
$config = MvpConfig::load();

$service = new MvpReportsDataService($config);
$result = $service->getArrayResponse($_REQUEST);

// For direct HTTP calls, output JSON; for includes, return array
if (php_sapi_name() === 'cli' || !empty($_SERVER['HTTP_HOST'])) {
    // Called directly via HTTP
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
} else {
    // Called via include/require
    return $result;
}
?>
