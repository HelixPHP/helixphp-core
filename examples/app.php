<?php
// Exemplo completo do Express PHP - Versão Modular 2.0
// Este arquivo demonstra todas as funcionalidades principais do framework

// Configuração para URLs amigáveis
if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $scriptNameClean = preg_replace('/\.php$/', '', $scriptName);

    if (strpos($requestUri, $scriptNameClean) === 0) {
        $pathInfo = substr($requestUri, strlen($scriptNameClean));
        $pathInfo = strtok($pathInfo, '?');
        $_SERVER['PATH_INFO'] = $pathInfo ?: '/';
    }
}

// Autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Usar a nova arquitetura modular
use Express\ApiExpress;
use Express\Middleware\Security\CorsMiddleware;
use Express\Middleware\Security\AuthMiddleware;
use Express\Middleware\Security\SecurityMiddleware;

// Construir base URL de forma robusta
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
$script = $_SERVER['SCRIPT_NAME'] ?? '';
$baseUrl = $protocol . $host . $script;

if (substr($baseUrl, -4) === '.php') {
    $baseUrl = substr($baseUrl, 0, -4);
}
if (substr($baseUrl, -1) === '/') {
    $baseUrl = substr($baseUrl, 0, -1);
}

// Criar aplicação
$app = new ApiExpress($baseUrl);

// ========================================
// MIDDLEWARES GLOBAIS
// ========================================

// CORS para desenvolvimento
$app->use(CorsMiddleware::development());

// Headers de segurança
$app->use(new SecurityMiddleware());

// Middleware de log
$app->use(function ($request, $response, $next) {
    $response->header('X-Powered-By', 'Express-PHP-2.0');
    return $next($request, $response);
});

// ========================================
// ROTAS PRINCIPAIS
// ========================================

// Rota de boas-vindas
$app->get('/', function($request, $response) {
    return $response->json([
        'message' => 'Express-PHP 2.0 - Framework Modular',
        'version' => '2.0.0',
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoints' => [
            '/' => 'Esta página',
            '/docs' => 'Documentação da API',
            '/user/{id}' => 'Informações do usuário',
            '/admin/logs' => 'Logs administrativos',
            '/upload' => 'Upload de arquivos',
            '/blog/posts' => 'Posts do blog'
        ]
    ]);
});

// Rota de documentação
$app->get('/docs', function($request, $response) {
    return $response->json([
        'api' => 'Express-PHP 2.0',
        'documentation' => 'API Documentation',
        'endpoints' => [
            '/user/:id' => [
                'method' => 'GET',
                'description' => 'Get user by ID',
                'params' => ['id' => 'User ID (integer)']
            ],
            '/admin/logs' => [
                'method' => 'GET',
                'description' => 'Get system logs',
                'auth' => 'Required'
            ],
            '/upload' => [
                'method' => 'POST',
                'description' => 'Upload files',
                'content-type' => 'multipart/form-data'
            ],
            '/blog/posts' => [
                'method' => 'GET|POST',
                'description' => 'Blog posts management'
            ]
        ]
    ]);
});

// ========================================
// SUB-ROUTERS
// ========================================

// Router de usuários
$userRouter = $app->router();
$userRouter->use(function ($request, $response, $next) {
    $response->header('X-Module', 'User');
    return $next($request, $response);
});

$userRouter->get('/:id', function ($request, $response) {
    $userId = $request->param('id');

    if (!is_numeric($userId)) {
        return $response->status(400)->json(['error' => 'ID deve ser numérico']);
    }

    return $response->json([
        'user' => [
            'id' => (int)$userId,
            'name' => "Usuário #$userId",
            'email' => "user$userId@example.com",
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
});

// Router administrativo
$adminRouter = $app->router();
$adminRouter->use(function ($request, $response, $next) {
    $response->header('X-Module', 'Admin');
    return $next($request, $response);
});

$adminRouter->get('/logs', function ($request, $response) {
    return $response->json([
        'logs' => [
            ['level' => 'info', 'message' => 'System started', 'time' => date('Y-m-d H:i:s')],
            ['level' => 'debug', 'message' => 'User accessed admin panel', 'time' => date('Y-m-d H:i:s')],
            ['level' => 'warning', 'message' => 'High memory usage detected', 'time' => date('Y-m-d H:i:s')]
        ]
    ]);
});

// Router de upload
$uploadRouter = $app->router();
$uploadRouter->use(function ($request, $response, $next) {
    $response->header('X-Module', 'Upload');
    return $next($request, $response);
});

$uploadRouter->post('/', function ($request, $response) {
    if (empty($request->files)) {
        return $response->status(400)->json(['error' => 'Nenhum arquivo enviado']);
    }

    $arquivos = [];
    foreach ($request->files as $file) {
        $arquivos[] = [
            'name' => $file['name'],
            'type' => $file['type'],
            'size' => $file['size']
        ];
    }

    return $response->json([
        'message' => 'Arquivos recebidos com sucesso',
        'arquivos' => $arquivos
    ]);
});

// Router de blog
$blogRouter = $app->router();
$blogRouter->use(function ($request, $response, $next) {
    $response->header('X-Module', 'Blog');
    return $next($request, $response);
});

$blogRouter->get('/posts', function ($request, $response) {
    return $response->json([
        'posts' => [
            ['id' => 1, 'title' => 'Primeiro Post', 'content' => 'Conteúdo do primeiro post'],
            ['id' => 2, 'title' => 'Segundo Post', 'content' => 'Conteúdo do segundo post'],
            ['id' => 3, 'title' => 'Express-PHP 2.0', 'content' => 'Framework moderno e modular']
        ]
    ]);
});

$blogRouter->post('/posts', function ($request, $response) {
    $data = $request->body;

    if (!isset($data['title']) || !isset($data['content'])) {
        return $response->status(400)->json(['error' => 'Title and content are required']);
    }

    return $response->status(201)->json([
        'message' => 'Post criado com sucesso',
        'post' => [
            'id' => rand(100, 999),
            'title' => $data['title'],
            'content' => $data['content'],
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);
});

// ========================================
// REGISTRAR SUB-ROUTERS
// ========================================

$app->use('/user', $userRouter);
$app->use('/admin', $adminRouter);
$app->use('/upload', $uploadRouter);
$app->use('/blog', $blogRouter);

// ========================================
// ROTA DE TESTE
// ========================================

$app->get('/test', function($request, $response) {
    return $response->json([
        'message' => 'Sistema funcionando perfeitamente!',
        'framework' => 'Express-PHP 2.0',
        'architecture' => 'Modular',
        'features' => [
            'Sub-routers funcionais',
            'Middlewares de segurança',
            'Documentação automática',
            'Sistema modular'
        ]
    ]);
});

// ========================================
// INICIAR APLICAÇÃO
// ========================================

if (php_sapi_name() === 'cli-server') {
    $app->listen(8000);
} else {
    $app->run();
}
?>
