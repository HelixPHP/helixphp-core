<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middleware\Security\CorsMiddleware;

// Criar aplicação
$app = new ApiExpress('http://localhost:8000');

// Middleware CORS global
$app->use(CorsMiddleware::development());

// Middleware de log
$app->use(function($req, $res, $next) {
    echo "Request: {$req->method} {$req->pathCallable}\n";
    return $next($req, $res);
});

// Rota de teste
$app->get('/', function($req, $res) {
    return $res->json([
        'message' => 'Express-PHP Modular Framework',
        'version' => '2.0.0',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Rota com parâmetros
$app->get('/users/:id', function($req, $res) {
    $userId = $req->param('id');
    return $res->json([
        'user' => [
            'id' => $userId,
            'name' => "User #{$userId}",
            'email' => "user{$userId}@example.com"
        ]
    ]);
});

// Rota POST
$app->post('/users', function($req, $res) {
    $data = $req->body;
    return $res->status(201)->json([
        'message' => 'User created',
        'data' => $data
    ]);
});

// Grupo de rotas API
$apiRouter = $app->Router();
$apiRouter->get('/status', function($req, $res) {
    return $res->json(['status' => 'API is running']);
});

$apiRouter->get('/info', function($req, $res) {
    return $res->json([
        'framework' => 'Express-PHP',
        'version' => '2.0.0',
        'architecture' => 'Modular',
        'features' => [
            'Dependency Injection',
            'Configuration Management',
            'Middleware Pipeline',
            'HTTP Streaming',
            'JWT Authentication',
            'CORS Support'
        ]
    ]);
});

$app->use('/api', $apiRouter);

// Iniciar aplicação
$app->listen(8000);
