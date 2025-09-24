<?php
require_once __DIR__ . '/../lib/session.php';
initializeSession();

$_SESSION['organization_authenticated'] = true;

$sessionId = session_id();
header('Location: ../reports/?PHPSESSID=' . urlencode($sessionId));
exit;
?>
