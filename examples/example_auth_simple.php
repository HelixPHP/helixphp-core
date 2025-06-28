<?php
/**
 * Exemplo de Autenticação Simples - Express PHP
 *
 * Este exemplo demonstra como implementar autenticação básica
 * usando JWT de forma simples e prática.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;
use Express\Authentication\JWTHelper;

// Criar aplicação
$app = new Application();

// Configurações
$JWT_SECRET = 'minha_chave_secreta_super_segura';

// Simulação de usuários (em produção, use banco de dados)
$users = [
    'admin@example.com' => [
        'id' => 1,
        'email' => 'admin@example.com',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'name' => 'Administrador',
        'role' => 'admin'
    ],
    'user@example.com' => [
        'id' => 2,
        'email' => 'user@example.com',
        'password' => password_hash('123456', PASSWORD_DEFAULT),
        'name' => 'Usuário',
        'role' => 'user'
    ]
];

// ================================
// FUNÇÕES AUXILIARES
// ================================

function findUserByEmail($email, $users) {
    return isset($users[$email]) ? $users[$email] : null;
}

function verifyToken($token, $secret) {
    try {
        return JWTHelper::decode($token, $secret);
    } catch (Exception $e) {
        return null;
    }
}

// ================================
// MIDDLEWARE DE AUTENTICAÇÃO
// ================================

$authMiddleware = function(Request $req, Response $res, callable $next) use ($JWT_SECRET) {
    $token = null;

    // Verificar header Authorization
    $authHeader = $req->getHeader('Authorization');
    if ($authHeader && preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }

    if (!$token) {
        $res->status(401)->json([
            'success' => false,
            'message' => 'Token de acesso requerido'
        ]);
        return;
    }

    $payload = verifyToken($token, $JWT_SECRET);
    if (!$payload) {
        $res->status(401)->json([
            'success' => false,
            'message' => 'Token inválido ou expirado'
        ]);
        return;
    }

    // Adicionar dados do usuário ao request
    $req->user = $payload;

    return $next($req, $res);
};

// ================================
// ROTAS PÚBLICAS
// ================================

// Página inicial
$app->get('/', function(Request $req, Response $res) {
    $res->json([
        'message' => 'API de Autenticação - Express PHP',
        'version' => '2.0',
        'endpoints' => [
            'POST /auth/login' => 'Fazer login',
            'GET /auth/me' => 'Dados do usuário logado (requer token)',
            'GET /protected' => 'Rota protegida (requer token)'
        ]
    ]);
});

// Login
$app->post('/auth/login', function(Request $req, Response $res) use ($users, $JWT_SECRET) {
    $data = $req->getBody();

    if (!isset($data['email']) || !isset($data['password'])) {
        $res->status(400)->json([
            'success' => false,
            'message' => 'Email e senha são obrigatórios'
        ]);
        return;
    }

    $user = findUserByEmail($data['email'], $users);

    if (!$user || !password_verify($data['password'], $user['password'])) {
        $res->status(401)->json([
            'success' => false,
            'message' => 'Credenciais inválidas'
        ]);
        return;
    }

    // Gerar token JWT
    $payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'name' => $user['name'],
        'role' => $user['role'],
        'exp' => time() + 3600 // Expira em 1 hora
    ];

    $token = JWTHelper::encode($payload, $JWT_SECRET);

    $res->json([
        'success' => true,
        'message' => 'Login realizado com sucesso',
        'data' => [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role']
            ]
        ]
    ]);
});

// ================================
// ROTAS PROTEGIDAS
// ================================

// Dados do usuário logado
$app->get('/auth/me', $authMiddleware, function(Request $req, Response $res) {
    $res->json([
        'success' => true,
        'data' => $req->user
    ]);
});

// Rota protegida exemplo
$app->get('/protected', $authMiddleware, function(Request $req, Response $res) {
    $res->json([
        'success' => true,
        'message' => 'Você acessou uma rota protegida!',
        'user' => $req->user->name ?? 'Usuário',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Rota apenas para admins
$app->get('/admin/dashboard', $authMiddleware, function(Request $req, Response $res) {
    if ($req->user->role !== 'admin') {
        $res->status(403)->json([
            'success' => false,
            'message' => 'Acesso negado. Apenas administradores.'
        ]);
        return;
    }

    $res->json([
        'success' => true,
        'message' => 'Dashboard do administrador',
        'data' => [
            'total_users' => 2,
            'active_sessions' => 1,
            'server_status' => 'online'
        ]
    ]);
});

// ================================
// MIDDLEWARE GLOBAL
// ================================

// Log de requisições
$app->use(function(Request $req, Response $res, callable $next) {
    $method = $req->getMethod();
    $path = $req->getPath();
    $ip = $req->getClientIp() ?? 'unknown';
    $timestamp = date('Y-m-d H:i:s');

    error_log("[{$timestamp}] {$method} {$path} - IP: {$ip}");

    return $next($req, $res);
});

// CORS básico
$app->use(function(Request $req, Response $res, callable $next) {
    $res->header('Access-Control-Allow-Origin', '*');
    $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');

    if ($req->getMethod() === 'OPTIONS') {
        $res->status(200)->send();
        return;
    }

    return $next($req, $res);
});

// ================================
// EXECUTAR APLICAÇÃO
// ================================

if (php_sapi_name() === 'cli-server') {
    echo "Express PHP Auth Server rodando em http://localhost:8000\n";
    echo "\nCredenciais de teste:\n";
    echo "  admin@example.com : 123456 (admin)\n";
    echo "  user@example.com  : 123456 (user)\n";
    echo "\nEndpoints:\n";
    echo "  POST /auth/login     - Fazer login\n";
    echo "  GET  /auth/me        - Dados do usuário (requer token)\n";
    echo "  GET  /protected      - Rota protegida (requer token)\n";
    echo "  GET  /admin/dashboard - Apenas admins (requer token)\n";
    echo "\nExemplo de uso:\n";
    echo "  1. curl -X POST http://localhost:8000/auth/login -d '{\"email\":\"admin@example.com\",\"password\":\"123456\"}' -H 'Content-Type: application/json'\n";
    echo "  2. curl -H 'Authorization: Bearer SEU_TOKEN' http://localhost:8000/auth/me\n";
    echo "\n";
}

$app->run();
