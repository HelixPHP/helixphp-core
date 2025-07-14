# Getting Started - PivotPHP v1.1.4+

## 🚀 Quick Start (5 minutos)

### 1. Instalação
```bash
composer require pivotphp/core
```

### 2. Hello World com Array Callables
```php
<?php
// index.php

require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// ✅ NOVO v1.1.4+: Array callable nativo
class WelcomeController 
{
    public function hello($req, $res) 
    {
        return $res->json([
            'message' => 'Hello from PivotPHP v1.1.4!',
            'features' => [
                'Array Callables' => '✅ Suporte nativo',
                'JSON Optimization' => '✅ Threshold inteligente',
                'Error Diagnostics' => '✅ Contextual e detalhado'
            ]
        ]);
    }
}

// Usar array callable diretamente
$app->get('/hello', [WelcomeController::class, 'hello']);

$app->run();
```

### 3. Testar
```bash
php -S localhost:8000
curl http://localhost:8000/hello
```

## 🎯 Principais Novidades v1.1.4+

### ✅ Array Callables Nativos
```php
// ANTES v1.1.3 (workaround necessário)
$app->get('/users', function($req, $res) {
    $controller = new UserController();
    return $controller->index($req, $res);
});

// AGORA v1.1.4+ (nativo e direto)
$app->get('/users', [UserController::class, 'index']);
```

### ✅ JsonBufferPool com Threshold Inteligente
```php
// Sistema decide automaticamente quando usar pooling
$app->get('/api/data', function($req, $res) {
    $data = DataService::get($req->query('size', 'small'));
    
    // Pequeno: json_encode() direto (sem overhead)
    // Grande: pooling automático (98% mais rápido)
    return $res->json($data); // Sempre otimizado!
});
```

### ✅ Error Diagnostics Melhorados
```php
// Erros agora incluem contexto detalhado, sugestões e debug info
try {
    $app->get('/invalid', [NonExistentController::class, 'method']);
} catch (Exception $e) {
    // Erro rico com contexto:
    // "Route handler validation failed: Class 'NonExistentController' not found"
    // + sugestões de correção
    // + informações de debug
}
```

## 🏗️ Estrutura de Projeto Recomendada

```
my-api/
├── composer.json
├── public/
│   └── index.php              # Entry point
├── src/
│   ├── Controllers/           # Array callables
│   │   ├── UserController.php
│   │   └── ApiController.php
│   ├── Middleware/           # Custom middleware
│   └── Services/             # Business logic
├── config/
│   └── app.php              # Configuration
└── tests/                   # Unit tests
```

## 🎯 Exemplo Completo - API RESTful

### Controller
```php
<?php
// src/Controllers/UserController.php

namespace App\Controllers;

class UserController 
{
    public function index($req, $res) 
    {
        // JsonBufferPool otimiza automaticamente baseado no tamanho
        $users = User::paginate($req->query('limit', 10));
        
        return $res->json([
            'users' => $users,
            'meta' => [
                'page' => $req->query('page', 1),
                'limit' => $req->query('limit', 10)
            ]
        ]);
    }
    
    public function show($req, $res) 
    {
        $id = $req->param('id');
        $user = User::find($id);
        
        if (!$user) {
            // ContextualException com diagnóstico detalhado
            throw ContextualException::parameterError(
                'id', 
                'existing user ID', 
                $id, 
                '/users/:id'
            );
        }
        
        return $res->json(['user' => $user]);
    }
    
    public function store($req, $res) 
    {
        $data = $req->body();
        
        // Validação simples
        if (empty($data['name']) || empty($data['email'])) {
            return $res->status(400)->json([
                'error' => 'Name and email are required'
            ]);
        }
        
        $user = User::create($data);
        
        return $res->status(201)->json(['user' => $user]);
    }
}
```

### Aplicação Principal
```php
<?php
// public/index.php

require_once '../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Security\CorsMiddleware;
use PivotPHP\Core\Middleware\Http\ErrorMiddleware;
use App\Controllers\UserController;

$app = new Application();

// Middleware global
$app->use(new CorsMiddleware([
    'origins' => ['http://localhost:3000'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE']
]));

$app->use(new ErrorMiddleware([
    'show_details' => true // Apenas em desenvolvimento
]));

// ✅ Rotas com array callables v1.1.4+
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/:id<\d+>', [UserController::class, 'show']);
$app->post('/users', [UserController::class, 'store']);

// Health check com closure (para endpoints simples)
$app->get('/health', function($req, $res) {
    return $res->json([
        'status' => 'healthy',
        'version' => '1.1.4+',
        'features' => [
            'array_callables' => true,
            'json_threshold' => true,
            'contextual_errors' => true
        ],
        'timestamp' => time()
    ]);
});

$app->run();
```

## 🔧 Configuração Avançada

### JSON Optimization
```php
// config/app.php

use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Configurar para alta performance (opcional)
JsonBufferPool::configure([
    'threshold_bytes' => 128,    // Mais agressivo
    'max_pool_size' => 500,      // Pool maior para alta carga
    'enable_statistics' => true  // Monitoramento
]);
```

### Error Handling
```php
// Configurar error handling avançado
$app->use(function($req, $res, $next) {
    try {
        return $next($req, $res);
    } catch (Exception $e) {
        // Log detalhado com contexto
        error_log("Error: " . $e->getMessage());
        
        if ($e instanceof ContextualException) {
            error_log("Context: " . json_encode($e->getContext()));
            error_log("Suggestions: " . implode(', ', $e->getSuggestions()));
        }
        
        return $res->status(500)->json([
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
            'debug' => $e instanceof ContextualException ? $e->getDebugInfo() : null
        ]);
    }
});
```

## 🧪 Testing

### Unit Test para Array Callable
```php
<?php
// tests/UserControllerTest.php

use PHPUnit\Framework\TestCase;
use App\Controllers\UserController;

class UserControllerTest extends TestCase 
{
    public function testArrayCallableRegistration() 
    {
        $app = new Application();
        
        // Deve registrar sem erros
        $app->get('/users', [UserController::class, 'index']);
        
        $this->assertTrue(true); // Se chegou aqui, funcionou
    }
    
    public function testIndexMethod() 
    {
        $controller = new UserController();
        
        // Mock request/response
        $req = $this->createMock(RequestInterface::class);
        $res = $this->createMock(ResponseInterface::class);
        
        $res->expects($this->once())
           ->method('json')
           ->with($this->arrayHasKey('users'));
        
        $controller->index($req, $res);
    }
}
```

## 🚀 Performance Monitoring

### Endpoint de Métricas
```php
$app->get('/metrics', function($req, $res) {
    $jsonStats = JsonBufferPool::getStatistics();
    
    return $res->json([
        'json_pool' => [
            'efficiency' => $jsonStats['efficiency'],
            'operations' => $jsonStats['total_operations'],
            'memory_saved_mb' => round($jsonStats['memory_saved'] / 1024 / 1024, 2)
        ],
        'memory' => [
            'usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ],
        'php' => [
            'version' => PHP_VERSION,
            'opcache' => function_exists('opcache_get_status')
        ]
    ]);
});
```

## 🔗 Próximos Passos

1. **Explorar Documentação:**
   - [Array Callable Guide](../technical/routing/ARRAY_CALLABLE_GUIDE.md)
   - [JsonBufferPool Optimization](../technical/json/BUFFER_POOL_OPTIMIZATION.md)
   - [Troubleshooting](../troubleshooting/COMMON_ISSUES.md)

2. **Exemplos Práticos:**
   - [API RESTful Completa](../examples/rest-api.md)
   - [Authentication & Authorization](../examples/auth.md)
   - [Performance Optimization](../examples/performance.md)

3. **Comunidade:**
   - [Discord](https://discord.gg/DMtxsP7z)
   - [GitHub Issues](https://github.com/PivotPHP/pivotphp-core/issues)
   - [Contributing Guide](../contributing/README.md)

## ✅ Checklist de Migração v1.1.4+

- [ ] Atualizar para `pivotphp/core: ^1.1.4`
- [ ] Substituir closures por array callables onde apropriado
- [ ] Verificar performance do JsonBufferPool
- [ ] Testar error handling melhorado
- [ ] Atualizar testes para cobrir novos recursos
- [ ] Revisar documentação do projeto

🎉 **Pronto! Você está usando PivotPHP v1.1.4+ com todas as otimizações e melhorias implementadas!**