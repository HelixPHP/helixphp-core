<?php
/**
 * Benchmark Comparison Tool
 * Compares two benchmark reports and shows performance differences
 */

declare(strict_types=1);

if ($argc < 3) {
    echo "Usage: php compare_benchmarks.php <baseline.json> <current.json>\n";
    exit(1);
}

$baselineFile = $argv[1];
$currentFile = $argv[2];

if (!file_exists($baselineFile)) {
    echo "Error: Baseline file not found: {$baselineFile}\n";
    exit(1);
}

if (!file_exists($currentFile)) {
    echo "Error: Current file not found: {$currentFile}\n";
    exit(1);
}

$baseline = json_decode(file_get_contents($baselineFile), true);
$current = json_decode(file_get_contents($currentFile), true);

if (!$baseline || !$current) {
    echo "Error: Invalid JSON in benchmark files\n";
    exit(1);
}

echo "📊 BENCHMARK COMPARISON\n";
echo "=======================\n\n";

echo "📅 Baseline: {$baseline['timestamp']} (PHP {$baseline['php_version']})\n";
echo "📅 Current:  {$current['timestamp']} (PHP {$current['php_version']})\n\n";

echo "| Test | Baseline (ops/s) | Current (ops/s) | Change | Performance |\n";
echo "|------|------------------|-----------------|--------|-------------|\n";

foreach ($current['results'] as $test => $currentResult) {
    if ($test === 'Memory Usage') continue;

    if (!isset($baseline['results'][$test])) {
        echo "| {$test} | N/A | " . number_format($currentResult['ops_per_second'], 0) . " | NEW | 🆕 New Test |\n";
        continue;
    }

    $baselineOps = $baseline['results'][$test]['ops_per_second'];
    $currentOps = $currentResult['ops_per_second'];
    $change = (($currentOps - $baselineOps) / $baselineOps) * 100;

    $changeStr = sprintf("%+.1f%%", $change);
    $emoji = $change > 5 ? "🚀" : ($change < -5 ? "🐌" : "➡️");
    $status = $change > 5 ? "Faster" : ($change < -5 ? "Slower" : "Similar");

    echo sprintf(
        "| %s | %s | %s | %s | %s %s |\n",
        $test,
        number_format($baselineOps, 0),
        number_format($currentOps, 0),
        $changeStr,
        $emoji,
        $status
    );
}

echo "\n📋 Summary:\n";

// Calculate overall performance change
$improvements = 0;
$regressions = 0;
$total = 0;

foreach ($current['results'] as $test => $currentResult) {
    if ($test === 'Memory Usage' || !isset($baseline['results'][$test])) continue;

    $baselineOps = $baseline['results'][$test]['ops_per_second'];
    $currentOps = $currentResult['ops_per_second'];
    $change = (($currentOps - $baselineOps) / $baselineOps) * 100;

    if ($change > 1) $improvements++;
    if ($change < -1) $regressions++;
    $total++;
}

echo "✅ Improvements: {$improvements}/{$total} tests\n";
echo "❌ Regressions: {$regressions}/{$total} tests\n";
echo "➡️ No significant change: " . ($total - $improvements - $regressions) . "/{$total} tests\n";

// Memory comparison
if (isset($baseline['results']['Memory Usage']) && isset($current['results']['Memory Usage'])) {
    $baselineMemory = $baseline['results']['Memory Usage']['memory_per_app'];
    $currentMemory = $current['results']['Memory Usage']['memory_per_app'];
    $memoryChange = (($currentMemory - $baselineMemory) / $baselineMemory) * 100;

    echo "\n💾 Memory Usage Change: " . sprintf("%+.1f%%", $memoryChange) . "\n";
    echo "   Baseline: " . formatBytes($baselineMemory) . " per app\n";
    echo "   Current:  " . formatBytes($currentMemory) . " per app\n";
}

function formatBytes(float $bytes): string
{
    if ($bytes < 1024) return round($bytes) . ' B';
    if ($bytes < 1048576) return round($bytes / 1024, 2) . ' KB';
    if ($bytes < 1073741824) return round($bytes / 1048576, 2) . ' MB';
    return round($bytes / 1073741824, 2) . ' GB';
}
