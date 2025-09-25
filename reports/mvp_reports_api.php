<?php
/**
 * MVP reports_api.php - External API endpoint (simplified to 15 lines)
 * Replaces 800+ line complex API with simple service call
 */
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/mvp_config.php';
require_once __DIR__ . '/../lib/mvp_utils.php';
require_once __DIR__ . '/../lib/mvp_reports_data_service.php';

initializeSession();
$config = MvpConfig::load();

$service = new MvpReportsDataService($config);
$service->getJsonResponse($_REQUEST);
?>
