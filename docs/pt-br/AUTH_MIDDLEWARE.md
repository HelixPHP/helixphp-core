# 🔐 Middleware de Autenticação - Express PHP

## Visão Geral

O Express PHP agora inclui um **middleware de autenticação automática** robusto e flexível que suporta múltiplos métodos de autenticação nativamente:

- ✅ **JWT (JSON Web Tokens)** - com suporte à biblioteca Firebase ou implementação nativa
- ✅ **Basic Authentication** - autenticação HTTP básica
- ✅ **Bearer Token** - tokens personalizados
- ✅ **API Key** - via header ou query parameter
- ✅ **Autenticação Customizada** - callback personalizado
- ✅ **Múltiplos Métodos** - permite vários métodos em uma única configuração
- ✅ **Caminhos Excluídos** - exclui rotas específicas da autenticação
- ✅ **Modo Flexível** - autenticação opcional

## 🚀 Instalação

### Dependências Opcionais

Para JWT com máxima compatibilidade, instale:

```bash
composer require firebase/php-jwt
```

> **Nota:** O Express PHP inclui uma implementação nativa de JWT HS256, então a biblioteca Firebase é opcional.

## 📖 Uso Básico

### 1. JWT Authentication

```php
use Express\Middlewares\Security\AuthMiddleware;
use Express\Helpers\JWTHelper;

// Configuração simples
$app->use(AuthMiddleware::jwt('sua_chave_secreta'));

// Login para obter token
$app->post('/login', function($req, $res) {
    // Validar credenciais...

    $token = JWTHelper::encode([
        'user_id' => $userId,
        'username' => $username,
        'role' => $userRole
    ], 'sua_chave_secreta', [
        'expiresIn' => 3600 // 1 hora
    ]);

    $res->json(['token' => $token]);
});

// Usar: Authorization: Bearer <token>
```

### 2. Basic Authentication

```php
function validateUser($username, $password) {
    // Consultar banco de dados
    $users = ['admin' => 'senha123'];

    return isset($users[$username]) && $users[$username] === $password
        ? ['id' => 1, 'username' => $username] : false;
}

$app->use(AuthMiddleware::basic('validateUser'));

// Usar: Authorization: Basic <base64(username:password)>
```

### 3. API Key Authentication

```php
function validateApiKey($key) {
    $validKeys = [
        'key123456' => ['name' => 'App Mobile', 'permissions' => ['read', 'write']],
        'service_key' => ['name' => 'Integration', 'permissions' => ['read']]
    ];

    return $validKeys[$key] ?? false;
}

$app->use(AuthMiddleware::apiKey('validateApiKey'));

// Usar: X-API-Key: key123456 OU ?api_key=key123456
```

### 4. Múltiplos Métodos

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => getenv('JWT_SECRET'),
    'basicAuthCallback' => 'validateUser',
    'apiKeyCallback' => 'validateApiKey',
    'allowMultiple' => true,
    'excludePaths' => ['/public', '/login', '/docs']
]));
```

## 🛠️ Configurações Avançadas

### Configuração Completa

```php
$app->use(new AuthMiddleware([
    // Métodos de autenticação suportados
    'authMethods' => ['jwt', 'basic', 'bearer', 'apikey', 'custom'],

    // Configurações JWT
    'jwtSecret' => 'sua_chave_jwt_super_secreta',
    'jwtAlgorithm' => 'HS256',

    // Callbacks de validação
    'basicAuthCallback' => 'validateBasicAuth',
    'bearerTokenCallback' => 'validateBearerToken',
    'apiKeyCallback' => 'validateApiKey',
    'customAuthCallback' => 'customAuthentication',

    // Configurações de API Key
    'headerName' => 'X-API-Key',
    'queryParam' => 'api_key',

    // Comportamento
    'requireAuth' => true,
    'allowMultiple' => false,
    'userProperty' => 'user', // $req->user

    // Caminhos excluídos
    'excludePaths' => ['/health', '/docs', '/public'],

    // Mensagens personalizadas
    'errorMessages' => [
        'missing' => 'Autenticação requerida',
        'invalid' => 'Credenciais inválidas',
        'expired' => 'Token expirado'
    ]
]));
```

### Autenticação por Rota

```php
// Rota específica com JWT
$app->get('/admin/users',
    AuthMiddleware::jwt('chave_secreta'),
    function($req, $res) {
        // Apenas usuários com JWT válido
        $res->json(['users' => [...], 'admin' => $req->user]);
    }
);

// Rota específica com API Key
$app->get('/api/data',
    AuthMiddleware::apiKey('validateApiKey'),
    function($req, $res) {
        // Apenas clientes com API Key válida
        $res->json(['data' => [...], 'client' => $req->user]);
    }
);
```

### Modo Flexível (Autenticação Opcional)

```php
$app->use(AuthMiddleware::flexible([
    'authMethods' => ['jwt', 'apikey'],
    'jwtSecret' => 'chave_secreta',
    'apiKeyCallback' => 'validateApiKey'
]));

$app->get('/mixed', function($req, $res) {
    if (isset($req->auth) && $req->auth['authenticated']) {
        // Usuário autenticado
        $message = "Olá, " . $req->user['username'];
    } else {
        // Usuário anônimo
        $message = "Olá, visitante";
    }

    $res->json(['message' => $message]);
});
```

## 🔧 JWTHelper - Utilitário JWT

### Métodos Principais

```php
use Express\Helpers\JWTHelper;

// Gerar token
$token = JWTHelper::encode($payload, $secret, $options);

// Validar token
$isValid = JWTHelper::isValid($token, $secret);

// Decodificar token
$payload = JWTHelper::decode($token, $secret);

// Verificar expiração
$isExpired = JWTHelper::isExpired($token, $leeway);

// Gerar chave secreta
$secret = JWTHelper::generateSecret(32);

// Refresh tokens
$refreshToken = JWTHelper::createRefreshToken($userId, $secret);
$refreshData = JWTHelper::validateRefreshToken($refreshToken, $secret);
```

### Sistema de Refresh Token

```php
// Login com refresh token
$app->post('/login', function($req, $res) {
    // Validar credenciais...

    $accessToken = JWTHelper::encode([
        'user_id' => $userId
    ], 'jwt_secret', ['expiresIn' => 900]); // 15 min

    $refreshToken = JWTHelper::createRefreshToken(
        $userId,
        'refresh_secret'
    ); // 30 dias

    $res->json([
        'access_token' => $accessToken,
        'refresh_token' => $refreshToken,
        'expires_in' => 900
    ]);
});

// Renovar token
$app->post('/refresh', function($req, $res) {
    $refreshToken = $req->body['refresh_token'];
    $payload = JWTHelper::validateRefreshToken($refreshToken, 'refresh_secret');

    if ($payload) {
        $newToken = JWTHelper::encode([
            'user_id' => $payload['user_id']
        ], 'jwt_secret');

        $res->json(['access_token' => $newToken]);
    } else {
        $res->status(401)->json(['error' => 'Invalid refresh token']);
    }
});
```

## 📊 Acessando Dados do Usuário

Após autenticação bem-sucedida:

```php
$app->get('/profile', function($req, $res) {
    // Dados do usuário autenticado
    $user = $req->user;

    // Informações da autenticação
    $authMethod = $req->auth['method']; // 'jwt', 'basic', 'apikey', etc.
    $authenticated = $req->auth['authenticated']; // true

    $res->json([
        'user' => $user,
        'auth_method' => $authMethod,
        'message' => "Olá, {$user['username']}"
    ]);
});
```

## 🔒 Validação de Roles e Permissões

```php
// Middleware para verificar role de admin
function requireAdmin($req, $res, $next) {
    if (!$req->user || $req->user['role'] !== 'admin') {
        $res->status(403)->json(['error' => 'Admin role required']);
        return;
    }
    $next();
}

// Middleware para verificar permissões específicas
function requirePermission($permission) {
    return function($req, $res, $next) use ($permission) {
        $userPermissions = $req->user['permissions'] ?? [];

        if (!in_array($permission, $userPermissions)) {
            $res->status(403)->json(['error' => "Permission '{$permission}' required"]);
            return;
        }

        $next();
    };
}

// Usar em rotas
$app->get('/admin/panel',
    AuthMiddleware::jwt('chave_secreta'),
    'requireAdmin',
    function($req, $res) {
        $res->json(['message' => 'Admin panel']);
    }
);

$app->delete('/api/data/:id',
    AuthMiddleware::apiKey('validateApiKey'),
    requirePermission('delete'),
    function($req, $res) {
        // Apenas usuários com permissão 'delete'
        $res->json(['message' => 'Data deleted']);
    }
);
```

## 🧪 Testando

### cURL Examples

```bash
# JWT
curl -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..." \
     http://localhost/api/protected

# Basic Auth
curl -u admin:password123 \
     http://localhost/api/protected

# API Key (Header)
curl -H "X-API-Key: key123456" \
     http://localhost/api/protected

# API Key (Query)
curl "http://localhost/api/protected?api_key=key123456"
```

### Executar Testes

```bash
# Teste completo de autenticação
composer test:auth

# Ou diretamente
php test/auth_test.php

# Testes unitários
./vendor/bin/phpunit tests/Security/AuthMiddlewareTest.php
./vendor/bin/phpunit tests/Helpers/JWTHelperTest.php
```

## 📋 Exemplos Completos

- **Exemplo básico:** [`examples/example_auth.php`](../../examples/example_auth.php)
- **Snippets rápidos:** [`examples/snippets/auth_snippets.php`](examples/snippets/auth_snippets.php)

## 🔧 Configuração de Produção

### Variáveis de Ambiente

```env
JWT_SECRET=sua_chave_jwt_super_secreta_em_producao
REFRESH_SECRET=sua_chave_refresh_super_secreta
JWT_EXPIRE_TIME=3600
REFRESH_EXPIRE_TIME=2592000
```

### Configuração Recomendada

```php
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'apikey'],
    'jwtSecret' => getenv('JWT_SECRET'),
    'apiKeyCallback' => 'validateApiKey',
    'excludePaths' => ['/health', '/docs'],
    'requireAuth' => true,
    'allowMultiple' => false,
    'errorMessages' => [
        'missing' => 'Access token required',
        'invalid' => 'Invalid authentication credentials',
        'expired' => 'Authentication token expired'
    ]
]));
```

## 💡 Dicas de Segurança

1. **Sempre use HTTPS em produção**
2. **Use chaves secretas fortes e aleatórias**
3. **Configure tokens com tempo de expiração apropriado**
4. **Implemente refresh tokens para sessões longas**
5. **Valide permissões específicas além da autenticação**
6. **Use rate limiting em rotas de login**
7. **Monitore tentativas de acesso inválidas**

---

## 🆕 Novos Recursos

### ✨ Implementados

- ✅ Middleware de autenticação automática com múltiplos métodos
- ✅ Helper JWT com implementação nativa HS256
- ✅ Suporte a Firebase JWT (opcional)
- ✅ Autenticação Basic Auth
- ✅ Autenticação Bearer Token
- ✅ Autenticação API Key (header/query)
- ✅ Autenticação customizada via callback
- ✅ Sistema de refresh tokens
- ✅ Caminhos excluídos configuráveis
- ✅ Modo flexível (autenticação opcional)
- ✅ Mensagens de erro customizáveis
- ✅ Testes unitários completos
- ✅ Documentação abrangente
- ✅ Exemplos práticos

### 🔄 Compatibilidade

O novo middleware é totalmente compatível com a estrutura existente do Express PHP e não quebra nenhuma funcionalidade anterior.

---

**Express PHP** - Microframework moderno e seguro para APIs PHP! 🚀
