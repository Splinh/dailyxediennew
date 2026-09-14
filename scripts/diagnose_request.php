<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "=========================================================\n";
echo "🔍 WORDPRESS DIAGNOSTIC PROBE (dailynew.bluerabike.com)\n";
echo "=========================================================\n";

echo "[0] PHP Environment:\n";
echo "  -> Current binary: " . PHP_BINARY . "\n";
echo "  -> PHP Version: " . PHP_VERSION . "\n";
echo "  -> mysqli loaded: " . (extension_loaded('mysqli') ? '✅ YES' : '❌ NO') . "\n";

// If mysqli is missing in current PHP CLI, search for aaPanel PHP binaries
if (!extension_loaded('mysqli')) {
    echo "\n⚠️ CẢNH BÁO: Lệnh `php` hiện tại (" . PHP_BINARY . ") thiếu extension mysqli.\n";
    echo "Đang tìm các phiên bản PHP của aaPanel / OpenLiteSpeed trên VPS:\n";
    
    $candidates = [
        '/www/server/php/84/bin/php',
        '/www/server/php/83/bin/php',
        '/www/server/php/82/bin/php',
        '/www/server/php/81/bin/php',
        '/www/server/php/80/bin/php',
        '/usr/local/lsws/lsphp84/bin/php',
        '/usr/local/lsws/lsphp83/bin/php',
        '/usr/local/lsws/lsphp82/bin/php',
        '/usr/local/lsws/lsphp81/bin/php',
    ];
    
    $found_good = null;
    foreach ($candidates as $bin) {
        if (file_exists($bin)) {
            $has_mysqli = @shell_exec("{$bin} -m 2>&1");
            $ok = $has_mysqli && str_contains($has_mysqli, 'mysqli');
            echo "  - {$bin}: " . ($ok ? "✅ Có mysqli" : "❌ Không có mysqli") . "\n";
            if ($ok && !$found_good) {
                $found_good = $bin;
            }
        }
    }
    
    if ($found_good) {
        echo "\n👉 BẠN HÃY CHẠY LẠI BẰNG LỆNH SAU:\n";
        echo "   {$found_good} scripts/diagnose_request.php\n\n";
    }
}

// Check logs IMMEDIATELY before attempting WP boot
echo "\n[1] Checking error logs in /www/wwwlogs/...\n";
$logs = glob('/www/wwwlogs/*dailynew*');
if (!empty($logs)) {
    foreach ($logs as $log) {
        echo "\n  📄 Log file: {$log} (" . filesize($log) . " bytes)\n";
        $lines = file($log);
        $recent = array_slice($lines, -25);
        foreach ($recent as $l) {
            echo "     " . trim($l) . "\n";
        }
    }
} else {
    echo "  -> No *dailynew* logs in /www/wwwlogs/\n";
    // Check all error logs in /www/wwwlogs
    $all_error_logs = glob('/www/wwwlogs/*error*');
    if ($all_error_logs) {
        echo "  -> Found generic error logs:\n";
        foreach ($all_error_logs as $ael) {
            echo "     * {$ael} (" . filesize($ael) . " bytes)\n";
        }
    }
}

// Check OpenLiteSpeed logs
echo "\n[2] Checking /usr/local/lsws/logs/ for recent errors...\n";
$lsws_logs = ['/usr/local/lsws/logs/stderr.log', '/usr/local/lsws/logs/error.log'];
foreach ($lsws_logs as $lf) {
    if (file_exists($lf)) {
        echo "\n  📄 {$lf} (" . filesize($lf) . " bytes):\n";
        $lines = file($lf);
        $recent = array_slice($lines, -20);
        foreach ($recent as $l) {
            echo "     " . trim($l) . "\n";
        }
    }
}

// If mysqli is available, proceed to test WordPress boot
if (extension_loaded('mysqli')) {
    echo "\n[3] Testing WordPress Boot Sequence...\n";
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

    require_once __DIR__ . '/../wp/wp-load.php';
    echo "  -> ✅ wp-load.php loaded successfully.\n";
    echo "  -> Active theme: " . wp_get_theme()->get('Name') . "\n";

    wp();
    echo "  -> ✅ wp() executed OK.\n";

    echo "\n[4] Rendering front page template...\n";
    ob_start();
    try {
        if (!defined('WP_USE_THEMES')) {
            define('WP_USE_THEMES', true);
        }
        require_once ABSPATH . WPINC . '/template-loader.php';
        $out = ob_get_clean();
        echo "  -> ✅ Front page rendered OK! Length: " . strlen($out) . " bytes\n";
    } catch (\Throwable $e) {
        ob_end_clean();
        echo "\n❌ EXCEPTION THROWN:\n";
        echo "Message: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }
}

echo "\n=========================================================\n";
echo "✅ DIAGNOSTIC PROBE FINISHED\n";
echo "=========================================================\n";
