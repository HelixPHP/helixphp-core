<?php

/**
 * 🎯 PivotPHP - Routing com Regex
 * 
 * Demonstra o poder do sistema de routing do PivotPHP com regex personalizado
 * Permite criar rotas altamente específicas e validação de parâmetros
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/02-routing/regex-routing.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/users/123
 * curl http://localhost:8000/users/invalid  # Deve dar 404
 * curl http://localhost:8000/products/abc-def-123
 * curl http://localhost:8000/api/v1/posts/2024/12/25
 * curl http://localhost:8000/files/document.pdf
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📋 Página inicial explicativa
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Regex Routing Examples',
        'description' => 'Demonstrações de routing avançado com regex personalizado',
        'examples' => [
            'Numeric ID' => [
                'route' => '/users/:id<\\d+>',
                'test' => '/users/123',
                'description' => 'Aceita apenas IDs numéricos'
            ],
            'Slug Pattern' => [
                'route' => '/products/:slug<[a-z0-9-]+>',
                'test' => '/products/meu-produto-123',
                'description' => 'Slug com letras, números e hífens'
            ],
            'Date Format' => [
                'route' => '/api/v:version<\\d+>/posts/:year<\\d{4}>/:month<\\d{2}>/:day<\\d{2}>',
                'test' => '/api/v1/posts/2024/12/25',
                'description' => 'Data no formato YYYY/MM/DD'
            ],
            'File Extension' => [
                'route' => '/files/:filename<[^/]+\\.(pdf|jpg|png|gif)>',
                'test' => '/files/document.pdf',
                'description' => 'Arquivos com extensões específicas'
            ],
            'UUID Format' => [
                'route' => '/objects/:uuid<[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}>',
                'test' => '/objects/550e8400-e29b-41d4-a716-446655440000',
                'description' => 'UUID formato padrão'
            ]
        ],
        'shortcuts' => [
            'Predefined' => [
                ':id<slug>' => 'Slug pattern (letras, números, hífens)',
                ':id<uuid>' => 'UUID format',
                ':id<date>' => 'Date format (YYYY-MM-DD)',
                ':id<alpha>' => 'Apenas letras',
                ':id<alnum>' => 'Letras e números'
            ]
        ]
    ]);
});

// 🔢 Routing com ID numérico obrigatório
$app->get('/users/:id<\\d+>', function ($req, $res) {
    $id = (int) $req->param('id');
    
    // Simular busca de usuário
    $user = [
        'id' => $id,
        'name' => "User {$id}",
        'email' => "user{$id}@example.com",
        'created_at' => date('Y-m-d H:i:s'),
        'regex_matched' => '\\d+ (apenas números)'
    ];
    
    return $res->json([
        'user' => $user,
        'route_info' => [
            'pattern' => '/users/:id<\\d+>',
            'matched_id' => $id,
            'validation' => 'ID deve ser numérico'
        ]
    ]);
});

// 🏷️ Routing com slug (padrão de URL amigável)
$app->get('/products/:slug<[a-z0-9-]+>', function ($req, $res) {
    $slug = $req->param('slug');
    
    // Simular busca de produto
    $product = [
        'slug' => $slug,
        'name' => ucwords(str_replace('-', ' ', $slug)),
        'price' => rand(10, 1000) . '.99',
        'category' => 'electronics',
        'regex_matched' => '[a-z0-9-]+ (letras minúsculas, números e hífens)'
    ];
    
    return $res->json([
        'product' => $product,
        'route_info' => [
            'pattern' => '/products/:slug<[a-z0-9-]+>',
            'matched_slug' => $slug,
            'validation' => 'Slug deve conter apenas letras minúsculas, números e hífens'
        ]
    ]);
});

// 📅 Routing com data específica (ano/mês/dia)
$app->get('/api/v:version<\\d+>/posts/:year<\\d{4}>/:month<\\d{2}>/:day<\\d{2}>', function ($req, $res) {
    $version = (int) $req->param('version');
    $year = (int) $req->param('year');
    $month = (int) $req->param('month');
    $day = (int) $req->param('day');
    
    // Validar data
    if (!checkdate($month, $day, $year)) {
        return $res->status(400)->json([
            'error' => 'Data inválida',
            'provided' => "{$year}-{$month}-{$day}"
        ]);
    }
    
    $date = sprintf('%04d-%02d-%02d', $year, $month, $day);
    
    // Simular posts do dia
    $posts = [
        [
            'id' => 1,
            'title' => "Post do dia {$date}",
            'content' => 'Conteúdo exemplo',
            'published_at' => $date,
            'api_version' => $version
        ]
    ];
    
    return $res->json([
        'posts' => $posts,
        'date_info' => [
            'date' => $date,
            'day_of_week' => date('l', strtotime($date)),
            'api_version' => $version,
            'regex_patterns' => [
                'version' => '\\d+ (versão da API)',
                'year' => '\\d{4} (ano com 4 dígitos)',
                'month' => '\\d{2} (mês com 2 dígitos)',
                'day' => '\\d{2} (dia com 2 dígitos)'
            ]
        ]
    ]);
});

// 📁 Routing para arquivos com extensões específicas
$app->get('/files/:filename<[^/]+\\.(pdf|jpg|png|gif)>', function ($req, $res) {
    $filename = $req->param('filename');
    $extension = pathinfo($filename, PATHINFO_EXTENSION);
    $basename = pathinfo($filename, PATHINFO_FILENAME);
    
    // Simular informações do arquivo
    $fileInfo = [
        'filename' => $filename,
        'basename' => $basename,
        'extension' => $extension,
        'size' => rand(1024, 10485760), // 1KB a 10MB
        'mime_type' => [
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif'
        ][$extension] ?? 'application/octet-stream',
        'regex_matched' => '[^/]+\\.(pdf|jpg|png|gif) (nome + extensão válida)'
    ];
    
    return $res->json([
        'file' => $fileInfo,
        'route_info' => [
            'pattern' => '/files/:filename<[^/]+\\.(pdf|jpg|png|gif)>',
            'allowed_extensions' => ['pdf', 'jpg', 'png', 'gif'],
            'validation' => 'Arquivo deve ter extensão pdf, jpg, png ou gif'
        ]
    ]);
});

// 🆔 Routing com UUID
$app->get('/objects/:uuid<[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}>', function ($req, $res) {
    $uuid = $req->param('uuid');
    
    // Simular objeto com UUID
    $object = [
        'uuid' => $uuid,
        'type' => 'document',
        'created_at' => date('c'),
        'metadata' => [
            'version' => '1.0',
            'author' => 'system'
        ],
        'regex_matched' => 'UUID format (8-4-4-4-12 hex characters)'
    ];
    
    return $res->json([
        'object' => $object,
        'route_info' => [
            'pattern' => '/objects/:uuid<[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}>',
            'format' => 'UUID v4 (xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx)',
            'validation' => 'Deve ser um UUID válido em lowercase'
        ]
    ]);
});

// 🔤 Usando shortcuts predefinidos do PivotPHP
$app->get('/categories/:slug<slug>', function ($req, $res) {
    $slug = $req->param('slug');
    
    return $res->json([
        'category' => [
            'slug' => $slug,
            'name' => ucwords(str_replace('-', ' ', $slug)),
            'type' => 'category'
        ],
        'shortcut_info' => [
            'pattern' => '/categories/:slug<slug>',
            'shortcut_used' => 'slug',
            'equivalent_regex' => '[a-zA-Z0-9-_]+',
            'description' => 'Atalho predefinido para slugs'
        ]
    ]);
});

// 📧 Email pattern
$app->get('/contact/:email<[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}>', function ($req, $res) {
    $email = $req->param('email');
    
    return $res->json([
        'contact' => [
            'email' => $email,
            'domain' => substr(strrchr($email, '@'), 1),
            'username' => strstr($email, '@', true)
        ],
        'route_info' => [
            'pattern' => '/contact/:email<[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\\.[a-zA-Z]{2,}>',
            'validation' => 'Deve ser um email válido',
            'regex_parts' => [
                'username' => '[a-zA-Z0-9._%+-]+',
                'at_symbol' => '@',
                'domain' => '[a-zA-Z0-9.-]+',
                'tld' => '\\.[a-zA-Z]{2,}'
            ]
        ]
    ]);
});

// 🌍 Múltiplos parâmetros com regex complexo
$app->get('/geo/:country<[A-Z]{2}>/:state<[A-Z]{2}>/:city<[a-zA-Z-]+>', function ($req, $res) {
    $country = $req->param('country');
    $state = $req->param('state');
    $city = $req->param('city');
    
    return $res->json([
        'location' => [
            'country' => $country,
            'state' => $state,
            'city' => ucwords(str_replace('-', ' ', $city)),
            'full_path' => "{$country}/{$state}/{$city}"
        ],
        'route_info' => [
            'pattern' => '/geo/:country<[A-Z]{2}>/:state<[A-Z]{2}>/:city<[a-zA-Z-]+>',
            'validations' => [
                'country' => 'Código do país (2 letras maiúsculas)',
                'state' => 'Código do estado (2 letras maiúsculas)',
                'city' => 'Nome da cidade (letras e hífens)'
            ]
        ]
    ]);
});

// 🛡️ Demonstração de rota que NÃO vai fazer match
$app->get('/demo-no-match', function ($req, $res) {
    return $res->json([
        'message' => 'Esta rota sempre funciona',
        'examples_that_wont_match' => [
            '/users/abc' => 'ID deve ser numérico',
            '/products/UPPERCASE' => 'Slug deve ser lowercase',
            '/files/doc.txt' => 'Extensão não permitida',
            '/api/v1/posts/24/12/25' => 'Ano deve ter 4 dígitos',
            '/geo/BR/sp/sao-paulo' => 'Estado deve ser maiúsculo (SP)',
        ],
        'tip' => 'Teste essas URLs para ver como o regex bloqueia padrões inválidos'
    ]);
});

$app->run();