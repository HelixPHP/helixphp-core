<?php

/**
 * 🎯 PivotPHP v1.1.4+ - Parâmetros de Rota Modernizados
 * 
 * Demonstra todas as funcionalidades modernizadas de parâmetros de rota:
 * • Array callables nativos para controllers
 * • JsonBufferPool com threshold inteligente
 * • Enhanced error diagnostics para parâmetros inválidos
 * • Controllers organizados com dependency injection
 * 
 * ✨ Novidades v1.1.4+:
 * • RouteController com array callables
 * • Validação contextual de parâmetros
 * • Otimização automática de JSON
 * • Error handling aprimorado
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/02-routing/route-parameters-v114.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/users/123
 * curl http://localhost:8000/posts/2024/technology
 * curl "http://localhost:8000/search?q=php&category=tech&page=2"
 * curl http://localhost:8000/api/users/123/posts/456/comments
 * curl http://localhost:8000/users/invalid-id  # Error demo
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;

// ===============================================
// CONTROLLERS v1.1.4+ (Array Callables)
// ===============================================

class RouteParamsController
{
    public function index($req, $res)
    {
        $documentation = [
            'title' => 'PivotPHP v1.1.4+ - Route Parameters Examples',
            'description' => 'Demonstrações modernizadas de parâmetros de rota com novos recursos',
            'features_v114' => [
                'array_callables' => 'Controllers organizados com array callables ✅',
                'json_optimization' => 'JsonBufferPool automático baseado no tamanho ✅',
                'enhanced_errors' => 'Validação contextual de parâmetros ✅',
                'performance_monitoring' => 'Estatísticas em tempo real ✅'
            ],
            'examples' => [
                'Basic Parameters' => [
                    'GET /users/:id' => 'Parâmetro básico com validação',
                    'GET /posts/:year/:category' => 'Múltiplos parâmetros',
                    'GET /api/users/:userId/posts/:postId/comments' => 'Parâmetros aninhados'
                ],
                'Query Parameters' => [
                    'GET /search?q=term&page=1' => 'Query strings com validação',
                    'GET /filter?category=tech&sort=date&order=desc' => 'Filtros complexos'
                ],
                'Mixed Parameters' => [
                    'GET /posts/:category?page=1&limit=10' => 'Route + Query params',
                    'GET /users/:id/posts?status=published' => 'Aninhados + Query'
                ],
                'Wildcard Parameters' => [
                    'GET /files/*' => 'Captura de caminhos completos',
                    'GET /browse/:category/*' => 'Wildcards com parâmetros'
                ]
            ],
            'parameter_methods' => [
                '$req->param(name)' => 'Obter parâmetro de rota',
                '$req->get(name, default)' => 'Obter query parameter',
                '$req->query()' => 'Todos os query parameters',
                '$req->params()' => 'Todos os parâmetros de rota'
            ],
            'v114_improvements' => [
                'contextual_validation' => 'ContextualException para parâmetros inválidos',
                'automatic_optimization' => 'JsonBufferPool decide automaticamente',
                'controller_organization' => 'Array callables para melhor estrutura',
                'performance_tracking' => 'Monitoramento integrado de performance'
            ]
        ];

        return $res->json($documentation);
    }
}

class UserController
{
    private array $users;
    
    public function __construct()
    {
        $this->users = [
            1 => ['id' => 1, 'name' => 'João Silva', 'email' => 'joao@example.com'],
            2 => ['id' => 2, 'name' => 'Maria Santos', 'email' => 'maria@example.com'],
            3 => ['id' => 3, 'name' => 'Pedro Costa', 'email' => 'pedro@example.com']
        ];
    }
    
    public function show($req, $res)
    {
        $id = $req->param('id');
        
        // ✅ NOVO v1.1.4+: Enhanced parameter validation
        if (!is_numeric($id)) {
            throw ContextualException::parameterError(
                'id',
                'numeric user ID',
                $id,
                '/users/:id'
            );
        }
        
        $id = (int) $id;
        
        if (!isset($this->users[$id])) {
            throw ContextualException::parameterError(
                'id',
                'existing user ID',
                $id,
                '/users/:id'
            );
        }
        
        $user = $this->users[$id];
        
        // Enrich user data
        $user['profile'] = [
            'bio' => "Biografia do usuário {$id}",
            'location' => 'São Paulo, Brasil',
            'joined' => '2024-01-15',
            'posts_count' => rand(5, 50),
            'followers' => rand(100, 1000)
        ];
        
        $response = [
            'user' => $user,
            'route_params' => $req->params(),
            'extracted_id' => $id,
            'id_type' => gettype($id),
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($user),
                'data_size' => strlen(json_encode($user)) . ' bytes',
                'strategy' => 'Single user - optimized for speed'
            ]
        ];
        
        return $res->json($response);
    }
}

class PostController
{
    public function byYearAndCategory($req, $res)
    {
        $year = $req->param('year');
        $category = $req->param('category');
        
        // ✅ NOVO v1.1.4+: Enhanced validation for year parameter
        if (!is_numeric($year) || $year < 2000 || $year > 2030) {
            throw ContextualException::parameterError(
                'year',
                'valid year (2000-2030)',
                $year,
                '/posts/:year/:category'
            );
        }
        
        // Validate category
        $validCategories = ['technology', 'science', 'business', 'lifestyle', 'programming'];
        if (!in_array($category, $validCategories)) {
            throw new ContextualException(
                400,
                'Invalid category parameter',
                [
                    'parameter' => 'category',
                    'received_value' => $category,
                    'valid_categories' => $validCategories,
                    'route_pattern' => '/posts/:year/:category'
                ],
                [
                    'Use one of the valid categories: ' . implode(', ', $validCategories),
                    'Check the spelling of the category name',
                    'Categories are case-sensitive'
                ],
                'PARAMETER_VALIDATION'
            );
        }
        
        // Generate posts for demonstration
        $posts = array_fill(0, rand(3, 8), [
            'id' => rand(1, 1000),
            'title' => "Post sobre {$category} em {$year}",
            'category' => $category,
            'year' => (int) $year,
            'content' => 'Conteúdo detalhado do post sobre ' . $category,
            'published_at' => "{$year}-" . sprintf('%02d', rand(1, 12)) . "-" . sprintf('%02d', rand(1, 28)),
            'author' => ['Autor A', 'Autor B', 'Autor C'][rand(0, 2)],
            'views' => rand(100, 10000),
            'likes' => rand(10, 500)
        ]);
        
        $response = [
            'posts' => $posts,
            'filters' => [
                'year' => (int) $year,
                'category' => $category
            ],
            'route_params' => $req->params(),
            'total_posts' => count($posts),
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($posts),
                'data_size' => $this->estimateDataSize($posts),
                'performance_note' => 'Large dataset automatically uses buffer pooling'
            ],
            'pool_stats' => JsonBufferPool::getStatistics()
        ];
        
        return $res->json($response);
    }
    
    private function estimateDataSize(array $data): string
    {
        $size = strlen(json_encode($data));
        if ($size < 1024) return $size . ' bytes';
        if ($size < 1024 * 1024) return round($size / 1024, 1) . ' KB';
        return round($size / (1024 * 1024), 1) . ' MB';
    }
}

class SearchController
{
    public function search($req, $res)
    {
        // Parâmetros obrigatórios
        $query = $req->get('q');
        
        if (!$query) {
            throw new ContextualException(
                400,
                'Search query parameter is required',
                [
                    'missing_parameter' => 'q',
                    'received_params' => $req->query(),
                    'endpoint' => '/search'
                ],
                [
                    'Add ?q=your-search-term to the URL',
                    'Example: /search?q=php&category=tech&page=1',
                    'Query parameter "q" cannot be empty'
                ],
                'MISSING_PARAMETER'
            );
        }
        
        // Parâmetros opcionais com defaults e validação
        $page = max(1, (int) $req->get('page', 1));
        $limit = max(1, min(100, (int) $req->get('limit', 10)));
        $category = $req->get('category', 'all');
        $sort = $req->get('sort', 'relevance');
        $order = $req->get('order', 'desc');
        
        // Validar sort parameter
        $validSorts = ['relevance', 'date', 'title', 'author'];
        if (!in_array($sort, $validSorts)) {
            $sort = 'relevance'; // Fallback silencioso
        }
        
        // Parâmetros de filtro avançado
        $dateFrom = $req->get('date_from');
        $dateTo = $req->get('date_to');
        $author = $req->get('author');
        $tags = $req->get('tags');
        
        // Processar tags se fornecidas
        $tagsArray = $tags ? array_map('trim', explode(',', $tags)) : [];
        
        // Simular resultados de busca baseados nos parâmetros
        $results = array_fill(0, min($limit, rand(3, 15)), [
            'id' => rand(1, 1000),
            'title' => "Tutorial de {$query}",
            'category' => $category !== 'all' ? $category : ['technology', 'programming', 'science'][rand(0, 2)],
            'author' => $author ?: ['João Silva', 'Maria Santos', 'Pedro Costa'][rand(0, 2)],
            'published_at' => date('Y-m-d', strtotime('-' . rand(1, 365) . ' days')),
            'relevance_score' => round(rand(70, 100) + rand(0, 99) / 100, 2),
            'excerpt' => "Trecho do conteúdo sobre {$query}...",
            'tags' => !empty($tagsArray) ? array_slice($tagsArray, 0, 3) : ['tag1', 'tag2']
        ]);
        
        $response = [
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
                'total_results' => rand(50, 500),
                'total_pages' => rand(5, 50),
                'has_next' => $page < rand(5, 10),
                'has_prev' => $page > 1
            ],
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($results),
                'query_complexity' => 'medium',
                'response_strategy' => 'Automatic optimization based on result count'
            ],
            'all_query_params' => $req->query()
        ];
        
        return $res->json($response);
    }
}

class CommentController
{
    public function byUserAndPost($req, $res)
    {
        $userId = $req->param('userId');
        $postId = $req->param('postId');
        
        // ✅ NOVO v1.1.4+: Enhanced nested parameter validation
        if (!is_numeric($userId)) {
            throw ContextualException::parameterError(
                'userId',
                'numeric user ID',
                $userId,
                '/api/users/:userId/posts/:postId/comments'
            );
        }
        
        if (!is_numeric($postId)) {
            throw ContextualException::parameterError(
                'postId',
                'numeric post ID',
                $postId,
                '/api/users/:userId/posts/:postId/comments'
            );
        }
        
        $userId = (int) $userId;
        $postId = (int) $postId;
        
        // Query parameters para paginação
        $page = max(1, (int) $req->get('page', 1));
        $limit = max(1, min(50, (int) $req->get('limit', 5)));
        $status = $req->get('status', 'approved');
        
        // Validar status
        $validStatuses = ['approved', 'pending', 'rejected', 'all'];
        if (!in_array($status, $validStatuses)) {
            throw new ContextualException(
                400,
                'Invalid status parameter',
                [
                    'parameter' => 'status',
                    'received_value' => $status,
                    'valid_statuses' => $validStatuses,
                    'endpoint' => '/api/users/:userId/posts/:postId/comments'
                ],
                [
                    'Use one of: ' . implode(', ', $validStatuses),
                    'Status parameter is case-sensitive',
                    'Default status is "approved"'
                ],
                'PARAMETER_VALIDATION'
            );
        }
        
        // Simular comentários
        $comments = array_fill(0, rand(2, 10), [
            'id' => rand(1, 1000),
            'user_id' => $userId,
            'post_id' => $postId,
            'author' => ['Ana Costa', 'Carlos Lima', 'Lucia Ferreira', 'Roberto Silva'][rand(0, 3)],
            'content' => 'Comentário interessante sobre o post. Muito informativo e bem escrito.',
            'status' => $status === 'all' ? ['approved', 'pending'][rand(0, 1)] : $status,
            'likes' => rand(0, 50),
            'created_at' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 30) . ' days')),
            'updated_at' => date('Y-m-d H:i:s', strtotime('-' . rand(0, 5) . ' days'))
        ]);
        
        $response = [
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
            ],
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($comments),
                'nested_params' => 'Successfully validated',
                'performance_note' => 'Nested routes with automatic optimization'
            ]
        ];
        
        return $res->json($response);
    }
}

class FileController
{
    public function handleWildcard($req, $res)
    {
        $path = $req->param('*'); // Captura tudo após /files/
        
        if (empty($path)) {
            throw new ContextualException(
                400,
                'File path is required',
                [
                    'wildcard_param' => '*',
                    'captured_value' => $path,
                    'route_pattern' => '/files/*'
                ],
                [
                    'Provide a file path after /files/',
                    'Example: /files/documents/report.pdf',
                    'Wildcard parameter cannot be empty'
                ],
                'WILDCARD_PARAMETER'
            );
        }
        
        // Analisar o caminho
        $pathParts = explode('/', trim($path, '/'));
        $filename = end($pathParts);
        $directory = implode('/', array_slice($pathParts, 0, -1));
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        
        $fileInfo = [
            'full_path' => $path,
            'directory' => $directory ?: 'root',
            'filename' => $filename,
            'extension' => $extension,
            'path_parts' => $pathParts,
            'depth' => count($pathParts),
            'file_type' => $this->getFileType($extension),
            'estimated_size' => rand(1024, 1024 * 1024) . ' bytes'
        ];
        
        $response = [
            'file_info' => $fileInfo,
            'wildcard_info' => [
                'pattern' => '/files/*',
                'captured' => $path,
                'description' => 'Wildcard captura todo o resto da URL'
            ],
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($fileInfo),
                'wildcard_handling' => 'Enhanced with contextual validation'
            ]
        ];
        
        return $res->json($response);
    }
    
    private function getFileType(string $extension): string
    {
        $types = [
            'pdf' => 'document',
            'doc' => 'document', 'docx' => 'document',
            'jpg' => 'image', 'jpeg' => 'image', 'png' => 'image', 'gif' => 'image',
            'mp4' => 'video', 'avi' => 'video', 'mov' => 'video',
            'mp3' => 'audio', 'wav' => 'audio',
            'zip' => 'archive', 'rar' => 'archive',
            'txt' => 'text', 'md' => 'text'
        ];
        
        return $types[strtolower($extension)] ?? 'unknown';
    }
}

// ===============================================
// MIDDLEWARE v1.1.4+
// ===============================================

class RouteMiddleware
{
    public static function parameterLogger($req, $res, $next)
    {
        $routeParams = $req->params();
        $queryParams = $req->query();
        
        error_log("Route Params: " . json_encode($routeParams));
        error_log("Query Params: " . json_encode($queryParams));
        
        $res->header('X-Route-Params', json_encode($routeParams));
        $res->header('X-Query-Params', json_encode($queryParams));
        
        return $next($req, $res);
    }
    
    public static function performanceTracker($req, $res, $next)
    {
        $start = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        $response = $next($req, $res);
        
        $duration = round((microtime(true) - $start) * 1000, 2);
        $memoryUsed = memory_get_usage(true) - $memoryBefore;
        
        $res->header('X-Response-Time', $duration . 'ms');
        $res->header('X-Memory-Used', round($memoryUsed / 1024, 2) . 'KB');
        $res->header('X-Optimization-Active', 'JsonBufferPool-v1.1.4+');
        
        return $response;
    }
}

// ===============================================
// APPLICATION SETUP v1.1.4+
// ===============================================

$app = new Application();

// ✅ Apply middleware using array callables
$app->use([RouteMiddleware::class, 'parameterLogger']);
$app->use([RouteMiddleware::class, 'performanceTracker']);

// ✅ Initialize controllers
$routeController = new RouteParamsController();
$userController = new UserController();
$postController = new PostController();
$searchController = new SearchController();
$commentController = new CommentController();
$fileController = new FileController();

// ===============================================
// ROUTES with Array Callables v1.1.4+
// ===============================================

// ✅ Main documentation (Array Callable)
$app->get('/', [$routeController, 'index']);

// ✅ Basic parameter routes (Array Callables)
$app->get('/users/:id', [$userController, 'show']);
$app->get('/posts/:year/:category', [$postController, 'byYearAndCategory']);

// ✅ Query parameter routes (Array Callables)
$app->get('/search', [$searchController, 'search']);

// ✅ Nested parameter routes (Array Callables)
$app->get('/api/users/:userId/posts/:postId/comments', [$commentController, 'byUserAndPost']);

// ✅ Wildcard routes (Array Callables)
$app->get('/files/*', [$fileController, 'handleWildcard']);

// Advanced parameter demo with mixed types
$app->get('/reports/:type/:year', function($req, $res) {
    $type = $req->param('type');
    $year = $req->param('year');
    
    // Validate parameters
    if (!is_numeric($year) || $year < 2020 || $year > 2030) {
        throw ContextualException::parameterError(
            'year',
            'valid year (2020-2030)',
            $year,
            '/reports/:type/:year'
        );
    }
    
    $validTypes = ['sales', 'financial', 'operational', 'marketing'];
    if (!in_array($type, $validTypes)) {
        throw new ContextualException(
            400,
            'Invalid report type',
            [
                'parameter' => 'type',
                'received_value' => $type,
                'valid_types' => $validTypes
            ],
            [
                'Use one of: ' . implode(', ', $validTypes),
                'Report types are case-sensitive'
            ],
            'PARAMETER_VALIDATION'
        );
    }
    
    // Query parameters para customização
    $format = $req->get('format', 'json');
    $detailed = $req->get('detailed', 'false') === 'true';
    $department = $req->get('department');
    $months = $req->get('months');
    
    $monthsArray = $months ? array_map('intval', explode(',', $months)) : range(1, 12);
    
    // Simular dados do relatório
    $reportData = [
        'type' => $type,
        'year' => (int) $year,
        'months_included' => $monthsArray,
        'department' => $department,
        'summary' => [
            'total_records' => rand(1000, 5000),
            'average_per_month' => rand(80, 400),
            'peak_month' => ['Janeiro', 'Dezembro', 'Julho'][rand(0, 2)]
        ]
    ];
    
    if ($detailed) {
        $reportData['detailed_data'] = [
            'monthly_breakdown' => array_map(function ($month) {
                return [
                    'month' => $month,
                    'value' => rand(50, 500),
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
                'year' => (int) $year
            ],
            'query' => [
                'format' => $format,
                'detailed' => $detailed,
                'department' => $department,
                'months' => $months
            ]
        ],
        'optimization_v114' => [
            'uses_pooling' => JsonBufferPool::shouldUsePooling($reportData),
            'response_strategy' => 'Mixed parameters with automatic optimization'
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
        return $res->send("type,year,total_records\n{$type},{$year},{$reportData['summary']['total_records']}");
    }
    
    return $res->json($response);
});

// Comprehensive parameter demonstration
$app->get('/demo/:category/:id', function($req, $res) {
    $category = $req->param('category');
    $id = $req->param('id');
    
    // Enhanced parameter info with v1.1.4+ features
    $response = [
        'demonstration' => 'Todos os tipos de parâmetros v1.1.4+',
        'route_parameters' => [
            'all_params' => $req->params(),
            'category' => $category,
            'id' => $id,
            'parameter_types' => [
                'category' => gettype($category),
                'id' => gettype($id)
            ]
        ],
        'query_parameters' => [
            'all_query' => $req->query(),
            'specific_examples' => [
                'page' => $req->get('page'),
                'limit' => $req->get('limit', 10),
                'sort' => $req->get('sort')
            ],
            'query_count' => count($req->query())
        ],
        'request_info' => [
            'method' => $req->method(),
            'uri' => $req->uri(),
            'full_url' => $req->header('Host') . $req->uri(),
            'user_agent' => $req->header('User-Agent')
        ],
        'optimization_v114' => [
            'uses_pooling' => JsonBufferPool::shouldUsePooling($req->params()),
            'performance_note' => 'Demonstration endpoint with automatic optimization',
            'pool_stats' => JsonBufferPool::getStatistics()
        ],
        'tips' => [
            'basic_test' => '/demo/technology/123?page=2&limit=20&sort=date',
            'advanced_test' => '/demo/programming/456?page=1&limit=5&sort=title&detailed=true',
            'error_test' => 'Try invalid parameters to see enhanced error diagnostics'
        ]
    ];
    
    return $res->json($response);
});

// Performance stats endpoint
$app->get('/performance-stats', function($req, $res) {
    $stats = JsonBufferPool::getStatistics();
    
    return $res->json([
        'title' => 'Route Parameters Performance Stats v1.1.4+',
        'json_pool_stats' => $stats,
        'memory_usage' => [
            'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ],
        'optimization_benefits' => [
            'automatic_threshold' => '256 bytes - system decides when to use pooling',
            'route_optimization' => 'Complex route responses use buffer pooling',
            'parameter_validation' => 'Enhanced error diagnostics prevent issues',
            'controller_organization' => 'Array callables improve code maintainability'
        ],
        'timestamp' => date('c')
    ]);
});

$app->run();