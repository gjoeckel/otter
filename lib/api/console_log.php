<?php
// Console Log API - Receives browser console errors and logs them
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    require_once __DIR__ . '/../error_messages.php';
    echo json_encode(['error' => ErrorMessages::getTechnicalDifficulties()]);
    exit;
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data || !isset($data['errors'])) {
        throw new Exception('Invalid JSON data or missing errors array');
    }

    // Create logs directory if it doesn't exist
    $logsDir = __DIR__ . '/../../logs';
    if (!file_exists($logsDir)) {
        mkdir($logsDir, 0777, true);
    }

    // Generate log filename with date
    $date = date('Y-m-d');
    $logFile = $logsDir . "/console_errors_{$date}.log";

    // Format errors for logging
    $sessionId = $data['session_id'] ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');

    foreach ($data['errors'] as $error) {
        $logEntry = sprintf(
            "[%s] [Session: %s] [%s] %s | URL: %s | Stack: %s\n",
            $timestamp,
            $sessionId,
            strtoupper($error['type']),
            $error['message'],
            $error['url'] ?? 'unknown',
            $error['stack'] ?? 'N/A'
        );

        // Append to log file
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'logged_errors' => count($data['errors']),
        'log_file' => basename($logFile)
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Failed to log console errors',
        'message' => $e->getMessage()
    ]);
}