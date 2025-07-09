# Guia do Objeto Response

O objeto `Response` é responsável por construir e enviar respostas HTTP com **suporte híbrido PSR-7**. Ele oferece métodos para definir status, cabeçalhos e enviar dados em diferentes formatos, mantendo compatibilidade total com Express.js e implementando completamente a interface PSR-7 `ResponseInterface`.

## 🔄 Compatibilidade Híbrida

O Response PivotPHP oferece:
- ✅ **API Express.js** completa para facilidade de uso
- ✅ **Interface PSR-7** completa para compatibilidade com middleware PSR-15
- ✅ **Lazy Loading** para performance otimizada
- ✅ **Object Pooling** para melhor utilização de memória
- ✅ **Imutabilidade** respeitando padrões PSR-7
- ✅ **Streaming** para respostas em tempo real

## Estrutura do Response

### Propriedades Principais

- **statusCode**: Código de status HTTP (padrão: 200)
- **headers**: Array de cabeçalhos da resposta
- **body**: Corpo da resposta
- **isStreaming**: Indica se está em modo streaming
- **testMode**: Modo de teste (não faz output direto)

## 🔄 Usando PSR-7 (ResponseInterface)

O Response implementa completamente a interface PSR-7:

```php
use Psr\Http\Message\ResponseInterface;

function myMiddleware($request, ResponseInterface $response, $next) {
    // Métodos PSR-7 padrão
    $status = $response->getStatusCode();
    $headers = $response->getHeaders();
    $body = $response->getBody();
    
    // Métodos PSR-7 (imutável)
    $newResponse = $response->withStatus(200)
                            ->withHeader('X-Custom', 'value')
                            ->withBody($stream);
    
    return $next($request, $newResponse);
}
```

### Imutabilidade PSR-7

Métodos `with*()` retornam **nova instância** respeitando imutabilidade:

```php
$response1 = new Response();
$response2 = $response1->withStatus(404);
$response3 = $response2->withHeader('Content-Type', 'application/json');

// $response1, $response2, $response3 são objetos DIFERENTES
// Imutabilidade garantida - nenhum objeto original é modificado
```

### Lazy Loading PSR-7

O objeto PSR-7 interno é criado apenas quando necessário:

```php
$response = new Response();
// ✅ Rápido - sem PSR-7 criado ainda

$response->status(200);       // ✅ Express.js - sem PSR-7
$response->json(['ok' => true]); // ✅ Express.js - sem PSR-7

$response->getStatusCode();   // ✅ PSR-7 criado agora (lazy loading)
$response->getHeaders();      // ✅ Reutiliza PSR-7 já criado
```

### Object Pooling

Use a factory otimizada para melhor performance:

```php
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

// Configurar pooling
OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
]);

// Criar responses com pooling
$response = OptimizedHttpFactory::createResponse();
// Objetos PSR-7 internos são reutilizados automaticamente
```

## Definindo Status HTTP

### Método `status(int $code)`

```php
$app->get('/api/users/:id', function($req, $res) {
    $id = $req->param('id');
    $user = findUser($id);

    if (!$user) {
        return $res->status(404)->json(['error' => 'User not found']);
    }

    return $res->status(200)->json($user);
});

// Códigos de status comuns
$res->status(200); // OK
$res->status(201); // Created
$res->status(204); // No Content
$res->status(400); // Bad Request
$res->status(401); // Unauthorized
$res->status(403); // Forbidden
$res->status(404); // Not Found
$res->status(422); // Unprocessable Entity
$res->status(500); // Internal Server Error
```

### Status com Mensagens Específicas

```php
$app->post('/api/users', function($req, $res) {
    $data = $req->body;

    // Validação
    if (empty($data->email)) {
        return $res->status(400)->json([
            'error' => 'Bad Request',
            'message' => 'Email is required'
        ]);
    }

    if (userExists($data->email)) {
        return $res->status(409)->json([
            'error' => 'Conflict',
            'message' => 'User already exists'
        ]);
    }

    $user = createUser($data);
    return $res->status(201)->json([
        'message' => 'User created successfully',
        'user' => $user
    ]);
});
```

## Definindo Cabeçalhos

### Método `header(string $name, string $value)`

```php
$app->get('/api/data', function($req, $res) {
    return $res
        ->header('X-API-Version', 'v1.0')
        ->header('X-Rate-Limit', '1000')
        ->header('X-Rate-Remaining', '999')
        ->header('Cache-Control', 'no-cache')
        ->json(['data' => 'example']);
});
```

### Cabeçalhos de CORS

```php
$app->get('/api/public', function($req, $res) {
    return $res
        ->header('Access-Control-Allow-Origin', '*')
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->json(['message' => 'Public API']);
});
```

### Cabeçalhos de Cache

```php
$app->get('/api/static-data', function($req, $res) {
    return $res
        ->header('Cache-Control', 'public, max-age=3600') // 1 hora
        ->header('Expires', gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT')
        ->header('ETag', '"' . md5('static-data-v1') . '"')
        ->json(['data' => getStaticData()]);
});
```

### Cabeçalhos de Segurança

```php
$app->get('/app/*', function($req, $res) {
    return $res
        ->header('X-Content-Type-Options', 'nosniff')
        ->header('X-Frame-Options', 'DENY')
        ->header('X-XSS-Protection', '1; mode=block')
        ->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        ->header('Content-Security-Policy', "default-src 'self'")
        ->html('<h1>Secure Page</h1>');
});
```

## Enviando Respostas JSON

### Método `json($data)`

```php
$app->get('/api/users', function($req, $res) {
    $users = getAllUsers();

    return $res->json([
        'data' => $users,
        'count' => count($users),
        'timestamp' => time()
    ]);
});
```

### JSON com Estruturas Complexas

```php
$app->get('/api/dashboard', function($req, $res) {
    return $res->json([
        'user' => [
            'id' => 1,
            'name' => 'João Silva',
            'email' => 'joao@example.com',
            'preferences' => [
                'theme' => 'dark',
                'language' => 'pt-BR'
            ]
        ],
        'stats' => [
            'orders' => 5,
            'revenue' => 1500.50,
            'last_login' => '2025-01-01T10:00:00Z'
        ],
        'meta' => [
            'api_version' => '1.0',
            'server_time' => date('c'),
            'timezone' => 'America/Sao_Paulo'
        ]
    ]);
});
```

### Tratamento de Erros JSON

```php
$app->post('/api/orders', function($req, $res) {
    try {
        $order = createOrder($req->body);

        return $res->status(201)->json([
            'success' => true,
            'order' => $order,
            'message' => 'Order created successfully'
        ]);

    } catch (ValidationException $e) {
        return $res->status(422)->json([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $e->getErrors()
        ]);

    } catch (Exception $e) {
        return $res->status(500)->json([
            'success' => false,
            'error' => 'Internal server error',
            'message' => 'Something went wrong'
        ]);
    }
});
```

## Enviando Respostas de Texto

### Método `text($text)`

```php
$app->get('/api/health', function($req, $res) {
    return $res->text('OK');
});

$app->get('/api/version', function($req, $res) {
    return $res
        ->header('Content-Type', 'text/plain; charset=utf-8')
        ->text('PivotPHP Framework');
});
```

### Texto com Formatação

```php
$app->get('/logs/latest', function($req, $res) {
    $logs = getLatestLogs(10);

    $output = "Latest 10 log entries:\n";
    $output .= str_repeat("=", 50) . "\n";

    foreach ($logs as $log) {
        $output .= "[{$log['timestamp']}] {$log['level']}: {$log['message']}\n";
    }

    return $res->text($output);
});
```

## Enviando Respostas HTML

### Método `html($html)`

```php
$app->get('/', function($req, $res) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>PivotPHP</title>
        <meta charset="utf-8">
    </head>
    <body>
        <h1>Welcome to PivotPHP!</h1>
        <p>Your application is running.</p>
    </body>
    </html>';

    return $res->html($html);
});
```

### HTML Dinâmico

```php
$app->get('/dashboard', function($req, $res) {
    $user = getCurrentUser();

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Dashboard - {$user['name']}</title>
    </head>
    <body>
        <header>
            <h1>Welcome, {$user['name']}!</h1>
        </header>
        <main>
            <p>Last login: {$user['last_login']}</p>
            <p>Account status: {$user['status']}</p>
        </main>
    </body>
    </html>";

    return $res->html($html);
});
```

### Integrando com Templates

```php
function renderTemplate($template, $data = []) {
    $templatePath = __DIR__ . "/templates/{$template}.html";

    if (!file_exists($templatePath)) {
        throw new Exception("Template not found: {$template}");
    }

    $content = file_get_contents($templatePath);

    // Substituição simples de variáveis
    foreach ($data as $key => $value) {
        $content = str_replace("{{" . $key . "}}", $value, $content);
    }

    return $content;
}

$app->get('/profile/:id', function($req, $res) {
    $user = getUserById($req->param('id'));

    if (!$user) {
        return $res->status(404)->html('<h1>User not found</h1>');
    }

    $html = renderTemplate('user-profile', [
        'name' => $user['name'],
        'email' => $user['email'],
        'joined' => $user['created_at']
    ]);

    return $res->html($html);
});
```

## Streaming de Dados

### Método `startStream(?string $contentType = null)`

```php
$app->get('/api/stream/logs', function($req, $res) {
    // Iniciar streaming
    $res->startStream('text/plain');

    // Enviar dados em tempo real
    for ($i = 1; $i <= 10; $i++) {
        $res->write("Log entry #{$i} - " . date('Y-m-d H:i:s') . "\n");
        sleep(1); // Simular delay
    }

    return $res;
});
```

### Método `write(string $data, bool $flush = true)`

```php
$app->get('/api/export/csv', function($req, $res) {
    $res->startStream('text/csv')
        ->header('Content-Disposition', 'attachment; filename="export.csv"');

    // Cabeçalho CSV
    $res->write("ID,Name,Email,Created\n");

    // Dados em chunks
    $users = getUsersInBatches(1000); // Processar em lotes

    foreach ($users as $batch) {
        foreach ($batch as $user) {
            $line = "{$user['id']},{$user['name']},{$user['email']},{$user['created_at']}\n";
            $res->write($line);
        }
    }

    return $res;
});
```

### Método `writeJson($data, bool $flush = true)`

```php
$app->get('/api/stream/notifications', function($req, $res) {
    $res->startStream('application/json');

    // Enviar notificações em tempo real
    while (true) {
        $notifications = getNewNotifications();

        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                $res->writeJson([
                    'type' => 'notification',
                    'data' => $notification,
                    'timestamp' => time()
                ]);
            }
        }

        sleep(5); // Verificar a cada 5 segundos
    }
});
```

## Streaming de Arquivos

### Método `streamFile(string $filePath, array $headers = [])`

```php
$app->get('/download/:filename', function($req, $res) {
    $filename = $req->param('filename');
    $filePath = "/uploads/{$filename}";

    if (!file_exists($filePath)) {
        return $res->status(404)->json(['error' => 'File not found']);
    }

    return $res->streamFile($filePath, [
        'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        'Content-Description' => 'File Transfer'
    ]);
});
```

### Download com Controle de Acesso

```php
$app->get('/private/files/:id', function($req, $res) {
    $fileId = $req->param('id');
    $user = getCurrentUser($req);

    if (!$user || !canAccessFile($user, $fileId)) {
        return $res->status(403)->json(['error' => 'Access denied']);
    }

    $file = getFileById($fileId);

    if (!$file) {
        return $res->status(404)->json(['error' => 'File not found']);
    }

    return $res->streamFile($file['path'], [
        'Content-Disposition' => "attachment; filename=\"{$file['name']}\"",
        'X-Download-Count' => incrementDownloadCount($fileId)
    ]);
});
```

### Streaming de Imagens com Cache

```php
$app->get('/images/:id', function($req, $res) {
    $imageId = $req->param('id');
    $image = getImageById($imageId);

    if (!$image) {
        return $res->status(404)->json(['error' => 'Image not found']);
    }

    // Verificar ETag para cache
    $etag = md5_file($image['path']);
    $clientEtag = $req->headers->get('If-None-Match');

    if ($clientEtag === '"' . $etag . '"') {
        return $res->status(304); // Not Modified
    }

    return $res->streamFile($image['path'], [
        'Cache-Control' => 'public, max-age=86400', // 24 horas
        'ETag' => '"' . $etag . '"'
    ]);
});
```

## Respostas Especiais

### Redirect

```php
$app->get('/old-path', function($req, $res) {
    return $res
        ->status(301)
        ->header('Location', '/new-path')
        ->text('Moved Permanently');
});

// Helper para redirect
function redirect($res, $url, $status = 302) {
    return $res
        ->status($status)
        ->header('Location', $url)
        ->text('Redirecting...');
}

$app->get('/login', function($req, $res) {
    if (isLoggedIn($req)) {
        return redirect($res, '/dashboard');
    }

    return $res->html(getLoginForm());
});
```

### No Content

```php
$app->delete('/api/users/:id', function($req, $res) {
    $id = $req->param('id');

    if (!userExists($id)) {
        return $res->status(404)->json(['error' => 'User not found']);
    }

    deleteUser($id);

    // Resposta sem conteúdo
    return $res->status(204);
});
```

### Resposta Vazia com Cabeçalhos

```php
$app->options('/api/*', function($req, $res) {
    return $res
        ->status(200)
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->header('Access-Control-Max-Age', '86400')
        ->text('');
});
```

## Middleware de Response

### Middleware de Timing

```php
$app->use(function($req, $res, $next) {
    $start = microtime(true);

    $response = $next();

    $duration = (microtime(true) - $start) * 1000; // em ms
    $res->header('X-Response-Time', number_format($duration, 2) . 'ms');

    return $response;
});
```

### Middleware de Compressão

```php
$app->use(function($req, $res, $next) {
    $response = $next();

    $acceptEncoding = $req->headers->get('Accept-Encoding', '');

    if (str_contains($acceptEncoding, 'gzip') && !headers_sent()) {
        $body = $res->getBody();

        if (strlen($body) > 1024) { // Só comprimir se > 1KB
            $compressed = gzencode($body);
            $res->header('Content-Encoding', 'gzip');
            $res->header('Content-Length', strlen($compressed));
            echo $compressed;
            return $response;
        }
    }

    return $response;
});
```

## Debug e Desenvolvimento

### Response Inspector

```php
$app->use(function($req, $res, $next) {
    $response = $next();

    if ($_ENV['APP_DEBUG'] ?? false) {
        error_log("Response Debug: " . json_encode([
            'status' => $res->getStatusCode(),
            'headers' => $res->getHeaders(),
            'body_length' => strlen($res->getBody())
        ]));
    }

    return $response;
});
```

### Modo de Teste

```php
// Em testes
$app = new Application();
$response = new Response();
$response->setTestMode(true); // Não faz output direto

$response->json(['test' => true]);

// Verificar resultado
assertEquals(200, $response->getStatusCode());
assertEquals('{"test":true}', $response->getBody());
assertArrayHasKey('Content-Type', $response->getHeaders());
```

## Padrões e Boas Práticas

### API RESTful

```php
// GET - Listar recursos
$app->get('/api/users', function($req, $res) {
    return $res->json(['data' => getAllUsers()]);
});

// GET - Obter recurso específico
$app->get('/api/users/:id', function($req, $res) {
    $user = getUserById($req->param('id'));
    return $user ? $res->json($user) : $res->status(404)->json(['error' => 'Not found']);
});

// POST - Criar recurso
$app->post('/api/users', function($req, $res) {
    $user = createUser($req->body);
    return $res->status(201)->json($user);
});

// PUT - Atualizar recurso completo
$app->put('/api/users/:id', function($req, $res) {
    $user = updateUser($req->param('id'), $req->body);
    return $res->json($user);
});

// PATCH - Atualizar recurso parcialmente
$app->patch('/api/users/:id', function($req, $res) {
    $user = partialUpdateUser($req->param('id'), $req->body);
    return $res->json($user);
});

// DELETE - Remover recurso
$app->delete('/api/users/:id', function($req, $res) {
    deleteUser($req->param('id'));
    return $res->status(204);
});
```

### Tratamento de Erros Consistente

```php
function errorResponse($res, $code, $message, $details = null) {
    $error = [
        'error' => true,
        'message' => $message,
        'code' => $code
    ];

    if ($details) {
        $error['details'] = $details;
    }

    return $res->status($code)->json($error);
}

$app->get('/api/users/:id', function($req, $res) {
    try {
        $user = getUserById($req->param('id'));

        if (!$user) {
            return errorResponse($res, 404, 'User not found');
        }

        return $res->json($user);

    } catch (Exception $e) {
        return errorResponse($res, 500, 'Internal server error', [
            'trace_id' => uniqid()
        ]);
    }
});
```

O objeto Response é flexível e poderoso, permitindo enviar qualquer tipo de resposta HTTP. Use os métodos apropriados para cada tipo de conteúdo e sempre configure os cabeçalhos corretos para uma melhor experiência do cliente.
