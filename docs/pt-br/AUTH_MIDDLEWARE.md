# Middleware de Autenticação - Express PHP

> **Atenção:** Todos os exemplos e recomendações de uso de autenticação neste projeto seguem o padrão PSR-15. Utilize apenas o middleware `Express\Http\Psr15\Middleware\AuthMiddleware`. Middlewares antigos (não-PSR-15) estão **depreciados** e não são mais suportados.

## 🔐 Visão Geral

O sistema de autenticação do Express PHP oferece múltiplos métodos de autenticação integrados com middleware robusto e configurável, seguindo PSR-15.

## 🛡️ Tipos de Autenticação Suportados

### 1. JWT (JSON Web Tokens)
Autenticação baseada em tokens JWT com suporte completo a claims customizados.

```php
use Express\Http\Psr15\Middleware\AuthMiddleware;

// Configuração básica JWT
$app->use(AuthMiddleware::jwt('seu-secret-super-seguro'));
```

### 2. Bearer Token
Autenticação simples com tokens de acesso.

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['bearer'],
    'bearerTokens' => [
        'abc123' => ['user_id' => 1, 'role' => 'admin'],
        'def456' => ['user_id' => 2, 'role' => 'user']
    ],
    'excludePaths' => ['/public']
]));
```

### 3. Basic Authentication
Autenticação HTTP Basic para casos simples.

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['basic'],
    'basicAuthCallback' => function($username, $password) {
        return $username === 'admin' && $password === 'password123';
    },
    'realm' => 'Express PHP API'
]));
```

### 4. Autenticação Customizada
Sistema flexível para implementar sua própria lógica de autenticação.

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['custom'],
    'customAuthCallback' => function($request) {
        $token = $request->header('X-API-Key');
        if (!$token) return false;
        $user = validateApiKey($token);
        if ($user) {
            $request->user = $user;
            return true;
        }
        return false;
    }
]));
```

## 🔧 Configuração Avançada

### JWT com Refresh Tokens
```php
use Express\Authentication\JWTHelper;

$app->post('/login', function($req, $res) {
    // Validar credenciais
    $user = validateUser($req->body['username'], $req->body['password']);
    if ($user) {
        $accessToken = JWTHelper::encode([
            'user_id' => $user['id'],
            'role' => $user['role'],
            'exp' => time() + 3600 // 1 hora
        ], 'seu-secret');
        $refreshToken = JWTHelper::createRefreshToken($user['id']);
        return $res->json([
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'expires_in' => 3600
        ]);
    }
    return $res->status(401)->json(['error' => 'Credenciais inválidas']);
});
```

### Middleware Condicional
```php
// Proteger apenas rotas administrativas
$app->group('/admin', function() use ($app) {
    $app->use(new AuthMiddleware([
        'authMethods' => ['jwt'],
        'jwtSecret' => 'admin-secret',
        'requiredClaims' => ['role' => 'admin']
    ]));
    $app->get('/users', function($req, $res) {
        return ['admin_users' => getUsersList()];
    });
});
```

## 🎯 Funcionalidades Avançadas

### 1. Validação de Claims
```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'secret',
    'validateClaims' => function($payload) {
        if (isset($payload['user_id'])) {
            $user = getUserById($payload['user_id']);
            return $user && $user['active'];
        }
        return false;
    }
]));
```

### 2. Rate Limiting por Usuário
```php
use Express\Http\Psr15\Middleware\RateLimitMiddleware;

$app->use(new AuthMiddleware(['jwtSecret' => 'secret', 'authMethods' => ['jwt']]));
$app->use(new RateLimitMiddleware([
    'maxRequests' => 100,
    'timeWindow' => 3600,
    'keyGenerator' => function($req) {
        return 'user_' . ($req->user['id'] ?? 'anonymous');
    }
]));
```

### 3. Logs de Autenticação
```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'secret',
    'onSuccess' => function($req, $payload) {
        logAuthSuccess($payload['user_id'], $req->ip());
    },
    'onFailure' => function($req, $error) {
        logAuthFailure($error, $req->ip());
    }
]));
```

## 🔍 Tratamento de Erros

### Respostas de Erro Customizadas
```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'secret',
    'errorHandler' => function($error, $req, $res) {
        return $res->status(401)->json([
            'error' => 'Não autorizado',
            'code' => $error['code'],
            'message' => $error['message'],
            'timestamp' => time()
        ]);
    }
]));
```

## 📊 Performance e Benchmarks

O sistema de autenticação foi otimizado para performance:

- **JWT Validation:** 50.000+ tokens/segundo
- **Cache de Tokens:** Hit ratio de 95%
- **Overhead por Request:** < 0.1ms
- **Memory Usage:** ~512 bytes por token

## 🛡️ Segurança

### Boas Práticas Implementadas

1. **Secrets Seguros:** Use secrets com pelo menos 256 bits
2. **Rotação de Tokens:** Implemente refresh tokens
3. **Rate Limiting:** Proteja contra ataques de força bruta
4. **Logs Detalhados:** Monitore tentativas de autenticação
5. **HTTPS Only:** Use apenas em conexões seguras

### Exemplo de Configuração Segura
```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => hash('sha256', $_ENV['JWT_SECRET']),
    'algorithm' => 'HS256',
    'leeway' => 30,
    'maxAge' => 3600,
    'requiredClaims' => ['iss', 'aud', 'exp'],
    'validateClaims' => function($payload) {
        return $payload['iss'] === 'sua-aplicacao' &&
               $payload['aud'] === 'api-v1';
    }
]));
```

> **Nota:** Para novas aplicações, utilize:
> ```php
> use Express\Http\Psr15\Middleware\AuthMiddleware;
> $app->use(new AuthMiddleware(['jwtSecret' => 'sua_chave', 'authMethods' => ['jwt']]));
> ```

## 📚 Exemplos Práticos

Veja mais exemplos em:
- [examples/example_auth.php](../../examples/example_auth.php)
- [examples/example_auth_simple.php](../../examples/example_auth_simple.php)
- [examples/snippets/auth_snippets.php](../../examples/snippets/auth_snippets.php)
