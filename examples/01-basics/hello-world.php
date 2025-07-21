<?php

/**
 * 🌍 PivotPHP v1.2.0 - Hello World
 * 
 * O exemplo mais simples possível do PivotPHP Core.
 * Demonstra a simplicidade Express.js para PHP com arquitetura v1.2.0.
 * 
 * ✨ Novidades v1.2.0:
 * • Arquitetura simplificada seguindo "Simplicidade sobre Otimização Prematura"
 * • PerformanceMode simplificado ao invés de HighPerformanceMode complexo
 * • Mantém array callables nativos e JSON optimization
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/01-basics/hello-world.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/hello/PivotPHP
 * curl http://localhost:8000/features
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Controller para demonstrar array callables v1.1.4+
class HelloController
{
    public function index($req, $res)
    {
        // JsonBufferPool otimiza automaticamente baseado no tamanho
        return $res->json([
            'message' => 'Hello, World! 🌍',
            'framework' => 'PivotPHP Core',
            'version' => Application::VERSION,
            'style' => 'Express.js for PHP',
            'features_v120' => [
                'simplified_architecture' => 'Simplicidade sobre Otimização Prematura ✅',
                'array_callables' => 'Native support maintained ✅',
                'json_optimization' => 'Intelligent threshold maintained ✅',
                'performance_mode' => 'Simplified PerformanceMode ✅'
            ]
        ]);
    }
    
    public function greeting($req, $res)
    {
        $name = $req->param('name');
        
        // Dados pequenos - JsonBufferPool usa json_encode() direto (sem overhead)
        return $res->json([
            'greeting' => "Hello, {$name}! 👋",
            'timestamp' => date('Y-m-d H:i:s'),
            'optimization' => 'Small data - direct json_encode()'
        ]);
    }
    
    public function features($req, $res)
    {
        // Dados maiores - JsonBufferPool usa pooling automático
        $features = array_fill(0, 20, [
            'id' => rand(1, 1000),
            'feature' => 'PivotPHP Core Feature',
            'description' => 'Advanced microframework capabilities with Express.js style API',
            'performance' => 'Optimized with intelligent JSON pooling',
            'compatibility' => 'PSR-7 hybrid implementation'
        ]);
        
        return $res->json([
            'framework' => 'PivotPHP Core v1.2.0',
            'optimization_note' => 'Large data - automatic pooling activated',
            'features' => $features,
            'pool_stats' => JsonBufferPool::getStatistics()
        ]);
    }
}

// Criar aplicação
$app = new Application();

// ✅ MANTIDO v1.2.0: Array callables nativos
$controller = new HelloController();

$app->get('/', [$controller, 'index']);
$app->get('/hello/:name', [$controller, 'greeting']);
$app->get('/features', [$controller, 'features']);

// Rota com closure (ainda suportada)
$app->get('/text', function ($req, $res) {
    return $res->send('Hello from PivotPHP v1.2.0! 🚀');
});

// Health check com demonstração de threshold
$app->get('/health', function ($req, $res) {
    $smallData = ['status' => 'healthy', 'timestamp' => time()];
    $usePooling = JsonBufferPool::shouldUsePooling($smallData);
    
    return $res->json([
        'status' => 'healthy',
        'version' => Application::VERSION,
        'optimization' => [
            'data_size' => 'small',
            'uses_pooling' => $usePooling,
            'strategy' => $usePooling ? 'buffer pool' : 'direct json_encode()'
        ],
        'memory' => [
            'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ]
    ]);
});

// Executar aplicação
$app->run();