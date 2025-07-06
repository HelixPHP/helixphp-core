# 🚀 Guia de Implementação Básica

Como criar sua primeira API RESTful com HelixPHP em 5 minutos.

## 📋 Pré-requisitos

- PHP 8.1 ou superior
- Composer instalado
- Conhecimento básico de PHP e APIs REST

## ⚡ Instalação Rápida

### 1. Criar Projeto
```bash
mkdir minha-api
cd minha-api
composer init
composer require cafernandes/helixphp-core
```

### 2. Estrutura Básica
```
minha-api/
├── composer.json
├── index.php          # Ponto de entrada
├── routes/
│   └── api.php        # Definição das rotas
└── config/
    └── app.php        # Configurações
```

## 🎯 Seu Primeiro Endpoint

### Arquivo Principal (index.php)
```php
<?php
require_once 'vendor/autoload.php';

use Helix\Core\Application;

$app = new Application();

// Rota simples
$app->get('/', function($req, $res) {
    $res->json([
        'message' => 'Bem-vindo à minha API!',
        'version' => '1.0.0',
        'timestamp' => time()
    ]);
});

// Rota com parâmetro
$app->get('/hello/:name', function($req, $res) {
    $name = $req->params['name'];
    $res->json([
        'message' => "Olá, {$name}!",
        'greeting' => 'Hello from HelixPHP'
    ]);
});

$app->run();
```

### Testando
```bash
# Instalar servidor PHP
php -S localhost:8000

# Testar endpoints
curl http://localhost:8000/
curl http://localhost:8000/hello/João
```

## 📚 CRUD Básico - API de Usuários

### Simulação de Banco de Dados
```php
<?php
// Arquivo: data/users.php
return [
    1 => ['id' => 1, 'name' => 'João Silva', 'email' => 'joao@email.com'],
    2 => ['id' => 2, 'name' => 'Maria Santos', 'email' => 'maria@email.com'],
    3 => ['id' => 3, 'name' => 'Pedro Costa', 'email' => 'pedro@email.com']
];
```

### Implementação Completa
```php
<?php
// index.php
require_once 'vendor/autoload.php';

use Helix\Core\Application;

$app = new Application();

// Carregar dados simulados
$users = include 'data/users.php';
$nextId = count($users) + 1;

// Headers globais
$app->use(function($req, $res, $next) {
    $res->header('Content-Type', 'application/json');
    $res->header('Access-Control-Allow-Origin', '*');
    return $next();
});

// 📖 GET /users - Listar todos os usuários
$app->get('/users', function($req, $res) use ($users) {
    $res->json([
        'success' => true,
        'data' => array_values($users),
        'total' => count($users)
    ]);
});

// 📖 GET /users/:id - Buscar usuário específico
$app->get('/users/:id', function($req, $res) use ($users) {
    $id = (int) $req->params['id'];

    if (!isset($users[$id])) {
        $res->status(404)->json([
            'success' => false,
            'error' => 'Usuário não encontrado'
        ]);
        return;
    }

    $res->json([
        'success' => true,
        'data' => $users[$id]
    ]);
});

// ✏️ POST /users - Criar novo usuário
$app->post('/users', function($req, $res) use (&$users, &$nextId) {
    $data = $req->body;

    // Validação básica
    if (empty($data['name']) || empty($data['email'])) {
        $res->status(400)->json([
            'success' => false,
            'error' => 'Nome e email são obrigatórios'
        ]);
        return;
    }

    // Criar usuário
    $newUser = [
        'id' => $nextId,
        'name' => $data['name'],
        'email' => $data['email']
    ];

    $users[$nextId] = $newUser;
    $nextId++;

    $res->status(201)->json([
        'success' => true,
        'data' => $newUser,
        'message' => 'Usuário criado com sucesso'
    ]);
});

// 🔄 PUT /users/:id - Atualizar usuário
$app->put('/users/:id', function($req, $res) use (&$users) {
    $id = (int) $req->params['id'];
    $data = $req->body;

    if (!isset($users[$id])) {
        $res->status(404)->json([
            'success' => false,
            'error' => 'Usuário não encontrado'
        ]);
        return;
    }

    // Atualizar campos fornecidos
    if (isset($data['name'])) {
        $users[$id]['name'] = $data['name'];
    }
    if (isset($data['email'])) {
        $users[$id]['email'] = $data['email'];
    }

    $res->json([
        'success' => true,
        'data' => $users[$id],
        'message' => 'Usuário atualizado com sucesso'
    ]);
});

// 🗑️ DELETE /users/:id - Deletar usuário
$app->delete('/users/:id', function($req, $res) use (&$users) {
    $id = (int) $req->params['id'];

    if (!isset($users[$id])) {
        $res->status(404)->json([
            'success' => false,
            'error' => 'Usuário não encontrado'
        ]);
        return;
    }

    unset($users[$id]);

    $res->status(204)->send(); // 204 No Content
});

$app->run();
```

## 🧪 Testando sua API

### Usando cURL
```bash
# Listar usuários
curl http://localhost:8000/users

# Buscar usuário específico
curl http://localhost:8000/users/1

# Criar usuário
curl -X POST http://localhost:8000/users \
  -H "Content-Type: application/json" \
  -d '{"name":"Ana Silva","email":"ana@email.com"}'

# Atualizar usuário
curl -X PUT http://localhost:8000/users/1 \
  -H "Content-Type: application/json" \
  -d '{"name":"João Santos"}'

# Deletar usuário
curl -X DELETE http://localhost:8000/users/1
```

### Usando Postman/Insomnia
1. **GET** `http://localhost:8000/users` - Listar usuários
2. **GET** `http://localhost:8000/users/1` - Buscar usuário
3. **POST** `http://localhost:8000/users` - Criar usuário
   ```json
   {
     "name": "Novo Usuário",
     "email": "novo@email.com"
   }
   ```
4. **PUT** `http://localhost:8000/users/1` - Atualizar usuário
5. **DELETE** `http://localhost:8000/users/1` - Deletar usuário

## 🔧 Organizando o Código

### Separando Rotas
```php
// routes/users.php
function setupUserRoutes($app, &$users, &$nextId) {
    $app->group('/users', function($group) use (&$users, &$nextId) {
        $group->get('/', function($req, $res) use ($users) {
            $res->json(['data' => array_values($users)]);
        });

        $group->get('/:id', function($req, $res) use ($users) {
            $id = (int) $req->params['id'];
            if (!isset($users[$id])) {
                $res->status(404)->json(['error' => 'Not found']);
                return;
            }
            $res->json(['data' => $users[$id]]);
        });

        // ... outras rotas
    });
}

// index.php
require_once 'vendor/autoload.php';
require_once 'routes/users.php';

$app = new Application();
$users = include 'data/users.php';
$nextId = count($users) + 1;

setupUserRoutes($app, $users, $nextId);

$app->run();
```

## 📊 Tratamento de Erros Básico

```php
// Middleware global de erro
$app->use(function($req, $res, $next) {
    try {
        return $next();
    } catch (\Exception $e) {
        $res->status(500)->json([
            'success' => false,
            'error' => 'Erro interno do servidor',
            'message' => $e->getMessage()
        ]);
    }
});

// Rota não encontrada (404)
$app->use('*', function($req, $res) {
    $res->status(404)->json([
        'success' => false,
        'error' => 'Rota não encontrada',
        'path' => $req->getUri()->getPath()
    ]);
});
```

## 🎯 Query Parameters

```php
// GET /users?limit=10&offset=0&search=joão
$app->get('/users', function($req, $res) use ($users) {
    $query = $req->query;

    $limit = isset($query['limit']) ? (int) $query['limit'] : 10;
    $offset = isset($query['offset']) ? (int) $query['offset'] : 0;
    $search = $query['search'] ?? '';

    $filteredUsers = array_values($users);

    // Filtrar por busca
    if ($search) {
        $filteredUsers = array_filter($filteredUsers, function($user) use ($search) {
            return stripos($user['name'], $search) !== false ||
                   stripos($user['email'], $search) !== false;
        });
    }

    // Aplicar paginação
    $total = count($filteredUsers);
    $filteredUsers = array_slice($filteredUsers, $offset, $limit);

    $res->json([
        'success' => true,
        'data' => $filteredUsers,
        'pagination' => [
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
});
```

## 💡 Próximos Passos

Agora que você tem uma API básica funcionando:

1. **[Adicione Middlewares](usage_with_middleware.md)** - Segurança, CORS, autenticação
2. **[Conecte um Banco Real](../technical/application.md)** - PostgreSQL, MySQL, MongoDB
3. **[Implemente Validação](../technical/middleware/ValidationMiddleware.md)** - Dados de entrada
4. **[Adicione Testes](../testing/api_testing.md)** - Teste sua API

## 🎉 Parabéns!

Você criou sua primeira API RESTful com HelixPHP! 🚀

---

*🚀 Uma API simples e funcional em poucos minutos!*
