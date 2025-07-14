# Array Callable Support - Guia Completo

## 🎯 Visão Geral

O PivotPHP v1.1.4+ oferece suporte robusto para array callables, permitindo que você use métodos de classe como handlers de rota de forma nativa e segura.

## ✅ Sintaxes Suportadas

### 1. Closure/Função Anônima (Recomendado)
```php
$app->get('/users', function($req, $res) {
    return $res->json(['users' => User::all()]);
});
```

### 2. Array Callable com Classe
```php
// Método estático
$app->get('/users', [UserController::class, 'index']);

// Instância de objeto
$controller = new UserController();
$app->get('/users', [$controller, 'index']);

// Com parâmetros
$app->get('/users/:id', [UserController::class, 'show']);
```

### 3. Função Nomeada
```php
function getUsersHandler($req, $res) {
    return $res->json(['users' => User::all()]);
}

$app->get('/users', 'getUsersHandler');
```

## ❌ Sintaxes NÃO Suportadas

### String no Formato Controller@method
```php
// ❌ ISTO NÃO FUNCIONA!
$app->get('/users', 'UserController@index');

// ✅ USE ISTO EM VEZ DISSO:
$app->get('/users', [UserController::class, 'index']);
```

**Por que não funciona?** O PHP não considera strings no formato `Controller@method` como callable válido.

## 📖 Exemplos Práticos

### Exemplo 1: Controller Básico
```php
<?php

class UserController 
{
    public function index($req, $res) 
    {
        $users = User::all();
        return $res->json(['users' => $users]);
    }
    
    public function show($req, $res) 
    {
        $id = $req->param('id');
        $user = User::find($id);
        
        if (!$user) {
            return $res->status(404)->json(['error' => 'User not found']);
        }
        
        return $res->json(['user' => $user]);
    }
    
    public function store($req, $res) 
    {
        $data = $req->body();
        $user = User::create($data);
        
        return $res->status(201)->json(['user' => $user]);
    }
}

// Registrar rotas
$app->get('/users', [UserController::class, 'index']);
$app->get('/users/:id', [UserController::class, 'show']);
$app->post('/users', [UserController::class, 'store']);
```

### Exemplo 2: Controller com Injeção de Dependência
```php
<?php

class ApiController 
{
    private $userService;
    
    public function __construct(UserService $userService) 
    {
        $this->userService = $userService;
    }
    
    public function getUsers($req, $res) 
    {
        $filters = $req->query();
        $users = $this->userService->getFilteredUsers($filters);
        
        return $res->json([
            'users' => $users,
            'count' => count($users)
        ]);
    }
}

// Instanciar controller com dependências
$userService = new UserService();
$apiController = new ApiController($userService);

// Registrar com instância
$app->get('/api/users', [$apiController, 'getUsers']);
```

### Exemplo 3: Middleware + Array Callable
```php
<?php

class AdminController 
{
    public function dashboard($req, $res) 
    {
        return $res->json(['message' => 'Admin Dashboard']);
    }
}

// Middleware de autenticação
$authMiddleware = function($req, $res, $next) {
    $token = $req->header('Authorization');
    
    if (!$token || !Auth::validate($token)) {
        return $res->status(401)->json(['error' => 'Unauthorized']);
    }
    
    return $next($req, $res);
};

// Rota protegida com array callable
$app->get('/admin/dashboard', [AdminController::class, 'dashboard'])
    ->middleware($authMiddleware);
```

## 🔧 Validação e Error Handling

O PivotPHP v1.1.4+ inclui validação automática de array callables:

### Validações Automáticas
```php
// ✅ Método público - ACEITO
class PublicController {
    public function handle($req, $res) { /* ... */ }
}
$app->get('/public', [PublicController::class, 'handle']);

// ❌ Método privado - REJEITADO
class PrivateController {
    private function handle($req, $res) { /* ... */ }
}
$app->get('/private', [PrivateController::class, 'handle']); // Erro!

// ❌ Método inexistente - REJEITADO
$app->get('/missing', [Controller::class, 'methodNotExists']); // Erro!
```

### Mensagens de Erro Melhoradas
```php
try {
    $app->get('/invalid', [InvalidController::class, 'privateMethod']);
} catch (InvalidArgumentException $e) {
    echo $e->getMessage();
    // "Route handler validation failed: Method 'privateMethod' is not accessible"
}
```

## 🚀 Performance e Otimizações

### Array Callables são Otimizados
- ✅ **Validação antecipada** durante registro da rota
- ✅ **Cache interno** de callables validados
- ✅ **Zero overhead** em runtime
- ✅ **Error handling** robusto e informativo

### Exemplo de Performance
```php
// Registrar 1000 rotas com array callables
$start = microtime(true);

for ($i = 0; $i < 1000; $i++) {
    $app->get("/route-{$i}", [Controller::class, 'handle']);
}

$time = microtime(true) - $start;
echo "1000 rotas registradas em: {$time}s"; // ~0.01s típico
```

## 🔍 Troubleshooting

### Problema: "Route handler validation failed"
```php
// Erro comum:
$app->get('/users', [UserController::class, 'index']);
// TypeError: Route handler validation failed

// Soluções:
1. Verifique se a classe existe: class_exists('UserController')
2. Verifique se o método é público: method_exists() && is_callable()
3. Verifique o namespace correto: UserController::class vs App\Controllers\UserController::class
```

### Problema: "Method does not exist"
```php
// Verificar método existe
if (!method_exists(UserController::class, 'index')) {
    echo "Método 'index' não existe em UserController";
}

// Verificar se é público
$reflection = new ReflectionMethod(UserController::class, 'index');
if (!$reflection->isPublic()) {
    echo "Método 'index' não é público";
}
```

### Problema: Namespace incorreto
```php
// ❌ Erro comum
$app->get('/users', ['UserController', 'index']); // String, não ::class

// ✅ Correto
$app->get('/users', [UserController::class, 'index']); // Usa ::class

// ✅ Com namespace completo
$app->get('/users', [\App\Controllers\UserController::class, 'index']);
```

## 📝 Migração de Código Existente

### Migrar de string para array callable
```php
// ANTES (v1.1.3 e anteriores)
$app->get('/users', function($req, $res) {
    $controller = new UserController();
    return $controller->index($req, $res);
});

// DEPOIS (v1.1.4+)
$app->get('/users', [UserController::class, 'index']);
```

### Migrar múltiplas rotas
```php
// ANTES
$routes = [
    ['GET', '/users', 'UserController@index'],
    ['POST', '/users', 'UserController@store'],
    ['GET', '/users/:id', 'UserController@show']
];

foreach ($routes as [$method, $path, $handler]) {
    // Implementação manual necessária
}

// DEPOIS
$routes = [
    ['GET', '/users', [UserController::class, 'index']],
    ['POST', '/users', [UserController::class, 'store']],
    ['GET', '/users/:id', [UserController::class, 'show']]
];

foreach ($routes as [$method, $path, $handler]) {
    $app->{strtolower($method)}($path, $handler); // Funciona diretamente!
}
```

## ✅ Checklist de Implementação

- [ ] Classe existe e está acessível
- [ ] Método existe na classe
- [ ] Método é público (não private/protected)
- [ ] Usa `::class` em vez de string
- [ ] Handler aceita `($req, $res)` como parâmetros
- [ ] Retorna response válida

## 🔗 Próximos Passos

- [Guia de Controllers](../controllers/README.md)
- [Middleware com Array Callables](../middleware/ARRAY_CALLABLE_MIDDLEWARE.md)
- [Performance e Otimizações](../performance/ROUTING_PERFORMANCE.md)
- [Testing Array Callables](../../testing/ARRAY_CALLABLE_TESTING.md)