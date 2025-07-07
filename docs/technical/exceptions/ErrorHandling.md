# Tratamento de Erros no PivotPHP

O PivotPHP oferece um sistema robusto de tratamento de erros que captura exceções automaticamente e fornece respostas padronizadas. O sistema é flexível e permite customização completa.

## Como Funciona o Tratamento de Erros

### Sistema Automático

O PivotPHP automaticamente:
- **Captura exceções** não tratadas
- **Converte erros PHP** em exceções
- **Retorna respostas JSON** padronizadas
- **Registra logs** de erros
- **Aplica diferentes comportamentos** baseado no ambiente

### Fluxo de Tratamento

1. **Exceção é lançada** em qualquer lugar do código
2. **Error Handler** captura a exceção
3. **Determina o tipo** de resposta (JSON, HTML, etc.)
4. **Aplica formatação** apropriada
5. **Envia resposta** ao cliente
6. **Registra log** do erro

## Configuração de Error Handling

### Configuração Básica

```php
// config/app.php
return [
    'debug' => env('APP_DEBUG', false),
    'error_reporting' => E_ALL,
    'display_errors' => env('APP_DEBUG', false),
    'log_errors' => true,
    'error_log' => storage_path('logs/error.log')
];
```

### Handler Customizado

```php
use PivotPHP\Core\Core\Application;

$app = new Application();

// Configurar handler customizado
$app->setErrorHandler(function($error, $req, $res) {
    // Log do erro
    $logger = app('logger');
    $logger->error($error->getMessage(), [
        'exception' => get_class($error),
        'file' => $error->getFile(),
        'line' => $error->getLine(),
        'trace' => $error->getTraceAsString()
    ]);

    // Resposta baseada no tipo de erro
    if ($error instanceof HttpException) {
        return $res->status($error->getStatusCode())
                  ->json([
                      'error' => true,
                      'message' => $error->getMessage(),
                      'code' => $error->getStatusCode()
                  ]);
    }

    // Erro genérico
    $code = 500;
    $message = app('config')->get('app.debug')
        ? $error->getMessage()
        : 'Internal Server Error';

    return $res->status($code)->json([
        'error' => true,
        'message' => $message,
        'code' => $code
    ]);
});
```

## Tratamento por Tipo de Erro

### Erros HTTP (4xx, 5xx)

```php
$app->get('/api/users/:id', function($req, $res) {
    $id = $req->param('id');
    $user = getUserById($id);

    if (!$user) {
        // Lança exceção HTTP 404
        throw new HttpException(404, 'User not found');
    }

    return $res->json($user);
});
```

### Erros de Validação

```php
$app->post('/api/users', function($req, $res) {
    $data = $req->body;

    // Validação
    $errors = validateUserData($data);

    if (!empty($errors)) {
        throw new ValidationException('Validation failed', $errors);
    }

    $user = createUser($data);
    return $res->status(201)->json($user);
});

// Handler específico para ValidationException
$app->setErrorHandler(function($error, $req, $res) {
    if ($error instanceof ValidationException) {
        return $res->status(422)->json([
            'error' => true,
            'message' => 'Validation failed',
            'errors' => $error->getErrors()
        ]);
    }

    // Outros erros...
});
```

### Erros de Autorização

```php
$app->use('/api/admin', function($req, $res, $next) {
    $user = getCurrentUser($req);

    if (!$user) {
        throw new AuthenticationException('Authentication required');
    }

    if (!$user->isAdmin()) {
        throw new AuthorizationException('Admin access required');
    }

    return $next();
});
```

### Erros de Banco de Dados

```php
$app->get('/api/reports', function($req, $res) {
    try {
        $reports = getReports();
        return $res->json($reports);

    } catch (PDOException $e) {
        throw new DatabaseException(
            'Database error occurred',
            previous: $e
        );
    }
});

// Handler para DatabaseException
$app->setErrorHandler(function($error, $req, $res) {
    if ($error instanceof DatabaseException) {
        $logger = app('logger');
        $logger->critical('Database error', [
            'message' => $error->getMessage(),
            'previous' => $error->getPrevious()?->getMessage()
        ]);

        return $res->status(500)->json([
            'error' => true,
            'message' => 'A database error occurred',
            'code' => 500
        ]);
    }

    // Outros erros...
});
```

## Error Handlers Avançados

### Handler com Diferentes Formatos

```php
$app->setErrorHandler(function($error, $req, $res) {
    $acceptHeader = $req->headers->get('Accept', '');

    // Determinar formato da resposta
    if (str_contains($acceptHeader, 'application/json') ||
        str_starts_with($req->path(), '/api/')) {
        return handleJsonError($error, $res);
    }

    if (str_contains($acceptHeader, 'text/html')) {
        return handleHtmlError($error, $res);
    }

    // Padrão: JSON
    return handleJsonError($error, $res);
});

function handleJsonError($error, $res) {
    $code = $error instanceof HttpException ? $error->getStatusCode() : 500;
    $message = app('config')->get('app.debug') ? $error->getMessage() : 'An error occurred';

    return $res->status($code)->json([
        'error' => true,
        'message' => $message,
        'code' => $code,
        'timestamp' => date('c')
    ]);
}

function handleHtmlError($error, $res) {
    $code = $error instanceof HttpException ? $error->getStatusCode() : 500;

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Error {$code}</title>
        <style>
            body { font-family: sans-serif; margin: 40px; }
            .error { background: #f8f8f8; padding: 20px; border-left: 4px solid #e74c3c; }
        </style>
    </head>
    <body>
        <div class='error'>
            <h1>Error {$code}</h1>
            <p>" . htmlspecialchars($error->getMessage()) . "</p>
        </div>
    </body>
    </html>";

    return $res->status($code)->html($html);
}
```

### Handler com Stack Trace

```php
$app->setErrorHandler(function($error, $req, $res) {
    $debug = app('config')->get('app.debug', false);

    $response = [
        'error' => true,
        'message' => $error->getMessage(),
        'code' => $error instanceof HttpException ? $error->getStatusCode() : 500
    ];

    if ($debug) {
        $response['debug'] = [
            'exception' => get_class($error),
            'file' => $error->getFile(),
            'line' => $error->getLine(),
            'trace' => explode("\n", $error->getTraceAsString())
        ];
    }

    return $res->status($response['code'])->json($response);
});
```

### Handler com Rate Limiting

```php
$app->setErrorHandler(function($error, $req, $res) {
    $ip = $req->ip();
    $cacheKey = "error_rate_limit:{$ip}";
    $cache = app('cache');

    // Contar erros por IP
    $errorCount = $cache->get($cacheKey, 0);
    $cache->set($cacheKey, $errorCount + 1, 300); // 5 minutos

    // Se muitos erros, retornar 429
    if ($errorCount > 10) {
        return $res->status(429)->json([
            'error' => true,
            'message' => 'Too many errors from this IP',
            'code' => 429
        ]);
    }

    // Tratamento normal do erro
    return handleError($error, $res);
});
```

## Middleware de Error Handling

### Middleware de Logging

```php
$app->use(function($req, $res, $next) {
    try {
        return $next();
    } catch (Exception $e) {
        $logger = app('logger');

        $logger->error('Request failed', [
            'method' => $req->method,
            'path' => $req->path(),
            'user_agent' => $req->userAgent(),
            'ip' => $req->ip(),
            'error' => $e->getMessage(),
            'trace_id' => uniqid()
        ]);

        throw $e; // Re-lançar para o handler principal
    }
});
```

### Middleware de Notificação

```php
$app->use(function($req, $res, $next) {
    try {
        return $next();
    } catch (Exception $e) {
        // Notificar erros críticos
        if ($e instanceof CriticalException ||
            ($e instanceof HttpException && $e->getStatusCode() >= 500)) {

            $notifier = app('notifier');
            $notifier->sendAlert([
                'type' => 'critical_error',
                'message' => $e->getMessage(),
                'context' => [
                    'url' => $req->path(),
                    'method' => $req->method,
                    'user_id' => $req->user->id ?? null
                ]
            ]);
        }

        throw $e;
    }
});
```

## Error Pages Customizadas

### Páginas de Erro por Status

```php
$app->setErrorHandler(function($error, $req, $res) {
    $code = $error instanceof HttpException ? $error->getStatusCode() : 500;

    // Para requisições web (não API)
    if (!str_starts_with($req->path(), '/api/')) {
        $errorPage = getErrorPage($code);

        if ($errorPage) {
            return $res->status($code)->html($errorPage);
        }
    }

    // Fallback para JSON
    return $res->status($code)->json([
        'error' => true,
        'message' => $error->getMessage(),
        'code' => $code
    ]);
});

function getErrorPage($code) {
    $pages = [
        404 => 'errors/404.html',
        500 => 'errors/500.html',
        403 => 'errors/403.html'
    ];

    $template = $pages[$code] ?? 'errors/generic.html';
    $path = __DIR__ . "/templates/{$template}";

    if (file_exists($path)) {
        return file_get_contents($path);
    }

    return null;
}
```

### Páginas de Erro Dinâmicas

```php
function renderErrorPage($code, $message, $debug = false) {
    $title = getErrorTitle($code);

    return "
    <!DOCTYPE html>
    <html>
    <head>
        <title>{$title}</title>
        <style>
            body { font-family: system-ui; margin: 0; padding: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            h1 { color: #e74c3c; margin-bottom: 20px; }
            .message { background: #fff5f5; padding: 15px; border-left: 4px solid #e74c3c; margin: 20px 0; }
            .debug { background: #f8f9fa; padding: 15px; border: 1px solid #dee2e6; margin-top: 20px; font-family: monospace; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>{$code} - {$title}</h1>
            <div class='message'>{$message}</div>
            " . ($debug ? "<div class='debug'>" . htmlspecialchars(debug_backtrace()) . "</div>" : "") . "
            <p><a href='/'>← Back to home</a></p>
        </div>
    </body>
    </html>";
}

function getErrorTitle($code) {
    return match($code) {
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Page Not Found',
        405 => 'Method Not Allowed',
        422 => 'Unprocessable Entity',
        429 => 'Too Many Requests',
        500 => 'Internal Server Error',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        default => 'Error'
    };
}
```

## Debugging e Desenvolvimento

### Handler de Debug

```php
if (app('config')->get('app.debug')) {
    $app->setErrorHandler(function($error, $req, $res) {
        return $res->status($error instanceof HttpException ? $error->getStatusCode() : 500)
                  ->json([
                      'error' => true,
                      'message' => $error->getMessage(),
                      'exception' => get_class($error),
                      'file' => $error->getFile(),
                      'line' => $error->getLine(),
                      'trace' => $error->getTrace(),
                      'request' => [
                          'method' => $req->method,
                          'path' => $req->path(),
                          'params' => $req->params,
                          'query' => $req->query,
                          'body' => $req->body
                      ]
                  ]);
    });
}
```

### Error Reporting

```php
$app->get('/debug/errors', function($req, $res) {
    if (!app('config')->get('app.debug')) {
        throw new HttpException(404);
    }

    $errors = getRecentErrors();

    return $res->json([
        'total_errors' => count($errors),
        'errors' => $errors,
        'error_rate' => calculateErrorRate(),
        'top_errors' => getTopErrors()
    ]);
});
```

## Boas Práticas

### 1. Logging Estruturado

```php
$app->setErrorHandler(function($error, $req, $res) {
    $logger = app('logger');

    $context = [
        'exception' => get_class($error),
        'message' => $error->getMessage(),
        'file' => $error->getFile(),
        'line' => $error->getLine(),
        'request_id' => $req->headers->get('X-Request-ID', uniqid()),
        'user_id' => $req->user->id ?? null,
        'ip' => $req->ip(),
        'user_agent' => $req->userAgent(),
        'method' => $req->method,
        'path' => $req->path()
    ];

    if ($error instanceof HttpException && $error->getStatusCode() < 500) {
        $logger->warning('Client error', $context);
    } else {
        $logger->error('Server error', $context);
    }

    // Retornar resposta...
});
```

### 2. Sanitização de Dados Sensíveis

```php
function sanitizeForLogging($data) {
    $sensitive = ['password', 'token', 'secret', 'key', 'authorization'];

    if (is_array($data) || is_object($data)) {
        foreach ($data as $key => $value) {
            if (in_array(strtolower($key), $sensitive)) {
                $data->$key = '[REDACTED]';
            }
        }
    }

    return $data;
}
```

### 3. Error Boundaries

```php
$app->use('/api', function($req, $res, $next) {
    try {
        return $next();
    } catch (Exception $e) {
        // Log específico para API
        $apiLogger = app('api_logger');
        $apiLogger->error('API Error', [
            'endpoint' => $req->path(),
            'error' => $e->getMessage()
        ]);

        throw $e;
    }
});
```

O sistema de tratamento de erros do PivotPHP é flexível e poderoso, permitindo desde configurações simples até handlers complexos que atendem às necessidades específicas da sua aplicação.
