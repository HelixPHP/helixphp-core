<?php
// Exemplo de uso do middleware de autenticação automática do Express PHP

require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middleware\Security\AuthMiddleware;
use Express\Authentication\JWTHelper;

// Cria a aplicação
$app = new ApiExpress();

// ========================================
// CONFIGURAÇÕES DE EXEMPLO
// ========================================

// Chave secreta para JWT (em produção, use variável de ambiente)
$jwtSecret = 'sua_chave_secreta_super_segura_aqui';

// Função para validar usuários Basic Auth
function validateBasicAuth($username, $password) {
    // Em produção, consulte banco de dados
    $users = [
        'admin' => 'password123',
        'user' => 'user123',
        'test' => 'test123'
    ];

    if (isset($users[$username]) && $users[$username] === $password) {
        return [
            'id' => uniqid(),
            'username' => $username,
            'role' => $username === 'admin' ? 'admin' : 'user',
            'authenticated_at' => date('Y-m-d H:i:s')
        ];
    }

    return false;
}

// Função para validar Bearer tokens
function validateBearerToken($token) {
    // Em produção, consulte banco de dados ou cache
    $validTokens = [
        'token123' => ['id' => 1, 'username' => 'bearer_user', 'role' => 'user'],
        'admin_token' => ['id' => 2, 'username' => 'bearer_admin', 'role' => 'admin'],
        'api_token_xyz' => ['id' => 3, 'username' => 'api_service', 'role' => 'service']
    ];

    return $validTokens[$token] ?? false;
}

// Função para validar API Keys
function validateApiKey($apiKey) {
    // Em produção, consulte banco de dados
    $validKeys = [
        'key123456' => ['id' => 1, 'name' => 'App Mobile', 'permissions' => ['read', 'write']],
        'service_key' => ['id' => 2, 'name' => 'Service Integration', 'permissions' => ['read']],
        'admin_key' => ['id' => 3, 'name' => 'Admin Panel', 'permissions' => ['read', 'write', 'delete']]
    ];

    return $validKeys[$apiKey] ?? false;
}

// Função para autenticação customizada
function customAuth($request) {
    // Exemplo: autenticação por cookie de sessão
    $sessionId = $_COOKIE['session_id'] ?? null;

    if ($sessionId) {
        // Em produção, valide a sessão no banco/cache
        if ($sessionId === 'valid_session_123') {
            return [
                'id' => 1,
                'username' => 'session_user',
                'role' => 'user',
                'session_id' => $sessionId
            ];
        }
    }

    return false;
}

// ========================================
// EXEMPLOS DE CONFIGURAÇÃO DO MIDDLEWARE
// ========================================

// 1. Autenticação JWT apenas
echo "=== EXEMPLO 1: JWT APENAS ===\n";
$jwtAuth = AuthMiddleware::jwt($jwtSecret, [
    'excludePaths' => ['/public', '/login']
]);

// 2. Basic Auth apenas
echo "=== EXEMPLO 2: BASIC AUTH APENAS ===\n";
$basicAuth = AuthMiddleware::basic('validateBasicAuth', [
    'excludePaths' => ['/public']
]);

// 3. Múltiplos métodos de autenticação
echo "=== EXEMPLO 3: MÚLTIPLOS MÉTODOS ===\n";
$multiAuth = new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'bearer', 'apikey'],
    'jwtSecret' => $jwtSecret,
    'basicAuthCallback' => 'validateBasicAuth',
    'bearerTokenCallback' => 'validateBearerToken',
    'apiKeyCallback' => 'validateApiKey',
    'allowMultiple' => true,
    'excludePaths' => ['/public', '/login', '/register']
]);

// 4. Configuração flexível (não requer autenticação, mas processa se presente)
echo "=== EXEMPLO 4: FLEXÍVEL ===\n";
$flexibleAuth = AuthMiddleware::flexible([
    'authMethods' => ['jwt', 'apikey'],
    'jwtSecret' => $jwtSecret,
    'apiKeyCallback' => 'validateApiKey'
]);

// 5. Configuração customizada
echo "=== EXEMPLO 5: AUTENTICAÇÃO CUSTOMIZADA ===\n";
$customAuthMiddleware = AuthMiddleware::custom('customAuth');

// ========================================
// APLICAÇÃO DO MIDDLEWARE
// ========================================

// Usa o middleware de múltiplos métodos para a aplicação
$app->use($multiAuth);

// ========================================
// ROTAS DE EXEMPLO
// ========================================

// Rota pública (sem autenticação)
$app->get('/public/status', function($req, $res) {
    $res->json([
        'message' => 'Esta é uma rota pública',
        'timestamp' => time(),
        'authenticated' => isset($req->auth) ? $req->auth['authenticated'] : false
    ]);
});

// Rota para login/geração de token JWT
$app->post('/login', function($req, $res) {
    $username = $req->body['username'] ?? '';
    $password = $req->body['password'] ?? '';

    // Valida credenciais (exemplo simples)
    $user = validateBasicAuth($username, $password);

    if ($user) {
        // Gera token JWT
        $token = JWTHelper::encode([
            'user_id' => $user['id'],
            'username' => $user['username'],
            'role' => $user['role']
        ], $jwtSecret, [
            'expiresIn' => 3600 // 1 hora
        ]);

        // Gera refresh token
        $refreshToken = JWTHelper::createRefreshToken($user['id'], $jwtSecret);

        $res->json([
            'message' => 'Login successful',
            'access_token' => $token,
            'refresh_token' => $refreshToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'user' => $user
        ]);
    } else {
        $res->status(401)->json([
            'error' => 'Invalid credentials'
        ]);
    }
});

// Rota para refresh token
$app->post('/refresh', function($req, $res) use ($jwtSecret) {
    $refreshToken = $req->body['refresh_token'] ?? '';

    $payload = JWTHelper::validateRefreshToken($refreshToken, $jwtSecret);

    if ($payload) {
        // Gera novo access token
        $newToken = JWTHelper::encode([
            'user_id' => $payload['user_id'],
            'refreshed_at' => time()
        ], $jwtSecret);

        $res->json([
            'access_token' => $newToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ]);
    } else {
        $res->status(401)->json([
            'error' => 'Invalid refresh token'
        ]);
    }
});

// Rota protegida - informações do usuário
$app->get('/user/profile', function($req, $res) {
    $res->json([
        'message' => 'Perfil do usuário autenticado',
        'user' => $req->user,
        'auth_method' => $req->auth['method'],
        'timestamp' => time()
    ]);
});

// Rota protegida - apenas para admins
$app->get('/admin/users', function($req, $res) {
    // Verifica se o usuário tem permissão de admin
    if (!isset($req->user['role']) || $req->user['role'] !== 'admin') {
        $res->status(403)->json([
            'error' => 'Access denied',
            'message' => 'Admin role required'
        ]);
        return;
    }

    $res->json([
        'message' => 'Lista de usuários (apenas admins)',
        'users' => [
            ['id' => 1, 'username' => 'user1'],
            ['id' => 2, 'username' => 'user2'],
            ['id' => 3, 'username' => 'admin']
        ],
        'admin_user' => $req->user
    ]);
});

// Rota com autenticação específica (apenas JWT)
$app->get('/jwt-only', AuthMiddleware::jwt($jwtSecret), function($req, $res) {
    $res->json([
        'message' => 'Esta rota aceita apenas JWT',
        'user' => $req->user,
        'method' => $req->auth['method']
    ]);
});

// Rota com autenticação específica (apenas API Key)
$app->get('/api-key-only', AuthMiddleware::apiKey('validateApiKey'), function($req, $res) {
    $res->json([
        'message' => 'Esta rota aceita apenas API Key',
        'user' => $req->user,
        'method' => $req->auth['method']
    ]);
});

// Rota de teste para diferentes métodos de auth
$app->post('/test-auth', function($req, $res) {
    $res->json([
        'message' => 'Teste de autenticação realizado com sucesso',
        'auth_method' => $req->auth['method'],
        'user_data' => $req->user,
        'request_info' => [
            'method' => $req->method,
            'path' => $req->path,
            'headers_received' => [
                'authorization' => $req->headers->authorization ?? 'não fornecido',
                'x-api-key' => $_SERVER['HTTP_X_API_KEY'] ?? 'não fornecido'
            ]
        ]
    ]);
});

// Middleware de erro global
$app->use(function($req, $res, $next) {
    $res->status(404)->json([
        'error' => 'Route not found',
        'path' => $req->path ?? $_SERVER['REQUEST_URI']
    ]);
});

// ========================================
// INFORMAÇÕES DE USO
// ========================================

echo "\n=== COMO TESTAR ===\n\n";

echo "1. Login para obter JWT:\n";
echo "   POST /login\n";
echo "   Body: {\"username\":\"admin\",\"password\":\"password123\"}\n\n";

echo "2. Usar JWT:\n";
echo "   Header: Authorization: Bearer <token>\n\n";

echo "3. Usar Basic Auth:\n";
echo "   Header: Authorization: Basic " . base64_encode('admin:password123') . "\n\n";

echo "4. Usar Bearer Token:\n";
echo "   Header: Authorization: Bearer token123\n\n";

echo "5. Usar API Key:\n";
echo "   Header: X-API-Key: key123456\n";
echo "   Ou Query: ?api_key=key123456\n\n";

echo "6. Rotas para testar:\n";
echo "   GET /public/status (pública)\n";
echo "   GET /user/profile (protegida)\n";
echo "   GET /admin/users (apenas admin)\n";
echo "   POST /test-auth (teste geral)\n\n";

// Inicia a aplicação
$app->run();
