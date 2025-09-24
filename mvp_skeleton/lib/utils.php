<?php
function jsonError(string $msg): void {
    header('Content-Type: application/json');
    echo json_encode(['error' => $msg]);
    exit;
}
?>
