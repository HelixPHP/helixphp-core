<?php

/**
 * 🔐 PivotPHP - Middleware de Autenticação
 * 
 * Demonstra implementação completa de autenticação com middleware
 * JWT, Session, API Key e autenticação baseada em roles
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/03-middleware/auth-middleware.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"username":"admin","password":"secret"}'
 * curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..." http://localhost:8000/protected
 * curl -H "X-API-Key: api-key-123" http://localhost:8000/api/data
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📋 Página inicial
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Authentication Middleware Examples',
        'description' => 'Demonstrações completas de autenticação',
        'auth_methods' => [
            'JWT Token' => [
                'description' => 'JSON Web Tokens para autenticação stateless',
                'header' => 'Authorization: Bearer <jwt-token>',
                'use_case' => 'APIs, SPA, mobile apps'
            ],
            'API Key' => [
                'description' => 'Chaves de API para integração de sistemas',
                'header' => 'X-API-Key: <api-key>',
                'use_case' => 'Integrações, webhooks, automação'
            ],
            'Session' => [
                'description' => 'Autenticação baseada em sessão PHP',
                'storage' => 'Server-side session storage',
                'use_case' => 'Aplicações web tradicionais'
            ],
            'Basic Auth' => [
                'description' => 'Autenticação HTTP básica',
                'header' => 'Authorization: Basic <base64(user:pass)>',
                'use_case' => 'Integrações simples, desenvolvimento'
            ]
        ],
        'role_system' => [
            'admin' => 'Acesso total ao sistema',
            'moderator' => 'Gerenciamento de conteúdo',
            'user' => 'Acesso básico',
            'guest' => 'Acesso limitado/público'
        ],
        'endpoints' => [
            'POST /auth/login' => 'Login com credenciais',
            'POST /auth/logout' => 'Logout e invalidação',
            'GET /auth/me' => 'Informações do usuário logado',
            'GET /protected' => 'Rota protegida (JWT)',
            'GET /api/data' => 'Rota protegida (API Key)',
            'GET /admin/dashboard' => 'Área administrativa',
            'GET /user/profile' => 'Perfil do usuário'
        ]
    ]);
});

// ===============================================
// SIMULAÇÃO DE BANCO DE DADOS
// ===============================================

$users = [
    1 => [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => password_hash('secret', PASSWORD_BCRYPT),
        'role' => 'admin',
        'active' => true,
        'api_key' => 'api-key-admin-123'
    ],
    2 => [
        'id' => 2,
        'username' => 'moderator',
        'email' => 'mod@example.com',
        'password' => password_hash('password', PASSWORD_BCRYPT),
        'role' => 'moderator',
        'active' => true,
        'api_key' => 'api-key-mod-456'
    ],
    3 => [
        'id' => 3,
        'username' => 'user',
        'email' => 'user@example.com',
        'password' => password_hash('123456', PASSWORD_BCRYPT),
        'role' => 'user',
        'active' => true,
        'api_key' => 'api-key-user-789'
    ]
];

$activeSessions = [];
$revokedTokens = [];

// ===============================================
// UTILITY FUNCTIONS
// ===============================================

function generateJWT($payload, $secret = 'secret-key') {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload = json_encode($payload);
    
    $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
    $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
    
    $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
    $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
    
    return $base64Header . "." . $base64Payload . "." . $base64Signature;
}

function verifyJWT($token, $secret = 'secret-key') {
    $tokenParts = explode('.', $token);
    
    if (count($tokenParts) !== 3) {
        return false;
    }
    
    [$header, $payload, $signature] = $tokenParts;
    
    $expectedSignature = hash_hmac('sha256', $header . "." . $payload, $secret, true);
    $expectedSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($expectedSignature));
    
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }
    
    $decodedPayload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $payload)), true);
    
    // Verificar expiração
    if (isset($decodedPayload['exp']) && time() > $decodedPayload['exp']) {
        return false;
    }
    
    return $decodedPayload;
}

function findUserByCredentials($username, $password, $users) {
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return null;
}

function findUserByApiKey($apiKey, $users) {
    foreach ($users as $user) {
        if ($user['api_key'] === $apiKey) {
            return $user;
        }
    }
    return null;
}

// ===============================================
// MIDDLEWARE DE AUTENTICAÇÃO
// ===============================================

// 🔑 JWT Authentication Middleware
$jwtAuth = function ($req, $res, $next) use ($users, $revokedTokens) {
    $authHeader = $req->header('Authorization');
    
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return $res->status(401)->json([
            'error' => 'Token JWT obrigatório',
            'required_header' => 'Authorization: Bearer <jwt-token>',
            'middleware' => 'jwtAuth'
        ]);
    }
    
    $token = substr($authHeader, 7);
    
    // Verificar se token foi revogado
    if (in_array($token, $revokedTokens)) {
        return $res->status(401)->json([
            'error' => 'Token revogado',
            'middleware' => 'jwtAuth'
        ]);
    }
    
    $payload = verifyJWT($token);
    
    if (!$payload) {
        return $res->status(401)->json([
            'error' => 'Token JWT inválido ou expirado',
            'middleware' => 'jwtAuth'
        ]);
    }
    
    // Buscar usuário
    $user = $users[$payload['user_id']] ?? null;
    
    if (!$user || !$user['active']) {
        return $res->status(401)->json([
            'error' => 'Usuário não encontrado ou inativo',
            'middleware' => 'jwtAuth'
        ]);
    }
    
    // Adicionar usuário ao request
    $req->user = $user;
    $req->authMethod = 'jwt';
    $req->token = $token;
    
    return $next($req, $res);
};

// 🔐 API Key Authentication Middleware
$apiKeyAuth = function ($req, $res, $next) use ($users) {
    $apiKey = $req->header('X-API-Key');
    
    if (!$apiKey) {
        return $res->status(401)->json([
            'error' => 'API Key obrigatória',
            'required_header' => 'X-API-Key: <api-key>',
            'middleware' => 'apiKeyAuth'
        ]);
    }
    
    $user = findUserByApiKey($apiKey, $users);
    
    if (!$user || !$user['active']) {
        return $res->status(403)->json([
            'error' => 'API Key inválida',
            'provided_key' => substr($apiKey, 0, 8) . '...',
            'middleware' => 'apiKeyAuth'
        ]);
    }
    
    $req->user = $user;
    $req->authMethod = 'api_key';
    $req->apiKey = $apiKey;
    
    return $next($req, $res);
};

// 👥 Role-based Authorization Middleware
$requireRole = function ($requiredRoles) {
    return function ($req, $res, $next) use ($requiredRoles) {
        if (!isset($req->user)) {
            return $res->status(401)->json([
                'error' => 'Usuário não autenticado',
                'middleware' => 'requireRole'
            ]);
        }
        
        $userRole = $req->user['role'];
        $allowedRoles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
        
        if (!in_array($userRole, $allowedRoles)) {
            return $res->status(403)->json([
                'error' => 'Acesso negado',
                'user_role' => $userRole,
                'required_roles' => $allowedRoles,
                'middleware' => 'requireRole'
            ]);
        }
        
        return $next($req, $res);
    };
};

// 🕒 Session Authentication Middleware
$sessionAuth = function ($req, $res, $next) use ($users, $activeSessions) {
    session_start();
    
    $sessionId = session_id();
    $userId = $_SESSION['user_id'] ?? null;
    
    if (!$userId || !isset($activeSessions[$sessionId])) {
        return $res->status(401)->json([
            'error' => 'Sessão inválida ou expirada',
            'middleware' => 'sessionAuth'
        ]);
    }
    
    $user = $users[$userId] ?? null;
    
    if (!$user || !$user['active']) {
        return $res->status(401)->json([
            'error' => 'Usuário não encontrado ou inativo',
            'middleware' => 'sessionAuth'
        ]);
    }
    
    // Verificar expiração da sessão
    $sessionData = $activeSessions[$sessionId];
    if (time() > $sessionData['expires_at']) {
        unset($activeSessions[$sessionId]);
        session_destroy();
        
        return $res->status(401)->json([
            'error' => 'Sessão expirada',
            'middleware' => 'sessionAuth'
        ]);
    }
    
    // Renovar sessão
    $activeSessions[$sessionId]['last_activity'] = time();
    
    $req->user = $user;
    $req->authMethod = 'session';
    $req->sessionId = $sessionId;
    
    return $next($req, $res);
};

// 🔒 Basic Authentication Middleware
$basicAuth = function ($req, $res, $next) use ($users) {
    $authHeader = $req->header('Authorization');
    
    if (!$authHeader || !str_starts_with($authHeader, 'Basic ')) {
        $res->header('WWW-Authenticate', 'Basic realm="Protected Area"');
        return $res->status(401)->json([
            'error' => 'Autenticação básica obrigatória',
            'required_header' => 'Authorization: Basic <base64(username:password)>',
            'middleware' => 'basicAuth'
        ]);
    }
    
    $credentials = base64_decode(substr($authHeader, 6));
    [$username, $password] = explode(':', $credentials, 2);
    
    $user = findUserByCredentials($username, $password, $users);
    
    if (!$user || !$user['active']) {
        $res->header('WWW-Authenticate', 'Basic realm="Protected Area"');
        return $res->status(401)->json([
            'error' => 'Credenciais inválidas',
            'middleware' => 'basicAuth'
        ]);
    }
    
    $req->user = $user;
    $req->authMethod = 'basic';
    
    return $next($req, $res);
};

// ===============================================
// ROTAS DE AUTENTICAÇÃO
// ===============================================

// Login com credenciais
$app->post('/auth/login', function ($req, $res) use ($users, &$activeSessions) {
    $body = $req->getBodyAsStdClass();
    
    if (empty($body->username) || empty($body->password)) {
        return $res->status(400)->json([
            'error' => 'Username e password são obrigatórios'
        ]);
    }
    
    $user = findUserByCredentials($body->username, $body->password, $users);
    
    if (!$user || !$user['active']) {
        return $res->status(401)->json([
            'error' => 'Credenciais inválidas'
        ]);
    }
    
    // Gerar JWT
    $payload = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'iat' => time(),
        'exp' => time() + 3600 // 1 hora
    ];
    
    $jwtToken = generateJWT($payload);
    
    // Criar sessão
    session_start();
    $_SESSION['user_id'] = $user['id'];
    $sessionId = session_id();
    
    $activeSessions[$sessionId] = [
        'user_id' => $user['id'],
        'created_at' => time(),
        'last_activity' => time(),
        'expires_at' => time() + 3600,
        'ip' => $req->ip(),
        'user_agent' => $req->header('User-Agent')
    ];
    
    return $res->json([
        'message' => 'Login realizado com sucesso',
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'tokens' => [
            'jwt' => $jwtToken,
            'session_id' => $sessionId,
            'api_key' => $user['api_key']
        ],
        'expires_at' => date('c', time() + 3600)
    ]);
});

// Logout
$app->post('/auth/logout', $jwtAuth, function ($req, $res) use (&$revokedTokens, &$activeSessions) {
    // Revogar JWT
    $revokedTokens[] = $req->token;
    
    // Destruir sessão se existir
    if (isset($req->sessionId)) {
        unset($activeSessions[$req->sessionId]);
        session_destroy();
    }
    
    return $res->json([
        'message' => 'Logout realizado com sucesso',
        'tokens_revoked' => ['jwt', 'session']
    ]);
});

// Informações do usuário autenticado
$app->get('/auth/me', $jwtAuth, function ($req, $res) {
    return $res->json([
        'user' => [
            'id' => $req->user['id'],
            'username' => $req->user['username'],
            'email' => $req->user['email'],
            'role' => $req->user['role']
        ],
        'auth_info' => [
            'method' => $req->authMethod,
            'authenticated_at' => date('c')
        ]
    ]);
});

// ===============================================
// ROTAS PROTEGIDAS
// ===============================================

// Rota protegida por JWT
$app->get('/protected', $jwtAuth, function ($req, $res) {
    return $res->json([
        'message' => 'Acesso autorizado via JWT',
        'user' => $req->user['username'],
        'role' => $req->user['role'],
        'auth_method' => $req->authMethod
    ]);
});

// Rota protegida por API Key
$app->get('/api/data', $apiKeyAuth, function ($req, $res) {
    return $res->json([
        'message' => 'Dados da API',
        'data' => [
            'sensitive_info' => 'Informações importantes',
            'timestamp' => date('c')
        ],
        'auth_info' => [
            'method' => $req->authMethod,
            'user' => $req->user['username'],
            'api_key' => substr($req->apiKey, 0, 8) . '...'
        ]
    ]);
});

// Área administrativa (apenas admin)
$app->get('/admin/dashboard', $jwtAuth, $requireRole('admin'), function ($req, $res) {
    return $res->json([
        'message' => 'Dashboard administrativo',
        'admin_data' => [
            'total_users' => 1250,
            'pending_approvals' => 15,
            'system_status' => 'operational'
        ],
        'access_info' => [
            'admin' => $req->user['username'],
            'role' => $req->user['role'],
            'access_level' => 'admin_only'
        ]
    ]);
});

// Área de moderação (admin ou moderator)
$app->get('/mod/content', $jwtAuth, $requireRole(['admin', 'moderator']), function ($req, $res) {
    return $res->json([
        'message' => 'Área de moderação',
        'moderation_queue' => [
            ['id' => 1, 'type' => 'post', 'status' => 'pending'],
            ['id' => 2, 'type' => 'comment', 'status' => 'flagged']
        ],
        'moderator_info' => [
            'name' => $req->user['username'],
            'role' => $req->user['role']
        ]
    ]);
});

// Perfil do usuário (qualquer usuário autenticado)
$app->get('/user/profile', $jwtAuth, function ($req, $res) {
    return $res->json([
        'message' => 'Perfil do usuário',
        'profile' => [
            'id' => $req->user['id'],
            'username' => $req->user['username'],
            'email' => $req->user['email'],
            'role' => $req->user['role'],
            'last_login' => date('c')
        ]
    ]);
});

// Rota com autenticação básica
$app->get('/basic-protected', $basicAuth, function ($req, $res) {
    return $res->json([
        'message' => 'Acesso via Basic Authentication',
        'user' => $req->user['username'],
        'auth_method' => $req->authMethod
    ]);
});

// Demonstração de múltiplos métodos de auth
$app->get('/multi-auth', function ($req, $res, $next) use ($jwtAuth, $apiKeyAuth, $basicAuth) {
    // Tentar JWT primeiro
    if ($req->header('Authorization') && str_starts_with($req->header('Authorization'), 'Bearer ')) {
        return $jwtAuth($req, $res, $next);
    }
    
    // Tentar API Key
    if ($req->header('X-API-Key')) {
        return $apiKeyAuth($req, $res, $next);
    }
    
    // Tentar Basic Auth
    if ($req->header('Authorization') && str_starts_with($req->header('Authorization'), 'Basic ')) {
        return $basicAuth($req, $res, $next);
    }
    
    // Nenhum método encontrado
    return $res->status(401)->json([
        'error' => 'Autenticação obrigatória',
        'supported_methods' => ['JWT Bearer', 'API Key', 'Basic Auth'],
        'examples' => [
            'jwt' => 'Authorization: Bearer <jwt-token>',
            'api_key' => 'X-API-Key: <api-key>',
            'basic' => 'Authorization: Basic <base64(user:pass)>'
        ]
    ]);
}, function ($req, $res) {
    return $res->json([
        'message' => 'Autenticado com sucesso usando múltiplos métodos',
        'auth_method' => $req->authMethod,
        'user' => $req->user['username']
    ]);
});

$app->run();