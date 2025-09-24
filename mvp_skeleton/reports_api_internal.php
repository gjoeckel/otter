<?php
/**
 * reports_api_internal.php - Internal API endpoint (simplified)
 */
require_once __DIR__ . '/../lib/config.php';
require_once __DIR__ . '/../lib/utils.php';
require_once __DIR__ . '/../lib/reports_data_service.php';

session_start();
$config = Config::load();

$service = new ReportsDataService($config);
return $service->getArrayResponse($_REQUEST);
?>
