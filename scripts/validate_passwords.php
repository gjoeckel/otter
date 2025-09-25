<?php
// Simple validator for config/passwords.json
// Usage: php scripts/validate_passwords.php

$path = __DIR__ . '/../config/passwords.json';
if (!file_exists($path)) {
    fwrite(STDERR, "ERROR: file not found: $path\n");
    exit(1);
}

$raw = file_get_contents($path);
$data = json_decode($raw, true);
if ($data === null) {
    fwrite(STDERR, "ERROR: invalid JSON\n");
    exit(1);
}

$orgs = isset($data['organizations']) && is_array($data['organizations']) ? $data['organizations'] : [];
$total = count($orgs);
$withDemo = 0;

foreach ($orgs as $org) {
    if (!isset($org['enterprise'])) {
        continue;
    }
    $e = $org['enterprise'];
    if (is_string($e) && $e === 'demo') {
        $withDemo++;
    } elseif (is_array($e) && in_array('demo', $e, true)) {
        $withDemo++;
    }
}

echo "OK: JSON valid. organizations={$total}, with demo={$withDemo}\n";


