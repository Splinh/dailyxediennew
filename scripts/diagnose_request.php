<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

header('Content-Type: text/plain; charset=UTF-8');

echo "=========================================================\n";
echo "🔍 WORDPRESS STEP-BY-STEP DIAGNOSTIC PROBE\n";
echo "=========================================================\n";

$_SERVER['HTTP_HOST'] = 'dailynew.bluerabike.com';
$_SERVER['SERVER_NAME'] = 'dailynew.bluerabike.com';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTPS'] = 'on';

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

// Step 1: Load wp-load.php
echo "[STEP 1] Loading wp-load.php...\n";
require_once __DIR__ . '/../wp/wp-load.php';
echo "  -> ✅ wp-load.php loaded successfully.\n";

// Step 2: Load theme functions.php explicitly if not loaded
echo "\n[STEP 2] Loading Theme functions.php...\n";
try {
    $func = get_template_directory() . '/functions.php';
    if (file_exists($func)) {
        require_once $func;
        echo "  -> ✅ Theme functions.php loaded successfully.\n";
    } else {
        echo "  -> ❌ {$func} not found!\n";
    }
} catch (\Throwable $e) {
    echo "  -> ❌ ERROR in functions.php: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Step 3: Trigger after_setup_theme
echo "\n[STEP 3] Triggering 'after_setup_theme' action...\n";
try {
    do_action('after_setup_theme');
    echo "  -> ✅ 'after_setup_theme' finished without errors.\n";
} catch (\Throwable $e) {
    echo "  -> ❌ ERROR in after_setup_theme: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Step 4: Trigger init
echo "\n[STEP 4] Triggering 'init' action...\n";
try {
    do_action('init');
    echo "  -> ✅ 'init' finished without errors.\n";
} catch (\Throwable $e) {
    echo "  -> ❌ ERROR in init: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Step 5: Trigger wp_loaded
echo "\n[STEP 5] Triggering 'wp_loaded' action...\n";
try {
    do_action('wp_loaded');
    echo "  -> ✅ 'wp_loaded' finished without errors.\n";
} catch (\Throwable $e) {
    echo "  -> ❌ ERROR in wp_loaded: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Step 6: Setup main query
echo "\n[STEP 6] Executing wp()...\n";
try {
    wp();
    echo "  -> ✅ wp() finished without errors.\n";
} catch (\Throwable $e) {
    echo "  -> ❌ ERROR in wp(): " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Step 7: Load template
echo "\n[STEP 7] Loading template via template-loader.php...\n";
global $wp_query, $template;
echo "  -> show_on_front: " . get_option('show_on_front') . "\n";
echo "  -> page_on_front: " . get_option('page_on_front') . "\n";
echo "  -> home option: " . get_option('home') . "\n";
echo "  -> siteurl option: " . get_option('siteurl') . "\n";
echo "  -> is_front_page: " . (is_front_page() ? 'YES' : 'NO') . "\n";
echo "  -> is_home: " . (is_home() ? 'YES' : 'NO') . "\n";
echo "  -> is_page: " . (is_page() ? 'YES' : 'NO') . "\n";
echo "  -> is_404: " . (is_404() ? 'YES' : 'NO') . "\n";
try {
    ob_start();
    require_once ABSPATH . WPINC . '/template-loader.php';
    $out = ob_get_clean();
    echo "  -> Selected template: " . ($template ?: '(none)') . "\n";
    echo "  -> ✅ Template rendered! Output length: " . strlen($out) . " bytes\n";
} catch (\Throwable $e) {
    ob_end_clean();
    echo "  -> ❌ ERROR in template-loader: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo $e->getTraceAsString() . "\n";
}

// Step 8: Test root index.php isolation
echo "\n[STEP 8] Testing root index.php...\n";
$index_file = realpath(__DIR__ . '/../index.php');
echo "  -> Root index path: {$index_file}\n";
echo "  -> Readable: " . (is_readable($index_file) ? 'YES' : 'NO') . "\n";
echo "  -> Permissions: " . substr(sprintf('%o', fileperms($index_file)), -4) . "\n";

echo "\n=========================================================\n";
echo "✅ ALL STEPS COMPLETED\n";
echo "=========================================================\n";
