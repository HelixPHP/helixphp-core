<?php
// Exemplo de uso de middlewares de segurança do Express PHP

require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middleware\Security\CorsMiddleware;
use Express\Middleware\Security\AuthMiddleware;
use Express\Middleware\Security\SecurityMiddleware;
use Express\Middleware\Security\XssMiddleware;
use Express\Middleware\Security\CsrfMiddleware;
use Express\Middleware\Core\RateLimitMiddleware;

// Criar aplicação
$app = new ApiExpress('http://localhost:8000');

// ========================================
// MIDDLEWARES DE SEGURANÇA
// ========================================

// 1. CORS - Controle de acesso entre origens
$corsOptions = [
    'origin' => ['http://localhost:3000', 'https://meuapp.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowedHeaders' => ['Content-Type', 'Authorization'],
    'credentials' => true
];
$app->use(new CorsMiddleware($corsOptions));

// 2. Security Headers - Headers de segurança padrão
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => true,
    'hsts' => true,
    'noSniff' => true,
    'frameOptions' => 'DENY',
    'xssProtection' => true
]));

// 3. Rate Limiting - Limitação de taxa de requisições
$app->use(new RateLimitMiddleware([
    'windowMs' => 60000, // 1 minuto
    'maxRequests' => 100, // 100 requisições por minuto
    'message' => 'Muitas requisições, tente novamente em 1 minuto'
]));

// 4. XSS Protection - Proteção contra XSS
$app->use(new XssMiddleware([
    'sanitizeInput' => true,
    'allowedTags' => '<p><strong><em>',
    'checkUrls' => true
]));

// ========================================
// ROTAS PÚBLICAS
// ========================================

// Rota pública de login
$app->post('/login', function($req, $res) {
    $data = $req->body;

    if (!isset($data['username']) || !isset($data['password'])) {
        return $res->status(400)->json([
            'error' => 'Username and password are required'
        ]);
    }

    // Validação simples (em produção, use hash de senha)
    if ($data['username'] === 'admin' && $data['password'] === 'password') {
        $token = \Express\Authentication\JWTHelper::encode([
            'user_id' => 1,
            'username' => 'admin',
            'role' => 'admin'
        ], 'sua_chave_secreta');

        return $res->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => 1,
                'username' => 'admin',
                'role' => 'admin'
            ]
        ]);
    }

    return $res->status(401)->json([
        'error' => 'Invalid credentials'
    ]);
});

// Rota para obter CSRF token
$app->get('/csrf-token', function($req, $res) {
    $token = CsrfMiddleware::getToken();
    return $res->json(['csrf_token' => $token]);
});

// ========================================
// ROTAS PROTEGIDAS
// ========================================

// Middleware de autenticação JWT para rotas protegidas
$jwtAuth = AuthMiddleware::jwt('sua_chave_secreta');

// Aplicar autenticação a todas as rotas /api/*
$app->use('/api', $jwtAuth);

// Rota protegida - perfil do usuário
$app->get('/api/profile', function($req, $res) {
    return $res->json([
        'message' => 'Profile accessed successfully',
        'user' => $req->user
    ]);
});

// Rota protegida com CSRF para operações sensíveis
$app->use('/api/sensitive', new CsrfMiddleware());

$app->post('/api/sensitive/data', function($req, $res) {
    return $res->json([
        'message' => 'Sensitive operation completed',
        'data' => $req->body
    ]);
});

// ========================================
// DEMONSTRAÇÕES DE FERRAMENTAS DE SEGURANÇA
// ========================================

// Demonstração de detecção de XSS
$app->post('/test/xss', function($req, $res) {
    $input = $req->body['content'] ?? '';

    $hasXss = XssMiddleware::containsXss($input);
    $sanitized = XssMiddleware::sanitize($input);

    return $res->json([
        'original' => $input,
        'has_xss' => $hasXss,
        'sanitized' => $sanitized
    ]);
});

// Demonstração de limpeza de URL
$app->post('/test/url', function($req, $res) {
    $url = $req->body['url'] ?? '';
    $cleanUrl = XssMiddleware::cleanUrl($url);

    return $res->json([
        'original' => $url,
        'cleaned' => $cleanUrl
    ]);
});

// ========================================
// ROTAS DE INFORMAÇÃO
// ========================================

$app->get('/', function($req, $res) {
    return $res->json([
        'message' => 'Express-PHP Security Example',
        'endpoints' => [
            'POST /login' => 'Authenticate and get JWT token',
            'GET /csrf-token' => 'Get CSRF token',
            'GET /api/profile' => 'Get user profile (requires JWT)',
            'POST /api/sensitive/data' => 'Sensitive operation (requires JWT + CSRF)',
            'POST /test/xss' => 'Test XSS detection and sanitization',
            'POST /test/url' => 'Test URL cleaning'
        ],
        'security_features' => [
            'CORS protection',
            'Security headers',
            'Rate limiting',
            'XSS protection',
            'CSRF protection',
            'JWT authentication'
        ]
    ]);
});

// Iniciar servidor
$app->listen(8000);
