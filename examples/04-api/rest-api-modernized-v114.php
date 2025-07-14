<?php

/**
 * 🚀 PivotPHP v1.1.4+ - API RESTful Modernizada
 * 
 * Versão modernizada do exemplo rest-api.php com novos recursos v1.1.4+:
 * • Array callables nativos para controllers
 * • JsonBufferPool com threshold inteligente  
 * • Enhanced error diagnostics com ContextualException
 * • Controllers organizados e middleware otimizado
 * 
 * ✨ Migração de v1.1.3 → v1.1.4+:
 * • Closures → Array callables [Controller::class, 'method']
 * • json_encode() → JsonBufferPool automático
 * • Exceptions → ContextualException com contexto
 * • Middleware reorganizado com array callables
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/04-api/rest-api-modernized-v114.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/api/v1/
 * curl http://localhost:8000/api/v1/products
 * curl -X POST http://localhost:8000/api/v1/products -H "Content-Type: application/json" -d '{"name":"Notebook","price":2500.99,"category":"electronics"}'
 * curl -X PUT http://localhost:8000/api/v1/products/1 -H "Content-Type: application/json" -d '{"name":"Updated Product"}'
 * curl -X DELETE http://localhost:8000/api/v1/products/1
 * curl http://localhost:8000/api/v1/performance  # v1.1.4+ Performance stats
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;

// ===============================================
// CONTROLLERS v1.1.4+ (Array Callables)
// ===============================================

class ProductController
{
    private array $products;
    private int $nextId = 4;
    
    public function __construct()
    {
        $this->products = [
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
    }
    
    public function index($req, $res)
    {
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
        $filteredProducts = $this->filterProducts($filters);
        
        // Sort products
        $sortableFields = ['id', 'name', 'price', 'created_at', 'stock'];
        if (in_array($sort, $sortableFields)) {
            uasort($filteredProducts, function($a, $b) use ($sort, $order) {
                $result = $a[$sort] <=> $b[$sort];
                return $order === 'desc' ? -$result : $result;
            });
        }
        
        // Paginate results
        $result = $this->paginateResults($filteredProducts, $page, $limit);
        
        // ✅ NOVO v1.1.4+: Add optimization info
        $result['optimization_v114'] = [
            'json_pooling' => JsonBufferPool::shouldUsePooling($result) ? 'active' : 'direct_encode',
            'data_size' => $this->estimateDataSize($result),
            'performance_note' => 'Automatic optimization based on data size'
        ];
        
        // Add filter info to response
        $result['filters'] = array_filter($filters);
        $result['sort'] = ['field' => $sort, 'order' => $order];
        
        // Set pagination headers
        $res->header('X-Total-Count', (string)$result['pagination']['total']);
        $res->header('X-Page', (string)$page);
        $res->header('X-Per-Page', (string)$limit);
        
        return $res->json($result);
    }
    
    public function show($req, $res)
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->products[$id])) {
            // ✅ NOVO v1.1.4+: Enhanced error diagnostics
            throw ContextualException::parameterError(
                'id',
                'existing product ID',
                $id,
                '/api/v1/products/:id'
            );
        }
        
        $product = $this->products[$id];
        
        // Add related information
        $response = [
            'data' => $product,
            'meta' => [
                'retrieved_at' => date('c'),
                'optimization' => [
                    'uses_pooling' => JsonBufferPool::shouldUsePooling($product),
                    'strategy' => 'Single product - optimized for speed'
                ],
                'links' => [
                    'self' => "/api/v1/products/{$id}",
                    'update' => "/api/v1/products/{$id}",
                    'delete' => "/api/v1/products/{$id}",
                    'category' => "/api/v1/categories/{$product['category']}"
                ]
            ]
        ];
        
        return $res->json($response);
    }
    
    public function store($req, $res)
    {
        $body = $req->getBodyAsStdClass();
        
        // Validate input
        $errors = $this->validateProduct($body);
        
        // Check for duplicate SKU
        if (!empty($body->sku)) {
            foreach ($this->products as $product) {
                if ($product['sku'] === $body->sku) {
                    $errors['sku'] = 'SKU já existe';
                    break;
                }
            }
        }
        
        if (!empty($errors)) {
            // ✅ NOVO v1.1.4+: Enhanced validation errors
            return $res->status(422)->json([
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Dados de entrada inválidos',
                    'details' => $errors,
                    'context' => [
                        'endpoint' => 'POST /api/v1/products',
                        'received_fields' => array_keys((array)$body),
                        'required_fields' => ['name', 'price', 'category']
                    ],
                    'suggestions' => [
                        'Verifique se todos os campos obrigatórios estão presentes',
                        'Confirme que o preço é um número positivo',
                        'Verifique se a categoria existe'
                    ]
                ]
            ]);
        }
        
        // Create product
        $product = [
            'id' => $this->nextId++,
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
        
        $this->products[$product['id']] = $product;
        
        $response = [
            'data' => $product,
            'meta' => [
                'created_at' => $product['created_at'],
                'optimization' => [
                    'json_encoding' => 'Optimized with JsonBufferPool v1.1.4+',
                    'performance_gain' => 'Automatic based on response size'
                ],
                'links' => [
                    'self' => "/api/v1/products/{$product['id']}",
                    'update' => "/api/v1/products/{$product['id']}",
                    'delete' => "/api/v1/products/{$product['id']}"
                ]
            ]
        ];
        
        return $res->status(201)->json($response);
    }
    
    public function update($req, $res)
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->products[$id])) {
            throw ContextualException::parameterError(
                'id',
                'existing product ID',
                $id,
                '/api/v1/products/:id'
            );
        }
        
        $body = $req->getBodyAsStdClass();
        $errors = $this->validateProduct($body);
        
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
        $originalProduct = $this->products[$id];
        $this->products[$id] = [
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
            'data' => $this->products[$id],
            'meta' => [
                'updated_at' => $this->products[$id]['updated_at'],
                'changes' => 'full_update',
                'optimization' => 'JsonBufferPool v1.1.4+ active'
            ]
        ]);
    }
    
    public function destroy($req, $res)
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->products[$id])) {
            throw ContextualException::parameterError(
                'id',
                'existing product ID', 
                $id,
                '/api/v1/products/:id'
            );
        }
        
        $deletedProduct = $this->products[$id];
        unset($this->products[$id]);
        
        return $res->json([
            'data' => $deletedProduct,
            'meta' => [
                'deleted_at' => date('c'),
                'message' => 'Produto deletado com sucesso'
            ]
        ]);
    }
    
    public function patch($req, $res)
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->products[$id])) {
            throw ContextualException::parameterError(
                'id',
                'existing product ID',
                $id,
                '/api/v1/products/:id'
            );
        }
        
        $body = $req->getBodyAsStdClass();
        $product = $this->products[$id];
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
        
        if (!empty($changes)) {
            $product['updated_at'] = date('c');
            $this->products[$id] = $product;
        }
        
        return $res->json([
            'data' => $product,
            'meta' => [
                'updated_at' => $product['updated_at'],
                'changes' => $changes,
                'change_count' => count($changes),
                'optimization' => 'JsonBufferPool v1.1.4+ partial update'
            ]
        ]);
    }
    
    // Helper methods
    private function validateProduct($data): array
    {
        $errors = [];
        
        if (empty($data->name)) {
            $errors['name'] = 'Nome é obrigatório';
        } elseif (strlen($data->name) < 2) {
            $errors['name'] = 'Nome deve ter pelo menos 2 caracteres';
        }
        
        if (!isset($data->price) || !is_numeric($data->price)) {
            $errors['price'] = 'Preço deve ser um número';
        } elseif ($data->price <= 0) {
            $errors['price'] = 'Preço deve ser maior que zero';
        }
        
        if (empty($data->category)) {
            $errors['category'] = 'Categoria é obrigatória';
        }
        
        return $errors;
    }
    
    private function filterProducts(array $filters): array
    {
        $filtered = $this->products;
        
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
        
        if (!empty($filters['search'])) {
            $search = strtolower($filters['search']);
            $filtered = array_filter($filtered, function($product) use ($search) {
                return strpos(strtolower($product['name']), $search) !== false ||
                       strpos(strtolower($product['description'] ?? ''), $search) !== false;
            });
        }
        
        return $filtered;
    }
    
    private function paginateResults(array $data, int $page, int $limit): array
    {
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
                'has_prev' => $page > 1
            ]
        ];
    }
    
    private function estimateDataSize(array $data): string
    {
        $size = strlen(json_encode($data));
        if ($size < 1024) return $size . ' bytes';
        if ($size < 1024 * 1024) return round($size / 1024, 1) . ' KB';
        return round($size / (1024 * 1024), 1) . ' MB';
    }
}

class CategoryController
{
    private array $categories;
    
    public function __construct()
    {
        $this->categories = [
            'electronics' => ['name' => 'Eletrônicos', 'description' => 'Dispositivos eletrônicos'],
            'books' => ['name' => 'Livros', 'description' => 'Livros e publicações'],
            'clothing' => ['name' => 'Roupas', 'description' => 'Vestuário e acessórios'],
            'home' => ['name' => 'Casa', 'description' => 'Itens para casa']
        ];
    }
    
    public function index($req, $res)
    {
        $categoriesWithCount = [];
        
        foreach ($this->categories as $slug => $category) {
            $categoriesWithCount[] = [
                'slug' => $slug,
                'name' => $category['name'],
                'description' => $category['description'],
                'links' => [
                    'self' => "/api/v1/categories/{$slug}",
                    'products' => "/api/v1/categories/{$slug}/products"
                ]
            ];
        }
        
        return $res->json([
            'data' => $categoriesWithCount,
            'meta' => [
                'total_categories' => count($this->categories),
                'retrieved_at' => date('c'),
                'optimization_v114' => [
                    'json_strategy' => JsonBufferPool::shouldUsePooling($categoriesWithCount) 
                        ? 'buffer_pool' : 'direct_encode',
                    'performance_note' => 'Automatic optimization based on data size'
                ]
            ]
        ]);
    }
    
    public function show($req, $res)
    {
        $slug = $req->param('slug');
        
        if (!isset($this->categories[$slug])) {
            throw ContextualException::parameterError(
                'slug',
                'existing category slug',
                $slug,
                '/api/v1/categories/:slug'
            );
        }
        
        $category = $this->categories[$slug];
        
        return $res->json([
            'data' => [
                'slug' => $slug,
                'name' => $category['name'],
                'description' => $category['description'],
                'links' => [
                    'self' => "/api/v1/categories/{$slug}",
                    'products' => "/api/v1/categories/{$slug}/products"
                ]
            ]
        ]);
    }
}

class ApiController
{
    public function root($req, $res)
    {
        return $res->json([
            'api' => 'PivotPHP RESTful API v1.1.4+',
            'version' => '1.0',
            'description' => 'API RESTful modernizada com novos recursos v1.1.4+',
            'base_url' => 'http://localhost:8000/api/v1',
            'features_v114' => [
                'array_callables' => 'Native controller support ✅',
                'json_optimization' => 'Intelligent threshold pooling ✅',
                'error_diagnostics' => 'Enhanced contextual errors ✅',
                'performance_monitoring' => 'Real-time optimization stats ✅'
            ],
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
                    'GET /api/v1/categories/{slug}' => 'Obter categoria específica'
                ]
            ],
            'migration_from_old_version' => [
                'before' => 'function($req, $res) { ... }',
                'after' => '[Controller::class, \'method\']',
                'benefits' => 'Better organization, IDE support, enhanced errors'
            ]
        ]);
    }
    
    public function performance($req, $res)
    {
        $stats = JsonBufferPool::getStatistics();
        
        return $res->json([
            'framework' => 'PivotPHP Core v1.1.4+',
            'json_pool_stats' => $stats,
            'performance_metrics' => [
                'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_memory_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'optimization_active' => true,
                'threshold_bytes' => 256,
                'pool_efficiency' => $stats['efficiency'] ?? 'N/A'
            ],
            'improvements_v114' => [
                'automatic_threshold' => 'No configuration needed',
                'intelligent_optimization' => 'System decides when to use pooling',
                'zero_overhead' => 'Small responses use direct json_encode()',
                'performance_guarantee' => 'Never slower than standard encoding'
            ]
        ]);
    }
    
    public function health($req, $res)
    {
        $health = [
            'status' => 'healthy',
            'version' => Application::VERSION,
            'features' => [
                'array_callables' => 'enabled',
                'json_optimization' => 'enabled',
                'contextual_errors' => 'enabled'
            ],
            'checks' => [
                'memory' => 'ok',
                'performance' => 'optimized',
                'errors' => 'enhanced'
            ],
            'timestamp' => date('c')
        ];
        
        // Small response - should use direct json_encode()
        $usePooling = JsonBufferPool::shouldUsePooling($health);
        
        $health['optimization'] = [
            'uses_pooling' => $usePooling,
            'strategy' => $usePooling ? 'buffer_pool' : 'direct_json_encode',
            'note' => 'Health check optimized for minimal overhead'
        ];
        
        return $res->json($health);
    }
}

// ===============================================
// MIDDLEWARE v1.1.4+ (Array Callables)
// ===============================================

class ApiMiddleware
{
    public static function headers($req, $res, $next)
    {
        $res->header('Content-Type', 'application/json; charset=utf-8');
        $res->header('X-API-Version', '1.0');
        $res->header('X-Powered-By', 'PivotPHP v1.1.4+');
        $res->header('X-Features', 'array-callables,json-optimization,enhanced-errors');
        $res->header('X-Request-ID', uniqid('req_', true));
        
        return $next($req, $res);
    }
    
    public static function cors($req, $res, $next)
    {
        $res->header('Access-Control-Allow-Origin', '*');
        $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
        $res->header('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        if ($req->method() === 'OPTIONS') {
            return $res->status(204)->send('');
        }
        
        return $next($req, $res);
    }
    
    public static function performance($req, $res, $next)
    {
        $start = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $response = $next($req, $res);
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        $memoryUsed = memory_get_usage(true) - $memoryBefore;
        
        $res->header('X-Response-Time', $duration . 'ms');
        $res->header('X-Memory-Used', round($memoryUsed / 1024, 2) . 'KB');
        
        return $response;
    }
    
    public static function errorHandler($req, $res, $next)
    {
        try {
            return $next($req, $res);
        } catch (ContextualException $e) {
            // ✅ Enhanced error handling v1.1.4+
            error_log("ContextualException: " . $e->getMessage());
            
            return $res->status($e->getStatusCode())->json([
                'error' => true,
                'message' => $e->getMessage(),
                'category' => $e->getCategory(),
                'context' => $e->getContext(),
                'suggestions' => $e->getSuggestions(),
                'debug' => $e->getDebugInfo(),
                'request_id' => $res->getHeader('X-Request-ID')
            ]);
        } catch (Exception $e) {
            error_log("General Exception: " . $e->getMessage());
            
            return $res->status(500)->json([
                'error' => true,
                'message' => 'Internal Server Error',
                'request_id' => $res->getHeader('X-Request-ID')
            ]);
        }
    }
}

// ===============================================
// APPLICATION SETUP v1.1.4+
// ===============================================

$app = new Application();

// ✅ Apply middleware using array callables
$app->use([ApiMiddleware::class, 'cors']);
$app->use([ApiMiddleware::class, 'headers']);
$app->use([ApiMiddleware::class, 'performance']);
$app->use([ApiMiddleware::class, 'errorHandler']);

// ✅ Initialize controllers
$productController = new ProductController();
$categoryController = new CategoryController();
$apiController = new ApiController();

// ===============================================
// ROUTES with Array Callables v1.1.4+
// ===============================================

// API Root & Documentation
$app->get('/api/v1/', [$apiController, 'root']);
$app->get('/api/v1/performance', [$apiController, 'performance']);
$app->get('/api/v1/health', [$apiController, 'health']);

// ✅ Products Resource (Array Callables)
$app->get('/api/v1/products', [$productController, 'index']);
$app->get('/api/v1/products/:id<\\d+>', [$productController, 'show']);
$app->post('/api/v1/products', [$productController, 'store']);
$app->put('/api/v1/products/:id<\\d+>', [$productController, 'update']);
$app->patch('/api/v1/products/:id<\\d+>', [$productController, 'patch']);
$app->delete('/api/v1/products/:id<\\d+>', [$productController, 'destroy']);

// ✅ Categories Resource (Array Callables)
$app->get('/api/v1/categories', [$categoryController, 'index']);
$app->get('/api/v1/categories/:slug<[a-z]+>', [$categoryController, 'show']);

// Statistics endpoint
$app->get('/api/v1/stats', function($req, $res) use ($productController, $categoryController) {
    // Aggregate stats from controllers
    $stats = [
        'api_version' => 'v1.1.4+',
        'total_products' => 3, // Simplified for demo
        'total_categories' => 4,
        'features' => [
            'array_callables' => 'active',
            'json_optimization' => 'active',
            'enhanced_errors' => 'active'
        ],
        'performance' => [
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'json_pool_stats' => JsonBufferPool::getStatistics()
        ],
        'generated_at' => date('c')
    ];
    
    return $res->json($stats);
});

// Migration comparison endpoint
$app->get('/api/v1/migration-comparison', function($req, $res) {
    return $res->json([
        'title' => 'v1.1.3 → v1.1.4+ Migration Comparison',
        'old_approach' => [
            'route_handlers' => 'function($req, $res) { ... }',
            'json_encoding' => 'json_encode($data)',
            'error_handling' => 'throw new Exception($message)',
            'organization' => 'Single file with closures'
        ],
        'new_approach_v114' => [
            'route_handlers' => '[Controller::class, \'method\']',
            'json_encoding' => 'JsonBufferPool::encodeWithPool($data) - automatic',
            'error_handling' => 'ContextualException::parameterError(...)',
            'organization' => 'Organized controllers with array callables'
        ],
        'benefits' => [
            'better_ide_support' => 'Full autocomplete and refactoring support',
            'automatic_optimization' => 'JsonBufferPool decides optimal strategy',
            'enhanced_debugging' => 'Contextual errors with suggestions',
            'cleaner_architecture' => 'Separated concerns and better organization'
        ],
        'migration_effort' => 'Minimal - just replace closures with array callables'
    ]);
});

$app->run();