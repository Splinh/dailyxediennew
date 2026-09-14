<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=========================================================\n";
echo "🔍 WORDPRESS DIAGNOSTIC PROBE (dailynew.bluerabike.com)\n";
echo "=========================================================\n";

$_SERVER['HTTP_HOST'] = 'dailynew.bluerabike.com';
$_SERVER['SERVER_NAME'] = 'dailynew.bluerabike.com';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';

// Register shutdown function to catch any fatal error
register_shutdown_function(function() {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        echo "\n💥 SHUTDOWN FATAL ERROR DETECTED:\n";
        echo "Type: {$err['type']}\n";
        echo "Message: {$err['message']}\n";
        echo "File: {$err['file']}\n";
        echo "Line: {$err['line']}\n";
    }
});

// Step 1: Check theme vendor autoload
echo "[1] Checking Theme autoload...\n";
$theme_vendor = __DIR__ . '/../wp/wp-content/themes/spl/vendor/autoload.php';
if (file_exists($theme_vendor)) {
    echo "  -> ✅ {$theme_vendor} exists.\n";
} else {
    echo "  -> ❌ {$theme_vendor} MISSING!\n";
}

// Step 2: Load wp-load.php
echo "[2] Loading wp-load.php...\n";
require_once __DIR__ . '/../wp/wp-load.php';
echo "  -> ✅ wp-load.php loaded successfully.\n";
echo "  -> Active theme: " . wp_get_theme()->get('Name') . "\n";
echo "  -> Theme directory: " . get_template_directory() . "\n";

// Step 3: Run wp()
echo "[3] Setting up main query via wp()...\n";
wp();
echo "  -> ✅ wp() executed. is_front_page=" . (is_front_page() ? 'yes' : 'no') . ", is_home=" . (is_home() ? 'yes' : 'no') . "\n";

// Step 4: Render front page template
echo "[4] Rendering front page template...\n";
ob_start();
try {
    if (!defined('WP_USE_THEMES')) {
        define('WP_USE_THEMES', true);
    }
    require_once ABSPATH . WPINC . '/template-loader.php';
    $out = ob_get_clean();
    echo "  -> ✅ Template rendered OK! (Output length: " . strlen($out) . " bytes)\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "\n❌ EXCEPTION THROWN DURING TEMPLATE RENDER:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

// Step 5: Check logs in /www/wwwlogs
echo "\n[5] Checking error logs in /www/wwwlogs/...\n";
$logs = glob('/www/wwwlogs/*dailynew*');
if (!empty($logs)) {
    foreach ($logs as $log) {
        echo "  -> Log file: {$log} (" . filesize($log) . " bytes)\n";
        $lines = file($log);
        $recent = array_slice($lines, -15);
        foreach ($recent as $l) {
            echo "     " . trim($l) . "\n";
        }
    }
} else {
    echo "  -> No *dailynew* logs in /www/wwwlogs/\n";
}

// Step 6: Check OpenLiteSpeed logs
echo "\n[6] Checking /usr/local/lsws/logs/stderr.log for dailynew...\n";
if (file_exists('/usr/local/lsws/logs/stderr.log')) {
    $stderr = file('/usr/local/lsws/logs/stderr.log');
    $matched = array_filter($stderr, fn($l) => str_contains($l, 'dailynew'));
    if (!empty($matched)) {
        echo "  -> Found " . count($matched) . " entries in stderr.log:\n";
        foreach (array_slice($matched, -15) as $l) {
            echo "     " . trim($l) . "\n";
        }
    } else {
        echo "  -> No 'dailynew' entries found in stderr.log (last 5 lines below):\n";
        foreach (array_slice($stderr, -5) as $l) {
            echo "     " . trim($l) . "\n";
        }
    }
}

echo "\n=========================================================\n";
echo "✅ DIAGNOSTIC PROBE FINISHED\n";
echo "=========================================================\n";
