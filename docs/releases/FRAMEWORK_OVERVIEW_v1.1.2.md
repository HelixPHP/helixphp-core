# PivotPHP Core v1.1.2 - Framework Overview

**Versão:** 1.1.2 (Consolidation Edition)  
**Data de Release:** 2025-07-11  
**Status:** Stable Release  

## 📋 Visão Geral

PivotPHP Core v1.1.2 é uma versão de **consolidação técnica** que elimina duplicações críticas de código, reorganiza a estrutura de arquivos e otimiza a arquitetura do framework. Esta versão prepara o framework para uso em produção através de melhorias significativas na organização e manutenibilidade do código.

## 🎯 Objetivos da Versão

- **Eliminação de duplicações:** Remoção de 100% das duplicações críticas identificadas
- **Reorganização arquitetural:** Estrutura de middlewares organizada por responsabilidade
- **Manutenção de compatibilidade:** 100% backward compatibility através de aliases
- **Modernização de CI/CD:** Atualização para GitHub Actions v4
- **Melhoria de qualidade:** PHPStan Level 9, PSR-12, cobertura de testes

## 📊 Métricas da Versão

### Performance Benchmarks
- **Request Creation:** 28,693 ops/sec
- **Response Creation:** 131,351 ops/sec
- **PSR-7 Compatibility:** 13,376 ops/sec
- **Hybrid Operations:** 13,579 ops/sec
- **Object Pooling:** 24,161 ops/sec
- **Route Processing:** 31,699 ops/sec
- **Performance Média:** 40,476 ops/sec

### Qualidade de Código
- **PHPStan:** Level 9, 0 erros (119 arquivos)
- **PSR-12:** 100% compliance, 0 erros
- **Testes:** 429/430 passando (99.8% success rate)
- **Coverage:** 33.23% (3,261/9,812 statements)
- **Arquivos PHP:** 119 arquivos (-3 vs v1.1.1)
- **Linhas de Código:** 29,556 linhas (-1,071 vs v1.1.1)

### Redução Técnica
- **Duplicações Eliminadas:** 5 → 0 (100% redução)
- **Namespaces Organizados:** 3 fragmentados → 1 unificado
- **Aliases de Compatibilidade:** 12 aliases criados
- **Arquivos Consolidados:** 3 arquivos removidos

## 🏗️ Arquitetura Consolidada

### Nova Estrutura de Middlewares
```
src/Middleware/
├── Http/
│   ├── CorsMiddleware.php
│   └── ErrorMiddleware.php
├── Security/
│   ├── AuthMiddleware.php
│   ├── CsrfMiddleware.php
│   ├── SecurityHeadersMiddleware.php
│   └── XssMiddleware.php
└── Performance/
    ├── CacheMiddleware.php
    └── RateLimitMiddleware.php
```

### Componentes Consolidados
- **DynamicPoolManager:** `src/Http/Pool/DynamicPoolManager.php`
- **PerformanceMonitor:** `src/Performance/PerformanceMonitor.php`
- **Arr Utilities:** `src/Utils/Arr.php` (Support/Arr removido)

### Aliases de Compatibilidade
```php
// Middlewares HTTP
PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware
→ PivotPHP\Core\Middleware\Http\CorsMiddleware

// Middlewares de Segurança  
PivotPHP\Core\Http\Psr15\Middleware\CsrfMiddleware
→ PivotPHP\Core\Middleware\Security\CsrfMiddleware

// Performance e Pool
PivotPHP\Core\Monitoring\PerformanceMonitor
→ PivotPHP\Core\Performance\PerformanceMonitor

// Utilitários
PivotPHP\Core\Support\Arr
→ PivotPHP\Core\Utils\Arr
```

## 🔧 Melhorias Técnicas

### GitHub Actions Modernizado
- **actions/upload-artifact:** v3 → v4
- **actions/cache:** v3 → v4  
- **codecov/codecov-action:** v3 → v4
- **Coverage calculation:** Parser XML funcional
- **Error handling:** Graceful fallbacks

### Correções de Código
- **DynamicPoolManager:** Constructor com configuração
- **Arr::flatten:** Implementação depth-aware com dot notation
- **PSR-12 compliance:** Separação de functions.php e aliases.php
- **Type safety:** Strict typing em todos os componentes

### Validação Automática
- **Quality Gates:** 8 critérios críticos implementados
- **Pre-commit hooks:** Validação automática
- **CI/CD pipeline:** Integração contínua funcional
- **Coverage reporting:** Métricas precisas

## 💾 Configuração e Uso

### Autoload Atualizado
```json
{
  "autoload": {
    "psr-4": {
      "PivotPHP\\Core\\": "src/"
    },
    "files": [
      "src/functions.php",
      "src/aliases.php"
    ]
  }
}
```

### Migração Simples
```php
// Código v1.1.1 (continua funcionando)
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;

// Código v1.1.2 (recomendado)
use PivotPHP\Core\Middleware\Http\CorsMiddleware;
```

## 🔄 Compatibilidade

### Backward Compatibility
- **100% compatível** com código v1.1.1
- **Aliases automáticos** para todas as classes movidas
- **APIs públicas** inalteradas
- **Comportamento** idêntico

### Depreciação Planejada
- **Aliases temporários** serão removidos na v1.2.0
- **Migração automática** disponível via script
- **Documentação** de migração incluída

## 🚀 Recursos Mantidos

### Core Features
- **Express.js-inspired API:** Request/Response híbrido
- **PSR Standards:** PSR-7, PSR-15, PSR-12 compliance
- **Object Pooling:** High-performance object reuse
- **JSON Optimization:** v1.1.1 buffer pooling mantido
- **Middleware Pipeline:** PSR-15 compliant
- **Security Features:** CSRF, XSS, CORS, Rate Limiting

### Development Tools
- **OpenAPI/Swagger:** Documentação automática
- **Benchmarking:** Suite de performance
- **Quality Gates:** Validação automática
- **Testing:** 430+ testes unitários e integração

## 📈 Roadmap

### v1.2.0 (Próxima Major)
- [ ] Remoção de aliases temporários
- [ ] Novos middlewares de segurança
- [ ] Performance improvements
- [ ] Expanded documentation

### Ecosystem Integration
- [ ] PivotPHP Cycle ORM v1.1.0
- [ ] PivotPHP ReactPHP v0.2.0
- [ ] Enhanced benchmarking suite

## 🚀 Guia de Início Rápido

### Instalação

```bash
# Via Composer (recomendado)
composer create-project pivotphp/core my-app
cd my-app
php -S localhost:8000 -t public

# Via Git Clone
git clone https://github.com/PivotPHP/pivotphp-core.git
cd pivotphp-core
composer install
```

### Hello World v1.1.2

```php
<?php
// public/index.php
require '../vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// Rota básica
$app->get('/', function($req, $res) {
    return $res->json([
        'message' => 'Hello PivotPHP v1.1.2!',
        'version' => Application::VERSION,
        'timestamp' => time()
    ]);
});

// Rota com middleware de segurança
$app->get('/secure', function($req, $res) {
    return $res->json(['secure' => 'data']);
})
->middleware('auth')
->middleware('cors');

// Rotas com parâmetros e validação
$app->get('/users/:id<\d+>', function($req, $res) {
    $id = $req->param('id');
    return $res->json(['user_id' => (int)$id]);
});

$app->run();
```

## 🏗️ Arquitetura Detalhada v1.1.2

### Estrutura de Diretórios Consolidada

```
src/
├── Core/
│   ├── Application.php              # Aplicação principal
│   ├── Container.php               # Container DI
│   └── ServiceProvider.php         # Provider base
├── Http/
│   ├── Request.php                 # Requisições híbridas
│   ├── Response.php                # Respostas híbridas
│   ├── Factory/
│   │   └── OptimizedHttpFactory.php
│   └── Pool/
│       └── DynamicPoolManager.php  # ✨ Consolidado
├── Routing/
│   ├── Router.php                  # Roteador principal
│   ├── Route.php                   # Definição de rotas
│   └── ConstraintValidator.php     # Validação de regex
├── Middleware/                     # ✨ Nova organização
│   ├── Core/
│   │   ├── BaseMiddleware.php
│   │   └── MiddlewareInterface.php
│   ├── Http/                       # Middlewares HTTP
│   │   ├── CorsMiddleware.php
│   │   └── ErrorMiddleware.php
│   ├── Security/                   # Middlewares de Segurança
│   │   ├── AuthMiddleware.php
│   │   ├── CsrfMiddleware.php
│   │   ├── SecurityHeadersMiddleware.php
│   │   └── XssMiddleware.php
│   └── Performance/                # Middlewares de Performance
│       ├── CacheMiddleware.php
│       └── RateLimitMiddleware.php
├── Performance/
│   ├── HighPerformanceMode.php
│   ├── PerformanceMonitor.php      # ✨ Consolidado
│   └── MemoryManager.php
├── Json/
│   └── Pool/
│       ├── JsonBufferPool.php      # Buffer pooling v1.1.1
│       └── JsonBuffer.php
├── Utils/                          # ✨ Namespace consolidado
│   └── Arr.php                     # Ex-Support/Arr
├── Authentication/
│   ├── JWTHelper.php
│   └── AuthProvider.php
├── Providers/
│   ├── CoreServiceProvider.php
│   ├── HttpServiceProvider.php
│   └── MiddlewareServiceProvider.php
├── functions.php                   # ✨ Separado para PSR-12
└── aliases.php                     # ✨ Compatibilidade
```

### Middlewares Disponíveis

#### Segurança
```php
use PivotPHP\Core\Middleware\Security\{
    AuthMiddleware,
    CsrfMiddleware, 
    SecurityHeadersMiddleware,
    XssMiddleware
};

// Configuração de segurança
$app->use(new SecurityHeadersMiddleware([
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block'
]));

$app->use(new CsrfMiddleware([
    'exclude' => ['/api/webhook'],
    'secret' => 'your-csrf-secret'
]));
```

#### Performance
```php
use PivotPHP\Core\Middleware\Performance\{
    CacheMiddleware,
    RateLimitMiddleware
};

// Rate limiting
$app->use(new RateLimitMiddleware([
    'requests' => 100,
    'window' => 3600, // 1 hour
    'storage' => 'memory'
]));

// Cache de respostas
$app->use(new CacheMiddleware([
    'ttl' => 300,
    'cache_headers' => true
]));
```

#### HTTP
```php
use PivotPHP\Core\Middleware\Http\{
    CorsMiddleware,
    ErrorMiddleware
};

// CORS configurável
$app->use(new CorsMiddleware([
    'origins' => ['localhost:3000', 'app.example.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization']
]));
```

## 🎨 Recursos Avançados

### Express.js-style API

```php
// Middleware por rota
$app->get('/admin/*', function($req, $res, $next) {
    if (!$req->header('Authorization')) {
        return $res->status(401)->json(['error' => 'Unauthorized']);
    }
    return $next($req, $res);
});

// Grupos de rotas
$app->group('/api/v1', function($group) {
    $group->get('/users', 'UserController@index');
    $group->post('/users', 'UserController@store');
    $group->put('/users/:id', 'UserController@update');
    $group->delete('/users/:id', 'UserController@destroy');
})->middleware('auth')->middleware('throttle:60,1');

// Validação de parâmetros
$app->get('/posts/:id<\d+>/comments/:commentId<[a-f0-9]{8}>', 
    function($req, $res) {
        return $res->json([
            'post_id' => $req->param('id'),
            'comment_id' => $req->param('commentId')
        ]);
    }
);
```

### PSR-7 Hybrid Implementation

```php
// Express.js style (recomendado para produtividade)
$app->post('/users', function($req, $res) {
    $data = $req->getBodyAsStdClass();
    $name = $data->name ?? '';
    
    return $res->status(201)->json([
        'id' => uniqid(),
        'name' => $name
    ]);
});

// PSR-7 compliant (compatibilidade total)
$app->post('/psr7-example', function(ServerRequestInterface $req, ResponseInterface $res) {
    $body = $req->getBody();
    $data = json_decode($body->getContents(), true);
    
    $response = $res->withStatus(200)
                   ->withHeader('Content-Type', 'application/json');
    
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response;
});
```

### High Performance Mode

```php
use PivotPHP\Core\Performance\HighPerformanceMode;

// Ativação de alta performance
HighPerformanceMode::enable(HighPerformanceMode::PROFILE_EXTREME);

// Configuração personalizada
HighPerformanceMode::configure([
    'object_pooling' => true,
    'json_optimization' => true,
    'memory_management' => true,
    'performance_monitoring' => false // Desabilitar em prod
]);

// Monitoramento de performance
$monitor = HighPerformanceMode::getMonitor();
$metrics = $monitor->getPerformanceMetrics();
```

## 🔧 Configuração Avançada

### Container de Dependências

```php
use PivotPHP\Core\Core\Container;

$app = new Application();

// Registrar serviços
$app->container()->set('database', function() {
    return new PDO('sqlite::memory:');
});

$app->container()->set('logger', function() {
    return new Monolog\Logger('app');
});

// Usar em rotas
$app->get('/data', function($req, $res) use ($app) {
    $db = $app->container()->get('database');
    $logger = $app->container()->get('logger');
    
    $logger->info('Accessing data endpoint');
    // ... lógica
});
```

### Service Providers

```php
use PivotPHP\Core\Core\ServiceProvider;

class DatabaseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->container()->set('db', function() {
            return new Database($this->app->config('database'));
        });
    }
    
    public function boot(): void
    {
        // Inicialização após todos os providers
    }
}

// Registrar provider
$app->register(new DatabaseServiceProvider($app));
```

## 📊 Métricas Detalhadas de Performance

### Benchmarks Internos (Docker v1.1.2)
```
Component                    Operations/sec    Improvement vs v1.1.1
Request Creation             28,693           -6.8% (optimization trade-off)
Response Creation            131,351          +12.4% (pool efficiency)
PSR-7 Compatibility         13,376           +2.1% (code consolidation)
Hybrid Operations            13,579           +1.8% (reduced overhead)
Object Pooling               24,161           +15.2% (manager optimization)
Route Processing             31,699           +3.4% (validation improvements)
JSON Buffer Pooling          161,000          Maintained (v1.1.1 feature)
Memory Management            98.5%            +2.1% (reduced fragmentation)
```

### Comparativo com Frameworks (Docker)
```
Framework          Requests/sec    Memory (MB)    Response Time (ms)
Slim 4             6,881          3.2            0.29
Lumen              6,322          4.1            0.31  
PivotPHP v1.1.2    6,227          1.6            0.32
Flight             3,179          2.8            0.63
```

### Análise de Qualidade
```
Metric                       Value          Status
PHPStan Level               9              ✅ Maximum
PSR-12 Compliance          100%           ✅ Perfect
Test Coverage               33.23%         🟡 Adequate
Success Rate                99.8%          ✅ Excellent
Code Duplication            0              ✅ Eliminated
Cyclomatic Complexity       Low            ✅ Maintainable
Memory Leaks               0              ✅ Clean
```

## 🛡️ Recursos de Segurança

### Built-in Security Features

```php
// CSRF Protection
$app->use(new CsrfMiddleware([
    'secret' => $_ENV['CSRF_SECRET'],
    'header' => 'X-CSRF-Token',
    'exclude' => ['/api/webhook/*']
]));

// XSS Protection
$app->use(new XssMiddleware([
    'auto_escape' => true,
    'allowed_tags' => ['b', 'i', 'em', 'strong']
]));

// Rate Limiting
$app->use(new RateLimitMiddleware([
    'requests' => 100,
    'window' => 3600,
    'storage' => 'redis', // ou 'memory'
    'key_generator' => function($req) {
        return $req->getClientIp();
    }
]));

// Security Headers
$app->use(new SecurityHeadersMiddleware([
    'X-Frame-Options' => 'DENY',
    'X-Content-Type-Options' => 'nosniff',
    'X-XSS-Protection' => '1; mode=block',
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
]));
```

### JWT Authentication

```php
use PivotPHP\Core\Authentication\JWTHelper;

// Configurar JWT
$jwt = new JWTHelper([
    'secret' => $_ENV['JWT_SECRET'],
    'algorithm' => 'HS256',
    'expire' => 3600
]);

// Middleware de autenticação
$app->use(new AuthMiddleware([
    'jwt' => $jwt,
    'exclude' => ['/login', '/register', '/public/*']
]));

// Login endpoint
$app->post('/login', function($req, $res) use ($jwt) {
    $credentials = $req->getBodyAsStdClass();
    
    if (validateCredentials($credentials)) {
        $token = $jwt->encode([
            'user_id' => $user->id,
            'role' => $user->role,
            'exp' => time() + 3600
        ]);
        
        return $res->json(['token' => $token]);
    }
    
    return $res->status(401)->json(['error' => 'Invalid credentials']);
});
```

## 🔄 Migração de Versões Anteriores

### De v1.1.1 para v1.1.2

```php
// ✅ Este código continua funcionando (100% backward compatibility)
use PivotPHP\Core\Http\Psr15\Middleware\CorsMiddleware;
use PivotPHP\Core\Support\Arr;
use PivotPHP\Core\Monitoring\PerformanceMonitor;

// 🚀 Código recomendado para v1.1.2
use PivotPHP\Core\Middleware\Http\CorsMiddleware;
use PivotPHP\Core\Utils\Arr;
use PivotPHP\Core\Performance\PerformanceMonitor;
```

### Script de Migração Automática

```bash
# Executar migração automática
php scripts/migrate_v1.1.2.php

# Validar migração
composer test
composer phpstan
composer cs:check
```

### Checklist de Migração

- [ ] Atualizar imports para novos namespaces (opcional)
- [ ] Verificar middlewares customizados
- [ ] Testar integração com pools de objetos
- [ ] Validar configurações de performance
- [ ] Executar suite completa de testes

## 🎯 Conclusão

PivotPHP Core v1.1.2 representa um marco importante na evolução do framework, estabelecendo uma base sólida para crescimento futuro através de:

- **Arquitetura limpa** e organizada por responsabilidade
- **Qualidade de código** excepcional (PHPStan Level 9, PSR-12)
- **Performance** mantida e otimizada (40K+ ops/sec médio)
- **Compatibilidade** total preservada (100% backward compatible)
- **DevOps** modernizado (GitHub Actions v4, CI/CD robusto)
- **Segurança** robusta (múltiplas camadas de proteção)
- **Developer Experience** aprimorada (Express.js-style + PSR compliance)

Esta versão está **pronta para produção** e serve como fundação robusta para o ecossistema PivotPHP, oferecendo a base técnica necessária para aplicações modernas e escaláveis.

---

## 📚 Recursos Adicionais

**Documentação Completa:** [docs/](../README.md)  
**Migration Guide:** [MIGRATION_GUIDE_v1.1.2.md](MIGRATION_GUIDE_v1.1.2.md)  
**Changelog:** [CHANGELOG_v1.1.2.md](CHANGELOG_v1.1.2.md)  
**Performance Benchmarks:** [benchmarks/](../../benchmarks/)  
**API Reference:** [API_REFERENCE.md](../API_REFERENCE.md)  
**Examples:** [implementations/](../implementations/)  
**Community:** [Discord](https://discord.gg/DMtxsP7z) | [GitHub](https://github.com/PivotPHP/pivotphp-core)