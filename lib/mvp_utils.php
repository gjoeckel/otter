<?php
/**
 * MVP Utils - Simple utility functions
 * Replaces complex error handling with simple functions
 */
function mvpJsonError(string $msg): void {
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}

function mvpJsonSuccess(array $data): void {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function mvpHtmlError(string $msg): string {
    return '<div class="error-message">' . htmlspecialchars($msg) . '</div>';
}

function mvpHtmlSuccess(string $msg): string {
    return '<div class="success-message">' . htmlspecialchars($msg) . '</div>';
}
?>
