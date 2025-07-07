# 🔐 Autenticação Customizada

Guia prático para implementar métodos de autenticação personalizados no PivotPHP.

## 🚀 Conceitos Básicos

O PivotPHP permite criar sistemas de autenticação totalmente customizados através de callbacks no AuthMiddleware.

### Tipos de Autenticação Customizada

1. **Custom Auth** - Lógica completamente personalizada
2. **Bearer Auth** - Para API Keys, tokens customizados
3. **Basic Auth** - Com validação personalizada
4. **Múltiplos métodos** - Combinando diferentes estratégias

## 🛠️ Implementação Prática

### 1. Autenticação por API Key

```php
<?php
// Função de validação da API Key
function validateApiKey(string $apiKey): array|false
{
    // Simular busca no banco de dados
    $validKeys = [
        'app-mobile-v1' => [
            'id' => 1,
            'name' => 'App Mobile',
            'permissions' => ['read', 'write']
        ],
        'dashboard-admin' => [
            'id' => 2,
            'name' => 'Dashboard Admin',
            'permissions' => ['read', 'write', 'admin']
        ]
    ];

    return $validKeys[$apiKey] ?? false;
}

// Configurar middleware
$app->use(new AuthMiddleware([
    'authMethods' => ['bearer'],
    'bearerAuthCallback' => 'validateApiKey'
]));

// Uso na rota
$app->get('/api/data', function($req, $res) {
    $client = $req->user; // Dados retornados pela função de validação

    $res->json([
        'message' => 'Dados acessados com sucesso',
        'client' => $client['name'],
        'permissions' => $client['permissions']
    ]);
});
```

### 2. Autenticação por Sessão

```php
<?php
function validateSession(ServerRequestInterface $request): array|false
{
    // Verificar cookie de sessão
    $cookies = $request->getCookieParams();
    $sessionId = $cookies['session_id'] ?? null;

    if (!$sessionId) {
        return false;
    }

    // Validar sessão (exemplo com array, usar banco/redis em produção)
    $sessions = [
        'sess_123abc' => [
            'user_id' => 1,
            'username' => 'joao',
            'role' => 'admin',
            'expires' => time() + 3600
        ]
    ];

    $session = $sessions[$sessionId] ?? null;

    if (!$session || $session['expires'] < time()) {
        return false;
    }

    return $session;
}

// Configurar middleware
$app->use(new AuthMiddleware([
    'authMethods' => ['custom'],
    'customAuthCallback' => 'validateSession'
]));
```

### 3. Autenticação por Header Customizado

```php
<?php
function validateCustomHeader(ServerRequestInterface $request): array|false
{
    // Buscar header personalizado
    $authHeader = $request->getHeaderLine('X-App-Token');

    if (empty($authHeader)) {
        return false;
    }

    // Decodificar token personalizado (exemplo base64)
    $decoded = base64_decode($authHeader);
    $parts = explode(':', $decoded);

    if (count($parts) !== 3) {
        return false;
    }

    [$userId, $timestamp, $signature] = $parts;

    // Verificar se não expirou (30 minutos)
    if ((time() - (int)$timestamp) > 1800) {
        return false;
    }

    // Verificar assinatura
    $expectedSignature = hash_hmac('sha256', $userId . ':' . $timestamp, 'secret-key');

    if (!hash_equals($expectedSignature, $signature)) {
        return false;
    }

    // Buscar dados do usuário
    return [
        'user_id' => (int)$userId,
        'username' => 'user' . $userId,
        'auth_method' => 'custom_header'
    ];
}

$app->use(new AuthMiddleware([
    'authMethods' => ['custom'],
    'customAuthCallback' => 'validateCustomHeader'
]));
```

### 4. Autenticação Híbrida (Múltiplos Métodos)

```php
<?php
// Função para Basic Auth personalizada
function validateBasicAuth(string $username, string $password): array|false
{
    // Verificar contra banco de dados com hash
    $users = [
        'admin' => [
            'password_hash' => password_hash('secret123', PASSWORD_DEFAULT),
            'role' => 'admin',
            'permissions' => ['read', 'write', 'delete']
        ],
        'user' => [
            'password_hash' => password_hash('user123', PASSWORD_DEFAULT),
            'role' => 'user',
            'permissions' => ['read']
        ]
    ];

    $user = $users[$username] ?? null;

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return false;
    }

    return [
        'username' => $username,
        'role' => $user['role'],
        'permissions' => $user['permissions'],
        'auth_method' => 'basic'
    ];
}

// Configurar múltiplos métodos
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'bearer'],
    'jwtSecret' => 'jwt-secret-key',
    'basicAuthCallback' => 'validateBasicAuth',
    'bearerAuthCallback' => 'validateApiKey'
]));

// Rota que aceita qualquer método
$app->get('/api/profile', function($req, $res) {
    $user = $req->user;

    $res->json([
        'user' => $user,
        'authenticated_via' => $user['auth_method'] ?? 'unknown'
    ]);
});
```

## 🔒 Implementação com Classes

### Estratégia de Autenticação Personalizada

```php
<?php
class DatabaseAuthStrategy
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function validateUser(string $token): array|false
    {
        try {
            $stmt = $this->pdo->prepare(
                'SELECT u.*, s.expires_at
                 FROM users u
                 JOIN sessions s ON u.id = s.user_id
                 WHERE s.token = ? AND s.expires_at > NOW()'
            );

            $stmt->execute([$token]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return false;
            }

            return [
                'user_id' => $result['id'],
                'username' => $result['username'],
                'email' => $result['email'],
                'role' => $result['role'],
                'auth_method' => 'database_session'
            ];

        } catch (PDOException $e) {
            error_log('Auth error: ' . $e->getMessage());
            return false;
        }
    }
}

// Usar a estratégia
$authStrategy = new DatabaseAuthStrategy($pdo);

$app->use(new AuthMiddleware([
    'authMethods' => ['bearer'],
    'bearerAuthCallback' => [$authStrategy, 'validateUser']
]));
```

## 🚦 Autenticação Condicional

### Por Rota Específica

```php
<?php
// Middleware de autenticação diferente para rotas admin
$adminAuth = new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'admin-secret',
    'customAuthCallback' => function($request) {
        $user = $this->validateJWT($request);

        // Verificar se é admin
        if (!$user || $user['role'] !== 'admin') {
            return false;
        }

        return $user;
    }
]);

// Aplicar apenas em rotas admin
$app->group('/admin', function($group) {
    $group->get('/users', function($req, $res) {
        $res->json(['users' => getAllUsers()]);
    });

    $group->post('/users', function($req, $res) {
        $user = createUser($req->body);
        $res->status(201)->json(['user' => $user]);
    });
}, [$adminAuth]);
```

### Autenticação Opcional

```php
<?php
function optionalAuth(ServerRequestInterface $request): array|false
{
    // Tentar autenticar, mas não falhar se não conseguir
    $authHeader = $request->getHeaderLine('Authorization');

    if (empty($authHeader)) {
        return ['guest' => true, 'auth_method' => 'none'];
    }

    // Tentar validar token
    if (strpos($authHeader, 'Bearer ') === 0) {
        $token = substr($authHeader, 7);
        $user = validateToken($token);

        if ($user) {
            return $user;
        }
    }

    // Se falhou, retornar como guest
    return ['guest' => true, 'auth_method' => 'failed'];
}

$app->use(new AuthMiddleware([
    'authMethods' => ['custom'],
    'customAuthCallback' => 'optionalAuth',
    'required' => false // Não falhar se auth falhar
]));

$app->get('/api/content', function($req, $res) {
    $user = $req->user;

    if ($user['guest'] ?? false) {
        $content = getPublicContent();
    } else {
        $content = getPersonalizedContent($user);
    }

    $res->json(['content' => $content]);
});
```

## 🧪 Testando Autenticação Customizada

```php
<?php
class CustomAuthTest extends TestCase
{
    public function test_api_key_authentication(): void
    {
        $app = new Application();

        $app->use(new AuthMiddleware([
            'authMethods' => ['bearer'],
            'bearerAuthCallback' => function($key) {
                return $key === 'valid-key' ? ['client' => 'test'] : false;
            }
        ]));

        $app->get('/test', function($req, $res) {
            $res->json(['user' => $req->user]);
        });

        // Teste com chave válida
        $response = $this->makeRequestWithAuth('GET', '/test', [], 'valid-key');
        $this->assertEquals(200, $response['status']);
        $this->assertEquals('test', $response['body']['user']['client']);

        // Teste com chave inválida
        $response = $this->makeRequestWithAuth('GET', '/test', [], 'invalid-key');
        $this->assertEquals(401, $response['status']);
    }

    public function test_session_authentication(): void
    {
        // Mock de sessão válida
        $_SESSION = ['user_id' => 1, 'username' => 'test'];

        $response = $this->makeRequestWithSession('GET', '/protected');

        $this->assertEquals(200, $response['status']);
        $this->assertEquals('test', $response['body']['user']['username']);
    }
}
```

## 💡 Dicas de Boas Práticas

### ✅ O que Fazer
- **Sempre validar e sanitizar** dados de entrada
- **Use conexões seguras** (HTTPS) para credenciais
- **Implemente rate limiting** para tentativas de login
- **Log tentativas de autenticação** para auditoria
- **Use hashing seguro** para senhas (password_hash/password_verify)
- **Valide expirações** de tokens/sessões
- **Retorne erros genéricos** para não vazar informações

### ❌ O que Evitar
- **Não armazene credenciais** em texto plano
- **Não confie apenas** em dados do frontend
- **Não use algoritmos** de hash fracos (MD5, SHA1)
- **Não exponha informações** sensíveis em logs
- **Não reutilize** tokens/sessões indefinidamente

## 🔐 Exemplo Completo: Sistema de Autenticação com Cache

```php
<?php
class CachedAuthSystem
{
    private Redis $redis;
    private PDO $pdo;

    public function __construct(Redis $redis, PDO $pdo)
    {
        $this->redis = $redis;
        $this->pdo = $pdo;
    }

    public function authenticate(string $token): array|false
    {
        // Tentar buscar no cache primeiro
        $cached = $this->redis->get("auth:token:{$token}");

        if ($cached) {
            return json_decode($cached, true);
        }

        // Buscar no banco se não estiver em cache
        $stmt = $this->pdo->prepare(
            'SELECT u.id, u.username, u.email, u.role
             FROM users u
             JOIN access_tokens t ON u.id = t.user_id
             WHERE t.token = ? AND t.expires_at > NOW() AND t.is_active = 1'
        );

        $stmt->execute([$token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return false;
        }

        $userData = [
            'user_id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'],
            'auth_method' => 'token'
        ];

        // Cache por 5 minutos
        $this->redis->setex("auth:token:{$token}", 300, json_encode($userData));

        return $userData;
    }

    public function revokeToken(string $token): bool
    {
        // Remover do cache
        $this->redis->del("auth:token:{$token}");

        // Desativar no banco
        $stmt = $this->pdo->prepare('UPDATE access_tokens SET is_active = 0 WHERE token = ?');
        return $stmt->execute([$token]);
    }
}

// Uso
$authSystem = new CachedAuthSystem($redis, $pdo);

$app->use(new AuthMiddleware([
    'authMethods' => ['bearer'],
    'bearerAuthCallback' => [$authSystem, 'authenticate']
]));

// Rota para revogar token
$app->post('/auth/revoke', function($req, $res) use ($authSystem) {
    $token = $req->getHeaderLine('Authorization');
    $token = str_replace('Bearer ', '', $token);

    $authSystem->revokeToken($token);

    $res->json(['message' => 'Token revogado com sucesso']);
});
```

---

*🔐 Com autenticação customizada, você tem controle total sobre como os usuários acessam sua aplicação!*
