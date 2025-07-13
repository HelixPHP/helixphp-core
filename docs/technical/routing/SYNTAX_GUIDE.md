# Guia de Sintaxe de Roteamento - PivotPHP Core

Este guia documenta as sintaxes corretas para definir rotas no PivotPHP Core, esclarecendo as formas suportadas e não suportadas.

## ✅ Sintaxes Suportadas

### 1. Closure/Função Anônima (Recomendado)

A forma mais comum e recomendada para definir handlers de rota:

```php
<?php

use PivotPHP\Core\Core\Application;

$app = new Application();

// Rota simples
$app->get('/users', function($req, $res) {
    return $res->json(['users' => []]);
});

// Rota com parâmetros
$app->get('/users/:id', function($req, $res) {
    $id = $req->param('id');
    return $res->json(['user_id' => $id]);
});

// Rota POST com dados
$app->post('/users', function($req, $res) {
    $data = $req->input();
    // Processar dados...
    return $res->json(['message' => 'User created', 'data' => $data]);
});
```

### 2. Array Callable com Classe

> **✅ Funcionalidade Completa**: Array callables foram aprimorados na v1.1.3 com suporte total para PHP 8.4+

Usando controladores organizados em classes - ideal para aplicações estruturadas:

```php
<?php

class UserController 
{
    public function index($req, $res) 
    {
        return $res->json(['users' => User::all()]);
    }
    
    public function show($req, $res) 
    {
        $id = $req->param('id');
        return $res->json(['user' => User::find($id)]);
    }
    
    public function store($req, $res) 
    {
        $data = $req->input();
        $user = User::create($data);
        return $res->status(201)->json(['user' => $user]);
    }
    
    public function update($req, $res) 
    {
        $id = $req->param('id');
        $data = $req->input();
        $user = User::update($id, $data);
        return $res->json(['user' => $user]);
    }
    
    public function destroy($req, $res) 
    {
        $id = $req->param('id');
        User::delete($id);
        return $res->status(204)->send();
    }
}

// Registrar rotas usando array callable
$controller = new UserController();

// ✅ Método de instância (Recomendado para DI)
$app->get('/users', [$controller, 'index']);
$app->get('/users/:id', [$controller, 'show']);
$app->post('/users', [$controller, 'store']);
$app->put('/users/:id', [$controller, 'update']);
$app->delete('/users/:id', [$controller, 'destroy']);

// ✅ Método estático (Para utilitários)
$app->get('/status', [HealthController::class, 'getStatus']);
$app->get('/info', [ApiController::class, 'getInfo']);
```

#### Vantagens dos Array Callables

- **Organização**: Código organizado em classes e métodos
- **Testabilidade**: Fácil de testar unitariamente cada método
- **Reutilização**: Métodos podem ser reutilizados em diferentes contextos
- **Dependency Injection**: Controllers podem receber dependências no construtor
- **Performance**: Overhead mínimo (~29% comparado a closures)
- **PHP 8.4+ Compatível**: Totalmente compatível com tipagem estrita moderna

### 3. Função Nomeada

Usando funções globais como handlers:

```php
<?php

function getUsersHandler($req, $res) 
{
    return $res->json(['users' => User::all()]);
}

function createUserHandler($req, $res) 
{
    $data = $req->input();
    $user = User::create($data);
    return $res->status(201)->json(['user' => $user]);
}

// Registrar rotas usando nome da função
$app->get('/users', 'getUsersHandler');
$app->post('/users', 'createUserHandler');
```

## 📚 Exemplos Práticos

### Health Check com Array Callable

```php
<?php

class HealthController
{
    public function healthCheck($req, $res)
    {
        return $res->json([
            'status' => 'ok',
            'timestamp' => time(),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'version' => '1.1.3'
        ]);
    }

    public static function getSystemInfo($req, $res)
    {
        return $res->json([
            'php_version' => PHP_VERSION,
            'framework' => 'PivotPHP',
            'environment' => $_ENV['APP_ENV'] ?? 'production'
        ]);
    }
}

$healthController = new HealthController();

// ✅ Rota de health check
$app->get('/health', [$healthController, 'healthCheck']);

// ✅ Informações do sistema (método estático)
$app->get('/system/info', [HealthController::class, 'getSystemInfo']);
```

### API com Parâmetros

```php
<?php

class ApiController
{
    public function getUserById($req, $res)
    {
        $userId = $req->param('id');
        
        // Validação básica
        if (!is_numeric($userId)) {
            return $res->status(400)->json([
                'error' => 'Invalid user ID'
            ]);
        }
        
        return $res->json([
            'user_id' => $userId,
            'name' => "User {$userId}",
            'active' => true
        ]);
    }

    public function getUserPosts($req, $res)
    {
        $userId = $req->param('userId');
        $postId = $req->param('postId');
        
        return $res->json([
            'user_id' => $userId,
            'post_id' => $postId,
            'post' => [
                'title' => "Post {$postId} by User {$userId}",
                'content' => 'Lorem ipsum...'
            ]
        ]);
    }
}

$apiController = new ApiController();

// ✅ Rota com parâmetro simples
$app->get('/api/users/:id', [$apiController, 'getUserById']);

// ✅ Rota com múltiplos parâmetros
$app->get('/api/users/:userId/posts/:postId', [$apiController, 'getUserPosts']);
```

### 4. Middleware com Rotas

Combinando handlers com middleware:

```php
<?php

// Middleware em rota específica
$app->get('/admin/users', [AdminController::class, 'getUsers'])
    ->middleware(AuthMiddleware::class);

// Múltiplos middlewares
$app->post('/api/users', [UserController::class, 'store'])
    ->middleware(AuthMiddleware::class)
    ->middleware(ValidationMiddleware::class);

// Grupos com middleware
$app->group('/api/v1', function($group) {
    $group->get('/users', [UserController::class, 'index']);
    $group->post('/users', [UserController::class, 'store']);
})->middleware(ApiAuthMiddleware::class);
```

## ❌ Sintaxes NÃO Suportadas

### String no Formato Controller@method

**Esta sintaxe NÃO é suportada no PivotPHP Core:**

```php
// ❌ ERRO - Não funciona!
$app->get('/users', 'UserController@index');
$app->post('/users', 'UserController@create');
$app->put('/users/:id', 'UserController@update');
$app->delete('/users/:id', 'UserController@delete');
```

**Por que não funciona?**

O PivotPHP Core valida que todos os handlers sejam `callable`. Strings no formato `Controller@method` não são consideradas callable pelo PHP, resultando em erro:

```
TypeError: Argument #2 ($handler) must be of type callable, string given
```

## 🔧 Migração de Sintaxe Incorreta

Se você encontrou exemplos com a sintaxe `Controller@method`, aqui está como corrigi-los:

### Antes (Incorreto):
```php
$app->get('/users', 'UserController@index');
$app->post('/users', 'UserController@create');
$app->put('/users/:id', 'UserController@update');
$app->delete('/users/:id', 'UserController@delete');
```

### Depois (Correto):
```php
$app->get('/users', [UserController::class, 'index']);
$app->post('/users', [UserController::class, 'create']);
$app->put('/users/:id', [UserController::class, 'update']);
$app->delete('/users/:id', [UserController::class, 'delete']);
```

## 📋 Resumo das Regras

1. **Use sempre callables válidos**: closures, arrays callable ou nomes de função
2. **Para controladores**: Use `[ClassName::class, 'methodName']`
3. **Para flexibilidade**: Prefira closures para lógica simples
4. **Para organização**: Use controladores para lógica complexa
5. **Evite strings**: Nunca use strings no formato `Controller@method`

## 🔍 Verificação de Sintaxe

Para verificar se sua sintaxe está correta, certifique-se de que:

```php
// Teste se o handler é callable
$handler = [UserController::class, 'index'];
var_dump(is_callable($handler)); // deve retornar true

// Teste sintaxe incorreta
$wrongHandler = 'UserController@index';  
var_dump(is_callable($wrongHandler)); // retorna false
```

## 🎯 Exemplos Completos

### API RESTful Completa

```php
<?php

require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

class UserController 
{
    public function index($req, $res) {
        return $res->json(['users' => User::all()]);
    }
    
    public function show($req, $res) {
        $id = $req->param('id');
        return $res->json(['user' => User::find($id)]);
    }
    
    public function store($req, $res) {
        $data = $req->input();
        $user = User::create($data);
        return $res->status(201)->json(['user' => $user]);
    }
    
    public function update($req, $res) {
        $id = $req->param('id');
        $data = $req->input();
        $user = User::update($id, $data);
        return $res->json(['user' => $user]);
    }
    
    public function destroy($req, $res) {
        $id = $req->param('id');
        User::delete($id);
        return $res->status(204)->send();
    }
}

// Definir rotas RESTful
$app->get('/api/users', [UserController::class, 'index']);
$app->get('/api/users/:id', [UserController::class, 'show']);
$app->post('/api/users', [UserController::class, 'store']);
$app->put('/api/users/:id', [UserController::class, 'update']);
$app->delete('/api/users/:id', [UserController::class, 'destroy']);

$app->run();
```

---

**Nota:** Esta documentação reflete o estado atual de implementação do PivotPHP Core v1.1.1. Sempre consulte a documentação oficial e testes para verificar funcionalidades suportadas.