<?php
// WebSocket Console Bridge Include - Real-time console monitoring for AI agents
// Usage: include_once 'lib/websocket-console-include.php';

// Output the HTML/JS content
echo '<!-- WebSocket Console Bridge -->';
echo '<script src="websocket-console-bridge.js"></script>';
echo '<script>';
echo '// Optional: Add page-specific monitoring';
echo 'document.addEventListener("DOMContentLoaded", function() {';
echo '    // You can add custom monitoring here';
echo '    // For example, monitor specific form submissions or AJAX calls';
echo '    // Example: Monitor form submissions';
echo '    document.addEventListener("submit", function(event) {';
echo '    });';
echo '    // Example: Monitor button clicks';
echo '    document.addEventListener("click", function(event) {';
echo '        if (event.target.tagName === "BUTTON") {';
echo '        }';
echo '    });';
echo '});';
echo '</script>';