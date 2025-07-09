<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;
use PivotPHP\Core\Http\Pool\Psr7Pool;

/**
 * Exemplo de uso do Object Pooling para performance otimizada
 */

echo "🚀 PivotPHP Object Pooling Example\n";
echo "==================================\n\n";

// 1. Inicializar factory com configuração personalizada
echo "1. Initializing optimized factory...\n";
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
    'max_pool_size' => 50,
    'enable_metrics' => true,
]);

// 2. Criar múltiplos requests para demonstrar pooling
echo "2. Creating multiple requests to demonstrate pooling...\n";
$requests = [];
for ($i = 0; $i < 10; $i++) {
    $requests[] = OptimizedHttpFactory::createRequest('GET', "/api/users/{$i}", "/api/users/{$i}");
}

// 3. Criar múltiplas responses
echo "3. Creating multiple responses...\n";
$responses = [];
for ($i = 0; $i < 10; $i++) {
    $response = OptimizedHttpFactory::createResponse();
    $response->json(['user_id' => $i, 'name' => "User {$i}"]);
    $responses[] = $response;
}

// 4. Usar PSR-7 diretamente
echo "4. Using PSR-7 objects directly...\n";
$psr7Requests = [];
for ($i = 0; $i < 5; $i++) {
    $psr7Requests[] = OptimizedHttpFactory::createServerRequest('POST', "/api/posts/{$i}");
}

// 5. Liberar objetos (simulando fim de requests)
echo "5. Releasing objects (simulating end of requests)...\n";
unset($requests, $responses, $psr7Requests);
// Objects will be automatically returned to pool via __destruct

// 6. Exibir estatísticas
echo "6. Pool statistics:\n";
echo "-------------------\n";
displayPoolStats();

// 7. Demonstrar reutilização
echo "\n7. Demonstrating object reuse...\n";
$newRequests = [];
for ($i = 0; $i < 5; $i++) {
    $newRequests[] = OptimizedHttpFactory::createRequest('PUT', "/api/users/{$i}", "/api/users/{$i}");
}

// 8. Estatísticas após reutilização
echo "8. Pool statistics after reuse:\n";
echo "-------------------------------\n";
displayPoolStats();

// 9. Exemplo de configuração dinâmica
echo "\n9. Dynamic configuration example...\n";
echo "Current config: " . json_encode(OptimizedHttpFactory::getConfig()) . "\n";

// Desabilitar pooling temporariamente
OptimizedHttpFactory::setPoolingEnabled(false);
echo "Pooling disabled temporarily\n";

// Criar objeto sem pooling
$nonPooledRequest = OptimizedHttpFactory::createRequest('DELETE', '/api/users/1', '/api/users/1');
echo "Created request without pooling\n";

// Reabilitar pooling
OptimizedHttpFactory::setPoolingEnabled(true);
echo "Pooling re-enabled\n";

// 10. Métricas de performance
echo "\n10. Performance metrics:\n";
echo "------------------------\n";
displayPerformanceMetrics();

// 11. Limpeza final
echo "\n11. Final cleanup...\n";
OptimizedHttpFactory::clearPools();
echo "✅ All pools cleared\n";

echo "\n🎉 Example completed successfully!\n";
echo "    Check the metrics above to see pooling efficiency.\n";
echo "    Higher reuse rates indicate better performance.\n\n";

/**
 * Exibe estatísticas do pool
 */
function displayPoolStats(): void
{
    $stats = OptimizedHttpFactory::getPoolStats();
    
    if (isset($stats['metrics_disabled'])) {
        echo "⚠️  Metrics disabled\n";
        return;
    }

    echo "Pool Sizes:\n";
    foreach ($stats['pool_sizes'] as $type => $size) {
        echo "  {$type}: {$size}\n";
    }
    
    echo "Efficiency:\n";
    foreach ($stats['efficiency'] as $type => $rate) {
        $emoji = $rate > 80 ? '🟢' : ($rate > 50 ? '🟡' : '🔴');
        echo "  {$type}: {$emoji} {$rate}%\n";
    }
}

/**
 * Exibe métricas de performance
 */
function displayPerformanceMetrics(): void
{
    $metrics = OptimizedHttpFactory::getPerformanceMetrics();
    
    if (isset($metrics['metrics_disabled'])) {
        echo "⚠️  Metrics disabled\n";
        return;
    }

    echo "Memory Usage:\n";
    echo "  Current: " . formatBytes($metrics['memory_usage']['current']) . "\n";
    echo "  Peak: " . formatBytes($metrics['memory_usage']['peak']) . "\n";
    
    echo "Recommendations:\n";
    foreach ($metrics['recommendations'] as $recommendation) {
        echo "  • {$recommendation}\n";
    }
}

/**
 * Formata bytes
 */
function formatBytes(int $bytes): string
{
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}