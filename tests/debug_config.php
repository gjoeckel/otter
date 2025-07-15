<?php
echo "Current directory: " . __DIR__ . "\n";
echo "Config path: " . __DIR__ . '/config/csu/enterprise.json' . "\n";
echo "File exists: " . (file_exists(__DIR__ . '/config/csu/enterprise.json') ? 'Yes' : 'No') . "\n";
echo "File readable: " . (is_readable(__DIR__ . '/config/csu/enterprise.json') ? 'Yes' : 'No') . "\n";

if (file_exists(__DIR__ . '/config/csu/enterprise.json')) {
    echo "File size: " . filesize(__DIR__ . '/config/csu/enterprise.json') . " bytes\n";
    echo "First 100 chars: " . substr(file_get_contents(__DIR__ . '/config/csu/enterprise.json'), 0, 100) . "\n";
} 