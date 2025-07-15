<?php
if (session_status() === PHP_SESSION_NONE) session_start();
header('Content-Type: application/json');
$input = json_decode(file_get_contents('php://input'), true);
$start = $input['start_date'] ?? '';
$end = $input['end_date'] ?? '';
if (preg_match('/^\d{2}-\d{2}-\d{2}$/', $start) && preg_match('/^\d{2}-\d{2}-\d{2}$/', $end)) {
    $_SESSION['reports_date_range'] = [
        'start' => $start,
        'end' => $end
    ];
    echo json_encode(['success' => true]);
    exit;
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid date format']);
    exit;
} 