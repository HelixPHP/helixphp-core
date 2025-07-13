<?php

/**
 * 🔐 PivotPHP - Autenticação JWT Completa
 * 
 * Demonstra implementação completa de autenticação JWT
 * Login, logout, refresh tokens, proteção de rotas e middleware de segurança
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/06-security/jwt-auth.php
 * 
 * 🧪 Como testar:
 * curl -X POST http://localhost:8000/auth/login -H "Content-Type: application/json" -d '{"username":"admin","password":"secret123"}'
 * curl -H "Authorization: Bearer <jwt-token>" http://localhost:8000/protected
 * curl -X POST http://localhost:8000/auth/refresh -H "Content-Type: application/json" -d '{"refresh_token":"<refresh-token>"}'
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// ===============================================
// CONFIGURAÇÃO E DADOS
// ===============================================

// Chave secreta para JWT (em produção, use variável de ambiente)
$JWT_SECRET = 'your-super-secret-jwt-key-change-in-production';
$JWT_ALGORITHM = 'HS256';
$JWT_EXPIRY = 3600; // 1 hora
$REFRESH_TOKEN_EXPIRY = 604800; // 7 dias

// Banco de usuários simulado
$users = [
    1 => [
        'id' => 1,
        'username' => 'admin',
        'email' => 'admin@example.com',
        'password' => password_hash('secret123', PASSWORD_BCRYPT),
        'role' => 'admin',
        'permissions' => ['read', 'write', 'delete', 'admin'],
        'active' => true,
        'created_at' => '2024-01-01T00:00:00Z',
        'last_login' => null
    ],
    2 => [
        'id' => 2,
        'username' => 'user',
        'email' => 'user@example.com',
        'password' => password_hash('password123', PASSWORD_BCRYPT),
        'role' => 'user',
        'permissions' => ['read'],
        'active' => true,
        'created_at' => '2024-01-02T00:00:00Z',
        'last_login' => null
    ],
    3 => [
        'id' => 3,
        'username' => 'moderator',
        'email' => 'mod@example.com',
        'password' => password_hash('mod123', PASSWORD_BCRYPT),
        'role' => 'moderator',
        'permissions' => ['read', 'write', 'moderate'],
        'active' => true,
        'created_at' => '2024-01-03T00:00:00Z',
        'last_login' => null
    ]
];

// Tokens revogados (blacklist)
$revokedTokens = [];

// Refresh tokens válidos
$refreshTokens = [];

// ===============================================
// FUNÇÕES UTILITÁRIAS JWT
// ===============================================

function base64UrlEncode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64UrlDecode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function generateJWT($payload, $secret, $algorithm = 'HS256') {
    $header = json_encode(['typ' => 'JWT', 'alg' => $algorithm]);
    $payload = json_encode($payload);
    
    $base64Header = base64UrlEncode($header);
    $base64Payload = base64UrlEncode($payload);
    
    $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, $secret, true);
    $base64Signature = base64UrlEncode($signature);
    
    return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
}

function verifyJWT($token, $secret, $algorithm = 'HS256') {
    $parts = explode('.', $token);
    
    if (count($parts) !== 3) {
        return false;
    }
    
    [$header, $payload, $signature] = $parts;
    
    // Verificar header
    $decodedHeader = json_decode(base64UrlDecode($header), true);
    if (!$decodedHeader || $decodedHeader['alg'] !== $algorithm) {
        return false;
    }
    
    // Verificar assinatura
    $expectedSignature = hash_hmac('sha256', $header . '.' . $payload, $secret, true);
    $expectedSignature = base64UrlEncode($expectedSignature);
    
    if (!hash_equals($signature, $expectedSignature)) {
        return false;
    }
    
    // Decodificar payload
    $decodedPayload = json_decode(base64UrlDecode($payload), true);
    
    if (!$decodedPayload) {
        return false;
    }
    
    // Verificar expiração
    if (isset($decodedPayload['exp']) && time() > $decodedPayload['exp']) {
        return false;
    }
    
    return $decodedPayload;
}

function generateRefreshToken() {
    return bin2hex(random_bytes(32));
}

function findUserByCredentials($username, $password, $users) {
    foreach ($users as $user) {
        if ($user['username'] === $username && password_verify($password, $user['password'])) {
            return $user;
        }
    }
    return null;
}

function findUserById($id, $users) {
    return $users[$id] ?? null;
}

// ===============================================
// MIDDLEWARE DE AUTENTICAÇÃO
// ===============================================

$jwtMiddleware = function ($req, $res, $next) use ($JWT_SECRET, $revokedTokens, $users) {
    $authHeader = $req->header('Authorization');
    
    if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
        return $res->status(401)->json([
            'error' => 'Token de autenticação obrigatório',
            'code' => 'MISSING_TOKEN',
            'required_header' => 'Authorization: Bearer <jwt-token>'
        ]);
    }
    
    $token = substr($authHeader, 7);
    
    // Verificar se token está na blacklist
    if (in_array($token, $revokedTokens)) {
        return $res->status(401)->json([
            'error' => 'Token foi revogado',
            'code' => 'TOKEN_REVOKED'
        ]);
    }
    
    // Verificar e decodificar token
    $payload = verifyJWT($token, $JWT_SECRET);
    
    if (!$payload) {
        return $res->status(401)->json([
            'error' => 'Token inválido ou expirado',
            'code' => 'INVALID_TOKEN'
        ]);
    }
    
    // Buscar usuário
    $user = findUserById($payload['user_id'], $users);
    
    if (!$user || !$user['active']) {
        return $res->status(401)->json([
            'error' => 'Usuário não encontrado ou inativo',
            'code' => 'USER_NOT_FOUND'
        ]);
    }
    
    // Adicionar dados ao request
    $req->user = $user;
    $req->token = $token;
    $req->tokenPayload = $payload;
    
    return $next($req, $res);
};

// Middleware de autorização por role
$requireRole = function ($requiredRoles) {
    return function ($req, $res, $next) use ($requiredRoles) {
        if (!isset($req->user)) {
            return $res->status(401)->json([
                'error' => 'Usuário não autenticado',
                'code' => 'NOT_AUTHENTICATED'
            ]);
        }
        
        $userRole = $req->user['role'];
        $allowedRoles = is_array($requiredRoles) ? $requiredRoles : [$requiredRoles];
        
        if (!in_array($userRole, $allowedRoles)) {
            return $res->status(403)->json([
                'error' => 'Acesso negado',
                'code' => 'INSUFFICIENT_ROLE',
                'user_role' => $userRole,
                'required_roles' => $allowedRoles
            ]);
        }
        
        return $next($req, $res);
    };
};

// Middleware de autorização por permissão
$requirePermission = function ($requiredPermissions) {
    return function ($req, $res, $next) use ($requiredPermissions) {
        if (!isset($req->user)) {
            return $res->status(401)->json([
                'error' => 'Usuário não autenticado',
                'code' => 'NOT_AUTHENTICATED'
            ]);
        }
        
        $userPermissions = $req->user['permissions'] ?? [];
        $requiredPerms = is_array($requiredPermissions) ? $requiredPermissions : [$requiredPermissions];
        
        foreach ($requiredPerms as $permission) {
            if (!in_array($permission, $userPermissions)) {
                return $res->status(403)->json([
                    'error' => 'Permissão insuficiente',
                    'code' => 'INSUFFICIENT_PERMISSION',
                    'required_permission' => $permission,
                    'user_permissions' => $userPermissions
                ]);
            }
        }
        
        return $next($req, $res);
    };
};

// ===============================================
// ROTAS PÚBLICAS
// ===============================================

$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - JWT Authentication System',
        'description' => 'Sistema completo de autenticação JWT com refresh tokens',
        'features' => [
            'JWT Authentication' => 'Tokens seguros com expiração automática',
            'Refresh Tokens' => 'Renovação de tokens sem re-login',
            'Role-based Access' => 'Controle de acesso baseado em roles',
            'Permission System' => 'Permissões granulares por usuário',
            'Token Blacklist' => 'Revogação segura de tokens',
            'Security Headers' => 'Headers de segurança automáticos'
        ],
        'available_accounts' => [
            ['username' => 'admin', 'password' => 'secret123', 'role' => 'admin'],
            ['username' => 'user', 'password' => 'password123', 'role' => 'user'],
            ['username' => 'moderator', 'password' => 'mod123', 'role' => 'moderator']
        ],
        'endpoints' => [
            'POST /auth/login' => 'Login com credenciais',
            'POST /auth/refresh' => 'Renovar token com refresh token',
            'POST /auth/logout' => 'Logout e revogação de token',
            'GET /auth/me' => 'Informações do usuário autenticado',
            'GET /protected' => 'Rota protegida (requer autenticação)',
            'GET /admin' => 'Área administrativa (role: admin)',
            'GET /moderate' => 'Área de moderação (permission: moderate)',
            'GET /security/info' => 'Informações de segurança do token'
        ]
    ]);
});

// ===============================================
// ROTAS DE AUTENTICAÇÃO
// ===============================================

// Login
$app->post('/auth/login', function ($req, $res) use ($users, $JWT_SECRET, $JWT_EXPIRY, $REFRESH_TOKEN_EXPIRY, &$refreshTokens) {
    $body = $req->getBodyAsStdClass();
    
    if (empty($body->username) || empty($body->password)) {
        return $res->status(400)->json([
            'error' => 'Username e password são obrigatórios',
            'code' => 'MISSING_CREDENTIALS'
        ]);
    }
    
    $user = findUserByCredentials($body->username, $body->password, $users);
    
    if (!$user) {
        // Log de tentativa de login inválido
        error_log("Failed login attempt for username: {$body->username} from IP: " . $req->ip());
        
        return $res->status(401)->json([
            'error' => 'Credenciais inválidas',
            'code' => 'INVALID_CREDENTIALS'
        ]);
    }
    
    if (!$user['active']) {
        return $res->status(401)->json([
            'error' => 'Conta desativada',
            'code' => 'ACCOUNT_DISABLED'
        ]);
    }
    
    $now = time();
    
    // Criar JWT payload
    $jwtPayload = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'permissions' => $user['permissions'],
        'iat' => $now,
        'exp' => $now + $JWT_EXPIRY,
        'iss' => 'pivotphp-auth',
        'jti' => uniqid('jwt_', true) // JWT ID único
    ];
    
    // Gerar tokens
    $accessToken = generateJWT($jwtPayload, $JWT_SECRET);
    $refreshToken = generateRefreshToken();
    
    // Armazenar refresh token
    $refreshTokens[$refreshToken] = [
        'user_id' => $user['id'],
        'created_at' => $now,
        'expires_at' => $now + $REFRESH_TOKEN_EXPIRY,
        'ip' => $req->ip(),
        'user_agent' => $req->header('User-Agent')
    ];
    
    // Atualizar último login do usuário
    $users[$user['id']]['last_login'] = date('c');
    
    // Log de login bem-sucedido
    error_log("Successful login for user: {$user['username']} from IP: " . $req->ip());
    
    return $res->json([
        'message' => 'Login realizado com sucesso',
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'token_type' => 'Bearer',
        'expires_in' => $JWT_EXPIRY,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'permissions' => $user['permissions']
        ]
    ]);
});

// Refresh token
$app->post('/auth/refresh', function ($req, $res) use ($users, $JWT_SECRET, $JWT_EXPIRY, &$refreshTokens) {
    $body = $req->getBodyAsStdClass();
    
    if (empty($body->refresh_token)) {
        return $res->status(400)->json([
            'error' => 'Refresh token é obrigatório',
            'code' => 'MISSING_REFRESH_TOKEN'
        ]);
    }
    
    $refreshToken = $body->refresh_token;
    
    if (!isset($refreshTokens[$refreshToken])) {
        return $res->status(401)->json([
            'error' => 'Refresh token inválido',
            'code' => 'INVALID_REFRESH_TOKEN'
        ]);
    }
    
    $tokenData = $refreshTokens[$refreshToken];
    
    // Verificar expiração
    if (time() > $tokenData['expires_at']) {
        unset($refreshTokens[$refreshToken]);
        return $res->status(401)->json([
            'error' => 'Refresh token expirado',
            'code' => 'REFRESH_TOKEN_EXPIRED'
        ]);
    }
    
    $user = findUserById($tokenData['user_id'], $users);
    
    if (!$user || !$user['active']) {
        unset($refreshTokens[$refreshToken]);
        return $res->status(401)->json([
            'error' => 'Usuário não encontrado ou inativo',
            'code' => 'USER_NOT_FOUND'
        ]);
    }
    
    $now = time();
    
    // Criar novo JWT
    $jwtPayload = [
        'user_id' => $user['id'],
        'username' => $user['username'],
        'role' => $user['role'],
        'permissions' => $user['permissions'],
        'iat' => $now,
        'exp' => $now + $JWT_EXPIRY,
        'iss' => 'pivotphp-auth',
        'jti' => uniqid('jwt_', true)
    ];
    
    $newAccessToken = generateJWT($jwtPayload, $JWT_SECRET);
    
    return $res->json([
        'message' => 'Token renovado com sucesso',
        'access_token' => $newAccessToken,
        'token_type' => 'Bearer',
        'expires_in' => $JWT_EXPIRY
    ]);
});

// Logout
$app->post('/auth/logout', $jwtMiddleware, function ($req, $res) use (&$revokedTokens, &$refreshTokens) {
    $body = $req->getBodyAsStdClass();
    
    // Adicionar token atual à blacklist
    $revokedTokens[] = $req->token;
    
    // Revogar refresh token se fornecido
    if (!empty($body->refresh_token) && isset($refreshTokens[$body->refresh_token])) {
        unset($refreshTokens[$body->refresh_token]);
    }
    
    error_log("User logout: {$req->user['username']} from IP: " . $req->ip());
    
    return $res->json([
        'message' => 'Logout realizado com sucesso',
        'tokens_revoked' => ['access_token', 'refresh_token']
    ]);
});

// Informações do usuário autenticado
$app->get('/auth/me', $jwtMiddleware, function ($req, $res) {
    return $res->json([
        'user' => [
            'id' => $req->user['id'],
            'username' => $req->user['username'],
            'email' => $req->user['email'],
            'role' => $req->user['role'],
            'permissions' => $req->user['permissions'],
            'last_login' => $req->user['last_login']
        ],
        'token_info' => [
            'issued_at' => $req->tokenPayload['iat'],
            'expires_at' => $req->tokenPayload['exp'],
            'time_remaining' => $req->tokenPayload['exp'] - time(),
            'issuer' => $req->tokenPayload['iss']
        ]
    ]);
});

// ===============================================
// ROTAS PROTEGIDAS
// ===============================================

// Rota protegida básica
$app->get('/protected', $jwtMiddleware, function ($req, $res) {
    return $res->json([
        'message' => 'Acesso autorizado!',
        'user' => $req->user['username'],
        'role' => $req->user['role'],
        'permissions' => $req->user['permissions'],
        'accessed_at' => date('c')
    ]);
});

// Área administrativa (apenas admin)
$app->get('/admin', $jwtMiddleware, $requireRole('admin'), function ($req, $res) {
    return $res->json([
        'message' => 'Área administrativa',
        'admin_data' => [
            'total_users' => 3,
            'active_sessions' => 1,
            'system_status' => 'operational'
        ],
        'admin' => $req->user['username']
    ]);
});

// Área de moderação (permissão específica)
$app->get('/moderate', $jwtMiddleware, $requirePermission('moderate'), function ($req, $res) {
    return $res->json([
        'message' => 'Área de moderação',
        'moderation_queue' => [
            ['id' => 1, 'type' => 'post', 'status' => 'pending'],
            ['id' => 2, 'type' => 'comment', 'status' => 'flagged']
        ],
        'moderator' => $req->user['username']
    ]);
});

// Informações de segurança do token
$app->get('/security/info', $jwtMiddleware, function ($req, $res) {
    $tokenParts = explode('.', $req->token);
    $header = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0])), true);
    
    return $res->json([
        'token_security' => [
            'algorithm' => $header['alg'],
            'type' => $header['typ'],
            'issued_at' => date('c', $req->tokenPayload['iat']),
            'expires_at' => date('c', $req->tokenPayload['exp']),
            'time_to_expiry' => $req->tokenPayload['exp'] - time() . ' seconds',
            'jwt_id' => $req->tokenPayload['jti']
        ],
        'security_features' => [
            'token_signature' => 'HMAC SHA-256',
            'token_blacklist' => 'Active',
            'refresh_tokens' => 'Supported',
            'permission_system' => 'Active',
            'login_logging' => 'Enabled'
        ],
        'user_context' => [
            'user_id' => $req->user['id'],
            'role' => $req->user['role'],
            'permissions' => $req->user['permissions'],
            'account_status' => $req->user['active'] ? 'active' : 'disabled'
        ]
    ]);
});

// Status do sistema de autenticação
$app->get('/auth/status', function ($req, $res) use ($revokedTokens, $refreshTokens) {
    return $res->json([
        'authentication_system' => [
            'status' => 'operational',
            'jwt_algorithm' => 'HS256',
            'token_expiry' => '1 hour',
            'refresh_token_expiry' => '7 days'
        ],
        'statistics' => [
            'revoked_tokens' => count($revokedTokens),
            'active_refresh_tokens' => count($refreshTokens),
            'total_users' => 3
        ],
        'security_features' => [
            'token_blacklist' => 'enabled',
            'refresh_tokens' => 'enabled',
            'role_based_access' => 'enabled',
            'permission_system' => 'enabled',
            'audit_logging' => 'enabled'
        ]
    ]);
});

$app->run();