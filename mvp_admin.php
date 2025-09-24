<?php
/**
 * MVP admin.php - Simplified admin interface (50 lines)
 * Replaces 200+ line complex admin with simple direct ReportsDataService calls
 */
require_once __DIR__ . '/lib/session.php';
require_once __DIR__ . '/lib/mvp_config.php';
require_once __DIR__ . '/lib/mvp_utils.php';
require_once __DIR__ . '/lib/mvp_reports_data_service.php';

initializeSession();

// Check authentication
if (!isset($_SESSION['admin_authenticated'])) {
    header('Location: mvp_login.php');
    exit;
}

$config = MvpConfig::load();
$service = new MvpReportsDataService($config);
$message = '';

// Handle refresh
if (isset($_POST['refresh'])) {
    $result = $service->getArrayResponse([
        'force_refresh' => '1',
        'start_date' => '01-01-20',
        'end_date' => date('m-d-y')
    ]);
    
    if (isset($result['error'])) {
        $message = mvpHtmlError('Error: ' . $result['error']);
    } else {
        $message = mvpHtmlSuccess('Data refreshed successfully');
    }
}

// Get cache status
$enterprise = $_SESSION['enterprise_code'] ?? 'ccc';
$cachePath = __DIR__ . "/cache/{$enterprise}/all-registrants-data.json";
$timestamp = '';
if (file_exists($cachePath)) {
    $data = json_decode(file_get_contents($cachePath), true);
    $timestamp = $data['global_timestamp'] ?? 'Unknown';
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>MVP Admin</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 50px; }
        .success-message { color: green; margin: 10px 0; }
        .error-message { color: red; margin: 10px 0; }
        button { padding: 10px; margin: 5px; }
        .status { background: #f0f0f0; padding: 10px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>MVP Admin Dashboard</h1>
    
    <div class="status">
        <strong>Enterprise:</strong> <?= htmlspecialchars($_SESSION['enterprise_code'] ?? 'Unknown') ?><br>
        <strong>Last Updated:</strong> <?= htmlspecialchars($timestamp) ?>
    </div>
    
    <?= $message ?>
    
    <form method="POST">
        <button type="submit" name="refresh" value="1">Refresh Data</button>
    </form>
    
    <p><a href="mvp_login.php">Logout</a></p>
</body>
</html>
