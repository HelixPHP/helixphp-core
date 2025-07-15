<?php

/**
 * 🚀 PivotPHP v1.1.4+ - API RESTful Completa
 * 
 * Demonstra implementação de uma API RESTful completa com novos recursos v1.1.4+:
 * • Array callables nativos
 * • JsonBufferPool com threshold inteligente
 * • Enhanced error diagnostics
 * • CRUD operations, HTTP status codes, validação, paginação
 * 
 * ✨ Novidades v1.1.4+:
 * • Controllers com array callables
 * • Otimização automática de JSON
 * • Error handling contextual
 * • Performance monitoring
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/04-api/rest-api-v114.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/api/v1/
 * curl http://localhost:8000/api/v1/products
 * curl -X POST http://localhost:8000/api/v1/products -H "Content-Type: application/json" -d '{"name":"Notebook","price":2500.99,"category":"electronics"}'
 * curl -X PUT http://localhost:8000/api/v1/products/1 -H "Content-Type: application/json" -d '{"name":"Updated Product"}'
 * curl -X DELETE http://localhost:8000/api/v1/products/1
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
        
        // Add v1.1.4+ optimization info
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
        // Large response - JsonBufferPool will automatically use pooling
        $documentation = [
            'api' => 'PivotPHP RESTful API v1.1.4+',
            'version' => '1.0',
            'description' => 'Demonstração completa de API RESTful com novos recursos v1.1.4+',
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
                    'DELETE /api/v1/products/{id}' => 'Deletar produto'
                ],
                'Categories Resource' => [
                    'GET /api/v1/categories' => 'Listar categorias',
                    'GET /api/v1/categories/{slug}' => 'Obter categoria específica'
                ]
            ],
            'optimization_details' => [
                'automatic_json_pooling' => 'JsonBufferPool decides based on response size',
                'threshold' => '256 bytes - smaller responses use direct json_encode()',
                'performance_gain' => 'Up to 98% faster for large responses',
                'memory_efficiency' => 'Automatic buffer reuse and optimization'
            ],
            'examples' => array_fill(0, 15, [
                'method' => 'GET',
                'endpoint' => '/api/v1/products',
                'description' => 'List products with advanced filtering',
                'parameters' => ['page', 'limit', 'category', 'search', 'min_price', 'max_price']
            ])
        ];
        
        return $res->json($documentation);
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
                'array_callables' => class_exists('PivotPHP\\Core\\Utils\\CallableResolver'),
                'json_optimization' => method_exists('PivotPHP\\Core\\Json\\Pool\\JsonBufferPool', 'shouldUsePooling'),
                'contextual_errors' => class_exists('PivotPHP\\Core\\Exceptions\\Enhanced\\ContextualException')
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
// MIDDLEWARE v1.1.4+
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
        $res->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
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

// ✅ Initialize controllers (demonstrating dependency injection)
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

// Products Resource
$app->get('/api/v1/products', [$productController, 'index']);
$app->get('/api/v1/products/:id<\\d+>', [$productController, 'show']);
$app->post('/api/v1/products', [$productController, 'store']);
$app->put('/api/v1/products/:id<\\d+>', [$productController, 'update']);
$app->delete('/api/v1/products/:id<\\d+>', [$productController, 'destroy']);

// Categories Resource
$app->get('/api/v1/categories', [$categoryController, 'index']);
$app->get('/api/v1/categories/:slug<[a-z]+>', [$categoryController, 'show']);

// Demo endpoint to show JsonBufferPool threshold in action
$app->get('/api/v1/demo/json-optimization', function($req, $res) {
    $size = $req->get('size', 'small');
    
    switch($size) {
        case 'small':
            $data = ['message' => 'Small data', 'size' => 'small'];
            break;
        case 'medium':
            $data = array_fill(0, 50, ['id' => rand(), 'data' => str_repeat('x', 50)]);
            break;
        case 'large':
            $data = array_fill(0, 500, ['id' => rand(), 'data' => str_repeat('x', 100)]);
            break;
        default:
            $data = ['error' => 'Invalid size parameter'];
    }
    
    $usePooling = JsonBufferPool::shouldUsePooling($data);
    $stats = JsonBufferPool::getStatistics();
    
    return $res->json([
        'demo' => 'JsonBufferPool Optimization v1.1.4+',
        'requested_size' => $size,
        'data' => $data,
        'optimization' => [
            'uses_pooling' => $usePooling,
            'strategy' => $usePooling ? 'buffer_pool' : 'direct_json_encode',
            'threshold' => '256 bytes',
            'explanation' => $usePooling 
                ? 'Data size exceeds threshold - using buffer pool for optimization'
                : 'Data size below threshold - using direct json_encode() for minimal overhead'
        ],
        'pool_stats' => $stats,
        'test_urls' => [
            'small' => '/api/v1/demo/json-optimization?size=small',
            'medium' => '/api/v1/demo/json-optimization?size=medium',
            'large' => '/api/v1/demo/json-optimization?size=large'
        ]
    ]);
});

$app->run();