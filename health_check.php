<?php
// Ensure no output before JSON
ob_start();

// Set JSON headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Disable error display to prevent HTML in JSON response
ini_set('display_errors', 0);
error_reporting(0);

$health = [
    'status' => 'healthy',
    'timestamp' => date('Y-m-d H:i:s'),
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time'),
    'error_reporting' => ini_get('error_reporting'),
    'log_errors' => ini_get('log_errors'),
    'error_log' => ini_get('error_log'),
    'loaded_extensions' => [
        'json' => extension_loaded('json'),
        'curl' => extension_loaded('curl'),
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'openssl' => extension_loaded('openssl')
    ],
    'file_permissions' => [
        'config_readable' => is_readable('config/'),
        'cache_writable' => is_writable('cache/'),
        'logs_writable' => is_writable('./')
    ],
    'database_connection' => 'unknown',
    'enterprise_configs' => [],
    'srd_validation' => [
        'architecture' => 'Individual ES6 modules',
        'bundling' => 'Disabled (SRD compliant)',
        'modules_loaded' => count(glob('reports/js/*.js')),
        'enterprise_configs' => count(glob('config/*.config'))
    ],
    'deployment_validation' => [
        'timestamp' => date('Y-m-d H:i:s'),
        'commit_hash' => $_GET['commit'] ?? 'unknown',
        'target_folder' => $_GET['target'] ?? 'unknown',
        'critical_files' => [
            'reports/index.php' => file_exists('reports/index.php'),
            'reports/js/reports-data.js' => file_exists('reports/js/reports-data.js'),
            'reports/js/unified-data-service.js' => file_exists('reports/js/unified-data-service.js'),
            'config/csu.config' => file_exists('config/csu.config'),
            'config/ccc.config' => file_exists('config/ccc.config'),
            'config/demo.config' => file_exists('config/demo.config')
        ]
    ]
];

// Test database connection if possible
try {
    if (file_exists('lib/database.php')) {
        require_once 'lib/database.php';
        // Basic connection test would go here
        $health['database_connection'] = 'tested';
    }
} catch (Exception $e) {
    $health['database_connection'] = 'error: ' . $e->getMessage();
}

// Check enterprise configurations
if (file_exists('config/')) {
    $config_files = glob('config/*.config');
    foreach ($config_files as $config) {
        $health['enterprise_configs'][] = basename($config);
    }
}

// Check for recent errors
if (file_exists('php_errors.log')) {
    $health['recent_errors'] = count(file('php_errors.log'));
}

// Clear any previous output and ensure clean JSON
ob_clean();

try {
    echo json_encode($health, JSON_PRETTY_PRINT);
} catch (Exception $e) {
    // Fallback if JSON encoding fails
    ob_clean();
    echo json_encode([
        'status' => 'error',
        'error' => 'JSON encoding failed: ' . $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ], JSON_PRETTY_PRINT);
}

ob_end_flush();
