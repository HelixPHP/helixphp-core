# Guia do Router

O Router é o sistema de roteamento do PivotPHP, responsável por registrar, organizar e encontrar rotas HTTP de forma otimizada.

## Conceitos Fundamentais

### Estrutura do Router

O Router oferece:
- **Registro de rotas** por método HTTP
- **Grupos de rotas** com prefixos e middlewares
- **Cache e otimizações** integradas
- **Matching otimizado** de rotas
- **Suporte a parâmetros** dinâmicos

### Métodos HTTP Suportados

- `GET` - Obter recursos
- `POST` - Criar recursos
- `PUT` - Atualizar recursos completos
- `PATCH` - Atualizar recursos parciais
- `DELETE` - Remover recursos
- `OPTIONS` - Verificar métodos permitidos
- `HEAD` - Como GET, mas só cabeçalhos

## Registro de Rotas Básicas

### Rotas Simples

```php
use PivotPHP\Core\Routing\Router;

// Rota GET simples
Router::get('/', function($req, $res) {
    return $res->json(['message' => 'Hello World']);
});

// Rota POST
Router::post('/users', function($req, $res) {
    $data = $req->body;
    // Criar usuário
    return $res->status(201)->json($user);
});

// Rota PUT
Router::put('/users/:id', function($req, $res) {
    $id = $req->param('id');
    $data = $req->body;
    // Atualizar usuário
    return $res->json($user);
});

// Rota DELETE
Router::delete('/users/:id', function($req, $res) {
    $id = $req->param('id');
    // Deletar usuário
    return $res->status(204);
});
```

### Rotas com Parâmetros

```php
// Parâmetro simples
Router::get('/users/:id', function($req, $res) {
    $id = $req->param('id'); // Acessível via parâmetro
    return $res->json(getUserById($id));
});

// Múltiplos parâmetros
Router::get('/users/:userId/posts/:postId', function($req, $res) {
    $userId = $req->param('userId');
    $postId = $req->param('postId');

    return $res->json([
        'user_id' => $userId,
        'post_id' => $postId,
        'post' => getPostByUserAndId($userId, $postId)
    ]);
});

// Parâmetros opcionais com query string
Router::get('/search/:category', function($req, $res) {
    $category = $req->param('category');
    $page = $req->get('page', 1);
    $limit = $req->get('limit', 20);

    return $res->json(searchInCategory($category, $page, $limit));
});
```

### Rotas com Constraints e Regex

O PivotPHP suporta constraints (restrições) em parâmetros de rotas usando regex, permitindo validação de padrões diretamente no roteamento.

#### Sintaxe de Constraints

```php
// Sintaxe básica: :parametro<constraint>
Router::get('/users/:id<\d+>', function($req, $res) {
    // Aceita apenas IDs numéricos: /users/123
    $id = $req->param('id');
    return $res->json(['user_id' => $id]);
});

// Constraint com padrão específico
Router::get('/posts/:year<\d{4}>/:month<\d{2}>', function($req, $res) {
    // Aceita: /posts/2025/07
    // Rejeita: /posts/25/7
    $year = $req->param('year');
    $month = $req->param('month');
    return $res->json(['year' => $year, 'month' => $month]);
});
```

#### Shortcuts de Constraints

O framework oferece atalhos predefinidos para padrões comuns:

```php
// Inteiros
Router::get('/api/v:version<int>', handler); // Aceita: /api/v1, /api/v123

// Slugs
Router::get('/posts/:slug<slug>', handler); // Aceita: /posts/meu-artigo-legal

// Alfanuméricos
Router::get('/codes/:code<alnum>', handler); // Aceita: /codes/ABC123

// UUIDs
Router::get('/users/:uuid<uuid>', handler); // Aceita formato UUID válido

// Datas
Router::get('/events/:date<date>', handler); // Aceita: /events/2025-07-08
```

**Shortcuts disponíveis:**
- `int` - Números inteiros (`\d+`)
- `slug` - Slugs URL-friendly (`[a-z0-9-]+`)
- `alpha` - Apenas letras (`[a-zA-Z]+`)
- `alnum` - Alfanumérico (`[a-zA-Z0-9]+`)
- `uuid` - UUID válido
- `date` - Formato YYYY-MM-DD
- `year` - Ano 4 dígitos (`\d{4}`)
- `month` - Mês 2 dígitos (`\d{2}`)
- `day` - Dia 2 dígitos (`\d{2}`)

#### Regex Customizado

Para padrões mais complexos, use regex completo:

```php
// Email simples
Router::post('/subscribe/:email<[^@]+@[^@]+\.[^@]+>', function($req, $res) {
    $email = $req->param('email');
    // Validação básica de email na rota
});

// SKU personalizado
Router::get('/products/:sku<[A-Z]{3}-\d{4}>', function($req, $res) {
    // Aceita: /products/ABC-1234
    $sku = $req->param('sku');
});

// Código hexadecimal
Router::get('/colors/:hex<[0-9a-fA-F]{6}>', function($req, $res) {
    // Aceita: /colors/FF0000
    $hex = $req->param('hex');
});
```

### Blocos Regex Completos

Para controle total sobre partes da rota, use blocos regex entre chaves `{}`:

```php
// Versionamento de API com regex
Router::get('/api/{^v(\d+)$}/users', function($req, $res) {
    // Aceita: /api/v1/users, /api/v2/users
    // O número da versão é capturado automaticamente
});

// Arquivos com extensões específicas
Router::get('/download/{^(.+)\.(pdf|doc|txt)$}', function($req, $res) {
    // Aceita: /download/documento.pdf, /download/arquivo.txt
    // Captura nome do arquivo e extensão separadamente
});

// Padrões complexos de data
Router::get('/archive/{^(\d{4})/(\d{2})/(.+)$}', function($req, $res) {
    // Aceita: /archive/2025/07/meu-post
    // Captura ano, mês e slug separadamente
});
```

#### Limitações dos Blocos Regex

Os blocos regex são processados por um padrão que suporta:
- ✅ Padrões simples com grupos de captura
- ✅ Alternância básica `(option1|option2)`
- ✅ Quantificadores `{n}`, `+`, `*`, `?`
- ✅ Classes de caracteres `[A-Z]`, `\d`, `\w`
- ✅ Um nível de agrupamento interno

Limitações conhecidas:
- ❌ Múltiplos níveis de chaves aninhadas
- ❌ Padrões extremamente complexos com recursão
- ❌ Chaves desbalanceadas

Para casos simples e médios, o sistema funciona perfeitamente. Para padrões muito complexos, considere simplificar a lógica ou usar validação adicional no handler.

#### Combinando Constraints e Blocos Regex

```php
// Mix de sintaxes
Router::get('/files/{^(docs|images)$}/:name<[a-z0-9-]+>/{^\.(pdf|jpg)$}', 
    function($req, $res) {
        // Aceita: /files/docs/relatorio-anual.pdf
        // Aceita: /files/images/foto-perfil.jpg
        $name = $req->param('name');
    }
);

// Validação complexa de paths
Router::get('/app/:module<alpha>/{^/(.+\.js)$}', function($req, $res) {
    // Aceita: /app/admin/controllers/user.js
    $module = $req->param('module');
});
```

### Melhores Práticas para Regex em Rotas

1. **Use shortcuts quando possível** - São mais legíveis e otimizados
2. **Evite regex muito complexo** - Pode impactar performance
3. **Teste seus padrões** - Use ferramentas de teste de regex
4. **Documente padrões customizados** - Facilita manutenção

```php
// ❌ Evite - Muito complexo para rota
Router::get('/:email<^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$>', ...);

// ✅ Prefira - Validação básica na rota, completa no handler
Router::get('/:email<[^@]+@[^@]+>', function($req, $res) {
    $email = $req->param('email');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $res->status(400)->json(['error' => 'Invalid email']);
    }
    // ...
});
```

### Rotas com Controladores

```php
// Usando array [Classe, método]
Router::get('/api/users', [UserController::class, 'index']);
Router::post('/api/users', [UserController::class, 'store']);
Router::get('/api/users/:id', [UserController::class, 'show']);
Router::put('/api/users/:id', [UserController::class, 'update']);
Router::delete('/api/users/:id', [UserController::class, 'destroy']);

// Exemplo de controlador
class UserController
{
    public function index($req, $res)
    {
        return $res->json(User::all());
    }

    public function show($req, $res)
    {
        $id = $req->param('id');
        $user = User::findById($id);

        if (!$user) {
            return $res->status(404)->json(['error' => 'User not found']);
        }

        return $res->json($user);
    }

    public function store($req, $res)
    {
        $data = $req->body;
        $user = User::create($data);
        return $res->status(201)->json($user);
    }
}
```

## Grupos de Rotas

### Método `group(string $prefix, callable $callback, array $middlewares = [])`

```php
// Grupo básico com prefixo
Router::group('/api/v1', function() {
    Router::get('/users', [UserController::class, 'index']);
    Router::post('/users', [UserController::class, 'store']);
    Router::get('/posts', [PostController::class, 'index']);
});

// Resultado: /api/v1/users, /api/v1/posts
```

### Grupos com Middlewares

```php
// Grupo com middleware de autenticação
Router::group('/api/admin', function() {
    Router::get('/users', [AdminController::class, 'users']);
    Router::get('/settings', [AdminController::class, 'settings']);
    Router::post('/actions', [AdminController::class, 'executeAction']);
}, [
    AuthMiddleware::class,
    AdminMiddleware::class
]);
```

### Grupos Aninhados

```php
// API pública
Router::group('/api', function() {

    // Versão 1 - rotas públicas
    Router::group('/v1', function() {
        Router::get('/health', [HealthController::class, 'check']);
        Router::post('/auth/login', [AuthController::class, 'login']);
    });

    // Versão 1 - rotas autenticadas
    Router::group('/v1', function() {
        Router::get('/profile', [UserController::class, 'profile']);
        Router::get('/orders', [OrderController::class, 'index']);
        Router::post('/orders', [OrderController::class, 'store']);
    }, [AuthMiddleware::class]);

    // Versão 2 (nova API)
    Router::group('/v2', function() {
        Router::get('/users', [V2\UserController::class, 'index']);
        Router::get('/advanced-stats', [V2\StatsController::class, 'advanced']);
    }, [AuthMiddleware::class, V2Middleware::class]);
});
```

### Organizando por Funcionalidade

```php
// Grupo de usuários
Router::group('/users', function() {
    Router::get('/', [UserController::class, 'index']);
    Router::post('/', [UserController::class, 'store']);
    Router::get('/:id', [UserController::class, 'show']);
    Router::put('/:id', [UserController::class, 'update']);
    Router::delete('/:id', [UserController::class, 'destroy']);

    // Sub-recursos
    Router::get('/:id/posts', [UserController::class, 'posts']);
    Router::get('/:id/comments', [UserController::class, 'comments']);
});

// Grupo de posts
Router::group('/posts', function() {
    Router::get('/', [PostController::class, 'index']);
    Router::post('/', [PostController::class, 'store']);
    Router::get('/:id', [PostController::class, 'show']);
    Router::put('/:id', [PostController::class, 'update']);
    Router::delete('/:id', [PostController::class, 'destroy']);

    // Ações específicas
    Router::post('/:id/like', [PostController::class, 'like']);
    Router::post('/:id/share', [PostController::class, 'share']);
    Router::get('/:id/comments', [PostController::class, 'comments']);
});
```

## Middleware em Rotas

### Método `use(string $prefix, callable ...$middlewares)`

```php
// Middleware global para API
Router::use('/api', function($req, $res, $next) {
    // Headers de CORS
    $res->header('Access-Control-Allow-Origin', '*');
    $res->header('Content-Type', 'application/json');
    return $next();
});

// Middleware de autenticação para rotas protegidas
Router::use('/api/protected',
    function($req, $res, $next) {
        $token = $req->headers->get('Authorization');
        if (!$token) {
            return $res->status(401)->json(['error' => 'Token required']);
        }
        return $next();
    },
    function($req, $res, $next) {
        // Validar token
        $user = validateToken($token);
        if (!$user) {
            return $res->status(401)->json(['error' => 'Invalid token']);
        }
        $req->user = $user;
        return $next();
    }
);
```

### Middlewares por Tipo de Recurso

```php
// Middleware de rate limiting para uploads
Router::use('/api/upload', RateLimitMiddleware::class);

// Middleware de validação para formulários
Router::use('/api/forms', ValidationMiddleware::class);

// Middleware de cache para dados estáticos
Router::use('/api/static', CacheMiddleware::class);
```

## Recursos Avançados

### Métodos HTTP Customizados

```php
// Adicionar método customizado
Router::addHttpMethod('PATCH');
Router::addHttpMethod('PURGE');

// Usar método customizado
Router::purge('/cache/:key', [CacheController::class, 'purge']);
```

### Rotas Condicionais

```php
// Rota baseada em ambiente
if ($_ENV['APP_ENV'] === 'development') {
    Router::get('/debug', function($req, $res) {
        return $res->json([
            'routes' => Router::getRegisteredRoutes(),
            'memory' => memory_get_usage(),
            'time' => microtime(true)
        ]);
    });
}

// Rota baseada em feature flag
if (isFeatureEnabled('advanced_api')) {
    Router::group('/api/advanced', function() {
        Router::get('/analytics', [AdvancedController::class, 'analytics']);
        Router::post('/bulk-operations', [AdvancedController::class, 'bulk']);
    });
}
```

### Padrões de URL Complexos

```php
// Múltiplos IDs
Router::get('/companies/:companyId/departments/:deptId/employees/:empId',
    function($req, $res) {
        $companyId = $req->param('companyId');
        $deptId = $req->param('deptId');
        $empId = $req->param('empId');

        return $res->json(getEmployeeInDepartment($companyId, $deptId, $empId));
    }
);

// Rotas com extensões de arquivo
Router::get('/reports/:id.:format', function($req, $res) {
    $id = $req->param('id');
    $format = $req->param('format'); // pdf, json, xml

    $report = getReport($id);

    switch ($format) {
        case 'pdf':
            return $res->header('Content-Type', 'application/pdf')
                      ->streamFile($report->getPdfPath());
        case 'xml':
            return $res->header('Content-Type', 'application/xml')
                      ->text($report->toXml());
        default:
            return $res->json($report->toArray());
    }
});
```

## Otimizações e Performance

### Cache de Rotas

```php
// O Router automaticamente cacheia rotas para performance
// Verificar estatísticas de cache
$stats = Router::getStats();
echo "Cache hits: " . $stats['cache_hits'];
echo "Cache misses: " . $stats['cache_misses'];
```

### Indexação Otimizada

```php
// Rotas são automaticamente indexadas por:
// - Método HTTP
// - Prefixo exato
// - Padrões de parâmetros

// Ordem de definição importa para performance
// Rotas mais específicas primeiro
Router::get('/api/users/active', [UserController::class, 'active']);
Router::get('/api/users/:id', [UserController::class, 'show']);
Router::get('/api/users', [UserController::class, 'index']);
```

### Pré-compilação de Rotas

```php
// Compilar rotas para produção
Router::preCompileRoutes();

// Verificar rotas compiladas
$compiled = Router::getPreCompiledRoutes();
```

## Debugging e Desenvolvimento

### Listando Rotas Registradas

```php
// Obter todas as rotas
$routes = Router::getRegisteredRoutes();

foreach ($routes as $route) {
    echo "{$route['method']} {$route['path']} -> {$route['handler']}\n";
}
```

### Estatísticas do Router

```php
// Estatísticas gerais
$stats = Router::getStats();

// Estatísticas por grupo
$groupStats = Router::getGroupStats();

// Performance de matching
$timing = Router::getMatchingStats();
```

### Helpers de Debug

```php
// Verificar se uma rota existe
Router::get('/debug/routes/:path', function($req, $res) {
    $path = $req->param('path');
    $method = $req->get('method', 'GET');

    $route = Router::findRoute($method, $path);

    if ($route) {
        return $res->json([
            'found' => true,
            'route' => $route,
            'params' => Router::extractParams($path, $route['pattern'])
        ]);
    }

    return $res->status(404)->json(['found' => false]);
});
```

## Padrões RESTful

### CRUD Completo

```php
// Padrão RESTful para um recurso
class ResourceRouter
{
    public static function resource(string $name, string $controller)
    {
        $prefix = "/{$name}";

        Router::group($prefix, function() use ($controller) {
            Router::get('/', [$controller, 'index']);      // GET /users
            Router::post('/', [$controller, 'store']);     // POST /users
            Router::get('/:id', [$controller, 'show']);    // GET /users/123
            Router::put('/:id', [$controller, 'update']);  // PUT /users/123
            Router::patch('/:id', [$controller, 'patch']); // PATCH /users/123
            Router::delete('/:id', [$controller, 'destroy']); // DELETE /users/123
        });
    }
}

// Uso
ResourceRouter::resource('users', UserController::class);
ResourceRouter::resource('posts', PostController::class);
ResourceRouter::resource('orders', OrderController::class);
```

### API Versionada

```php
class ApiRouter
{
    public static function version(string $version, callable $callback)
    {
        Router::group("/api/{$version}", $callback, [
            ApiVersionMiddleware::class,
            RateLimitMiddleware::class,
            AuthMiddleware::class
        ]);
    }
}

// Uso
ApiRouter::version('v1', function() {
    ResourceRouter::resource('users', V1\UserController::class);
    ResourceRouter::resource('posts', V1\PostController::class);
});

ApiRouter::version('v2', function() {
    ResourceRouter::resource('users', V2\UserController::class);
    ResourceRouter::resource('posts', V2\PostController::class);
    Router::get('/advanced-features', [V2\FeatureController::class, 'index']);
});
```

## Organização de Rotas

### Separação por Arquivos

```php
// routes/web.php
Router::get('/', [HomeController::class, 'index']);
Router::get('/about', [PageController::class, 'about']);
Router::get('/contact', [PageController::class, 'contact']);

// routes/api.php
Router::group('/api/v1', function() {
    require_once __DIR__ . '/api/users.php';
    require_once __DIR__ . '/api/posts.php';
    require_once __DIR__ . '/api/orders.php';
});

// routes/api/users.php
Router::group('/users', function() {
    Router::get('/', [UserController::class, 'index']);
    Router::post('/', [UserController::class, 'store']);
    Router::get('/:id', [UserController::class, 'show']);
    // ...
});
```

### Service Provider de Rotas

```php
class RouteServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->mapWebRoutes();
        $this->mapApiRoutes();
    }

    protected function mapWebRoutes()
    {
        Router::group('/', function() {
            require_once base_path('routes/web.php');
        });
    }

    protected function mapApiRoutes()
    {
        Router::group('/api', function() {
            require_once base_path('routes/api.php');
        }, [
            'throttle:api',
            AuthMiddleware::class
        ]);
    }
}
```

O Router do PivotPHP é projetado para performance e flexibilidade, oferecendo todas as funcionalidades necessárias para aplicações modernas, desde APIs simples até sistemas complexos com múltiplas versões e recursos avançados.
