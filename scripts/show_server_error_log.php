<?php
declare(strict_types=1);

$log_paths = [
    '/www/wwwlogs/dailynew.bluerabike.com.error.log',
    '/www/wwwlogs/dailynew.bluerabike.com.log',
    '/usr/local/lsws/logs/error.log',
    '/usr/local/lsws/logs/stderr.log',
    '/www/server/php/84/var/log/php-fpm.log',
    __DIR__ . '/../wp/wp-content/debug.log',
];

echo "=========================================================\n";
echo "🔍 KIỂM TRA LOG LỖI TRÊN VPS\n";
echo "=========================================================\n";

foreach ($log_paths as $p) {
    if (file_exists($p)) {
        echo "\n📄 File log: $p (Size: " . filesize($p) . " bytes)\n";
        $lines = file($p);
        $last_lines = array_slice($lines, -25);
        foreach ($last_lines as $l) {
            echo "   " . trim($l) . "\n";
        }
    }
}
