<?php

/**
 * 🌍 PivotPHP - Hello World
 * 
 * O exemplo mais simples possível do PivotPHP Core.
 * Demonstra a simplicidade Express.js para PHP.
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/01-basics/hello-world.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

// Criar aplicação
$app = new Application();

// Rota simples
$app->get('/', function ($req, $res) {
    return $res->json([
        'message' => 'Hello, World! 🌍',
        'framework' => 'PivotPHP Core',
        'version' => Application::VERSION,
        'style' => 'Express.js for PHP'
    ]);
});

// Rota com texto simples
$app->get('/text', function ($req, $res) {
    return $res->send('Hello from PivotPHP! 🚀');
});

// Rota com parâmetro
$app->get('/hello/:name', function ($req, $res) {
    $name = $req->param('name');
    return $res->json([
        'greeting' => "Hello, {$name}! 👋",
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// Executar aplicação
$app->run();