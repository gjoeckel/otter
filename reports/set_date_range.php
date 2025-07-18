<?php
require_once __DIR__ . '/../lib/session.php';
require_once __DIR__ . '/../lib/output_buffer.php';
initializeSession();
startJsonResponse();
$input = json_decode(file_get_contents('php://input'), true);
$start = $input['start_date'] ?? '';
$end = $input['end_date'] ?? '';
if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{2}-\d{2}-\d{2}$/', $end)) {
    $_SESSION['reports_date_range'] = [
        'start' => $start,
        'end' => $end
    ];
    sendJsonResponse(['success' => true]);
} else {
    sendJsonResponse(['success' => false, 'error' => 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.']);
}