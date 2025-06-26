<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Core\Application;
use Express\Http\Request;
use Express\Http\Response;
use Express\Validation\Validator;
use Express\Cache\FileCache;
use Express\Events\EventDispatcher;
use Express\Logging\Logger;
use Express\Logging\FileHandler;
use Express\Support\Str;
use Express\Support\Arr;

// Inicializar aplicação
$app = new Application();

// Configurar logging
$logger = new Logger();
$logger->addHandler(new FileHandler(__DIR__ . '/../logs/app.log'));

// Configurar cache
$cache = new FileCache(__DIR__ . '/../cache');

// Configurar eventos
$events = new EventDispatcher();

// Configurar serviços no container
$app->getContainer()->bind('logger', $logger);
$app->getContainer()->bind('cache', $cache);
$app->getContainer()->bind('events', $events);

// Event listeners
$events->listen('user.created', function($event) use ($logger) {
    $logger->info('Novo usuário criado', $event->getData());
});

$events->listen('user.login', function($event) use ($logger) {
    $logger->info('Login realizado', $event->getData());
});

// Middleware para logging de requests
$app->use(function(Request $request, Response $response, callable $next) use ($logger) {
    $start = microtime(true);

    $logger->info('Request iniciado', [
        'method' => $request->getMethod(),
        'uri' => $request->getUri(),
        'ip' => $request->getClientIp()
    ]);

    $result = $next($request, $response);

    $duration = round((microtime(true) - $start) * 1000, 2);
    $logger->info('Request finalizado', [
        'duration' => $duration . 'ms',
        'status' => $response->getStatusCode()
    ]);

    return $result;
});

// API de usuários com validação e cache
$app->post('/api/users', function(Request $request, Response $response) use ($app) {
    $data = $request->getJsonData();

    // Validação
    $validator = Validator::make($data, [
        'name' => 'required|string|min:2|max:100',
        'email' => 'required|email',
        'age' => 'integer|min:18|max:120'
    ], [
        'name.required' => 'O nome é obrigatório',
        'name.min' => 'O nome deve ter pelo menos 2 caracteres',
        'email.required' => 'O email é obrigatório',
        'email.email' => 'O email deve ser válido'
    ]);

    if (!$validator->validate($data)) {
        return $response->status(422)->json([
            'error' => 'Dados inválidos',
            'errors' => $validator->getErrors()
        ]);
    }

    // Simular criação do usuário
    $user = [
        'id' => random_int(1000, 9999),
        'name' => $data['name'],
        'email' => $data['email'],
        'age' => $data['age'] ?? null,
        'slug' => Str::kebab(Str::ascii($data['name'])),
        'created_at' => date('Y-m-d H:i:s')
    ];

    // Cache do usuário
    $cache = $app->getContainer()->resolve('cache');
    $cache->set("user:{$user['id']}", $user, 3600); // 1 hora

    // Disparar evento
    $events = $app->getContainer()->resolve('events');
    $events->dispatch('user.created', $user);

    return $response->status(201)->json([
        'message' => 'Usuário criado com sucesso',
        'user' => $user
    ]);
});

// Obter usuário com cache
$app->get('/api/users/:id', function(Request $request, Response $response) use ($app) {
    $id = $request->getParam('id');
    $cache = $app->getContainer()->resolve('cache');

    // Tentar obter do cache
    $user = $cache->get("user:{$id}");

    if ($user) {
        return $response->json([
            'user' => $user,
            'cached' => true
        ]);
    }

    // Simular busca no banco
    $user = [
        'id' => (int)$id,
        'name' => 'Usuário Exemplo',
        'email' => 'exemplo@teste.com',
        'slug' => 'usuario-exemplo',
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 day'))
    ];

    // Armazenar no cache
    $cache->set("user:{$id}", $user, 3600);

    return $response->json([
        'user' => $user,
        'cached' => false
    ]);
});

// Login com eventos
$app->post('/api/login', function(Request $request, Response $response) use ($app) {
    $data = $request->getJsonData();

    // Validação simples
    $validator = Validator::make($data, [
        'email' => 'required|email',
        'password' => 'required|min:6'
    ]);

    if (!$validator->validate($data)) {
        return $response->status(400)->json([
            'error' => 'Dados inválidos',
            'errors' => $validator->getErrors()
        ]);
    }

    // Simular autenticação
    if ($data['email'] === 'admin@teste.com' && $data['password'] === '123456') {
        $events = $app->getContainer()->resolve('events');
        $events->dispatch('user.login', [
            'email' => $data['email'],
            'ip' => $request->getClientIp(),
            'timestamp' => time()
        ]);

        return $response->json([
            'message' => 'Login realizado com sucesso',
            'token' => 'fake-jwt-token-' . Str::random(32)
        ]);
    }

    return $response->status(401)->json([
        'error' => 'Credenciais inválidas'
    ]);
});

// Utilitários de string
$app->get('/api/utils/string', function(Request $request, Response $response) {
    $text = $request->getQuery('text', 'Express PHP Framework');

    return $response->json([
        'original' => $text,
        'camel' => Str::camel($text),
        'snake' => Str::snake($text),
        'kebab' => Str::kebab($text),
        'studly' => Str::studly($text),
        'limit' => Str::limit($text, 10),
        'ascii' => Str::ascii($text),
        'random' => Str::random(16)
    ]);
});

// Utilitários de array
$app->get('/api/utils/array', function(Request $request, Response $response) {
    $data = [
        'user' => [
            'profile' => [
                'name' => 'João',
                'age' => 30,
                'address' => [
                    'city' => 'São Paulo',
                    'country' => 'Brasil'
                ]
            ]
        ],
        'settings' => [
            'theme' => 'dark',
            'language' => 'pt-BR'
        ]
    ];

    return $response->json([
        'original' => $data,
        'operations' => [
            'get_name' => Arr::get($data, 'user.profile.name'),
            'get_city' => Arr::get($data, 'user.profile.address.city'),
            'has_theme' => Arr::has($data, 'settings.theme'),
            'has_missing' => Arr::has($data, 'user.profile.email'),
            'only_user' => Arr::only($data, ['user']),
            'except_settings' => Arr::except($data, ['settings']),
            'flattened' => Arr::flatten($data)
        ]
    ]);
});

// Cache info
$app->get('/api/cache/info', function(Request $request, Response $response) use ($app) {
    $cache = $app->getContainer()->resolve('cache');

    // Teste de operações de cache
    $cache->set('test_key', 'test_value', 60);

    return $response->json([
        'cache_test' => [
            'set_value' => 'test_value',
            'get_value' => $cache->get('test_key'),
            'has_key' => $cache->has('test_key'),
            'has_missing' => $cache->has('missing_key')
        ]
    ]);
});

// Documentação da API
$app->get('/api/docs', function(Request $request, Response $response) {
    $docs = [
        'title' => 'Express-PHP Advanced Example API',
        'version' => '2.0.0',
        'description' => 'API demonstrando os novos módulos avançados',
        'endpoints' => [
            'POST /api/users' => 'Criar usuário com validação',
            'GET /api/users/:id' => 'Obter usuário (com cache)',
            'POST /api/login' => 'Fazer login (dispara eventos)',
            'GET /api/utils/string' => 'Utilitários de string',
            'GET /api/utils/array' => 'Utilitários de array',
            'GET /api/cache/info' => 'Informações do cache',
            'GET /api/docs' => 'Esta documentação'
        ],
        'modules' => [
            'Validation' => 'Sistema de validação de dados',
            'Cache' => 'Sistema de cache em arquivo',
            'Events' => 'Sistema de eventos',
            'Logging' => 'Sistema de logging',
            'Support' => 'Utilitários (Str, Arr)',
            'Database' => 'Conexão com banco de dados (disponível)'
        ]
    ];

    return $response->json($docs);
});

// Página inicial
$app->get('/', function(Request $request, Response $response) {
    return $response->html('
        <!DOCTYPE html>
        <html>
        <head>
            <title>Express-PHP Advanced Example</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 40px; }
                .container { max-width: 800px; margin: 0 auto; }
                .endpoint { background: #f5f5f5; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .method { font-weight: bold; color: #2196F3; }
                code { background: #e8e8e8; padding: 2px 5px; border-radius: 3px; }
            </style>
        </head>
        <body>
            <div class="container">
                <h1>Express-PHP Framework - Módulos Avançados</h1>
                <p>Esta aplicação demonstra os novos módulos avançados do Express-PHP.</p>

                <h2>Endpoints Disponíveis</h2>

                <div class="endpoint">
                    <span class="method">GET</span> <code>/api/docs</code>
                    <p>Documentação completa da API</p>
                </div>

                <div class="endpoint">
                    <span class="method">POST</span> <code>/api/users</code>
                    <p>Criar usuário com validação completa</p>
                    <pre>{"name": "João", "email": "joao@teste.com", "age": 25}</pre>
                </div>

                <div class="endpoint">
                    <span class="method">GET</span> <code>/api/users/123</code>
                    <p>Obter usuário por ID (sistema de cache)</p>
                </div>

                <div class="endpoint">
                    <span class="method">POST</span> <code>/api/login</code>
                    <p>Login com sistema de eventos</p>
                    <pre>{"email": "admin@teste.com", "password": "123456"}</pre>
                </div>

                <div class="endpoint">
                    <span class="method">GET</span> <code>/api/utils/string?text=Express PHP</code>
                    <p>Utilitários de string (camel, snake, kebab, etc.)</p>
                </div>

                <div class="endpoint">
                    <span class="method">GET</span> <code>/api/utils/array</code>
                    <p>Utilitários de array (get, set, has, flatten, etc.)</p>
                </div>

                <div class="endpoint">
                    <span class="method">GET</span> <code>/api/cache/info</code>
                    <p>Informações e testes do sistema de cache</p>
                </div>

                <h2>Módulos Implementados</h2>
                <ul>
                    <li><strong>Validation:</strong> Sistema completo de validação de dados</li>
                    <li><strong>Cache:</strong> Cache em arquivo com TTL</li>
                    <li><strong>Events:</strong> Sistema de eventos com prioridades</li>
                    <li><strong>Logging:</strong> Sistema de logging estruturado</li>
                    <li><strong>Support:</strong> Helpers utilitários (Str, Arr)</li>
                    <li><strong>Database:</strong> Conexão PDO simplificada</li>
                </ul>

                <p><strong>Logs:</strong> Verifique o arquivo <code>logs/app.log</code> para ver os logs.</p>
            </div>
        </body>
        </html>
    ');
});

// Executar aplicação
$app->run();
