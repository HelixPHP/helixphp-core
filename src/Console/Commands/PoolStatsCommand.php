<?php

declare(strict_types=1);

namespace PivotPHP\Core\Console\Commands;

use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Pool\Psr7Pool;

/**
 * Comando para exibir estatísticas do object pool
 *
 * @package PivotPHP\Core\Console\Commands
 * @since 2.1.1
 */
class PoolStatsCommand
{
    /**
     * Executa o comando
     */
    public function execute(): void
    {
        $this->displayHeader();
        $this->displayPoolStats();
        $this->displayPerformanceMetrics();
        $this->displayRecommendations();
    }

    /**
     * Exibe cabeçalho
     */
    private function displayHeader(): void
    {
        echo "\n";
        echo "╔══════════════════════════════════════════════════════════════════════════════╗\n";
        echo "║                              POOL STATISTICS                                 ║\n";
        echo "╚══════════════════════════════════════════════════════════════════════════════╝\n";
        echo "\n";
    }

    /**
     * Exibe estatísticas do pool
     */
    private function displayPoolStats(): void
    {
        $stats = OptimizedHttpFactory::getPoolStats();

        if (isset($stats['metrics_disabled'])) {
            echo "⚠️  Pool metrics are disabled\n\n";
            return;
        }

        echo "📊 Pool Sizes:\n";
        echo "┌─────────────────┬─────────────────┐\n";
        echo "│ Pool Type       │ Current Size    │\n";
        echo "├─────────────────┼─────────────────┤\n";

        foreach ($stats['pool_sizes'] as $type => $size) {
            $type = ucfirst($type);
            echo sprintf("│ %-15s │ %15d │\n", $type, $size);
        }

        echo "└─────────────────┴─────────────────┘\n\n";
    }

    /**
     * Exibe métricas de performance
     */
    private function displayPerformanceMetrics(): void
    {
        $metrics = OptimizedHttpFactory::getPerformanceMetrics();

        if (isset($metrics['metrics_disabled'])) {
            return;
        }

        echo "🚀 Performance Metrics:\n";
        echo "┌─────────────────┬─────────────────┐\n";
        echo "│ Metric          │ Value           │\n";
        echo "├─────────────────┼─────────────────┤\n";

        echo sprintf("│ %-15s │ %15s │\n", 'Memory Usage', $this->formatBytes($metrics['memory_usage']['current']));
        echo sprintf("│ %-15s │ %15s │\n", 'Peak Memory', $this->formatBytes($metrics['memory_usage']['peak']));

        echo "└─────────────────┴─────────────────┘\n\n";

        echo "♻️  Reuse Efficiency:\n";
        echo "┌─────────────────┬─────────────────┐\n";
        echo "│ Object Type     │ Reuse Rate      │\n";
        echo "├─────────────────┼─────────────────┤\n";

        foreach ($metrics['pool_efficiency'] as $type => $rate) {
            $type = str_replace('_reuse_rate', '', $type);
            $type = ucfirst($type);
            $emoji = $rate > 80 ? '🟢' : ($rate > 50 ? '🟡' : '🔴');
            echo sprintf("│ %-15s │ %s %11.1f%% │\n", $type, $emoji, $rate);
        }

        echo "└─────────────────┴─────────────────┘\n\n";
    }

    /**
     * Exibe recomendações
     */
    private function displayRecommendations(): void
    {
        $metrics = OptimizedHttpFactory::getPerformanceMetrics();

        if (isset($metrics['metrics_disabled'])) {
            return;
        }

        echo "💡 Recommendations:\n";
        foreach ($metrics['recommendations'] as $recommendation) {
            echo "   • {$recommendation}\n";
        }
        echo "\n";
    }

    /**
     * Formata bytes para exibição
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Comando para limpar pools
     */
    public function clearPools(): void
    {
        OptimizedHttpFactory::clearPools();
        echo "✅ All pools cleared successfully\n";
    }

    /**
     * Comando para aquecer pools
     */
    public function warmUpPools(): void
    {
        OptimizedHttpFactory::warmUpPools();
        echo "🔥 Pools warmed up successfully\n";
    }

    /**
     * Exibe ajuda
     */
    public function help(): void
    {
        echo "\n";
        echo "PivotPHP Pool Statistics Commands:\n";
        echo "\n";
        echo "  stats     Show pool statistics and performance metrics\n";
        echo "  clear     Clear all object pools\n";
        echo "  warmup    Warm up all object pools\n";
        echo "  help      Show this help message\n";
        echo "\n";
        echo "Usage examples:\n";
        echo "  php bin/console pool:stats\n";
        echo "  php bin/console pool:clear\n";
        echo "  php bin/console pool:warmup\n";
        echo "\n";
    }
}
