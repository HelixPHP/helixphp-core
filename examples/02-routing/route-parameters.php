<?php

/**
 * 🎯 PivotPHP - Parâmetros de Rota Avançados
 * 
 * Demonstra todas as funcionalidades de parâmetros de rota do PivotPHP
 * Parâmetros obrigatórios, opcionais, query strings e wildcards
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/02-routing/route-parameters.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/users/123
 * curl http://localhost:8000/posts/2024/technology
 * curl "http://localhost:8000/search?q=php&category=tech&page=2"
 * curl http://localhost:8000/api/users/123/posts/456/comments
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📋 Página inicial com exemplos
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Route Parameters Examples',
        'description' => 'Demonstrações completas de parâmetros de rota',
        'examples' => [
            'Basic Parameters' => [
                'GET /users/:id' => 'Parâmetro básico',
                'GET /posts/:year/:category' => 'Múltiplos parâmetros',
                'GET /api/users/:userId/posts/:postId/comments' => 'Parâmetros aninhados'
            ],
            'Query Parameters' => [
                'GET /search?q=term&page=1' => 'Query strings',
                'GET /filter?category=tech&sort=date&order=desc' => 'Filtros complexos'
            ],
            'Mixed Parameters' => [
                'GET /posts/:category?page=1&limit=10' => 'Route + Query params',
                'GET /users/:id/posts?status=published' => 'Aninhados + Query'
            ],
            'Optional Parameters' => [
                'GET /browse/:category?/:subcategory?' => 'Parâmetros opcionais',
                'GET /archive/:year?/:month?' => 'Data opcional'
            ]
        ],
        'parameter_methods' => [
            '$req->param(name)' => 'Obter parâmetro de rota',
            '$req->get(name, default)' => 'Obter query parameter',
            '$req->query()' => 'Todos os query parameters',
            '$req->params()' => 'Todos os parâmetros de rota'
        ]
    ]);
});

// 👤 Parâmetro básico - ID de usuário
$app->get('/users/:id', function ($req, $res) {
    $id = $req->param('id');
    
    // Simular dados do usuário
    $user = [
        'id' => $id,
        'name' => "User {$id}",
        'email' => "user{$id}@example.com",
        'profile' => [
            'bio' => "Biografia do usuário {$id}",
            'location' => 'São Paulo, Brasil',
            'joined' => '2024-01-15'
        ]
    ];
    
    return $res->json([
        'user' => $user,
        'route_params' => $req->params(),
        'extracted_id' => $id,
        'id_type' => gettype($id)
    ]);
});

// 📝 Múltiplos parâmetros - Posts por ano e categoria
$app->get('/posts/:year/:category', function ($req, $res) {
    $year = $req->param('year');
    $category = $req->param('category');
    
    // Simular posts
    $posts = [
        [
            'id' => 1,
            'title' => "Post sobre {$category} em {$year}",
            'category' => $category,
            'year' => $year,
            'content' => 'Conteúdo do post...',
            'published_at' => "{$year}-06-15"
        ],
        [
            'id' => 2,
            'title' => "Outro post de {$category}",
            'category' => $category,
            'year' => $year,
            'content' => 'Mais conteúdo...',
            'published_at' => "{$year}-08-22"
        ]
    ];
    
    return $res->json([
        'posts' => $posts,
        'filters' => [
            'year' => $year,
            'category' => $category
        ],
        'route_params' => $req->params(),
        'total_posts' => count($posts)
    ]);
});

// 🔍 Query parameters - Sistema de busca
$app->get('/search', function ($req, $res) {
    // Parâmetros obrigatórios
    $query = $req->get('q');
    
    // Parâmetros opcionais com defaults
    $page = (int) $req->get('page', 1);
    $limit = (int) $req->get('limit', 10);
    $category = $req->get('category', 'all');
    $sort = $req->get('sort', 'relevance');
    $order = $req->get('order', 'desc');
    
    // Parâmetros de filtro avançado
    $dateFrom = $req->get('date_from');
    $dateTo = $req->get('date_to');
    $author = $req->get('author');
    $tags = $req->get('tags'); // Pode ser string com vírgulas
    
    // Processar tags se fornecidas
    $tagsArray = $tags ? explode(',', $tags) : [];
    
    if (!$query) {
        return $res->status(400)->json([
            'error' => 'Parâmetro q (query) é obrigatório',
            'example' => '/search?q=php&category=tech&page=1'
        ]);
    }
    
    // Simular resultados de busca
    $results = [
        [
            'id' => 1,
            'title' => "Tutorial de {$query}",
            'category' => $category !== 'all' ? $category : 'technology',
            'author' => $author ?: 'João Silva',
            'published_at' => '2024-01-15',
            'relevance_score' => 95.5
        ],
        [
            'id' => 2,
            'title' => "Guia avançado de {$query}",
            'category' => $category !== 'all' ? $category : 'programming',
            'author' => $author ?: 'Maria Santos',
            'published_at' => '2024-02-20',
            'relevance_score' => 87.2
        ]
    ];
    
    return $res->json([
        'results' => $results,
        'search_params' => [
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'category' => $category,
            'sort' => $sort,
            'order' => $order
        ],
        'filters' => [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'author' => $author,
            'tags' => $tagsArray
        ],
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total_results' => 42,
            'total_pages' => 5
        ],
        'all_query_params' => $req->query()
    ]);
});

// 🔗 Parâmetros aninhados - Comentários de posts de usuários
$app->get('/api/users/:userId/posts/:postId/comments', function ($req, $res) {
    $userId = $req->param('userId');
    $postId = $req->param('postId');
    
    // Query parameters para paginação
    $page = (int) $req->get('page', 1);
    $limit = (int) $req->get('limit', 5);
    $status = $req->get('status', 'approved');
    
    // Simular comentários
    $comments = [
        [
            'id' => 1,
            'user_id' => $userId,
            'post_id' => $postId,
            'author' => 'Ana Costa',
            'content' => 'Ótimo post! Muito informativo.',
            'status' => 'approved',
            'created_at' => '2024-01-16 10:30:00'
        ],
        [
            'id' => 2,
            'user_id' => $userId,
            'post_id' => $postId,
            'author' => 'Carlos Lima',
            'content' => 'Concordo totalmente com os pontos apresentados.',
            'status' => 'approved',
            'created_at' => '2024-01-16 14:45:00'
        ]
    ];
    
    return $res->json([
        'comments' => $comments,
        'context' => [
            'user_id' => $userId,
            'post_id' => $postId,
            'status_filter' => $status
        ],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => count($comments)
        ],
        'route_hierarchy' => [
            'user' => "/api/users/{$userId}",
            'post' => "/api/users/{$userId}/posts/{$postId}",
            'comments' => "/api/users/{$userId}/posts/{$postId}/comments"
        ]
    ]);
});

// 🗂️ Parâmetros com wildcards
$app->get('/files/*', function ($req, $res) {
    $path = $req->param('*'); // Captura tudo após /files/
    
    // Analisar o caminho
    $pathParts = explode('/', trim($path, '/'));
    $filename = end($pathParts);
    $directory = implode('/', array_slice($pathParts, 0, -1));
    
    return $res->json([
        'file_info' => [
            'full_path' => $path,
            'directory' => $directory ?: 'root',
            'filename' => $filename,
            'path_parts' => $pathParts,
            'depth' => count($pathParts)
        ],
        'wildcard_info' => [
            'pattern' => '/files/*',
            'captured' => $path,
            'description' => 'Wildcard captura todo o resto da URL'
        ]
    ]);
});

// 📊 Combinando route e query parameters
$app->get('/reports/:type/:year', function ($req, $res) {
    $type = $req->param('type');
    $year = $req->param('year');
    
    // Query parameters para customização
    $format = $req->get('format', 'json');
    $detailed = $req->get('detailed', 'false') === 'true';
    $department = $req->get('department');
    $months = $req->get('months'); // Exemplo: "1,2,3" para Q1
    
    $monthsArray = $months ? array_map('intval', explode(',', $months)) : range(1, 12);
    
    // Simular dados do relatório
    $reportData = [
        'type' => $type,
        'year' => (int) $year,
        'months_included' => $monthsArray,
        'department' => $department,
        'summary' => [
            'total_records' => 1250,
            'average_per_month' => 104.2,
            'peak_month' => 'Dezembro'
        ]
    ];
    
    if ($detailed) {
        $reportData['detailed_data'] = [
            'monthly_breakdown' => array_map(function ($month) {
                return [
                    'month' => $month,
                    'value' => rand(50, 200),
                    'growth' => rand(-10, 25) . '%'
                ];
            }, $monthsArray)
        ];
    }
    
    $response = [
        'report' => $reportData,
        'parameters' => [
            'route' => [
                'type' => $type,
                'year' => $year
            ],
            'query' => [
                'format' => $format,
                'detailed' => $detailed,
                'department' => $department,
                'months' => $months
            ]
        ],
        'metadata' => [
            'generated_at' => date('c'),
            'format' => $format,
            'request_uri' => $req->uri()
        ]
    ];
    
    // Retornar em formato diferente se solicitado
    if ($format === 'csv') {
        $res->header('Content-Type', 'text/csv');
        return $res->send("type,year,total_records\n{$type},{$year},1250");
    }
    
    return $res->json($response);
});

// 🏷️ Demonstração de todos os tipos de parâmetros juntos
$app->get('/demo/:category/:id', function ($req, $res) {
    return $res->json([
        'demonstration' => 'Todos os tipos de parâmetros',
        'route_parameters' => [
            'all_params' => $req->params(),
            'category' => $req->param('category'),
            'id' => $req->param('id')
        ],
        'query_parameters' => [
            'all_query' => $req->query(),
            'specific_examples' => [
                'page' => $req->get('page'),
                'limit' => $req->get('limit', 10), // com default
                'sort' => $req->get('sort')
            ]
        ],
        'request_info' => [
            'method' => $req->method(),
            'uri' => $req->uri(),
            'full_url' => $req->header('Host') . $req->uri()
        ],
        'tip' => 'Teste com: /demo/technology/123?page=2&limit=20&sort=date'
    ]);
});

$app->run();