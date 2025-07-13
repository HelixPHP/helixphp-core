<?php

/**
 * 🌐 PivotPHP - CORS Middleware
 * 
 * Demonstra implementação completa de CORS (Cross-Origin Resource Sharing)
 * Configurações flexíveis, preflight requests e políticas de segurança
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/03-middleware/cors-middleware.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl -H "Origin: https://example.com" http://localhost:8000/api/public
 * curl -X OPTIONS -H "Origin: https://trusted.com" -H "Access-Control-Request-Method: POST" http://localhost:8000/api/secure
 * curl -X POST -H "Origin: https://allowed.com" -H "Content-Type: application/json" http://localhost:8000/api/data
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📋 Página inicial
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - CORS Middleware Examples',
        'description' => 'Demonstrações de Cross-Origin Resource Sharing',
        'cors_concepts' => [
            'Simple Requests' => 'GET, POST, HEAD com headers básicos',
            'Preflight Requests' => 'OPTIONS request antes de requests complexos',
            'Allowed Origins' => 'Controle de quais domínios podem acessar',
            'Allowed Methods' => 'Métodos HTTP permitidos (GET, POST, etc)',
            'Allowed Headers' => 'Headers customizados permitidos',
            'Credentials' => 'Suporte a cookies e autenticação',
            'Max Age' => 'Cache do preflight no browser'
        ],
        'security_levels' => [
            'open' => 'CORS liberado para todos (*)',
            'restricted' => 'Lista específica de domínios permitidos',
            'secure' => 'Políticas rígidas para APIs sensíveis',
            'dynamic' => 'Configuração baseada em contexto'
        ],
        'test_origins' => [
            'https://trusted.com' => 'Sempre permitido',
            'https://example.com' => 'Permitido para rotas públicas',
            'https://blocked.com' => 'Sempre bloqueado',
            'http://localhost:3000' => 'Permitido para desenvolvimento'
        ]
    ]);
});

// ===============================================
// CORS MIDDLEWARE CONFIGURATIONS
// ===============================================

// 🌍 CORS Aberto (para desenvolvimento)
$openCors = function ($req, $res, $next) {
    $origin = $req->header('Origin');
    
    // Permitir qualquer origem
    $res->header('Access-Control-Allow-Origin', $origin ?: '*');
    $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
    $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-API-Key');
    $res->header('Access-Control-Allow-Credentials', 'true');
    $res->header('Access-Control-Max-Age', '86400'); // 24 horas
    
    // Expor headers personalizados
    $res->header('Access-Control-Expose-Headers', 'X-Total-Count, X-Page, X-Per-Page');
    
    // Responder preflight
    if ($req->method() === 'OPTIONS') {
        return $res->status(204)->send('');
    }
    
    return $next($req, $res);
};

// 🔒 CORS Restritivo (para produção)
$restrictiveCors = function ($allowedOrigins = [], $allowedMethods = ['GET']) {
    return function ($req, $res, $next) use ($allowedOrigins, $allowedMethods) {
        $origin = $req->header('Origin');
        $method = $req->method();
        
        // Verificar se origem é permitida
        $isAllowedOrigin = false;
        if ($origin) {
            foreach ($allowedOrigins as $allowedOrigin) {
                if ($allowedOrigin === '*' || $origin === $allowedOrigin) {
                    $isAllowedOrigin = true;
                    break;
                }
                
                // Suporte a wildcards
                if (str_contains($allowedOrigin, '*')) {
                    $pattern = str_replace('*', '.*', $allowedOrigin);
                    if (preg_match('/^' . $pattern . '$/', $origin)) {
                        $isAllowedOrigin = true;
                        break;
                    }
                }
            }
        }
        
        if (!$isAllowedOrigin && $origin) {
            return $res->status(403)->json([
                'error' => 'CORS: Origem não permitida',
                'origin' => $origin,
                'allowed_origins' => $allowedOrigins,
                'middleware' => 'restrictiveCors'
            ]);
        }
        
        // Verificar método para preflight
        if ($method === 'OPTIONS') {
            $requestedMethod = $req->header('Access-Control-Request-Method');
            
            if ($requestedMethod && !in_array($requestedMethod, $allowedMethods)) {
                return $res->status(405)->json([
                    'error' => 'CORS: Método não permitido',
                    'requested_method' => $requestedMethod,
                    'allowed_methods' => $allowedMethods,
                    'middleware' => 'restrictiveCors'
                ]);
            }
        }
        
        // Definir headers CORS
        if ($isAllowedOrigin) {
            $res->header('Access-Control-Allow-Origin', $origin);
        }
        
        $res->header('Access-Control-Allow-Methods', implode(', ', $allowedMethods));
        $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $res->header('Access-Control-Max-Age', '3600');
        
        // Responder preflight
        if ($method === 'OPTIONS') {
            return $res->status(204)->send('');
        }
        
        return $next($req, $res);
    };
};

// 🎯 CORS Dinâmico (baseado em contexto)
$dynamicCors = function ($req, $res, $next) {
    $origin = $req->header('Origin');
    $userAgent = $req->header('User-Agent');
    $path = $req->uri();
    
    // Configuração baseada no contexto
    $config = [
        'allowed_origin' => null,
        'allowed_methods' => ['GET'],
        'allowed_headers' => ['Content-Type'],
        'allow_credentials' => false,
        'max_age' => 3600
    ];
    
    // Regras baseadas no path
    if (str_starts_with($path, '/api/public')) {
        $config['allowed_origin'] = '*';
        $config['allowed_methods'] = ['GET', 'POST'];
    } elseif (str_starts_with($path, '/api/admin')) {
        $config['allowed_origin'] = 'https://admin.trusted.com';
        $config['allowed_methods'] = ['GET', 'POST', 'PUT', 'DELETE'];
        $config['allow_credentials'] = true;
    } elseif (str_starts_with($path, '/api/')) {
        // API geral - apenas origens confiáveis
        $trustedOrigins = [
            'https://trusted.com',
            'https://app.trusted.com',
            'http://localhost:3000' // Para desenvolvimento
        ];
        
        if (in_array($origin, $trustedOrigins)) {
            $config['allowed_origin'] = $origin;
            $config['allowed_methods'] = ['GET', 'POST', 'PUT', 'DELETE'];
            $config['allow_credentials'] = true;
        }
    }
    
    // Regras especiais para desenvolvimento
    if (str_contains($userAgent, 'Development') || str_contains($origin, 'localhost')) {
        $config['allowed_origin'] = $origin;
        $config['allowed_methods'] = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'];
        $config['allowed_headers'] = ['Content-Type', 'Authorization', 'X-Requested-With', 'X-Debug'];
    }
    
    // Aplicar configuração
    if ($config['allowed_origin'] === '*' || $config['allowed_origin'] === $origin) {
        $res->header('Access-Control-Allow-Origin', $config['allowed_origin']);
        $res->header('Access-Control-Allow-Methods', implode(', ', $config['allowed_methods']));
        $res->header('Access-Control-Allow-Headers', implode(', ', $config['allowed_headers']));
        $res->header('Access-Control-Max-Age', (string)$config['max_age']);
        
        if ($config['allow_credentials']) {
            $res->header('Access-Control-Allow-Credentials', 'true');
        }
        
        // Headers expostos para clientes
        $res->header('Access-Control-Expose-Headers', 'X-Request-ID, X-Rate-Limit-Remaining');
    } else if ($origin) {
        return $res->status(403)->json([
            'error' => 'CORS: Origem não permitida para este endpoint',
            'origin' => $origin,
            'path' => $path,
            'middleware' => 'dynamicCors'
        ]);
    }
    
    // Adicionar informações de debug
    $req->corsConfig = $config;
    
    // Responder preflight
    if ($req->method() === 'OPTIONS') {
        return $res->status(204)->send('');
    }
    
    return $next($req, $res);
};

// 🛡️ CORS com Validação de Headers
$corsWithHeaderValidation = function ($req, $res, $next) {
    $origin = $req->header('Origin');
    $requestedHeaders = $req->header('Access-Control-Request-Headers');
    
    // Headers permitidos
    $allowedHeaders = [
        'content-type',
        'authorization',
        'x-requested-with',
        'x-api-key',
        'x-client-version'
    ];
    
    // Headers bloqueados (potencialmente perigosos)
    $blockedHeaders = [
        'x-admin-token',
        'x-internal-api',
        'x-debug-mode'
    ];
    
    // Validar headers solicitados
    if ($requestedHeaders) {
        $requestedHeadersList = array_map('trim', array_map('strtolower', explode(',', $requestedHeaders)));
        
        foreach ($requestedHeadersList as $header) {
            if (in_array($header, $blockedHeaders)) {
                return $res->status(403)->json([
                    'error' => 'CORS: Header não permitido',
                    'blocked_header' => $header,
                    'blocked_headers' => $blockedHeaders,
                    'middleware' => 'corsWithHeaderValidation'
                ]);
            }
            
            if (!in_array($header, $allowedHeaders)) {
                return $res->status(403)->json([
                    'error' => 'CORS: Header desconhecido',
                    'unknown_header' => $header,
                    'allowed_headers' => $allowedHeaders,
                    'middleware' => 'corsWithHeaderValidation'
                ]);
            }
        }
    }
    
    // Configurar CORS
    $res->header('Access-Control-Allow-Origin', $origin ?: '*');
    $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $res->header('Access-Control-Allow-Headers', implode(', ', $allowedHeaders));
    $res->header('Access-Control-Max-Age', '7200');
    
    // Responder preflight
    if ($req->method() === 'OPTIONS') {
        return $res->status(204)->send('');
    }
    
    return $next($req, $res);
};

// ===============================================
// ROTAS COM DIFERENTES POLÍTICAS CORS
// ===============================================

// API Pública - CORS aberto
$app->get('/api/public/data', $openCors, function ($req, $res) {
    return $res->json([
        'message' => 'Dados públicos da API',
        'data' => [
            'public_info' => 'Esta informação é pública',
            'timestamp' => date('c')
        ],
        'cors_policy' => 'open',
        'origin' => $req->header('Origin'),
        'accessible_from' => 'Qualquer origem'
    ]);
});

// API Restrita - Apenas origens específicas
$app->get('/api/secure/data', 
    $restrictiveCors([
        'https://trusted.com',
        'https://app.trusted.com',
        'http://localhost:3000'
    ], ['GET', 'POST']),
    function ($req, $res) {
        return $res->json([
            'message' => 'Dados seguros da API',
            'sensitive_data' => [
                'secret_info' => 'Informação sensível',
                'access_level' => 'restricted'
            ],
            'cors_policy' => 'restrictive',
            'origin' => $req->header('Origin')
        ]);
    }
);

// API com CORS dinâmico
$app->get('/api/dynamic/info', $dynamicCors, function ($req, $res) {
    return $res->json([
        'message' => 'API com CORS dinâmico',
        'info' => 'Configuração baseada no contexto da requisição',
        'cors_config' => $req->corsConfig ?? null,
        'origin' => $req->header('Origin'),
        'path' => $req->uri()
    ]);
});

$app->post('/api/dynamic/submit', $dynamicCors, function ($req, $res) {
    $body = $req->getBodyAsStdClass();
    
    return $res->json([
        'message' => 'Dados recebidos com CORS dinâmico',
        'received_data' => $body,
        'cors_config' => $req->corsConfig ?? null,
        'origin' => $req->header('Origin')
    ]);
});

// API com validação rigorosa de headers
$app->post('/api/validated/upload', $corsWithHeaderValidation, function ($req, $res) {
    return $res->json([
        'message' => 'Upload processado com validação de headers',
        'headers_received' => $req->headers(),
        'cors_policy' => 'header_validated',
        'security_level' => 'high'
    ]);
});

// Demonstração de diferentes métodos HTTP
$app->get('/api/methods/test', $openCors, function ($req, $res) {
    return $res->json(['method' => 'GET', 'message' => 'GET request']);
});

$app->post('/api/methods/test', $openCors, function ($req, $res) {
    return $res->json(['method' => 'POST', 'message' => 'POST request', 'body' => $req->getBodyAsStdClass()]);
});

$app->put('/api/methods/test', $openCors, function ($req, $res) {
    return $res->json(['method' => 'PUT', 'message' => 'PUT request', 'body' => $req->getBodyAsStdClass()]);
});

$app->delete('/api/methods/test', $openCors, function ($req, $res) {
    return $res->json(['method' => 'DELETE', 'message' => 'DELETE request']);
});

// Rota para testar credentials
$app->get('/api/credentials/test', function ($req, $res, $next) {
    $origin = $req->header('Origin');
    
    // Apenas permitir credentials para origens confiáveis
    $trustedForCredentials = [
        'https://app.trusted.com',
        'http://localhost:3000'
    ];
    
    if (in_array($origin, $trustedForCredentials)) {
        $res->header('Access-Control-Allow-Origin', $origin);
        $res->header('Access-Control-Allow-Credentials', 'true');
    } else {
        $res->header('Access-Control-Allow-Origin', $origin ?: '*');
        $res->header('Access-Control-Allow-Credentials', 'false');
    }
    
    $res->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
    $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    if ($req->method() === 'OPTIONS') {
        return $res->status(204)->send('');
    }
    
    return $next($req, $res);
}, function ($req, $res) {
    $cookies = $req->cookies();
    
    return $res->json([
        'message' => 'Teste de credentials',
        'cookies_received' => $cookies,
        'credentials_support' => !empty($cookies) ? 'enabled' : 'disabled',
        'origin' => $req->header('Origin')
    ]);
});

// Rota de status CORS
$app->get('/cors/status', $dynamicCors, function ($req, $res) {
    $origin = $req->header('Origin');
    
    return $res->json([
        'cors_status' => 'operational',
        'current_origin' => $origin,
        'cors_headers_present' => [
            'Access-Control-Allow-Origin' => $res->getHeader('Access-Control-Allow-Origin'),
            'Access-Control-Allow-Methods' => $res->getHeader('Access-Control-Allow-Methods'),
            'Access-Control-Allow-Headers' => $res->getHeader('Access-Control-Allow-Headers'),
            'Access-Control-Allow-Credentials' => $res->getHeader('Access-Control-Allow-Credentials')
        ],
        'recommendations' => [
            'development' => 'Use origem http://localhost:3000',
            'production' => 'Use https://trusted.com',
            'testing' => 'Inclua header Origin nas requisições'
        ]
    ]);
});

// Rota para demonstrar erro CORS
$app->get('/cors/error-demo', function ($req, $res) {
    $origin = $req->header('Origin');
    
    // Simular política CORS muito restritiva
    if ($origin !== 'https://only-this-domain.com') {
        // Não definir headers CORS = erro CORS no browser
        return $res->status(200)->json([
            'error' => 'Esta resposta causará erro CORS no browser',
            'reason' => 'Nenhum header Access-Control-Allow-Origin definido',
            'origin' => $origin,
            'note' => 'Browser bloqueará esta resposta para requisições cross-origin'
        ]);
    }
    
    $res->header('Access-Control-Allow-Origin', $origin);
    return $res->json(['message' => 'CORS OK']);
});

$app->run();