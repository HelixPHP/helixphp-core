<?php
/**
 * Exemplo Básico do Express PHP
 *
 * Este exemplo demonstra o uso básico do framework
 * para criar uma API REST simples.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\ApiExpress;
use Express\Http\Request;
use Express\Http\Response;

// Criar aplicação
$app = new ApiExpress();

// ================================
// ROTAS BÁSICAS
// ================================

// Rota de boas-vindas
$app->get('/', function(Request $req, Response $res) {
    $res->json([
        'message' => 'Bem-vindo ao Express PHP!',
        'version' => '2.0',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Rota de teste
$app->get('/test', function(Request $req, Response $res) {
    $res->json([
        'status' => 'ok',
        'message' => 'API funcionando perfeitamente!'
    ]);
});

// Rota com parâmetro
$app->get('/hello/:name', function(Request $req, Response $res) {
    $name = $req->getParam('name');
    $res->json([
        'message' => "Olá, {$name}!",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// ================================
// API REST SIMPLES - USUÁRIOS
// ================================

// Simulação de dados (em produção, use banco de dados)
$users = [
    ['id' => 1, 'name' => 'João Silva', 'email' => 'joao@example.com'],
    ['id' => 2, 'name' => 'Maria Santos', 'email' => 'maria@example.com'],
    ['id' => 3, 'name' => 'Pedro Costa', 'email' => 'pedro@example.com']
];

// GET /api/users - Listar todos os usuários
$app->get('/api/users', function(Request $req, Response $res) use ($users) {
    $res->json([
        'success' => true,
        'data' => $users,
        'total' => count($users)
    ]);
});

// GET /api/users/:id - Buscar usuário por ID
$app->get('/api/users/:id', function(Request $req, Response $res) use ($users) {
    $id = (int) $req->getParam('id');

    $user = array_filter($users, function($u) use ($id) {
        return $u['id'] === $id;
    });

    if ($user) {
        $res->json([
            'success' => true,
            'data' => array_values($user)[0]
        ]);
    } else {
        $res->status(404)->json([
            'success' => false,
            'message' => 'Usuário não encontrado'
        ]);
    }
});

// POST /api/users - Criar novo usuário
$app->post('/api/users', function(Request $req, Response $res) use (&$users) {
    $data = $req->getBody();

    // Validação simples
    if (!isset($data['name']) || !isset($data['email'])) {
        $res->status(400)->json([
            'success' => false,
            'message' => 'Nome e email são obrigatórios'
        ]);
        return;
    }

    // Criar novo usuário
    $newUser = [
        'id' => count($users) + 1,
        'name' => $data['name'],
        'email' => $data['email']
    ];

    $users[] = $newUser;

    $res->status(201)->json([
        'success' => true,
        'message' => 'Usuário criado com sucesso',
        'data' => $newUser
    ]);
});

// PUT /api/users/:id - Atualizar usuário
$app->put('/api/users/:id', function(Request $req, Response $res) use (&$users) {
    $id = (int) $req->getParam('id');
    $data = $req->getBody();

    $userIndex = null;
    foreach ($users as $index => $user) {
        if ($user['id'] === $id) {
            $userIndex = $index;
            break;
        }
    }

    if ($userIndex === null) {
        $res->status(404)->json([
            'success' => false,
            'message' => 'Usuário não encontrado'
        ]);
        return;
    }

    // Atualizar dados
    if (isset($data['name'])) {
        $users[$userIndex]['name'] = $data['name'];
    }
    if (isset($data['email'])) {
        $users[$userIndex]['email'] = $data['email'];
    }

    $res->json([
        'success' => true,
        'message' => 'Usuário atualizado com sucesso',
        'data' => $users[$userIndex]
    ]);
});

// DELETE /api/users/:id - Remover usuário
$app->delete('/api/users/:id', function(Request $req, Response $res) use (&$users) {
    $id = (int) $req->getParam('id');

    $userIndex = null;
    foreach ($users as $index => $user) {
        if ($user['id'] === $id) {
            $userIndex = $index;
            break;
        }
    }

    if ($userIndex === null) {
        $res->status(404)->json([
            'success' => false,
            'message' => 'Usuário não encontrado'
        ]);
        return;
    }

    array_splice($users, $userIndex, 1);

    $res->json([
        'success' => true,
        'message' => 'Usuário removido com sucesso'
    ]);
});

// ================================
// MIDDLEWARE DE EXEMPLO
// ================================

// Middleware para log de requisições
$app->use(function(Request $req, Response $res, callable $next) {
    $method = $req->getMethod();
    $path = $req->getPath();
    $timestamp = date('Y-m-d H:i:s');

    error_log("[{$timestamp}] {$method} {$path}");

    return $next($req, $res);
});

// ================================
// EXECUTAR APLICAÇÃO
// ================================

// Iniciar servidor (se executado diretamente)
if (php_sapi_name() === 'cli-server') {
    echo "Express PHP Server rodando em http://localhost:8000\n";
    echo "Teste os endpoints:\n";
    echo "  GET  /               - Página inicial\n";
    echo "  GET  /test           - Teste da API\n";
    echo "  GET  /api/users      - Listar usuários\n";
    echo "  POST /api/users      - Criar usuário\n";
    echo "  GET  /api/users/1    - Buscar usuário\n";
    echo "\n";
}

$app->run();
