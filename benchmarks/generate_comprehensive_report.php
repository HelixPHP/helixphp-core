<?php
/**
 * HelixPHP Framework - Comprehensive Benchmark Report Generator
 *
 * Generates a comprehensive report comparing Low, Normal, and High quantity benchmarks
 */

declare(strict_types=1);

$reportsDir = __DIR__ . '/reports';

// Find the latest reports for each category
$categories = ['low', 'normal', 'high'];
$reports = [];

foreach ($categories as $category) {
    $pattern = $reportsDir . "/benchmark_{$category}_*.json";
    $files = glob($pattern);

    if (!empty($files)) {
        // Sort by modification time, newest first
        usort($files, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });

        $reports[$category] = $files[0];
    }
}

if (empty($reports)) {
    echo "❌ No benchmark reports found. Run benchmarks first.\n";
    exit(1);
}

echo "📊 Generating comprehensive benchmark report...\n";

// Load and parse reports
$data = [];
foreach ($reports as $category => $file) {
    $content = file_get_contents($file);
    $report = json_decode($content, true);

    if ($report) {
        $data[$category] = $report;
        echo "✅ Loaded {$category} quantity report\n";
    } else {
        echo "⚠️  Failed to load {$category} quantity report\n";
    }
}

if (count($data) < 2) {
    echo "❌ Need at least 2 benchmark reports to generate comparison.\n";
    exit(1);
}

echo "🔄 Generating comparison analysis...\n";

// Create comprehensive comparison data
$comprehensive = [
    'generated_at' => date('Y-m-d H:i:s'),
    'categories' => [],
    'comparison' => []
];

// Collect all unique test names across categories
$allTests = [];
foreach ($data as $category => $report) {
    if (isset($report['results'])) {
        $allTests = array_merge($allTests, array_keys($report['results']));
        $comprehensive['categories'][$category] = [
            'iterations' => $report['iterations'] ?? 0,
            'timestamp' => $report['timestamp'] ?? 'Unknown',
            'php_version' => $report['php_version'] ?? 'Unknown'
        ];
    }
}
$allTests = array_unique($allTests);

// Generate comparison for each test
if (!empty($allTests)) {
    foreach ($allTests as $testName) {
        $testComparison = [
            'test_name' => $testName,
            'categories' => []
        ];

        foreach ($categories as $category) {
            if (isset($data[$category]['results'][$testName])) {
                $result = $data[$category]['results'][$testName];
                $testComparison['categories'][$category] = [
                    'ops_per_second' => (float)($result['ops_per_second'] ?? 0),
                    'avg_time_microseconds' => (float)($result['avg_time_microseconds'] ?? 0),
                    'memory_used' => (int)($result['memory_used'] ?? 0),
                    'iterations' => $data[$category]['iterations'] ?? 0
                ];
            } else {
                $testComparison['categories'][$category] = [
                    'ops_per_second' => 0.0,
                    'avg_time_microseconds' => 0.0,
                    'memory_used' => 0,
                    'iterations' => $data[$category]['iterations'] ?? 0
                ];
            }
        }

        $comprehensive['comparison'][$testName] = $testComparison;
    }
}

// Save comprehensive report
$comprehensiveFile = $reportsDir . '/comprehensive_benchmark_' . date('Y-m-d_H-i-s') . '.json';
file_put_contents($comprehensiveFile, json_encode($comprehensive, JSON_PRETTY_PRINT));

// Generate markdown summary
$markdownContent = generateMarkdownSummary($comprehensive);
$markdownFile = $reportsDir . '/COMPREHENSIVE_PERFORMANCE_SUMMARY.md';
file_put_contents($markdownFile, $markdownContent);

echo "✅ Comprehensive report generated!\n";
echo "📋 JSON Report: {$comprehensiveFile}\n";
echo "📄 Markdown Summary: {$markdownFile}\n";

function generateMarkdownSummary(array $data): string
{
    $markdown = "# HelixPHP Framework - Comprehensive Performance Report\n\n";
    $markdown .= "*Generated on: {$data['generated_at']}*\n\n";

    // Overview section
    $markdown .= "## Test Configuration Overview\n\n";
    $markdown .= "| Category | Iterations | Generated |\n";
    $markdown .= "|----------|------------|-----------|\n";

    if (isset($data['categories'])) {
        foreach ($data['categories'] as $category => $info) {
            $categoryName = ucfirst($category);
            $iterations = number_format($info['iterations']);
            $timestamp = $info['timestamp'];
            $markdown .= "| **{$categoryName}** | {$iterations} | {$timestamp} |\n";
        }
    }

    $markdown .= "\n";

    // Performance comparison table
    if (isset($data['comparison']) && !empty($data['comparison'])) {
        $markdown .= "## Performance Comparison\n\n";
        $markdown .= "| Test | Low (100) | Normal (1K) | High (10K) | Performance Trend |\n";
        $markdown .= "|------|-----------|-------------|------------|-------------------|\n";

        foreach ($data['comparison'] as $testName => $comparison) {
            $markdown .= "| **{$testName}** |";

            $opsData = [];
            foreach (['low', 'normal', 'high'] as $cat) {
                if (isset($comparison['categories'][$cat])) {
                    $ops = (float)($comparison['categories'][$cat]['ops_per_second'] ?? 0);
                    $opsData[$cat] = $ops;
                    if ($ops > 0) {
                        $markdown .= " " . number_format($ops) . " ops/s |";
                    } else {
                        $markdown .= " N/A |";
                    }
                } else {
                    $opsData[$cat] = 0;
                    $markdown .= " N/A |";
                }
            }

            // Generate performance trend
            $trend = generatePerformanceTrend($opsData);
            $markdown .= " {$trend} |\n";
        }
    }

    $markdown .= "\n";

    // Top performers section
    $markdown .= generateTopPerformers($data);

    // Insights section
    $markdown .= generateInsights($data);

    // Recommendations
    $markdown .= "## Recommendations\n\n";
    $markdown .= "### 🚀 Performance Optimization\n\n";
    $markdown .= "1. **Focus on variable performance tests** - These show the most room for improvement\n";
    $markdown .= "2. **Analyze memory usage patterns** - High memory usage may indicate optimization opportunities\n";
    $markdown .= "3. **Monitor scalability** - Tests that perform worse with higher iterations need attention\n\n";

    $markdown .= "### 📊 Monitoring\n\n";
    $markdown .= "1. **Regular benchmarking** - Run comprehensive benchmarks before releases\n";
    $markdown .= "2. **Performance regression testing** - Compare with baseline results\n";
    $markdown .= "3. **Load testing** - Use high-iteration results for capacity planning\n\n";

    return $markdown;
}

function generatePerformanceTrend(array $opsData): string
{
    $validOps = array_filter($opsData, function($ops) { return $ops > 0; });

    if (count($validOps) < 2) {
        return "Insufficient data";
    }

    $values = array_values($validOps);
    $first = $values[0];
    $last = end($values);

    if ($first == 0) {
        return "No trend data";
    }

    $change = (($last - $first) / $first) * 100;

    if (abs($change) < 5) {
        return "🔄 Stable";
    } elseif ($change > 0) {
        return "📈 Improving (" . number_format($change, 1) . "%)";
    } else {
        return "📉 Declining (" . number_format(abs($change), 1) . "%)";
    }
}

function generateTopPerformers(array $data): string
{
    if (!isset($data['comparison']) || empty($data['comparison'])) {
        return "## Top Performers\n\nNo performance data available.\n\n";
    }

    $performers = [];

    foreach ($data['comparison'] as $testName => $comparison) {
        $avgOps = 0;
        $count = 0;

        foreach (['low', 'normal', 'high'] as $cat) {
            if (isset($comparison['categories'][$cat]) && $comparison['categories'][$cat]['ops_per_second'] > 0) {
                $avgOps += $comparison['categories'][$cat]['ops_per_second'];
                $count++;
            }
        }

        if ($count > 0) {
            $performers[$testName] = $avgOps / $count;
        }
    }

    arsort($performers);

    $markdown = "## Top Performers\n\n";
    $markdown .= "### 🏆 Highest Average Performance\n\n";

    $topCount = 0;
    foreach ($performers as $testName => $avgOps) {
        if ($topCount >= 5) break;
        $markdown .= ($topCount + 1) . ". **{$testName}** - " . number_format($avgOps) . " avg ops/s\n";
        $topCount++;
    }

    $markdown .= "\n";
    return $markdown;
}

function generateInsights(array $data): string
{
    if (!isset($data['comparison']) || empty($data['comparison'])) {
        return "### Key Insights\n\nNo performance data available for analysis.\n\n";
    }

    $insights = "### Key Insights\n\n";

    // Find most consistent performers
    $consistentTests = [];
    $variableTests = [];

    foreach ($data['comparison'] as $testName => $comparison) {
        $opsData = [];
        foreach (['low', 'normal', 'high'] as $cat) {
            if (isset($comparison['categories'][$cat]) && $comparison['categories'][$cat]['ops_per_second'] > 0) {
                $opsData[] = $comparison['categories'][$cat]['ops_per_second'];
            }
        }

        if (count($opsData) >= 2) {
            $max = max($opsData);
            $min = min($opsData);

            if ($max > 0) {
                $variation = (($max - $min) / $max) * 100;
                if ($variation < 15) {
                    $consistentTests[] = $testName;
                } elseif ($variation > 30) {
                    $variableTests[] = $testName;
                }
            }
        }
    }

    if (!empty($consistentTests)) {
        $insights .= "**🎯 Most Consistent Performance:**\n";
        foreach (array_slice($consistentTests, 0, 3) as $test) {
            $insights .= "- {$test}\n";
        }
        $insights .= "\n";
    }

    if (!empty($variableTests)) {
        $insights .= "**⚠️ Variable Performance (needs optimization):**\n";
        foreach (array_slice($variableTests, 0, 3) as $test) {
            $insights .= "- {$test}\n";
        }
        $insights .= "\n";
    }

    return $insights;
}
