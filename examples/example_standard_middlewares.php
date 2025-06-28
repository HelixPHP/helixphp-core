<?php
/**
 * Exemplo Prático - Middlewares Padrão do Express PHP
 *
 * Este exemplo demonstra o uso dos middlewares padrão inclusos
 * no Express PHP Framework com diferentes configurações.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;

// Importar middlewares padrão
use Express\Middleware\Security\SecurityMiddleware;
use Express\Middleware\Security\CorsMiddleware;
use Express\Middleware\Security\AuthMiddleware;
use Express\Middleware\Security\CsrfMiddleware;
use Express\Middleware\Core\RateLimitMiddleware;

// Criar aplicação
$app = new Application();

// ================================
// MIDDLEWARES GLOBAIS DE SEGURANÇA
// ================================

// 1. Middleware de Segurança - Headers HTTP seguros
$app->use(new SecurityMiddleware([
    'contentSecurityPolicy' => [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: https:"
    ],
    'xFrameOptions' => 'DENY',
    'referrerPolicy' => 'strict-origin-when-cross-origin'
]));

// 2. Middleware CORS - Configuração para desenvolvimento
$app->use(new CorsMiddleware([
    'origins' => ['http://localhost:3000', 'http://localhost:8080'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'X-CSRF-Token'],
    'credentials' => true,
    'maxAge' => 3600 // 1 hora
]));

// 3. Rate Limiting Global - 1000 requisições por hora por IP
$app->use(new RateLimitMiddleware([
    'maxRequests' => 1000,
    'timeWindow' => 3600,
    'keyGenerator' => function($req) {
        return $req->getClientIP();
    }
]));

// ================================
// ROTAS PÚBLICAS
// ================================

// Status da API - sem middleware adicional
$app->get('/', function(Request $req, Response $res) {
    $res->json([
        'message' => 'Express PHP - Exemplo de Middlewares Padrão',
        'version' => '2.0',
        'timestamp' => date('c'),
        'endpoints' => [
            'GET /' => 'Esta página',
            'GET /public/info' => 'Informações públicas',
            'GET /api/protected' => 'Área protegida (requer auth)',
            'POST /api/users' => 'Criar usuário (requer auth + CSRF)',
            'GET /api/admin' => 'Área admin (rate limit restrito)'
        ]
    ]);
});

// Informações públicas com rate limiting específico
$app->get('/public/info', [
    new RateLimitMiddleware([
        'maxRequests' => 100,
        'timeWindow' => 300  // 5 minutos
    ]),
    function(Request $req, Response $res) {
        $res->json([
            'info' => 'Esta é uma rota pública',
            'rate_limit' => '100 requisições por 5 minutos',
            'client_ip' => $req->getClientIP(),
            'user_agent' => $req->getHeader('User-Agent')
        ]);
    }
]);

// ================================
// ÁREA PROTEGIDA COM AUTENTICAÇÃO
// ================================

// Middleware de autenticação JWT opcional para grupo
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'sua_chave_secreta_super_forte_aqui',
    'optional' => true,
    'excludePaths' => ['/', '/public/*']
]));

// Rota protegida básica
$app->get('/api/protected', [
    new AuthMiddleware([
        'authMethods' => ['jwt'],
        'jwtSecret' => 'sua_chave_secreta_super_forte_aqui',
        'optional' => false
    ]),
    function(Request $req, Response $res) {
        $user = $req->user ?? null;
        $authMethod = $req->auth['method'] ?? 'none';

        $res->json([
            'message' => 'Área protegida acessada com sucesso!',
            'user' => $user,
            'authenticated_via' => $authMethod,
            'access_time' => date('c')
        ]);
    }
]);

// ================================
// OPERAÇÕES COM CSRF PROTECTION
// ================================

// Criar usuário - requer autenticação + proteção CSRF
$app->post('/api/users', [
    new AuthMiddleware([
        'authMethods' => ['jwt'],
        'jwtSecret' => 'sua_chave_secreta_super_forte_aqui'
    ]),
    new CsrfMiddleware([
        'tokenName' => '_token',
        'cookieName' => 'csrf_token'
    ]),
    function(Request $req, Response $res) {
        $userData = $req->body;
        $currentUser = $req->user;

        // Simulação de criação de usuário
        $newUser = [
            'id' => rand(1000, 9999),
            'name' => $userData['name'] ?? 'Nome Padrão',
            'email' => $userData['email'] ?? 'email@example.com',
            'created_at' => date('c'),
            'created_by' => $currentUser['id'] ?? null
        ];

        $res->status(201)->json([
            'message' => 'Usuário criado com sucesso!',
            'user' => $newUser,
            'csrf_protected' => true
        ]);
    }
]);

// Endpoint para obter token CSRF
$app->get('/api/csrf-token', function(Request $req, Response $res) {
    $csrf = new CsrfMiddleware();
    $token = $csrf->generateToken();

    // Set cookie com token
    setcookie('csrf_token', $token, time() + 3600, '/', '', false, true);

    $res->json([
        'csrf_token' => $token,
        'expires_in' => 3600
    ]);
});

// ================================
// ÁREA ADMINISTRATIVA COM RATE LIMITING RESTRITO
// ================================

$app->group('/api/admin', [
    'middleware' => [
        new AuthMiddleware([
            'authMethods' => ['jwt'],
            'jwtSecret' => 'sua_chave_secreta_super_forte_aqui',
            'roleCheck' => function($user) {
                return isset($user['role']) && $user['role'] === 'admin';
            }
        ]),
        new RateLimitMiddleware([
            'maxRequests' => 50,     // Apenas 50 requisições
            'timeWindow' => 3600,    // por hora
            'keyGenerator' => function($req) {
                return 'admin_' . ($req->user['id'] ?? 'unknown');
            }
        ])
    ]
], function($admin) {

    $admin->get('/dashboard', function(Request $req, Response $res) {
        $res->json([
            'message' => 'Dashboard administrativo',
            'admin_user' => $req->user,
            'system_stats' => [
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
                'server_time' => date('c')
            ]
        ]);
    });

    $admin->get('/users', function(Request $req, Response $res) {
        // Simulação de lista de usuários
        $users = [
            ['id' => 1, 'name' => 'Admin User', 'role' => 'admin'],
            ['id' => 2, 'name' => 'Regular User', 'role' => 'user'],
            ['id' => 3, 'name' => 'Guest User', 'role' => 'guest']
        ];

        $res->json([
            'users' => $users,
            'total' => count($users),
            'admin_access' => true
        ]);
    });
});

// ================================
// ENDPOINT PARA DEMONSTRAR MÚLTIPLOS MIDDLEWARES
// ================================

$app->post('/api/secure-upload', [
    // 1. Autenticação obrigatória
    new AuthMiddleware([
        'authMethods' => ['jwt'],
        'jwtSecret' => 'sua_chave_secreta_super_forte_aqui'
    ]),

    // 2. Proteção CSRF
    new CsrfMiddleware(),

    // 3. Rate limiting específico para uploads
    new RateLimitMiddleware([
        'maxRequests' => 5,
        'timeWindow' => 300,  // 5 minutos
        'keyGenerator' => function($req) {
            return 'upload_' . ($req->user['id'] ?? $req->getClientIP());
        }
    ]),

    // 4. Handler da rota
    function(Request $req, Response $res) {
        $user = $req->user;
        $files = $req->files ?? [];

        $res->json([
            'message' => 'Upload protegido recebido!',
            'user' => $user,
            'files_count' => count($files),
            'security_layers' => [
                'authentication' => 'JWT verified',
                'csrf_protection' => 'Token validated',
                'rate_limiting' => '5 uploads per 5 minutes',
                'cors_headers' => 'Applied',
                'security_headers' => 'Applied'
            ],
            'timestamp' => date('c')
        ]);
    }
]);

// ================================
// ENDPOINTS DE DEMONSTRAÇÃO
// ================================

// Simular login para obter JWT (apenas para demonstração)
$app->post('/auth/login', function(Request $req, Response $res) {
    $credentials = $req->body;

    // Validação simples para demonstração
    if (($credentials['email'] ?? '') === 'admin@test.com' &&
        ($credentials['password'] ?? '') === '123456') {

        // Gerar JWT token
        $payload = [
            'id' => 1,
            'email' => 'admin@test.com',
            'role' => 'admin',
            'iat' => time(),
            'exp' => time() + 3600 // 1 hora
        ];

        $token = \Express\Authentication\JWTHelper::encode($payload, 'sua_chave_secreta_super_forte_aqui');

        $res->json([
            'message' => 'Login realizado com sucesso!',
            'token' => $token,
            'user' => [
                'id' => $payload['id'],
                'email' => $payload['email'],
                'role' => $payload['role']
            ],
            'expires_in' => 3600
        ]);
    } else {
        $res->status(401)->json([
            'error' => 'Credenciais inválidas',
            'hint' => 'Use email: admin@test.com, password: 123456'
        ]);
    }
});

// Informações sobre middlewares aplicados
$app->get('/middlewares/info', function(Request $req, Response $res) {
    $res->json([
        'middlewares_aplicados' => [
            'global' => [
                'SecurityMiddleware' => 'Headers de segurança HTTP',
                'CorsMiddleware' => 'Cross-Origin Resource Sharing',
                'RateLimitMiddleware' => '1000 req/hora por IP'
            ],
            'auth_protected' => [
                'AuthMiddleware' => 'Autenticação JWT obrigatória'
            ],
            'csrf_protected' => [
                'CsrfMiddleware' => 'Proteção contra CSRF'
            ],
            'admin_area' => [
                'AuthMiddleware (role: admin)' => 'Autenticação + verificação de role',
                'RateLimitMiddleware (restrictive)' => '50 req/hora para admins'
            ]
        ],
        'como_testar' => [
            '1. Login' => 'POST /auth/login com email: admin@test.com, password: 123456',
            '2. Token CSRF' => 'GET /api/csrf-token',
            '3. Área protegida' => 'GET /api/protected com Authorization: Bearer {token}',
            '4. Upload seguro' => 'POST /api/secure-upload com auth + CSRF + rate limit'
        ]
    ]);
});

// ================================
// EXECUTAR APLICAÇÃO
// ================================

echo "\n🛡️ Iniciando Express PHP com demonstração de middlewares padrão...\n";
echo "📋 Endpoints disponíveis:\n";
echo "  • GET /                     - Informações da API\n";
echo "  • GET /public/info          - Informações públicas (rate limited)\n";
echo "  • GET /middlewares/info     - Informações sobre middlewares\n";
echo "  • POST /auth/login          - Login para obter JWT\n";
echo "  • GET /api/csrf-token       - Obter token CSRF\n";
echo "  • GET /api/protected        - Área protegida (JWT required)\n";
echo "  • POST /api/users           - Criar usuário (JWT + CSRF required)\n";
echo "  • GET /api/admin/dashboard  - Admin área (JWT admin + rate limited)\n";
echo "  • POST /api/secure-upload   - Upload seguro (JWT + CSRF + rate limited)\n\n";
echo "🔐 Credenciais de teste: admin@test.com / 123456\n";
echo "🌐 Acesse: http://localhost:8080\n\n";

$app->run();
