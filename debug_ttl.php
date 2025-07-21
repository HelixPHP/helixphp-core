<?php
// Debug TTL implementation
require_once 'vendor/autoload.php';
use PivotPHP\Core\Cache\FileCache;

$cache = new FileCache('/tmp/debug_cache_' . uniqid());
$key = 'debug_key';

echo 'Starting TTL debug test...' . PHP_EOL;

// Record the start time
$startTime = time();
echo 'Start time: ' . $startTime . PHP_EOL;

// Set with 1 second TTL
$cache->set($key, 'test_value', 1);

// Check the cache file content
$cacheDir = '/tmp/debug_cache_' . str_replace('debug_cache_', '', uniqid());
$filePath = $cacheDir . '/' . md5($key) . '.cache';

if (file_exists($filePath)) {
    $data = unserialize(file_get_contents($filePath));
    echo 'Cache file expires at: ' . $data['expires'] . PHP_EOL;
    echo 'Expected expiry time: ' . ($startTime + 1) . PHP_EOL;
    echo 'Difference: ' . ($data['expires'] - ($startTime + 1)) . PHP_EOL;
} else {
    echo 'Cache file not found!' . PHP_EOL;
}

// Wait and check
sleep(2);
$currentTime = time();
echo 'Current time: ' . $currentTime . PHP_EOL;
echo 'Time elapsed: ' . ($currentTime - $startTime) . PHP_EOL;

// Check if expired
$hasKey = $cache->has($key);
echo 'Has key after 2 seconds: ' . ($hasKey ? 'YES' : 'NO') . PHP_EOL;

if ($hasKey) {
    // Check file contents again
    if (file_exists($filePath)) {
        $data = unserialize(file_get_contents($filePath));
        echo 'Cache file still exists with expires: ' . $data['expires'] . PHP_EOL;
        echo 'Should be expired? ' . ($currentTime >= $data['expires'] ? 'YES' : 'NO') . PHP_EOL;
    }
}