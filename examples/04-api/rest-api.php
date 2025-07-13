<?php

/**
 * 🚀 PivotPHP - API RESTful Completa
 * 
 * Demonstra implementação de uma API RESTful completa seguindo as melhores práticas
 * CRUD operations, HTTP status codes, validação, paginação e versionamento
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/04-api/rest-api.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/api/v1/
 * curl http://localhost:8000/api/v1/products
 * curl -X POST http://localhost:8000/api/v1/products -H "Content-Type: application/json" -d '{"name":"Notebook","price":2500.99,"category":"electronics"}'
 * curl -X PUT http://localhost:8000/api/v1/products/1 -H "Content-Type: application/json" -d '{"name":"Updated Product"}'
 * curl -X DELETE http://localhost:8000/api/v1/products/1
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// ===============================================
// DATA STORAGE (simulação de banco de dados)
// ===============================================

$products = [
    1 => [
        'id' => 1,
        'name' => 'iPhone 15 Pro',
        'description' => 'Smartphone Apple com chip A17 Pro',
        'price' => 8999.99,
        'category' => 'electronics',
        'stock' => 25,
        'sku' => 'IPHONE15PRO-256',
        'tags' => ['smartphone', 'apple', 'premium'],
        'status' => 'active',
        'created_at' => '2024-01-15T10:30:00Z',
        'updated_at' => '2024-01-15T10:30:00Z'
    ],
    2 => [
        'id' => 2,
        'name' => 'MacBook Pro M3',
        'description' => 'Laptop profissional com chip M3',
        'price' => 15999.99,
        'category' => 'electronics',
        'stock' => 12,
        'sku' => 'MACBOOK-M3-512',
        'tags' => ['laptop', 'apple', 'professional'],
        'status' => 'active',
        'created_at' => '2024-01-20T14:45:00Z',
        'updated_at' => '2024-01-20T14:45:00Z'
    ],
    3 => [
        'id' => 3,
        'name' => 'Clean Code',
        'description' => 'Livro sobre código limpo por Robert Martin',
        'price' => 89.90,
        'category' => 'books',
        'stock' => 50,
        'sku' => 'BOOK-CLEANCODE',
        'tags' => ['programming', 'development', 'bestseller'],
        'status' => 'active',
        'created_at' => '2024-01-10T09:15:00Z',
        'updated_at' => '2024-01-10T09:15:00Z'
    ]
];

$categories = [
    'electronics' => ['name' => 'Eletrônicos', 'description' => 'Dispositivos eletrônicos'],
    'books' => ['name' => 'Livros', 'description' => 'Livros e publicações'],
    'clothing' => ['name' => 'Roupas', 'description' => 'Vestuário e acessórios'],
    'home' => ['name' => 'Casa', 'description' => 'Itens para casa']
];

$nextId = 4;

// ===============================================
// UTILITY FUNCTIONS
// ===============================================

function validateProduct($data) {
    $errors = [];
    
    if (empty($data->name)) {
        $errors['name'] = 'Nome é obrigatório';
    } elseif (strlen($data->name) < 2) {
        $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
    } elseif (strlen($data->name) > 100) {
        $errors['name'] = 'Nome deve ter no máximo 100 caracteres';
    }
    
    if (!isset($data->price) || !is_numeric($data->price)) {
        $errors['price'] = 'Preço deve ser um número';
    } elseif ($data->price <= 0) {
        $errors['price'] = 'Preço deve ser maior que zero';
    } elseif ($data->price > 999999.99) {
        $errors['price'] = 'Preço deve ser menor que R$ 999.999,99';
    }
    
    if (empty($data->category)) {
        $errors['category'] = 'Categoria é obrigatória';
    }
    
    if (isset($data->stock) && (!is_numeric($data->stock) || $data->stock < 0)) {
        $errors['stock'] = 'Estoque deve ser um número não negativo';
    }
    
    if (isset($data->sku) && strlen($data->sku) > 50) {
        $errors['sku'] = 'SKU deve ter no máximo 50 caracteres';
    }
    
    return $errors;
}

function paginateResults($data, $page, $limit) {
    $total = count($data);
    $totalPages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;
    
    $paginatedData = array_slice($data, $offset, $limit, true);
    
    return [
        'data' => array_values($paginatedData),
        'pagination' => [
            'current_page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'from' => $total > 0 ? $offset + 1 : 0,
            'to' => min($offset + $limit, $total),
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1,
            'next_page' => $page < $totalPages ? $page + 1 : null,
            'prev_page' => $page > 1 ? $page - 1 : null
        ]
    ];
}

function filterProducts($products, $filters) {
    $filtered = $products;
    
    if (!empty($filters['category'])) {
        $filtered = array_filter($filtered, function($product) use ($filters) {
            return $product['category'] === $filters['category'];
        });
    }
    
    if (!empty($filters['status'])) {
        $filtered = array_filter($filtered, function($product) use ($filters) {
            return $product['status'] === $filters['status'];
        });
    }
    
    if (!empty($filters['min_price'])) {
        $filtered = array_filter($filtered, function($product) use ($filters) {
            return $product['price'] >= (float)$filters['min_price'];
        });
    }
    
    if (!empty($filters['max_price'])) {
        $filtered = array_filter($filtered, function($product) use ($filters) {
            return $product['price'] <= (float)$filters['max_price'];
        });
    }
    
    if (!empty($filters['search'])) {
        $search = strtolower($filters['search']);
        $filtered = array_filter($filtered, function($product) use ($search) {
            return strpos(strtolower($product['name']), $search) !== false ||
                   strpos(strtolower($product['description'] ?? ''), $search) !== false ||
                   in_array($search, array_map('strtolower', $product['tags'] ?? []));
        });
    }
    
    return $filtered;
}

// ===============================================
// MIDDLEWARE
// ===============================================

// API Headers middleware
$apiHeaders = function ($req, $res, $next) {
    $res->header('Content-Type', 'application/json; charset=utf-8');
    $res->header('X-API-Version', '1.0');
    $res->header('X-Powered-By', 'PivotPHP');
    $res->header('X-Request-ID', uniqid('req_', true));
    
    return $next($req, $res);
};

// CORS middleware
$cors = function ($req, $res, $next) {
    $res->header('Access-Control-Allow-Origin', '*');
    $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
    $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    $res->header('Access-Control-Expose-Headers', 'X-Total-Count, X-Page, X-Per-Page');
    
    if ($req->method() === 'OPTIONS') {
        return $res->status(204)->send('');
    }
    
    return $next($req, $res);
};

// Request logger middleware
$logger = function ($req, $res, $next) {
    $start = microtime(true);
    error_log("📡 API Request: {$req->method()} {$req->uri()}");
    
    $response = $next($req, $res);
    
    $duration = round((microtime(true) - $start) * 1000, 2);
    $res->header('X-Response-Time', $duration . 'ms');
    
    return $response;
};

// ===============================================
// APLICAR MIDDLEWARE GLOBALMENTE
// ===============================================

$app->use($cors);
$app->use($apiHeaders);
$app->use($logger);

// ===============================================
// API ROOT & DOCUMENTATION
// ===============================================

$app->get('/api/v1/', function ($req, $res) {
    return $res->json([
        'api' => 'PivotPHP RESTful API',
        'version' => '1.0',
        'description' => 'Demonstração completa de API RESTful',
        'base_url' => 'http://localhost:8000/api/v1',
        'documentation' => [
            'Products Resource' => [
                'GET /api/v1/products' => 'Listar produtos (com paginação e filtros)',
                'GET /api/v1/products/{id}' => 'Obter produto específico',
                'POST /api/v1/products' => 'Criar novo produto',
                'PUT /api/v1/products/{id}' => 'Atualizar produto completo',
                'PATCH /api/v1/products/{id}' => 'Atualizar produto parcial',
                'DELETE /api/v1/products/{id}' => 'Deletar produto'
            ],
            'Categories Resource' => [
                'GET /api/v1/categories' => 'Listar categorias',
                'GET /api/v1/categories/{slug}' => 'Obter categoria específica',
                'GET /api/v1/categories/{slug}/products' => 'Produtos da categoria'
            ]
        ],
        'query_parameters' => [
            'page' => 'Número da página (padrão: 1)',
            'limit' => 'Itens por página (padrão: 10, máx: 100)',
            'category' => 'Filtrar por categoria',
            'status' => 'Filtrar por status (active, inactive)',
            'min_price' => 'Preço mínimo',
            'max_price' => 'Preço máximo',
            'search' => 'Buscar em nome, descrição e tags',
            'sort' => 'Ordenação (name, price, created_at)',
            'order' => 'Direção (asc, desc)'
        ],
        'http_status_codes' => [
            200 => 'OK - Sucesso',
            201 => 'Created - Recurso criado',
            400 => 'Bad Request - Dados inválidos',
            404 => 'Not Found - Recurso não encontrado',
            422 => 'Unprocessable Entity - Erro de validação',
            500 => 'Internal Server Error - Erro interno'
        ],
        'examples' => [
            'list_products' => 'GET /api/v1/products?page=1&limit=5',
            'filter_products' => 'GET /api/v1/products?category=electronics&min_price=100',
            'search_products' => 'GET /api/v1/products?search=iphone',
            'create_product' => 'POST /api/v1/products {name, price, category}',
            'update_product' => 'PUT /api/v1/products/1 {name, price}'
        ]
    ]);
});

// ===============================================
// PRODUCTS RESOURCE
// ===============================================

// GET /api/v1/products - List products with pagination and filters
$app->get('/api/v1/products', function ($req, $res) use (&$products) {
    // Query parameters
    $page = max(1, (int) $req->get('page', 1));
    $limit = max(1, min(100, (int) $req->get('limit', 10)));
    $sort = $req->get('sort', 'id');
    $order = strtolower($req->get('order', 'asc')) === 'desc' ? 'desc' : 'asc';
    
    // Filters
    $filters = [
        'category' => $req->get('category'),
        'status' => $req->get('status', 'active'),
        'min_price' => $req->get('min_price'),
        'max_price' => $req->get('max_price'),
        'search' => $req->get('search')
    ];
    
    // Apply filters
    $filteredProducts = filterProducts($products, $filters);
    
    // Sort products
    $sortableFields = ['id', 'name', 'price', 'created_at', 'stock'];
    if (in_array($sort, $sortableFields)) {
        uasort($filteredProducts, function($a, $b) use ($sort, $order) {
            $result = $a[$sort] <=> $b[$sort];
            return $order === 'desc' ? -$result : $result;
        });
    }
    
    // Paginate results
    $result = paginateResults($filteredProducts, $page, $limit);
    
    // Add filter info to response
    $result['filters'] = array_filter($filters);
    $result['sort'] = ['field' => $sort, 'order' => $order];
    
    // Set pagination headers
    $res->header('X-Total-Count', (string)$result['pagination']['total']);
    $res->header('X-Page', (string)$page);
    $res->header('X-Per-Page', (string)$limit);
    
    return $res->json($result);
});

// GET /api/v1/products/{id} - Get specific product
$app->get('/api/v1/products/:id<\\d+>', function ($req, $res) use (&$products) {
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
    
    $product = $products[$id];
    
    // Add related information
    $response = [
        'data' => $product,
        'meta' => [
            'retrieved_at' => date('c'),
            'links' => [
                'self' => "/api/v1/products/{$id}",
                'update' => "/api/v1/products/{$id}",
                'delete' => "/api/v1/products/{$id}",
                'category' => "/api/v1/categories/{$product['category']}"
            ]
        ]
    ];
    
    return $res->json($response);
});

// POST /api/v1/products - Create new product
$app->post('/api/v1/products', function ($req, $res) use (&$products, &$nextId, $categories) {
    $body = $req->getBodyAsStdClass();
    
    // Validate input
    $errors = validateProduct($body);
    
    // Check if category exists
    if (!empty($body->category) && !isset($categories[$body->category])) {
        $errors['category'] = 'Categoria não existe';
    }
    
    // Check for duplicate SKU
    if (!empty($body->sku)) {
        foreach ($products as $product) {
            if ($product['sku'] === $body->sku) {
                $errors['sku'] = 'SKU já existe';
                break;
            }
        }
    }
    
    if (!empty($errors)) {
        return $res->status(422)->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Dados de entrada inválidos',
                'details' => $errors
            ]
        ]);
    }
    
    // Create product
    $product = [
        'id' => $nextId++,
        'name' => trim($body->name),
        'description' => trim($body->description ?? ''),
        'price' => (float) $body->price,
        'category' => $body->category,
        'stock' => (int) ($body->stock ?? 0),
        'sku' => trim($body->sku ?? ''),
        'tags' => $body->tags ?? [],
        'status' => $body->status ?? 'active',
        'created_at' => date('c'),
        'updated_at' => date('c')
    ];
    
    $products[$product['id']] = $product;
    
    $response = [
        'data' => $product,
        'meta' => [
            'created_at' => $product['created_at'],
            'links' => [
                'self' => "/api/v1/products/{$product['id']}",
                'update' => "/api/v1/products/{$product['id']}",
                'delete' => "/api/v1/products/{$product['id']}"
            ]
        ]
    ];
    
    return $res->status(201)->json($response);
});

// PUT /api/v1/products/{id} - Full update
$app->put('/api/v1/products/:id<\\d+>', function ($req, $res) use (&$products, $categories) {
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
    $errors = validateProduct($body);
    
    // Check category
    if (!empty($body->category) && !isset($categories[$body->category])) {
        $errors['category'] = 'Categoria não existe';
    }
    
    // Check SKU uniqueness (excluding current product)
    if (!empty($body->sku)) {
        foreach ($products as $productId => $product) {
            if ($productId !== $id && $product['sku'] === $body->sku) {
                $errors['sku'] = 'SKU já existe';
                break;
            }
        }
    }
    
    if (!empty($errors)) {
        return $res->status(422)->json([
            'error' => [
                'code' => 'VALIDATION_ERROR',
                'message' => 'Dados de entrada inválidos',
                'details' => $errors
            ]
        ]);
    }
    
    // Update product (full replacement)
    $originalProduct = $products[$id];
    $products[$id] = [
        'id' => $id,
        'name' => trim($body->name),
        'description' => trim($body->description ?? ''),
        'price' => (float) $body->price,
        'category' => $body->category,
        'stock' => (int) ($body->stock ?? 0),
        'sku' => trim($body->sku ?? ''),
        'tags' => $body->tags ?? [],
        'status' => $body->status ?? 'active',
        'created_at' => $originalProduct['created_at'],
        'updated_at' => date('c')
    ];
    
    return $res->json([
        'data' => $products[$id],
        'meta' => [
            'updated_at' => $products[$id]['updated_at'],
            'changes' => 'full_update'
        ]
    ]);
});

// PATCH /api/v1/products/{id} - Partial update
$app->patch('/api/v1/products/:id<\\d+>', function ($req, $res) use (&$products, $categories) {
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
    $changes = [];
    
    // Validate and update only provided fields
    if (isset($body->name)) {
        if (empty(trim($body->name))) {
            return $res->status(422)->json([
                'error' => ['name' => 'Nome não pode estar vazio']
            ]);
        }
        $product['name'] = trim($body->name);
        $changes[] = 'name';
    }
    
    if (isset($body->price)) {
        if (!is_numeric($body->price) || $body->price <= 0) {
            return $res->status(422)->json([
                'error' => ['price' => 'Preço deve ser um número positivo']
            ]);
        }
        $product['price'] = (float) $body->price;
        $changes[] = 'price';
    }
    
    if (isset($body->category)) {
        if (!isset($categories[$body->category])) {
            return $res->status(422)->json([
                'error' => ['category' => 'Categoria não existe']
            ]);
        }
        $product['category'] = $body->category;
        $changes[] = 'category';
    }
    
    if (isset($body->stock)) {
        if (!is_numeric($body->stock) || $body->stock < 0) {
            return $res->status(422)->json([
                'error' => ['stock' => 'Estoque deve ser um número não negativo']
            ]);
        }
        $product['stock'] = (int) $body->stock;
        $changes[] = 'stock';
    }
    
    if (isset($body->status)) {
        $allowedStatuses = ['active', 'inactive', 'discontinued'];
        if (!in_array($body->status, $allowedStatuses)) {
            return $res->status(422)->json([
                'error' => ['status' => 'Status deve ser: ' . implode(', ', $allowedStatuses)]
            ]);
        }
        $product['status'] = $body->status;
        $changes[] = 'status';
    }
    
    if (!empty($changes)) {
        $product['updated_at'] = date('c');
        $products[$id] = $product;
    }
    
    return $res->json([
        'data' => $product,
        'meta' => [
            'updated_at' => $product['updated_at'],
            'changes' => $changes,
            'change_count' => count($changes)
        ]
    ]);
});

// DELETE /api/v1/products/{id} - Delete product
$app->delete('/api/v1/products/:id<\\d+>', function ($req, $res) use (&$products) {
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
        'data' => $deletedProduct,
        'meta' => [
            'deleted_at' => date('c'),
            'message' => 'Produto deletado com sucesso'
        ]
    ]);
});

// ===============================================
// CATEGORIES RESOURCE
// ===============================================

// GET /api/v1/categories - List categories
$app->get('/api/v1/categories', function ($req, $res) use ($categories, $products) {
    $categoriesWithCount = [];
    
    foreach ($categories as $slug => $category) {
        $productCount = count(array_filter($products, function($product) use ($slug) {
            return $product['category'] === $slug && $product['status'] === 'active';
        }));
        
        $categoriesWithCount[] = [
            'slug' => $slug,
            'name' => $category['name'],
            'description' => $category['description'],
            'product_count' => $productCount,
            'links' => [
                'self' => "/api/v1/categories/{$slug}",
                'products' => "/api/v1/categories/{$slug}/products"
            ]
        ];
    }
    
    return $res->json([
        'data' => $categoriesWithCount,
        'meta' => [
            'total_categories' => count($categories),
            'retrieved_at' => date('c')
        ]
    ]);
});

// GET /api/v1/categories/{slug} - Get specific category
$app->get('/api/v1/categories/:slug<[a-z]+>', function ($req, $res) use ($categories, $products) {
    $slug = $req->param('slug');
    
    if (!isset($categories[$slug])) {
        return $res->status(404)->json([
            'error' => [
                'code' => 'CATEGORY_NOT_FOUND',
                'message' => 'Categoria não encontrada'
            ]
        ]);
    }
    
    $category = $categories[$slug];
    $productCount = count(array_filter($products, function($product) use ($slug) {
        return $product['category'] === $slug && $product['status'] === 'active';
    }));
    
    return $res->json([
        'data' => [
            'slug' => $slug,
            'name' => $category['name'],
            'description' => $category['description'],
            'product_count' => $productCount,
            'links' => [
                'self' => "/api/v1/categories/{$slug}",
                'products' => "/api/v1/categories/{$slug}/products"
            ]
        ]
    ]);
});

// GET /api/v1/categories/{slug}/products - Get products in category
$app->get('/api/v1/categories/:slug<[a-z]+>/products', function ($req, $res) use ($categories, $products) {
    $slug = $req->param('slug');
    
    if (!isset($categories[$slug])) {
        return $res->status(404)->json([
            'error' => [
                'code' => 'CATEGORY_NOT_FOUND',
                'message' => 'Categoria não encontrada'
            ]
        ]);
    }
    
    $page = max(1, (int) $req->get('page', 1));
    $limit = max(1, min(100, (int) $req->get('limit', 10)));
    
    $categoryProducts = array_filter($products, function($product) use ($slug) {
        return $product['category'] === $slug && $product['status'] === 'active';
    });
    
    $result = paginateResults($categoryProducts, $page, $limit);
    $result['category'] = [
        'slug' => $slug,
        'name' => $categories[$slug]['name']
    ];
    
    return $res->json($result);
});

// ===============================================
// API STATISTICS & HEALTH
// ===============================================

$app->get('/api/v1/stats', function ($req, $res) use ($products, $categories) {
    $stats = [
        'products' => [
            'total' => count($products),
            'active' => count(array_filter($products, fn($p) => $p['status'] === 'active')),
            'by_category' => []
        ],
        'categories' => [
            'total' => count($categories),
            'list' => array_keys($categories)
        ],
        'price_range' => [
            'min' => min(array_column($products, 'price')),
            'max' => max(array_column($products, 'price')),
            'avg' => round(array_sum(array_column($products, 'price')) / count($products), 2)
        ]
    ];
    
    foreach ($categories as $slug => $category) {
        $count = count(array_filter($products, fn($p) => $p['category'] === $slug));
        $stats['products']['by_category'][$slug] = $count;
    }
    
    return $res->json([
        'data' => $stats,
        'generated_at' => date('c')
    ]);
});

$app->get('/api/v1/health', function ($req, $res) {
    return $res->json([
        'status' => 'healthy',
        'version' => '1.0',
        'uptime' => 'simulated',
        'checks' => [
            'database' => 'ok',
            'memory' => 'ok',
            'disk' => 'ok'
        ],
        'timestamp' => date('c')
    ]);
});

$app->run();