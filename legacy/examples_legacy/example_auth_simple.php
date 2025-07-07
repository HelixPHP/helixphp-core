<?php
// =============================================================
// EXEMPLO 100% PSR-15: Uso exclusivo de middlewares PSR-15 no PivotPHP Framework
// Este exemplo NÃO é compatível com middlewares legados. Utilize apenas middlewares PSR-15.
// =============================================================
//
// Consulte a documentação oficial para detalhes sobre PSR-15:
// https://www.php-fig.org/psr/psr-15/
// =============================================================

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Authentication\JWTHelper;
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;

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
// MIDDLEWARES PSR-15
// ================================

// Middleware de autenticação JWT (PSR-15)
use PivotPHP\Core\Http\Psr15\Middleware\AuthMiddleware;
$app->use(AuthMiddleware::jwt($JWT_SECRET));

// Middleware de CORS (PSR-15)
$app->use(new CorsMiddleware([
    'origins' => ['*'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'headers' => ['Content-Type', 'Authorization']
]));

// Middleware de logging customizado (PSR-15)
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

class LoggingMiddleware implements MiddlewareInterface {
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        $start = microtime(true);
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        error_log("[REQUEST] {$method} {$path} - IP: {$ip}");
        $response = $handler->handle($request);
        $duration = round((microtime(true) - $start) * 1000, 2);
        error_log("[RESPONSE] {$method} {$path} - {$duration}ms");
        return $response;
    }
}
$app->use(new LoggingMiddleware());

// ================================
// FUNÇÕES AUXILIARES
// ================================

function findUserByEmail($email, $users) {
    return isset($users[$email]) ? $users[$email] : null;
}

// ================================
// ROTAS PÚBLICAS
// ================================

// Página inicial
$app->get('/', function(Request $req, Response $res) {
    $res->json([
        'message' => 'API de Autenticação - PivotPHP',
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
$app->get('/auth/me', function(Request $req, Response $res) {
    $res->json([
        'success' => true,
        'data' => $req->user
    ]);
});

// Rota protegida exemplo
$app->get('/protected', function(Request $req, Response $res) {
    $res->json([
        'success' => true,
        'message' => 'Você acessou uma rota protegida!',
        'user' => $req->user->name ?? 'Usuário',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Rota apenas para admins
$app->get('/admin/dashboard', function(Request $req, Response $res) {
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
// EXECUTAR APLICAÇÃO
// ================================

if (php_sapi_name() === 'cli-server') {
    echo "PivotPHP Auth Server rodando em http://localhost:8000\n";
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
