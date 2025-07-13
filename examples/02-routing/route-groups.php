<?php

/**
 * 🎯 PivotPHP - Agrupamento de Rotas
 * 
 * Demonstra como organizar rotas em grupos lógicos com prefixos
 * e middleware compartilhados usando o sistema de roteamento do PivotPHP
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/02-routing/route-groups.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/api/v1/users
 * curl http://localhost:8000/admin/dashboard
 * curl -H "Authorization: Bearer token123" http://localhost:8000/api/v1/protected
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📋 Página inicial
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Route Groups Example',
        'description' => 'Demonstração de agrupamento de rotas com prefixos e middleware',
        'available_groups' => [
            'Public API' => [
                'prefix' => '/api/v1',
                'middleware' => ['cors', 'rate-limiting'],
                'routes' => [
                    'GET /api/v1/users' => 'Listar usuários públicos',
                    'GET /api/v1/posts' => 'Listar posts públicos',
                    'GET /api/v1/stats' => 'Estatísticas públicas'
                ]
            ],
            'Protected API' => [
                'prefix' => '/api/v1',
                'middleware' => ['cors', 'auth', 'rate-limiting'],
                'routes' => [
                    'GET /api/v1/protected' => 'Dados protegidos',
                    'POST /api/v1/users' => 'Criar usuário',
                    'PUT /api/v1/profile' => 'Atualizar perfil'
                ]
            ],
            'Admin Panel' => [
                'prefix' => '/admin',
                'middleware' => ['auth', 'admin-role'],
                'routes' => [
                    'GET /admin/dashboard' => 'Dashboard administrativo',
                    'GET /admin/users' => 'Gerenciar usuários',
                    'GET /admin/settings' => 'Configurações'
                ]
            ],
            'Web Routes' => [
                'prefix' => '/',
                'middleware' => ['web', 'csrf'],
                'routes' => [
                    'GET /home' => 'Página inicial',
                    'GET /about' => 'Sobre nós',
                    'GET /contact' => 'Contato'
                ]
            ]
        ]
    ]);
});

// 🛡️ Middleware de CORS para API
$corsMiddleware = function ($req, $res, $next) {
    $res->header('Access-Control-Allow-Origin', '*');
    $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    if ($req->method() === 'OPTIONS') {
        return $res->status(200)->send('');
    }
    
    return $next($req, $res);
};

// 🔐 Middleware de autenticação
$authMiddleware = function ($req, $res, $next) {
    $token = $req->header('Authorization');
    
    if (!$token || !str_starts_with($token, 'Bearer ')) {
        return $res->status(401)->json([
            'error' => 'Token de autenticação obrigatório',
            'required_header' => 'Authorization: Bearer <token>'
        ]);
    }
    
    // Simular validação de token
    $tokenValue = substr($token, 7); // Remove "Bearer "
    if ($tokenValue !== 'token123') {
        return $res->status(401)->json([
            'error' => 'Token inválido',
            'provided_token' => $tokenValue
        ]);
    }
    
    // Adicionar usuário ao request (simulado)
    $req->user = [
        'id' => 1,
        'name' => 'Admin User',
        'role' => 'admin'
    ];
    
    return $next($req, $res);
};

// 👑 Middleware de role admin
$adminRoleMiddleware = function ($req, $res, $next) {
    if (!isset($req->user) || $req->user['role'] !== 'admin') {
        return $res->status(403)->json([
            'error' => 'Acesso negado',
            'required_role' => 'admin',
            'user_role' => $req->user['role'] ?? 'none'
        ]);
    }
    
    return $next($req, $res);
};

// 🚦 Middleware de rate limiting (simulado)
$rateLimitMiddleware = function ($req, $res, $next) {
    $ip = $req->ip();
    
    // Simular verificação de rate limit
    $res->header('X-RateLimit-Limit', '100');
    $res->header('X-RateLimit-Remaining', '95');
    $res->header('X-RateLimit-Reset', time() + 3600);
    
    return $next($req, $res);
};

// 🌐 Middleware para web routes
$webMiddleware = function ($req, $res, $next) {
    $res->header('X-Frame-Options', 'DENY');
    $res->header('X-Content-Type-Options', 'nosniff');
    return $next($req, $res);
};

// ===============================================
// 📡 GRUPO: API Pública v1
// ===============================================

// Aplicar middleware para todas as rotas da API
$app->use('/api/v1/*', $corsMiddleware);
$app->use('/api/v1/*', $rateLimitMiddleware);

// Rotas públicas da API
$app->get('/api/v1/users', function ($req, $res) {
    return $res->json([
        'users' => [
            ['id' => 1, 'name' => 'João Silva', 'public' => true],
            ['id' => 2, 'name' => 'Maria Santos', 'public' => true]
        ],
        'group_info' => [
            'group' => 'Public API v1',
            'middleware' => ['cors', 'rate-limiting'],
            'access_level' => 'public'
        ]
    ]);
});

$app->get('/api/v1/posts', function ($req, $res) {
    return $res->json([
        'posts' => [
            ['id' => 1, 'title' => 'Post Público 1', 'public' => true],
            ['id' => 2, 'title' => 'Post Público 2', 'public' => true]
        ],
        'group_info' => [
            'group' => 'Public API v1',
            'middleware' => ['cors', 'rate-limiting']
        ]
    ]);
});

$app->get('/api/v1/stats', function ($req, $res) {
    return $res->json([
        'statistics' => [
            'total_users' => 1250,
            'total_posts' => 3420,
            'active_today' => 89
        ],
        'group_info' => [
            'group' => 'Public API v1',
            'description' => 'Estatísticas públicas do sistema'
        ]
    ]);
});

// ===============================================
// 🔐 GRUPO: API Protegida v1  
// ===============================================

// Middleware adicional para rotas protegidas
$app->use('/api/v1/protected*', $authMiddleware);
$app->use('/api/v1/users', $authMiddleware, ['POST']); // Apenas POST precisa auth
$app->use('/api/v1/profile*', $authMiddleware);

$app->get('/api/v1/protected', function ($req, $res) {
    return $res->json([
        'protected_data' => [
            'secret' => 'Dados super secretos',
            'user_specific' => 'Informações do usuário logado'
        ],
        'authenticated_user' => $req->user,
        'group_info' => [
            'group' => 'Protected API v1',
            'middleware' => ['cors', 'rate-limiting', 'auth'],
            'access_level' => 'authenticated'
        ]
    ]);
});

$app->post('/api/v1/users', function ($req, $res) {
    $body = $req->getBodyAsStdClass();
    
    return $res->status(201)->json([
        'message' => 'Usuário criado com sucesso',
        'created_by' => $req->user,
        'new_user' => [
            'id' => 999,
            'name' => $body->name ?? 'Novo Usuário'
        ],
        'group_info' => [
            'group' => 'Protected API v1',
            'action' => 'create_user'
        ]
    ]);
});

$app->put('/api/v1/profile', function ($req, $res) {
    return $res->json([
        'message' => 'Perfil atualizado',
        'user' => $req->user,
        'group_info' => [
            'group' => 'Protected API v1',
            'action' => 'update_profile'
        ]
    ]);
});

// ===============================================
// 👑 GRUPO: Admin Panel
// ===============================================

// Middleware para todo o painel admin
$app->use('/admin/*', $authMiddleware);
$app->use('/admin/*', $adminRoleMiddleware);

$app->get('/admin/dashboard', function ($req, $res) {
    return $res->json([
        'dashboard_data' => [
            'total_users' => 1250,
            'pending_approvals' => 15,
            'system_status' => 'operational',
            'daily_revenue' => 'R$ 15.420,50'
        ],
        'admin_user' => $req->user,
        'group_info' => [
            'group' => 'Admin Panel',
            'middleware' => ['auth', 'admin-role'],
            'access_level' => 'admin_only'
        ]
    ]);
});

$app->get('/admin/users', function ($req, $res) {
    return $res->json([
        'admin_users_list' => [
            ['id' => 1, 'name' => 'João Admin', 'role' => 'admin', 'status' => 'active'],
            ['id' => 2, 'name' => 'Maria Manager', 'role' => 'manager', 'status' => 'active'],
            ['id' => 3, 'name' => 'Pedro User', 'role' => 'user', 'status' => 'pending']
        ],
        'group_info' => [
            'group' => 'Admin Panel',
            'section' => 'User Management'
        ]
    ]);
});

$app->get('/admin/settings', function ($req, $res) {
    return $res->json([
        'system_settings' => [
            'maintenance_mode' => false,
            'registration_enabled' => true,
            'max_file_size' => '10MB',
            'session_timeout' => 3600
        ],
        'group_info' => [
            'group' => 'Admin Panel',
            'section' => 'System Settings'
        ]
    ]);
});

// ===============================================
// 🌐 GRUPO: Web Routes
// ===============================================

// Middleware para rotas web
$app->use(['/home', '/about', '/contact'], $webMiddleware);

$app->get('/home', function ($req, $res) {
    return $res->json([
        'page' => 'Home',
        'content' => 'Bem-vindo ao PivotPHP!',
        'group_info' => [
            'group' => 'Web Routes',
            'middleware' => ['web', 'csrf-simulation'],
            'type' => 'public_web'
        ]
    ]);
});

$app->get('/about', function ($req, $res) {
    return $res->json([
        'page' => 'About',
        'content' => 'PivotPHP é um microframework Express.js para PHP',
        'group_info' => [
            'group' => 'Web Routes',
            'type' => 'public_web'
        ]
    ]);
});

$app->get('/contact', function ($req, $res) {
    return $res->json([
        'page' => 'Contact',
        'content' => 'Entre em contato conosco!',
        'group_info' => [
            'group' => 'Web Routes',
            'type' => 'public_web'
        ]
    ]);
});

// ===============================================
// 📊 GRUPO: Analytics & Monitoring
// ===============================================

$app->use('/analytics/*', function ($req, $res, $next) {
    $res->header('X-Analytics-Version', '2.0');
    return $next($req, $res);
});

$app->get('/analytics/visits', function ($req, $res) {
    return $res->json([
        'visits_data' => [
            'today' => 1250,
            'this_week' => 8750,
            'this_month' => 35000
        ],
        'group_info' => [
            'group' => 'Analytics & Monitoring',
            'endpoint' => 'visits'
        ]
    ]);
});

$app->get('/analytics/performance', function ($req, $res) {
    return $res->json([
        'performance_metrics' => [
            'avg_response_time' => '125ms',
            'error_rate' => '0.02%',
            'uptime' => '99.95%'
        ],
        'group_info' => [
            'group' => 'Analytics & Monitoring',
            'endpoint' => 'performance'
        ]
    ]);
});

// 📝 Demonstração de múltiplos middleware em uma rota
$app->get('/demo/multiple-middleware', 
    $corsMiddleware,
    $rateLimitMiddleware,
    function ($req, $res) {
        return $res->json([
            'message' => 'Esta rota usa múltiplos middleware',
            'middleware_chain' => ['cors', 'rate-limiting', 'route-handler'],
            'headers_set' => [
                'Access-Control-Allow-Origin' => '*',
                'X-RateLimit-Limit' => '100'
            ]
        ]);
    }
);

$app->run();