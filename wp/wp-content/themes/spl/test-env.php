<?php
// Nạp WordPress
require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/wp/wp-load.php';
header('Content-Type: text/plain');
global $wpdb;
echo "DB_NAME: " . DB_NAME . "\n";
echo "DB_USER: " . DB_USER . "\n";
echo "DB_PASSWORD: " . DB_PASSWORD . "\n";
echo "DB_HOST: " . DB_HOST . "\n";
echo "Table prefix: " . $wpdb->prefix . "\n";
echo "Last error: " . $wpdb->last_error . "\n";
// show tables
$tables = $wpdb->get_col("SHOW TABLES");
echo "Tables:\n" . implode("\n", $tables) . "\n";
