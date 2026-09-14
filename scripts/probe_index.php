<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=UTF-8');

echo "=== TEST INDEX.PHP DIRECT EXECUTION ===\n";

$_SERVER['HTTP_HOST'] = 'dailynew.bluerabike.com';
$_SERVER['SERVER_NAME'] = 'dailynew.bluerabike.com';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';

echo "1. Checking constants...\n";
echo "   WP_USE_THEMES defined: " . (defined('WP_USE_THEMES') ? 'YES' : 'NO') . "\n";

echo "2. Loading root index.php via output buffering...\n";
ob_start();
try {
    require __DIR__ . '/../index.php';
    $output = ob_get_clean();
    echo "   ✅ root index.php executed successfully!\n";
    echo "   Output length: " . strlen($output) . " bytes\n";
    echo "   Snippet (first 200 chars):\n" . substr($output, 0, 200) . "\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "   ❌ ERROR in root index.php:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   In: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "=== END TEST ===\n";
