<?php
// Centralized output buffering for JSON responses
function startJsonResponse() {
    ob_start();
    header('Content-Type: application/json');
}

function sendJsonError($message = null) {
    $defaultMessage = 'We are experiencing technical difficulties. Please close this browser window, wait a few minutes, and login again. If the problem persists, please contact accessibledocs@webaim.org for support.';
    ob_clean();
    echo json_encode(['error' => $message ?? $defaultMessage]);
    exit;
}

function sendJsonResponse($data, $prettyPrint = false) {
    ob_clean();
    if ($prettyPrint) {
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    } else {
        echo json_encode($data);
    }
    exit;
}

function sendJsonErrorWithStatus($message, $statusCode) {
    ob_clean();
    http_response_code($statusCode);
    echo json_encode(['error' => $message]);
    exit;
} 