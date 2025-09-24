<?php
/**
 * reports_api.php - External API endpoint (simplified)
 */
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/reports_data_service.php';

session_start();
$config = Config::load();

$service = new ReportsDataService($config);
$service->getJsonResponse($_REQUEST);
?>
