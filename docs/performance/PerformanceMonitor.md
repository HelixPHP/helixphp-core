# PerformanceMonitor

O PerformanceMonitor é uma ferramenta completa para análise e monitoramento de performance no Express PHP. Ele oferece insights em tempo real sobre uso de memória, cache, pools e performance geral da aplicação.

## Inicialização do Monitor

### Configuração Básica

```php
use Express\Monitoring\PerformanceMonitor;

// Inicializar com configuração padrão
PerformanceMonitor::initialize();

// Configuração customizada
PerformanceMonitor::initialize([
    'enable_alerts' => true,
    'memory_threshold' => 85,      // % de uso de memória para alertas
    'hit_rate_threshold' => 75,    // % mínimo de hit rate
    'gc_threshold' => 50,          // Limite de ciclos de GC
    'alert_cooldown' => 600        // Cooldown entre alertas (segundos)
]);
```

### Integração com Aplicação

```php
$app = new Application();

// Middleware de monitoring
$app->use(function($req, $res, $next) {
    $start = microtime(true);

    $response = $next();

    $duration = microtime(true) - $start;
    PerformanceMonitor::recordRequest($duration, [
        'path' => $req->path(),
        'method' => $req->method,
        'status' => $res->getStatusCode()
    ]);

    return $response;
});
```

## Dashboard de Performance

### Obtendo Dashboard Completo

```php
// Dashboard completo
$dashboard = PerformanceMonitor::getDashboard();

$app->get('/admin/performance', function($req, $res) {
    $dashboard = PerformanceMonitor::getDashboard();
    return $res->json($dashboard);
});
```

### Estrutura do Dashboard

```php
// Exemplo de retorno do dashboard
[
    'system_info' => [
        'uptime_seconds' => 3600.45,
        'uptime_formatted' => '1h 0m 0s',
        'requests_processed' => 1250,
        'requests_per_second' => 0.35,
        'php_version' => '8.3.0',
        'memory_limit' => '512M',
        'current_memory' => '45.2 MB',
        'peak_memory' => '52.1 MB',
        'opcache_enabled' => true
    ],
    'pool_status' => [
        'header_pool' => [
            'status' => 'optimal',
            'hit_rate' => 85.2,
            'total_items' => 1500,
            'memory_saved' => '2.4 MB'
        ],
        'stream_pool' => [
            'status' => 'optimal',
            'hit_rate' => 78.5,
            'total_items' => 850,
            'memory_usage' => '8.1 MB'
        ]
    ],
    'cache_status' => [
        'operations_cache' => [
            'status' => 'healthy',
            'hit_rate' => 92.1,
            'total_operations' => 5200,
            'cache_size' => '1.2 MB'
        ]
    ],
    'memory_analysis' => [
        'current_usage_percent' => 45.2,
        'trend' => 'stable',
        'efficiency_score' => 87.5
    ],
    'performance_alerts' => [],
    'recommendations' => [
        'Consider increasing header pool size for better hit rates',
        'Memory usage is optimal'
    ]
]
```

## Monitoramento de Componentes Específicos

### Pool Status

```php
// Status dos pools de objetos
$poolStatus = PerformanceMonitor::getPoolStatus();

foreach ($poolStatus as $poolName => $status) {
    echo "Pool: {$poolName}\n";
    echo "Status: {$status['status']}\n";
    echo "Hit Rate: {$status['hit_rate']}%\n";
    echo "Items: {$status['total_items']}\n";
    echo "---\n";
}
```

### Cache Analysis

```php
// Análise de cache
$cacheStatus = PerformanceMonitor::getCacheStatus();

// Verificar performance do cache
if ($cacheStatus['operations_cache']['hit_rate'] < 80) {
    echo "Cache hit rate baixo: considere otimizar!\n";
}
```

### Memory Analysis

```php
// Análise de memória
$memoryAnalysis = PerformanceMonitor::getMemoryAnalysis();

echo "Uso atual: {$memoryAnalysis['current_usage_percent']}%\n";
echo "Tendência: {$memoryAnalysis['trend']}\n";
echo "Score de eficiência: {$memoryAnalysis['efficiency_score']}\n";
```

## Alertas de Performance

### Sistema de Alertas

```php
// Verificar alertas ativos
$alerts = PerformanceMonitor::getActiveAlerts();

foreach ($alerts as $alert) {
    echo "⚠️ {$alert['type']}: {$alert['message']}\n";
    echo "Severidade: {$alert['severity']}\n";
    echo "Timestamp: {$alert['timestamp']}\n";
}
```

### Configuração de Alertas

```php
// Configurar thresholds customizados
PerformanceMonitor::setAlertThresholds([
    'memory_warning' => 70,     // % de uso de memória
    'memory_critical' => 90,    // % crítico
    'hit_rate_warning' => 60,   // % mínimo de hit rate
    'response_time_warning' => 2.0,  // segundos
    'error_rate_warning' => 5        // % de erros
]);

// Handler customizado para alertas
PerformanceMonitor::setAlertHandler(function($alert) {
    // Enviar notificação
    $notifier = app('notifier');
    $notifier->sendAlert($alert);

    // Log crítico
    if ($alert['severity'] === 'critical') {
        error_log("CRITICAL ALERT: " . json_encode($alert));
    }
});
```

## Métricas Customizadas

### Registrando Métricas

```php
// Registrar métrica customizada
PerformanceMonitor::recordMetric('api_calls', 1);
PerformanceMonitor::recordMetric('database_queries', 5);
PerformanceMonitor::recordMetric('cache_misses', 2);

// Métrica com contexto
PerformanceMonitor::recordMetric('user_action', 1, [
    'action' => 'login',
    'user_id' => 123,
    'duration' => 0.45
]);
```

### Timing de Operações

```php
// Medir tempo de operação
$timer = PerformanceMonitor::startTimer('database_query');

// ... executar query ...
$result = $database->query("SELECT * FROM users");

$duration = PerformanceMonitor::stopTimer($timer);
echo "Query executada em: {$duration}ms\n";

// Timing automático com callback
$result = PerformanceMonitor::time('expensive_operation', function() {
    // Operação custosa
    return processLargeDataset();
});
```

### Profiling de Métodos

```php
// Profiling de classe
class UserService
{
    use PerformanceMonitoringTrait;

    public function createUser($data)
    {
        return $this->profile(__METHOD__, function() use ($data) {
            // Lógica de criação
            return $this->repository->create($data);
        });
    }

    public function getAllUsers()
    {
        return $this->profile(__METHOD__, function() {
            return $this->repository->getAll();
        });
    }
}

// Usar o serviço
$userService = new UserService();
$users = $userService->getAllUsers();

// Ver estatísticas
$stats = PerformanceMonitor::getMethodStats(UserService::class);
```

## Relatórios e Análises

### Relatório Detalhado

```php
// Gerar relatório completo
$report = PerformanceMonitor::generateReport([
    'include_trends' => true,
    'include_recommendations' => true,
    'time_period' => '1 hour'
]);

// Salvar relatório
file_put_contents('performance_report.json', json_encode($report, JSON_PRETTY_PRINT));
```

### Análise de Tendências

```php
// Obter tendências de performance
$trends = PerformanceMonitor::getTrends([
    'metrics' => ['memory_usage', 'response_time', 'hit_rates'],
    'period' => '24 hours',
    'interval' => '1 hour'
]);

foreach ($trends as $metric => $data) {
    echo "Métrica: {$metric}\n";
    echo "Tendência: {$data['trend']}\n";
    echo "Variação: {$data['variance']}%\n";
}
```

### Comparação com Baseline

```php
// Estabelecer baseline
PerformanceMonitor::recordBaseline();

// ... depois de algum tempo ...

// Comparar com baseline
$comparison = PerformanceMonitor::compareWithBaseline();

echo "Performance vs Baseline:\n";
echo "Memória: {$comparison['memory']['change']}%\n";
echo "Response Time: {$comparison['response_time']['change']}ms\n";
echo "Hit Rate: {$comparison['hit_rate']['change']}%\n";
```

## Otimizações Baseadas em Dados

### Recomendações Automáticas

```php
// Obter recomendações de otimização
$recommendations = PerformanceMonitor::getRecommendations();

foreach ($recommendations as $rec) {
    echo "🔧 {$rec['title']}\n";
    echo "   Descrição: {$rec['description']}\n";
    echo "   Impacto estimado: {$rec['estimated_impact']}\n";
    echo "   Prioridade: {$rec['priority']}\n";
}
```

### Auto-tuning

```php
// Habilitar auto-tuning (experimental)
PerformanceMonitor::enableAutoTuning([
    'pool_sizes' => true,        // Ajustar tamanhos de pool automaticamente
    'cache_sizes' => true,       // Ajustar tamanhos de cache
    'gc_tuning' => false         // Ajustar configurações de GC
]);

// Verificar ajustes feitos
$adjustments = PerformanceMonitor::getAutoTuningHistory();
```

## Integração com Ferramentas Externas

### Export para Monitoring Tools

```php
// Export para Prometheus
$prometheusMetrics = PerformanceMonitor::exportPrometheus();

// Export para StatsD
PerformanceMonitor::exportStatsD('localhost:8125');

// Export para InfluxDB
PerformanceMonitor::exportInfluxDB([
    'host' => 'localhost',
    'port' => 8086,
    'database' => 'express_php_metrics'
]);
```

### Webhooks e Notificações

```php
// Configurar webhook para alertas
PerformanceMonitor::setWebhook('https://my-monitoring.com/webhook', [
    'events' => ['memory_critical', 'hit_rate_low', 'error_spike'],
    'format' => 'json'
]);

// Integração com Slack
PerformanceMonitor::setSlackNotifications([
    'webhook_url' => 'https://hooks.slack.com/services/...',
    'channel' => '#alerts',
    'severity_levels' => ['warning', 'critical']
]);
```

## API de Monitoramento

### Endpoints REST

```php
// Criar endpoints de monitoramento
$app->group('/monitoring', function() {

    // Dashboard principal
    Router::get('/dashboard', function($req, $res) {
        return $res->json(PerformanceMonitor::getDashboard());
    });

    // Métricas específicas
    Router::get('/metrics/:metric', function($req, $res) {
        $metric = $req->param('metric');
        $data = PerformanceMonitor::getMetric($metric);
        return $res->json($data);
    });

    // Alertas ativos
    Router::get('/alerts', function($req, $res) {
        return $res->json(PerformanceMonitor::getActiveAlerts());
    });

    // Relatório customizado
    Router::post('/report', function($req, $res) {
        $options = $req->body;
        $report = PerformanceMonitor::generateReport($options);
        return $res->json($report);
    });

    // Reset estatísticas
    Router::post('/reset', function($req, $res) {
        PerformanceMonitor::reset();
        return $res->json(['status' => 'reset']);
    });

}, [AdminMiddleware::class]);
```

## Configuração Avançada

### Sampling e Performance

```php
// Configurar sampling para reduzir overhead
PerformanceMonitor::setSampling([
    'requests' => 0.1,      // Monitorar 10% das requisições
    'database' => 0.5,      // Monitorar 50% das queries
    'cache' => 1.0          // Monitorar 100% das operações de cache
]);

// Configurar buffer para reduzir I/O
PerformanceMonitor::setBuffering([
    'enabled' => true,
    'buffer_size' => 1000,   // Número de métricas no buffer
    'flush_interval' => 60   // Flush a cada 60 segundos
]);
```

### Persistência de Dados

```php
// Configurar storage para métricas históricas
PerformanceMonitor::setStorage('file', [
    'path' => '/var/log/express-php/metrics',
    'rotation' => 'daily',
    'retention' => '30 days'
]);

// Ou usar Redis
PerformanceMonitor::setStorage('redis', [
    'host' => 'localhost',
    'port' => 6379,
    'database' => 1,
    'prefix' => 'perf:'
]);
```

## Boas Práticas

### 1. Monitoramento Contínuo

```php
// Implementar health check
$app->get('/health', function($req, $res) {
    $health = PerformanceMonitor::getHealthStatus();

    $statusCode = $health['status'] === 'healthy' ? 200 : 503;

    return $res->status($statusCode)->json($health);
});
```

### 2. Alertas Inteligentes

```php
// Configurar alertas baseados em tendências
PerformanceMonitor::setSmartAlerts([
    'memory_spike' => [
        'threshold' => '20% increase in 5 minutes',
        'action' => 'immediate_alert'
    ],
    'performance_degradation' => [
        'threshold' => '50% slower than baseline',
        'action' => 'warning'
    ]
]);
```

### 3. Overhead Mínimo

```php
// Configurar para produção (overhead mínimo)
PerformanceMonitor::setProductionMode([
    'sampling_rate' => 0.01,     // 1% das requisições
    'async_logging' => true,      // Log assíncrono
    'buffer_size' => 5000,       // Buffer maior
    'detailed_traces' => false   // Desabilitar traces detalhados
]);
```

O PerformanceMonitor é uma ferramenta essencial para manter sua aplicação Express PHP otimizada e monitorada em produção, oferecendo insights valiosos para tomada de decisões sobre performance.
