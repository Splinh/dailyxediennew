<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=UTF-8');

echo "=== INSPECTING VPS ROOT INDEX.PHP ===\n";

$file = __DIR__ . '/../index.php';
echo "File path: {$file}\n";
echo "Permissions: " . substr(sprintf('%o', fileperms($file)), -4) . "\n";
echo "Owner UID: " . fileowner($file) . "\n";

echo "\n--- FILE CONTENT ---\n";
echo file_get_contents($file) . "\n";

echo "\n--- GIT STATUS ON VPS ---\n";
echo shell_exec('git status 2>&1') . "\n";

echo "\n--- GIT DIFF ON VPS ---\n";
echo shell_exec('git diff index.php 2>&1') . "\n";
