#!/bin/bash

# Benchmark específico para rotas por grupo
# Express PHP Framework

set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

echo "🚀 Benchmark de Rotas por Grupo - Express PHP"
echo "=============================================="

# Função para executar benchmark PHP
run_benchmark() {
    local name="$1"
    local file="$2"
    local iterations="$3"

    echo "📊 Executando: $name ($iterations iterações)"
    echo "   Arquivo: $file"

    start_time=$(date +%s.%N)
    php "$file" "$iterations" 2>/dev/null
    end_time=$(date +%s.%N)

    execution_time=$(echo "$end_time - $start_time" | bc -l)
    ops_per_second=$(echo "scale=0; $iterations / $execution_time" | bc -l)

    echo "   ⏱️  Tempo total: ${execution_time}s"
    echo "   🔄 Ops/segundo: $ops_per_second"
    echo ""
}

# Criar benchmarks específicos temporários
create_group_benchmarks() {

# 1. Benchmark de registro de grupos
cat > "$PROJECT_ROOT/temp_group_registration_benchmark.php" << 'EOF'
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Express\ApiExpress;

$iterations = isset($argv[1]) ? (int)$argv[1] : 1000;

$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $app = new ApiExpress();

    // Registra grupo com middlewares
    $app->group('/api/v1', function() use ($app) {
        $app->get('/users', function() { return 'users'; });
        $app->get('/users/:id', function() { return 'user'; });
        $app->post('/users', function() { return 'create user'; });
        $app->get('/products', function() { return 'products'; });
        $app->get('/products/:id', function() { return 'product'; });
    }, [
        function() { return 'middleware1'; },
        function() { return 'middleware2'; }
    ]);
}

$end = microtime(true);
$time = ($end - $start) * 1000;

echo "Group Registration Benchmark\n";
echo "Iterations: $iterations\n";
echo "Total time: " . round($time, 3) . "ms\n";
echo "Avg time per op: " . round($time / $iterations, 6) . "ms\n";
echo "Ops per second: " . round($iterations / (($end - $start)), 0) . "\n";
EOF

# 2. Benchmark de identificação de rotas por grupo
cat > "$PROJECT_ROOT/temp_group_identification_benchmark.php" << 'EOF'
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Express\ApiExpress;
use Express\Routing\Router;

$iterations = isset($argv[1]) ? (int)$argv[1] : 1000;

// Setup inicial
$app = new ApiExpress();

$app->group('/api/v1', function() use ($app) {
    $app->get('/users', function() { return 'users'; });
    $app->get('/users/:id', function() { return 'user'; });
    $app->get('/products', function() { return 'products'; });
    $app->get('/products/:id', function() { return 'product'; });
    $app->get('/orders', function() { return 'orders'; });
    $app->get('/orders/:id', function() { return 'order'; });
});

$app->group('/api/v2', function() use ($app) {
    $app->get('/status', function() { return 'status'; });
    $app->get('/health', function() { return 'health'; });
});

// Aquece caches de grupos
$app->router->warmupGroups();

// Rotas de teste
$testRoutes = [
    ['GET', '/api/v1/users'],
    ['GET', '/api/v1/users/123'],
    ['GET', '/api/v1/products'],
    ['GET', '/api/v1/products/456'],
    ['GET', '/api/v1/orders/789'],
    ['GET', '/api/v2/status'],
    ['GET', '/api/v2/health']
];

$start = microtime(true);

for ($i = 0; $i < $iterations; $i++) {
    $route = $testRoutes[$i % count($testRoutes)];
    $app->router->identifyByGroup($route[0], $route[1]);
}

$end = microtime(true);
$time = ($end - $start) * 1000;

echo "Group Route Identification Benchmark\n";
echo "Iterations: $iterations\n";
echo "Total time: " . round($time, 3) . "ms\n";
echo "Avg time per op: " . round($time / $iterations, 6) . "ms\n";
echo "Ops per second: " . round($iterations / (($end - $start)), 0) . "\n";
EOF

# 3. Benchmark comparativo (com vs sem otimização)
cat > "$PROJECT_ROOT/temp_comparative_benchmark.php" << 'EOF'
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Express\ApiExpress;
use Express\Routing\Router;

$iterations = isset($argv[1]) ? (int)$argv[1] : 1000;

// Setup das rotas
$app = new ApiExpress();

// Registra rotas de forma tradicional (método estático)
for ($i = 1; $i <= 20; $i++) {
    Router::get("/old/api/v1/users/$i", function() use ($i) { return "user $i"; });
    Router::get("/old/api/v1/products/$i", function() use ($i) { return "product $i"; });
}
// Registra rotas otimizadas por grupo
$app->group('/new/api/v1', function() use ($app) {
    for ($i = 1; $i <= 20; $i++) {
        $app->get("/users/$i", function() use ($i) { return "user $i"; });
        $app->get("/products/$i", function() use ($i) { return "product $i"; });
    }
});

$app->router->warmupGroups();

echo "Comparative Benchmark (Traditional vs Group Optimized)\n";
echo "Iterations per test: $iterations\n\n";

// Teste com router tradicional
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $userId = ($i % 20) + 1;
    Router::identify('GET', "/old/api/v1/users/$userId");
}
$traditionalTime = (microtime(true) - $start) * 1000;

// Teste com router com grupos otimizados
$start = microtime(true);
for ($i = 0; $i < $iterations; $i++) {
    $userId = ($i % 20) + 1;
    $app->router->identifyByGroup('GET', "/new/api/v1/users/$userId");
}
$optimizedTime = (microtime(true) - $start) * 1000;

$improvement = (($traditionalTime - $optimizedTime) / $traditionalTime) * 100;

echo "Traditional Router:\n";
echo "  Time: " . round($traditionalTime, 3) . "ms\n";
echo "  Ops/sec: " . round($iterations / ($traditionalTime / 1000), 0) . "\n\n";

echo "Group Optimized Router:\n";
echo "  Time: " . round($optimizedTime, 3) . "ms\n";
echo "  Ops/sec: " . round($iterations / ($optimizedTime / 1000), 0) . "\n\n";

echo "Performance Improvement: " . round($improvement, 1) . "%\n";
EOF
}

# Executar benchmarks
echo "🔧 Criando scripts de benchmark temporários..."
create_group_benchmarks

echo "📈 Executando benchmarks de otimização de grupos:"
echo ""

# Benchmarks de baixa carga
echo "🟢 BAIXA CARGA (100 iterações)"
run_benchmark "Registro de Grupos" "$PROJECT_ROOT/temp_group_registration_benchmark.php" 100
run_benchmark "Identificação por Grupos" "$PROJECT_ROOT/temp_group_identification_benchmark.php" 100
run_benchmark "Comparativo (Tradicional vs Otimizado)" "$PROJECT_ROOT/temp_comparative_benchmark.php" 100

# Benchmarks de carga normal
echo "🟡 CARGA NORMAL (1,000 iterações)"
run_benchmark "Registro de Grupos" "$PROJECT_ROOT/temp_group_registration_benchmark.php" 1000
run_benchmark "Identificação por Grupos" "$PROJECT_ROOT/temp_group_identification_benchmark.php" 1000
run_benchmark "Comparativo (Tradicional vs Otimizado)" "$PROJECT_ROOT/temp_comparative_benchmark.php" 1000

# Benchmarks de alta carga
echo "🔴 ALTA CARGA (10,000 iterações)"
run_benchmark "Registro de Grupos" "$PROJECT_ROOT/temp_group_registration_benchmark.php" 10000
run_benchmark "Identificação por Grupos" "$PROJECT_ROOT/temp_group_identification_benchmark.php" 10000
run_benchmark "Comparativo (Tradicional vs Otimizado)" "$PROJECT_ROOT/temp_comparative_benchmark.php" 10000

# Teste de estatísticas de grupo
echo "📊 ESTATÍSTICAS DE GRUPOS"
cat > "$PROJECT_ROOT/temp_group_stats.php" << 'EOF'
<?php
require_once __DIR__ . '/vendor/autoload.php';

use Express\ApiExpress;

$app = new ApiExpress();

// Registra vários grupos
$app->group('/api/v1', function() use ($app) {
    $app->get('/users', function() {});
    $app->get('/users/:id', function() {});
    $app->post('/users', function() {});
});

$app->group('/api/v2', function() use ($app) {
    $app->get('/status', function() {});
    $app->get('/health', function() {});
});

$app->group('/admin', function() use ($app) {
    $app->get('/dashboard', function() {});
    $app->get('/users', function() {});
}, [function() {}]);

// Simula alguns acessos
for ($i = 0; $i < 50; $i++) {
    $app->router->identifyByGroup('GET', '/api/v1/users');
    $app->router->identifyByGroup('GET', '/api/v2/status');
    $app->router->identifyByGroup('GET', '/admin/dashboard');
}

echo "Group Statistics:\n";
$stats = $app->router->getGroupStats();
foreach ($stats as $prefix => $data) {
    echo "\nGroup: $prefix\n";
    echo "  Routes: {$data['routes_count']}\n";
    echo "  Registration time: {$data['registration_time_ms']}ms\n";
    echo "  Access count: {$data['access_count']}\n";
    echo "  Avg access time: {$data['avg_access_time_ms']}ms\n";
    echo "  Has middlewares: " . ($data['has_middlewares'] ? 'Yes' : 'No') . "\n";
    echo "  Cache hit ratio: " . round($data['cache_hit_ratio'] * 100, 1) . "%\n";
}
EOF

echo "   Arquivo: temp_group_stats.php"
php "$PROJECT_ROOT/temp_group_stats.php"
echo ""

# Limpeza
echo "🧹 Limpando arquivos temporários..."
rm -f "$PROJECT_ROOT/temp_group_registration_benchmark.php"
rm -f "$PROJECT_ROOT/temp_group_identification_benchmark.php"
rm -f "$PROJECT_ROOT/temp_comparative_benchmark.php"
rm -f "$PROJECT_ROOT/temp_group_stats.php"

echo "✅ Benchmark de grupos concluído!"
echo ""
echo "📋 Resumo das funcionalidades implementadas:"
echo "   • Cache de rotas por grupo (O(1) access)"
echo "   • Indexação por método HTTP"
echo "   • Exact match cache"
echo "   • Prefixos ordenados por especificidade"
echo "   • Middlewares pré-compilados"
echo "   • Warmup de caches"
echo "   • Estatísticas de performance em tempo real"
echo ""
echo "🚀 Para testar na prática:"
echo "   cd examples && php example_optimized_groups.php"
