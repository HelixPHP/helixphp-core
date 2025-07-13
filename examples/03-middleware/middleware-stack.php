<?php

/**
 * 🔧 PivotPHP - Stack de Middleware
 * 
 * Demonstra como compor e organizar stacks complexos de middleware
 * Ordem de execução, encadeamento e passagem de dados entre middleware
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/03-middleware/middleware-stack.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl -X POST http://localhost:8000/api/process -H "Content-Type: application/json" -d '{"data":"test"}'
 * curl -H "X-Debug: true" http://localhost:8000/debug
 * curl http://localhost:8000/performance-test
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📋 Página inicial
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Middleware Stack Examples',
        'description' => 'Demonstração de stacks complexos de middleware',
        'concepts' => [
            'Execution Order' => 'Middleware executam na ordem definida',
            'Request Flow' => 'Request passa por todos antes do handler',
            'Response Flow' => 'Response volta através dos middleware em ordem reversa',
            'Data Passing' => 'Middleware podem adicionar dados ao request/response',
            'Early Exit' => 'Middleware podem interromper a cadeia retornando resposta',
            'Error Handling' => 'Erros podem ser capturados e tratados em qualquer ponto'
        ],
        'stack_examples' => [
            'Basic Stack' => ['Logger', 'Timer', 'Handler'],
            'Security Stack' => ['RateLimit', 'Auth', 'CSRF', 'Handler'],
            'API Stack' => ['CORS', 'Auth', 'Validation', 'Transform', 'Handler'],
            'Performance Stack' => ['Cache', 'Compression', 'Metrics', 'Handler']
        ]
    ]);
});

// ===============================================
// MIDDLEWARE DEFINITIONS
// ===============================================

// 1️⃣ Logger Middleware
$logger = function ($req, $res, $next) {
    $id = uniqid('req_');
    $start = microtime(true);
    
    error_log("🔍 [{$id}] START {$req->method()} {$req->uri()}");
    
    // Adicionar ID único ao request
    $req->requestId = $id;
    $req->startTime = $start;
    
    // Continuar para próximo middleware
    $response = $next($req, $res);
    
    $duration = round((microtime(true) - $start) * 1000, 2);
    error_log("✅ [{$id}] END {$duration}ms");
    
    return $response;
};

// 2️⃣ Performance Timer
$timer = function ($req, $res, $next) {
    $stepStart = microtime(true);
    
    // Inicializar array de timing se não existir
    if (!isset($req->timing)) {
        $req->timing = [];
    }
    
    $req->timing['middleware_start'] = $stepStart;
    
    $response = $next($req, $res);
    
    $stepEnd = microtime(true);
    $req->timing['middleware_end'] = $stepEnd;
    $req->timing['middleware_duration'] = round(($stepEnd - $stepStart) * 1000, 2);
    
    // Adicionar timing ao response header
    $res->header('X-Timing-Middleware', $req->timing['middleware_duration'] . 'ms');
    
    return $response;
};

// 3️⃣ Request Validator
$validator = function ($req, $res, $next) {
    $validationStart = microtime(true);
    
    // Validações básicas
    $userAgent = $req->header('User-Agent');
    if (!$userAgent) {
        return $res->status(400)->json([
            'error' => 'User-Agent header é obrigatório',
            'middleware' => 'validator',
            'request_id' => $req->requestId ?? 'unknown'
        ]);
    }
    
    // Detectar bots maliciosos (simulado)
    $maliciousBots = ['BadBot', 'Scraper', 'Malicious'];
    foreach ($maliciousBots as $bot) {
        if (stripos($userAgent, $bot) !== false) {
            return $res->status(403)->json([
                'error' => 'Bot não autorizado',
                'user_agent' => $userAgent,
                'middleware' => 'validator'
            ]);
        }
    }
    
    $validationEnd = microtime(true);
    $req->timing['validation'] = round(($validationEnd - $validationStart) * 1000, 2);
    
    return $next($req, $res);
};

// 4️⃣ Data Enricher
$enricher = function ($req, $res, $next) {
    $enrichStart = microtime(true);
    
    // Enriquecer request com dados adicionais
    $req->enrichedData = [
        'ip' => $req->ip(),
        'timestamp' => date('c'),
        'user_agent' => $req->header('User-Agent'),
        'method' => $req->method(),
        'uri' => $req->uri(),
        'query_params' => $req->query(),
        'headers_count' => count($req->headers())
    ];
    
    $enrichEnd = microtime(true);
    $req->timing['enrichment'] = round(($enrichEnd - $enrichStart) * 1000, 2);
    
    return $next($req, $res);
};

// 5️⃣ Security Headers
$security = function ($req, $res, $next) {
    $securityStart = microtime(true);
    
    // Aplicar headers de segurança
    $res->header('X-Content-Type-Options', 'nosniff');
    $res->header('X-Frame-Options', 'DENY');
    $res->header('X-XSS-Protection', '1; mode=block');
    $res->header('Referrer-Policy', 'strict-origin-when-cross-origin');
    
    $response = $next($req, $res);
    
    $securityEnd = microtime(true);
    $req->timing['security'] = round(($securityEnd - $securityStart) * 1000, 2);
    
    return $response;
};

// 6️⃣ Response Modifier
$responseModifier = function ($req, $res, $next) {
    $response = $next($req, $res);
    
    // Adicionar metadados à resposta
    $res->header('X-Request-ID', $req->requestId ?? 'unknown');
    $res->header('X-Processing-Time', ($req->timing['total'] ?? 0) . 'ms');
    $res->header('X-Powered-By', 'PivotPHP-Core');
    
    return $response;
};

// 7️⃣ Debug Middleware (condicional)
$debugger = function ($req, $res, $next) {
    $isDebug = $req->header('X-Debug') === 'true';
    
    if ($isDebug) {
        $debugStart = microtime(true);
        
        // Coletar informações de debug
        $req->debugInfo = [
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'included_files' => count(get_included_files()),
            'php_version' => PHP_VERSION
        ];
        
        $response = $next($req, $res);
        
        $debugEnd = microtime(true);
        $req->timing['debug'] = round(($debugEnd - $debugStart) * 1000, 2);
        
        // Adicionar informações de debug ao response
        $res->header('X-Debug-Memory', $req->debugInfo['memory_usage']);
        $res->header('X-Debug-Peak-Memory', $req->debugInfo['peak_memory']);
        
        return $response;
    }
    
    return $next($req, $res);
};

// 8️⃣ Cache Middleware (simulado)
$cache = function ($req, $res, $next) {
    $cacheKey = $req->method() . ':' . $req->uri();
    
    // Simular cache hit/miss
    $isCacheHit = rand(0, 100) < 30; // 30% chance de cache hit
    
    if ($isCacheHit && $req->method() === 'GET') {
        return $res->json([
            'cached_data' => 'Este é um resultado do cache',
            'cache_key' => $cacheKey,
            'cached_at' => date('c', time() - rand(60, 3600)),
            'middleware_info' => [
                'cache_hit' => true,
                'execution_skipped' => true
            ]
        ]);
    }
    
    $cacheStart = microtime(true);
    $response = $next($req, $res);
    $cacheEnd = microtime(true);
    
    $req->timing['cache_processing'] = round(($cacheEnd - $cacheStart) * 1000, 2);
    
    // Simular armazenamento em cache
    $res->header('X-Cache-Status', 'MISS');
    $res->header('X-Cache-Key', $cacheKey);
    
    return $response;
};

// ===============================================
// STACKS PRÉ-DEFINIDOS
// ===============================================

// Stack básico para todas as rotas
$basicStack = [$logger, $timer, $security, $responseModifier];

// Stack de desenvolvimento
$devStack = array_merge($basicStack, [$debugger]);

// Stack de API
$apiStack = array_merge($basicStack, [$validator, $enricher]);

// Stack de performance
$performanceStack = array_merge($basicStack, [$cache]);

// ===============================================
// APLICAR MIDDLEWARE GLOBALMENTE
// ===============================================

// Aplicar stack básico a todas as rotas
foreach ($basicStack as $middleware) {
    $app->use($middleware);
}

// ===============================================
// ROTAS COM STACKS ESPECÍFICOS
// ===============================================

// Rota com stack de desenvolvimento
$app->get('/debug', $debugger, function ($req, $res) {
    $totalTime = microtime(true) - $req->startTime;
    $req->timing['total'] = round($totalTime * 1000, 2);
    
    return $res->json([
        'message' => 'Debug information',
        'request_id' => $req->requestId,
        'timing' => $req->timing,
        'debug_info' => $req->debugInfo ?? null,
        'enriched_data' => $req->enrichedData ?? null,
        'middleware_stack' => [
            'global' => ['logger', 'timer', 'security', 'responseModifier'],
            'route_specific' => ['debugger']
        ]
    ]);
});

// Rota com stack de API
$app->post('/api/process', $validator, $enricher, function ($req, $res) {
    $body = $req->getBodyAsStdClass();
    $totalTime = microtime(true) - $req->startTime;
    $req->timing['total'] = round($totalTime * 1000, 2);
    
    return $res->json([
        'message' => 'Data processed successfully',
        'request_id' => $req->requestId,
        'input_data' => $body,
        'enriched_data' => $req->enrichedData,
        'timing_breakdown' => $req->timing,
        'middleware_stack' => [
            'global' => ['logger', 'timer', 'security', 'responseModifier'],
            'route_specific' => ['validator', 'enricher']
        ]
    ]);
});

// Rota com stack de performance
$app->get('/performance-test', $cache, function ($req, $res) {
    // Simular processamento pesado
    usleep(rand(10000, 50000)); // 10-50ms
    
    $totalTime = microtime(true) - $req->startTime;
    $req->timing['total'] = round($totalTime * 1000, 2);
    
    return $res->json([
        'message' => 'Performance test completed',
        'request_id' => $req->requestId,
        'timing' => $req->timing,
        'performance_data' => [
            'processing_time' => rand(10, 50) . 'ms',
            'memory_usage' => memory_get_usage(true),
            'operations_count' => rand(1000, 5000)
        ],
        'middleware_stack' => [
            'global' => ['logger', 'timer', 'security', 'responseModifier'],
            'route_specific' => ['cache']
        ]
    ]);
});

// Demonstração de middleware stack completo
$app->post('/full-stack', 
    $validator,
    $enricher,
    $debugger,
    $cache,
    function ($req, $res) {
        $body = $req->getBodyAsStdClass();
        $totalTime = microtime(true) - $req->startTime;
        $req->timing['total'] = round($totalTime * 1000, 2);
        
        return $res->json([
            'message' => 'Full middleware stack processed',
            'request_id' => $req->requestId,
            'input_data' => $body,
            'enriched_data' => $req->enrichedData,
            'debug_info' => $req->debugInfo ?? null,
            'timing_detailed' => $req->timing,
            'middleware_execution_order' => [
                '1. Global: logger (start)',
                '2. Global: timer (start)', 
                '3. Global: security',
                '4. Route: validator',
                '5. Route: enricher',
                '6. Route: debugger',
                '7. Route: cache',
                '8. Route: handler',
                '9. Route: cache (end)',
                '10. Route: debugger (end)',
                '11. Route: enricher (end)',
                '12. Route: validator (end)',
                '13. Global: responseModifier',
                '14. Global: timer (end)',
                '15. Global: logger (end)'
            ],
            'metadata' => [
                'total_middleware' => 7,
                'execution_time' => $req->timing['total'] . 'ms',
                'framework' => 'PivotPHP Core'
            ]
        ]);
    }
);

// Demonstração de middleware que interrompe a cadeia
$app->get('/early-exit', function ($req, $res, $next) {
    // Este middleware pode decidir não continuar
    $shouldContinue = $req->get('continue', 'true') === 'true';
    
    if (!$shouldContinue) {
        return $res->status(200)->json([
            'message' => 'Execução interrompida pelo middleware',
            'middleware' => 'early-exit-detector',
            'note' => 'O handler principal nunca foi executado',
            'request_id' => $req->requestId
        ]);
    }
    
    return $next($req, $res);
}, function ($req, $res) {
    return $res->json([
        'message' => 'Handler principal executado',
        'note' => 'Todos os middleware anteriores foram executados com sucesso',
        'request_id' => $req->requestId
    ]);
});

// Rota de status do sistema com informações dos middleware
$app->get('/system/status', function ($req, $res) {
    $totalTime = microtime(true) - $req->startTime;
    $req->timing['total'] = round($totalTime * 1000, 2);
    
    return $res->json([
        'system_status' => 'operational',
        'middleware_health' => [
            'logger' => 'healthy',
            'timer' => 'healthy',
            'security' => 'healthy',
            'validator' => 'healthy',
            'enricher' => 'healthy',
            'cache' => 'healthy',
            'debugger' => 'conditional'
        ],
        'performance_metrics' => [
            'average_response_time' => '15ms',
            'middleware_overhead' => '3ms',
            'cache_hit_rate' => '30%',
            'error_rate' => '0.01%'
        ],
        'current_request' => [
            'id' => $req->requestId,
            'timing' => $req->timing,
            'enriched_data' => $req->enrichedData ?? null
        ]
    ]);
});

$app->run();