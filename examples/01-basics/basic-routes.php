<?php

/**
 * 🛣️ PivotPHP - Rotas Básicas
 * 
 * Demonstra todos os métodos HTTP básicos no estilo Express.js
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/01-basics/basic-routes.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl -X POST http://localhost:8000/users -H "Content-Type: application/json" -d '{"name":"John"}'
 * curl -X PUT http://localhost:8000/users/1 -H "Content-Type: application/json" -d '{"name":"Jane"}'
 * curl -X DELETE http://localhost:8000/users/1
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// Simulação de banco de dados em memória
$users = [
    1 => ['id' => 1, 'name' => 'Alice', 'email' => 'alice@example.com'],
    2 => ['id' => 2, 'name' => 'Bob', 'email' => 'bob@example.com'],
];
$nextId = 3;

// 📋 GET - Listar todos os usuários
$app->get('/', function ($req, $res) use (&$users) {
    return $res->json([
        'users' => array_values($users),
        'total' => count($users),
        'endpoints' => [
            'GET /' => 'Listar usuários',
            'GET /users/:id' => 'Obter usuário específico',
            'POST /users' => 'Criar usuário',
            'PUT /users/:id' => 'Atualizar usuário',
            'DELETE /users/:id' => 'Deletar usuário'
        ]
    ]);
});

// 👤 GET - Obter usuário específico
$app->get('/users/:id', function ($req, $res) use (&$users) {
    $id = (int) $req->param('id');
    
    if (!isset($users[$id])) {
        return $res->status(404)->json(['error' => 'Usuário não encontrado']);
    }
    
    return $res->json(['user' => $users[$id]]);
});

// ➕ POST - Criar novo usuário
$app->post('/users', function ($req, $res) use (&$users, &$nextId) {
    $body = $req->getBodyAsStdClass();
    
    // Validação básica
    if (empty($body->name)) {
        return $res->status(400)->json(['error' => 'Nome é obrigatório']);
    }
    
    $user = [
        'id' => $nextId++,
        'name' => $body->name,
        'email' => $body->email ?? null,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    $users[$user['id']] = $user;
    
    return $res->status(201)->json([
        'message' => 'Usuário criado com sucesso',
        'user' => $user
    ]);
});

// ✏️ PUT - Atualizar usuário
$app->put('/users/:id', function ($req, $res) use (&$users) {
    $id = (int) $req->param('id');
    
    if (!isset($users[$id])) {
        return $res->status(404)->json(['error' => 'Usuário não encontrado']);
    }
    
    $body = $req->getBodyAsStdClass();
    
    // Atualizar campos fornecidos
    if (isset($body->name)) {
        $users[$id]['name'] = $body->name;
    }
    if (isset($body->email)) {
        $users[$id]['email'] = $body->email;
    }
    
    $users[$id]['updated_at'] = date('Y-m-d H:i:s');
    
    return $res->json([
        'message' => 'Usuário atualizado com sucesso',
        'user' => $users[$id]
    ]);
});

// 🗑️ DELETE - Deletar usuário
$app->delete('/users/:id', function ($req, $res) use (&$users) {
    $id = (int) $req->param('id');
    
    if (!isset($users[$id])) {
        return $res->status(404)->json(['error' => 'Usuário não encontrado']);
    }
    
    $deletedUser = $users[$id];
    unset($users[$id]);
    
    return $res->json([
        'message' => 'Usuário deletado com sucesso',
        'deleted_user' => $deletedUser
    ]);
});

// 🔍 GET - Buscar usuários
$app->get('/search', function ($req, $res) use (&$users) {
    $query = $req->get('q', '');
    
    if (empty($query)) {
        return $res->status(400)->json(['error' => 'Parâmetro q é obrigatório']);
    }
    
    $results = array_filter($users, function ($user) use ($query) {
        return stripos($user['name'], $query) !== false || 
               stripos($user['email'] ?? '', $query) !== false;
    });
    
    return $res->json([
        'query' => $query,
        'results' => array_values($results),
        'count' => count($results)
    ]);
});

$app->run();