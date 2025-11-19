<?php
// Simple test for weather fetch
require_once __DIR__ . '/includes/functions.php';
$test = fetch_weather('London', 'metric');
if (isset($test['data'])) {
    echo "Weather fetch test: OK\n";
} else {
    echo "Weather fetch test: FAIL - ".$test['error']."\n";
}
