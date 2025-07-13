<?php

/**
 * 🚀 PivotPHP - API JSON Básica
 * 
 * Demonstra criação de uma API JSON simples e eficiente
 * Inclui validação, códigos de status apropriados e estruturas consistentes
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/01-basics/json-api.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/api/
 * curl http://localhost:8000/api/products
 * curl -X POST http://localhost:8000/api/products -H "Content-Type: application/json" -d '{"name":"Notebook","price":2500.99}'
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// Banco de dados simulado
$products = [
    1 => ['id' => 1, 'name' => 'Smartphone', 'price' => 899.99, 'category' => 'electronics'],
    2 => ['id' => 2, 'name' => 'Laptop', 'price' => 1299.99, 'category' => 'electronics'],
    3 => ['id' => 3, 'name' => 'Livro PHP', 'price' => 49.99, 'category' => 'books'],
];
$nextId = 4;

// Middleware para API JSON
$app->use('/api/*', function ($req, $res, $next) {
    // Headers padrão para API
    $res->header('Content-Type', 'application/json');
    $res->header('X-API-Version', '1.0');
    $res->header('X-Framework', 'PivotPHP');
    
    // CORS básico
    $res->header('Access-Control-Allow-Origin', '*');
    $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
    $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
    
    // Responder OPTIONS para CORS preflight
    if ($req->method() === 'OPTIONS') {
        return $res->status(200)->send('');
    }
    
    return $next($req, $res);
});

// 📋 API Info
$app->get('/api/', function ($req, $res) {
    return $res->json([
        'api' => 'PivotPHP Products API',
        'version' => '1.0',
        'framework' => 'PivotPHP Core ' . Application::VERSION,
        'endpoints' => [
            'GET /api/products' => 'Listar produtos',
            'GET /api/products/:id' => 'Obter produto específico',
            'POST /api/products' => 'Criar produto',
            'PUT /api/products/:id' => 'Atualizar produto',
            'DELETE /api/products/:id' => 'Deletar produto',
            'GET /api/categories' => 'Listar categorias',
            'GET /api/search' => 'Buscar produtos'
        ],
        'timestamp' => date('c'),
        'status' => 'operational'
    ]);
});

// 📦 GET - Listar produtos
$app->get('/api/products', function ($req, $res) use (&$products) {
    $page = max(1, (int) $req->get('page', 1));
    $limit = min(100, max(1, (int) $req->get('limit', 10)));
    $category = $req->get('category');
    
    $filteredProducts = $products;
    
    // Filtrar por categoria se especificado
    if ($category) {
        $filteredProducts = array_filter($products, function ($product) use ($category) {
            return $product['category'] === $category;
        });
    }
    
    $total = count($filteredProducts);
    $offset = ($page - 1) * $limit;
    $paginatedProducts = array_slice($filteredProducts, $offset, $limit, true);
    
    return $res->json([
        'data' => array_values($paginatedProducts),
        'meta' => [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
            'showing' => count($paginatedProducts)
        ],
        'filters' => [
            'category' => $category
        ]
    ]);
});

// 📦 GET - Produto específico
$app->get('/api/products/:id', function ($req, $res) use (&$products) {
    $id = (int) $req->param('id');
    
    if (!isset($products[$id])) {
        return $res->status(404)->json([
            'error' => [
                'code' => 'PRODUCT_NOT_FOUND',
                'message' => 'Produto não encontrado',
                'details' => "Produto com ID {$id} não existe"
            ]
        ]);
    }
    
    return $res->json([
        'data' => $products[$id],
        'links' => [
            'self' => "/api/products/{$id}",
            'update' => "/api/products/{$id}",
            'delete' => "/api/products/{$id}"
        ]
    ]);
});

// ➕ POST - Criar produto
$app->post('/api/products', function ($req, $res) use (&$products, &$nextId) {
    $body = $req->getBodyAsStdClass();
    
    // Validação
    $errors = [];
    
    if (empty($body->name)) {
        $errors[] = 'Nome é obrigatório';
    }
    
    if (!isset($body->price) || !is_numeric($body->price) || $body->price <= 0) {
        $errors[] = 'Preço deve ser um número positivo';
    }
    
    if (empty($body->category)) {
        $errors[] = 'Categoria é obrigatória';
    }
    
    if (!empty($errors)) {
        return $res->status(422)->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Dados inválidos',
                'details' => $errors
            ]
        ]);
    }
    
    $product = [
        'id' => $nextId++,
        'name' => trim($body->name),
        'price' => (float) $body->price,
        'category' => trim($body->category),
        'created_at' => date('c'),
        'updated_at' => date('c')
    ];
    
    $products[$product['id']] = $product;
    
    return $res->status(201)->json([
        'data' => $product,
        'message' => 'Produto criado com sucesso',
        'links' => [
            'self' => "/api/products/{$product['id']}"
        ]
    ]);
});

// ✏️ PUT - Atualizar produto
$app->put('/api/products/:id', function ($req, $res) use (&$products) {
    $id = (int) $req->param('id');
    
    if (!isset($products[$id])) {
        return $res->status(404)->json([
            'error' => [
                'code' => 'PRODUCT_NOT_FOUND',
                'message' => 'Produto não encontrado'
            ]
        ]);
    }
    
    $body = $req->getBodyAsStdClass();
    $product = $products[$id];
    
    // Atualizar campos fornecidos
    if (isset($body->name) && !empty(trim($body->name))) {
        $product['name'] = trim($body->name);
    }
    
    if (isset($body->price) && is_numeric($body->price) && $body->price > 0) {
        $product['price'] = (float) $body->price;
    }
    
    if (isset($body->category) && !empty(trim($body->category))) {
        $product['category'] = trim($body->category);
    }
    
    $product['updated_at'] = date('c');
    $products[$id] = $product;
    
    return $res->json([
        'data' => $product,
        'message' => 'Produto atualizado com sucesso'
    ]);
});

// 🗑️ DELETE - Deletar produto
$app->delete('/api/products/:id', function ($req, $res) use (&$products) {
    $id = (int) $req->param('id');
    
    if (!isset($products[$id])) {
        return $res->status(404)->json([
            'error' => [
                'code' => 'PRODUCT_NOT_FOUND',
                'message' => 'Produto não encontrado'
            ]
        ]);
    }
    
    $deletedProduct = $products[$id];
    unset($products[$id]);
    
    return $res->json([
        'message' => 'Produto deletado com sucesso',
        'data' => $deletedProduct
    ]);
});

// 📚 GET - Categorias disponíveis
$app->get('/api/categories', function ($req, $res) use (&$products) {
    $categories = array_unique(array_column($products, 'category'));
    sort($categories);
    
    $categoriesWithCount = array_map(function ($category) use ($products) {
        $count = count(array_filter($products, function ($product) use ($category) {
            return $product['category'] === $category;
        }));
        
        return [
            'name' => $category,
            'product_count' => $count
        ];
    }, $categories);
    
    return $res->json([
        'data' => $categoriesWithCount,
        'total_categories' => count($categories)
    ]);
});

// 🔍 GET - Buscar produtos
$app->get('/api/search', function ($req, $res) use (&$products) {
    $query = $req->get('q', '');
    $minPrice = (float) $req->get('min_price', 0);
    $maxPrice = (float) $req->get('max_price', PHP_FLOAT_MAX);
    
    if (empty($query)) {
        return $res->status(400)->json([
            'error' => [
                'code' => 'MISSING_QUERY',
                'message' => 'Parâmetro de busca q é obrigatório'
            ]
        ]);
    }
    
    $results = array_filter($products, function ($product) use ($query, $minPrice, $maxPrice) {
        $matchesQuery = stripos($product['name'], $query) !== false || 
                       stripos($product['category'], $query) !== false;
        $matchesPrice = $product['price'] >= $minPrice && $product['price'] <= $maxPrice;
        
        return $matchesQuery && $matchesPrice;
    });
    
    return $res->json([
        'data' => array_values($results),
        'query' => [
            'text' => $query,
            'min_price' => $minPrice,
            'max_price' => $maxPrice === PHP_FLOAT_MAX ? null : $maxPrice
        ],
        'results_count' => count($results)
    ]);
});

// 🚫 Rota não encontrada para API
$app->use('/api/*', function ($req, $res) {
    return $res->status(404)->json([
        'error' => [
            'code' => 'ENDPOINT_NOT_FOUND',
            'message' => 'Endpoint não encontrado',
            'path' => $req->uri(),
            'method' => $req->method(),
            'available_endpoints' => '/api/ para lista completa'
        ]
    ]);
});

$app->run();