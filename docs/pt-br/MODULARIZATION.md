# Modularização Express-PHP v2.0

## Visão Geral

O Express-PHP foi completamente reestruturado para seguir uma arquitetura modular e profissional, inspirada nos melhores frameworks PHP modernos. A versão 2.0 introduz:

- **Arquitetura Modular**: Separação clara de responsabilidades
- **Dependency Injection Container**: Gerenciamento de dependências
- **Sistema de Configuração**: Configuração centralizada e flexível
- **Pipeline de Middlewares**: Processamento de requisições em camadas
- **Streaming HTTP**: Suporte a streaming e Server-Sent Events
- **Compatibilidade PSR**: Seguindo padrões PSR-4, PSR-11, etc.

## Estrutura de Diretórios

```
src/
├── Core/                    # Núcleo do framework
│   ├── Application.php      # Classe principal da aplicação
│   ├── Container.php        # Container de Dependency Injection
│   └── Config.php           # Sistema de configuração
├── Http/                    # Camada HTTP
│   ├── Request.php          # Requisição HTTP
│   ├── Response.php         # Resposta HTTP
│   └── HeaderRequest.php    # Gerenciamento de cabeçalhos
├── Routing/                 # Sistema de roteamento
│   ├── Router.php           # Roteador principal
│   ├── Route.php            # Rota individual
│   ├── RouteCollection.php  # Coleção de rotas
│   └── RouterInstance.php   # Sub-roteador
├── Middleware/              # Middlewares
│   ├── Core/                # Middlewares principais
│   │   ├── MiddlewareInterface.php
│   │   └── BaseMiddleware.php
│   └── Security/            # Middlewares de segurança
│       └── CorsMiddleware.php
├── Authentication/          # Autenticação
│   └── JWTHelper.php        # Helper JWT
├── Utils/                   # Utilitários
│   ├── Arr.php              # Helpers de array
│   └── Utils.php            # Utilitários gerais
└── Exceptions/              # Exceções
    └── HttpException.php    # Exceção HTTP base
```

## Principais Mudanças

### 1. Namespace Reorganizado

**Antes:**
```php
use Express\Services\Request;
use Express\Services\Response;
use Express\Controller\Router;
```

**Depois:**
```php
use Express\Http\Request;
use Express\Http\Response;
use Express\Routing\Router;
```

### 2. Container de Dependency Injection

```php
use Express\Core\Container;

$container = new Container();

// Registrar dependências
$container->bind('database', function() {
    return new PDO('sqlite:database.db');
});

// Singleton
$container->singleton('logger', function() {
    return new Logger();
});

// Resolver dependências
$db = $container->get('database');
```

### 3. Sistema de Configuração

```php
use Express\Core\Config;

// Carregar configurações
$config = new Config();
$config->load([
    'app' => [
        'name' => 'My App',
        'debug' => true
    ],
    'database' => [
        'host' => 'localhost',
        'port' => 3306
    ]
]);

// Acessar com dot notation
$appName = $config->get('app.name');
$dbHost = $config->get('database.host', 'localhost');
```

### 4. Aplicação Principal

```php
use Express\Core\Application;

$app = new Application();

// Configurar aplicação
$app->configure([
    'app.name' => 'My App',
    'app.debug' => true
]);

// Middlewares
$app->use(function($req, $res, $next) {
    // Middleware global
    return $next($req, $res);
});

// Rotas
$app->get('/api/users', function($req, $res) {
    return $res->json(['users' => []]);
});

// Executar
$app->run();
```

### 5. Middlewares Orientados a Objetos

```php
use Express\Middleware\Core\BaseMiddleware;

class AuthMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        $token = $this->getHeader($request, 'Authorization');

        if (!$token) {
            return $this->respondWithError($response, 401, 'Unauthorized');
        }

        // Validar token...

        return $next($request, $response);
    }
}

// Usar middleware
$app->use(new AuthMiddleware());
```

### 6. Streaming HTTP Aprimorado

```php
$app->get('/stream', function($req, $res) {
    // Server-Sent Events
    $res->startStream('text/event-stream');

    for ($i = 0; $i < 10; $i++) {
        $res->sendEvent(['count' => $i], 'counter');
        sleep(1);
    }

    return $res->endStream();
});
```

## Migração da v1.x para v2.0

### Passo 1: Atualizar Composer
```bash
composer require express-php/microframework:^2.0
composer dump-autoload
```

### Passo 2: Atualizar Namespaces
```php
// Substituir imports antigos
use Express\Services\Request;
use Express\Services\Response;

// Por novos imports
use Express\Http\Request;
use Express\Http\Response;
```

### Passo 3: Atualizar Inicialização
```php
// Antes
$app = new Express\ApiExpress();

// Depois (compatibilidade mantida)
$app = new Express\ApiExpress();
// OU usar a nova Application diretamente
$app = new Express\Core\Application();
```

### Passo 4: Middlewares
```php
// Antes - function
$app->use(function($req, $res, $next) {
    return $next($req, $res);
});

// Depois - classe (recomendado)
class MyMiddleware extends BaseMiddleware
{
    public function handle($request, $response, callable $next)
    {
        return $next($request, $response);
    }
}

$app->use(new MyMiddleware());
```

## Vantagens da Nova Arquitetura

1. **Modularidade**: Cada componente tem responsabilidade única
2. **Testabilidade**: Fácil de testar com mocks e injeção de dependência
3. **Extensibilidade**: Interfaces e classes base para extensão
4. **Performance**: Container otimizado e pipeline eficiente
5. **Manutenibilidade**: Código limpo e bem organizado
6. **Compatibilidade**: Mantém compatibilidade com v1.x
7. **Padrões**: Segue PSR e melhores práticas PHP

## Exemplos de Uso

### Aplicação Completa
```php
<?php
use Express\Core\Application;
use Express\Middleware\Security\CorsMiddleware;

$app = new Application();

// Configuração
$app->configure([
    'app.name' => 'My API',
    'app.debug' => true,
    'jwt.secret' => 'your-secret-key'
]);

// Middlewares
$app->use(CorsMiddleware::development());
$app->use(new AuthMiddleware());

// Rotas
$app->get('/api/users', [UserController::class, 'index']);
$app->post('/api/users', [UserController::class, 'create']);

// Executar
$app->run();
```

### Controller com Dependency Injection
```php
class UserController
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index($request, $response)
    {
        $users = $this->userService->getAll();
        return $response->json($users);
    }
}
```

A nova arquitetura torna o Express-PHP muito mais robusto, modular e adequado para aplicações profissionais de grande escala.
