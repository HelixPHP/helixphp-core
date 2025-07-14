<?php

/**
 * 🔧 PivotPHP v1.1.4+ - Middleware Modernizados
 * 
 * Demonstra criação e uso de middleware customizados modernizados com:
 * • Array callables para middleware organizados
 * • JsonBufferPool com threshold inteligente
 * • Enhanced error diagnostics com ContextualException
 * • Controllers organizados com dependency injection
 * 
 * ✨ Novidades v1.1.4+:
 * • Middleware organizados em classes com array callables
 * • Validação contextual com ContextualException
 * • Otimização automática de JSON
 * • Performance monitoring integrado
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/03-middleware/custom-middleware-v114.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl -X POST http://localhost:8000/api/users -H "Content-Type: application/json" -d '{"name":"João","email":"joao@test.com"}'
 * curl -H "Authorization: Bearer valid-token" http://localhost:8000/protected
 * curl -H "Accept: application/xml" http://localhost:8000/api/data
 * curl -X POST http://localhost:8000/validate -H "Content-Type: application/json" -d '{"name":"Test","email":"invalid-email"}'
 * curl http://localhost:8000/error-demo  # Enhanced error demo
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;

// ===============================================
// CONTROLLERS v1.1.4+ (Array Callables)
// ===============================================

class MiddlewareController
{
    public function index($req, $res)
    {
        $documentation = [
            'title' => 'PivotPHP v1.1.4+ - Custom Middleware Examples',
            'description' => 'Demonstrações de middleware personalizados modernizados',
            'features_v114' => [
                'array_callable_middleware' => 'Middleware organizados em classes ✅',
                'json_optimization' => 'JsonBufferPool automático ✅',
                'enhanced_error_handling' => 'ContextualException com diagnósticos ✅',
                'performance_monitoring' => 'Tracking integrado de performance ✅'
            ],
            'middleware_examples' => [
                'RequestLogger' => 'Log de todas as requisições com contexto',
                'ResponseTimer' => 'Medição de tempo de resposta',
                'ApiKeyValidator' => 'Validação de chave de API com contexto',
                'ContentNegotiation' => 'Negociação de conteúdo (JSON/XML)',
                'InputValidator' => 'Validação contextual de dados de entrada',
                'RequestTransformer' => 'Transformação de dados da requisição',
                'ResponseModifier' => 'Modificação de resposta antes do envio',
                'ErrorHandler' => 'Tratamento contextual de erros'
            ],
            'test_endpoints' => [
                'GET /' => 'Esta página com logs',
                'POST /api/users' => 'Criação com validação contextual',
                'GET /protected' => 'Rota protegida por API key',
                'GET /api/data' => 'Negociação de conteúdo',
                'POST /validate' => 'Validação de entrada com diagnósticos',
                'GET /transform' => 'Transformação de dados',
                'GET /error-demo' => 'Demonstração de erro contextual',
                'GET /performance-stats' => 'Estatísticas de performance v1.1.4+'
            ],
            'migration_from_old_version' => [
                'before' => 'function($req, $res, $next) { ... }',
                'after' => '[MiddlewareClass::class, \'method\']',
                'benefits' => 'Better organization, IDE support, enhanced errors'
            ]
        ];
        
        return $res->json($documentation);
    }
}

// ===============================================
// MIDDLEWARE CLASSES v1.1.4+ (Array Callables)
// ===============================================

class RequestLogger
{
    public static function log($req, $res, $next)
    {
        $startTime = microtime(true);
        $method = $req->method();
        $uri = $req->uri();
        $ip = $req->ip();
        $userAgent = $req->header('User-Agent') ?? 'Unknown';
        $requestId = uniqid('req_', true);
        
        // ✅ NOVO v1.1.4+: Enhanced logging with context
        error_log("🔍 [{$requestId}] [{$method}] {$uri} - IP: {$ip} - UA: " . substr($userAgent, 0, 50));
        
        // Adicionar dados de log ao request
        $req->logData = [
            'request_id' => $requestId,
            'start_time' => $startTime,
            'method' => $method,
            'uri' => $uri,
            'ip' => $ip,
            'user_agent' => $userAgent
        ];
        
        // Add request ID header
        $res->header('X-Request-ID', $requestId);
        
        // Continuar para próximo middleware
        $response = $next($req, $res);
        
        // Log pós-processamento com contexto
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        $memoryUsed = round(memory_get_usage(true) / 1024 / 1024, 2);
        
        error_log("✅ [{$requestId}] [{$method}] {$uri} - {$duration}ms - {$memoryUsed}MB");
        
        return $response;
    }
}

class ResponseTimer
{
    public static function time($req, $res, $next)
    {
        $startTime = microtime(true);
        $memoryBefore = memory_get_usage(true);
        
        // Executar próximo middleware
        $response = $next($req, $res);
        
        // Calcular métricas e adicionar headers
        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2);
        $memoryUsed = memory_get_usage(true) - $memoryBefore;
        
        $res->header('X-Response-Time', $duration . 'ms');
        $res->header('X-Memory-Used', round($memoryUsed / 1024, 2) . 'KB');
        $res->header('X-Processed-At', date('c'));
        $res->header('X-JsonPool-Active', 'v1.1.4+');
        
        return $response;
    }
}

class ApiKeyValidator
{
    public static function validate($req, $res, $next)
    {
        $apiKey = $req->header('Authorization');
        
        // ✅ NOVO v1.1.4+: Enhanced validation with contextual errors
        if (!$apiKey) {
            throw new ContextualException(
                401,
                'API Key is required for this endpoint',
                [
                    'endpoint' => $req->uri(),
                    'method' => $req->method(),
                    'required_header' => 'Authorization',
                    'middleware' => 'ApiKeyValidator'
                ],
                [
                    'Add Authorization header: "Authorization: Bearer <api-key>"',
                    'Valid tokens for testing: valid-token, admin-token, user-token',
                    'Check API documentation for authentication requirements'
                ],
                'AUTHENTICATION'
            );
        }
        
        // Verificar formato Bearer
        if (!str_starts_with($apiKey, 'Bearer ')) {
            throw new ContextualException(
                401,
                'Invalid API Key format',
                [
                    'provided_format' => substr($apiKey, 0, 20) . '...',
                    'expected_format' => 'Bearer <api-key>',
                    'endpoint' => $req->uri(),
                    'middleware' => 'ApiKeyValidator'
                ],
                [
                    'Use Bearer token format: "Authorization: Bearer <your-token>"',
                    'Example: "Authorization: Bearer valid-token"',
                    'Ensure there is a space after "Bearer"'
                ],
                'AUTHENTICATION'
            );
        }
        
        $token = substr($apiKey, 7);
        
        // Validar token com contexto
        $validTokens = [
            'valid-token' => ['id' => 1, 'name' => 'Usuario Teste', 'role' => 'user'],
            'admin-token' => ['id' => 2, 'name' => 'Admin User', 'role' => 'admin'],
            'user-token' => ['id' => 3, 'name' => 'Regular User', 'role' => 'user']
        ];
        
        if (!isset($validTokens[$token])) {
            throw new ContextualException(
                403,
                'Invalid or expired API Key',
                [
                    'provided_token' => $token,
                    'token_length' => strlen($token),
                    'endpoint' => $req->uri(),
                    'middleware' => 'ApiKeyValidator',
                    'valid_token_count' => count($validTokens)
                ],
                [
                    'Use a valid test token: valid-token, admin-token, or user-token',
                    'Check if your token has expired',
                    'Verify token spelling and format',
                    'Contact support if you need a new API key'
                ],
                'AUTHORIZATION'
            );
        }
        
        // Adicionar informações do usuário ao request
        $req->authenticatedUser = $validTokens[$token];
        $req->apiToken = $token;
        
        return $next($req, $res);
    }
}

class ContentNegotiation
{
    public static function negotiate($req, $res, $next)
    {
        $acceptHeader = $req->header('Accept') ?? 'application/json';
        
        // Determinar formato preferido
        $preferredFormat = 'json'; // default
        
        if (strpos($acceptHeader, 'application/xml') !== false) {
            $preferredFormat = 'xml';
        } elseif (strpos($acceptHeader, 'text/csv') !== false) {
            $preferredFormat = 'csv';
        } elseif (strpos($acceptHeader, 'text/plain') !== false) {
            $preferredFormat = 'text';
        }
        
        // Adicionar informações ao request
        $req->preferredFormat = $preferredFormat;
        $req->acceptHeader = $acceptHeader;
        
        // Executar próximo middleware
        $response = $next($req, $res);
        
        // ✅ NOVO v1.1.4+: JsonBufferPool aware content negotiation
        if (isset($req->responseData) && $preferredFormat !== 'json') {
            $data = $req->responseData;
            
            switch ($preferredFormat) {
                case 'xml':
                    $xml = self::arrayToXml($data);
                    $res->header('Content-Type', 'application/xml');
                    return $res->send($xml);
                    
                case 'csv':
                    $csv = self::arrayToCsv($data);
                    $res->header('Content-Type', 'text/csv');
                    return $res->send($csv);
                    
                case 'text':
                    $text = self::arrayToText($data);
                    $res->header('Content-Type', 'text/plain');
                    return $res->send($text);
            }
        }
        
        return $response;
    }
    
    private static function arrayToXml(array $data): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<data>\n";
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $xml .= "  <{$key}>\n";
                foreach ($value as $subKey => $subValue) {
                    $xml .= "    <{$subKey}>" . htmlspecialchars($subValue) . "</{$subKey}>\n";
                }
                $xml .= "  </{$key}>\n";
            } else {
                $xml .= "  <{$key}>" . htmlspecialchars($value) . "</{$key}>\n";
            }
        }
        $xml .= '</data>';
        return $xml;
    }
    
    private static function arrayToCsv(array $data): string
    {
        $flatData = [];
        array_walk_recursive($data, function($value, $key) use (&$flatData) {
            $flatData[$key] = $value;
        });
        
        $csv = implode(',', array_keys($flatData)) . "\n";
        $csv .= implode(',', array_values($flatData));
        return $csv;
    }
    
    private static function arrayToText(array $data): string
    {
        $text = '';
        array_walk_recursive($data, function($value, $key) use (&$text) {
            $text .= "{$key}: {$value}\n";
        });
        return trim($text);
    }
}

class InputValidator
{
    public static function create(array $rules): callable
    {
        return function ($req, $res, $next) use ($rules) {
            $data = $req->getBodyAsStdClass();
            $errors = [];
            $warnings = [];
            
            foreach ($rules as $field => $rule) {
                $value = $data->$field ?? null;
                
                // Required validation
                if (isset($rule['required']) && $rule['required'] && empty($value)) {
                    $errors[$field][] = "Campo {$field} é obrigatório";
                    continue;
                }
                
                if (!empty($value)) {
                    // Type validation with enhanced diagnostics
                    if (isset($rule['type'])) {
                        switch ($rule['type']) {
                            case 'string':
                                if (!is_string($value)) {
                                    $errors[$field][] = "Campo {$field} deve ser string, recebido: " . gettype($value);
                                }
                                break;
                            case 'number':
                                if (!is_numeric($value)) {
                                    $errors[$field][] = "Campo {$field} deve ser numérico, recebido: " . gettype($value);
                                }
                                break;
                            case 'email':
                                if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                    $errors[$field][] = "Campo {$field} deve ser um email válido, recebido: {$value}";
                                }
                                break;
                        }
                    }
                    
                    // Length validation
                    if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                        $errors[$field][] = "Campo {$field} deve ter pelo menos {$rule['min_length']} caracteres (atual: " . strlen($value) . ")";
                    }
                    
                    if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                        $errors[$field][] = "Campo {$field} deve ter no máximo {$rule['max_length']} caracteres (atual: " . strlen($value) . ")";
                    }
                    
                    // Pattern validation
                    if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                        $errors[$field][] = "Campo {$field} não atende ao padrão exigido";
                    }
                }
            }
            
            // ✅ NOVO v1.1.4+: Enhanced validation errors with context
            if (!empty($errors)) {
                throw new ContextualException(
                    422,
                    'Data validation failed',
                    [
                        'validation_errors' => $errors,
                        'rules_applied' => $rules,
                        'received_fields' => array_keys((array)$data),
                        'required_fields' => array_keys(array_filter($rules, fn($rule) => $rule['required'] ?? false)),
                        'endpoint' => $req->uri(),
                        'middleware' => 'InputValidator'
                    ],
                    [
                        'Check all required fields are provided',
                        'Verify data types match the expected format',
                        'Ensure field lengths are within specified limits',
                        'Validate email format if email fields are used',
                        'Review API documentation for exact field requirements'
                    ],
                    'VALIDATION'
                );
            }
            
            // Adicionar dados validados ao request
            $req->validatedData = $data;
            
            return $next($req, $res);
        };
    }
}

class RequestTransformer
{
    public static function transform($req, $res, $next)
    {
        $body = $req->getBodyAsStdClass();
        $transformations = [];
        
        // Transformações automáticas com log
        if (isset($body->email)) {
            $original = $body->email;
            $body->email = strtolower(trim($body->email));
            $transformations['email'] = ['from' => $original, 'to' => $body->email];
        }
        
        if (isset($body->name)) {
            $original = $body->name;
            $body->name = ucwords(strtolower(trim($body->name)));
            $transformations['name'] = ['from' => $original, 'to' => $body->name];
        }
        
        if (isset($body->phone)) {
            $original = $body->phone;
            $body->phone = preg_replace('/[^0-9]/', '', $body->phone);
            $transformations['phone'] = ['from' => $original, 'to' => $body->phone];
        }
        
        // Adicionar campos automáticos com tracking
        $body->transformed_at = date('c');
        $body->ip_address = $req->ip();
        $body->user_agent = $req->header('User-Agent');
        
        // ✅ NOVO v1.1.4+: Enhanced transformation tracking
        $req->transformedData = $body;
        $req->transformationLog = $transformations;
        
        return $next($req, $res);
    }
}

class ResponseModifier
{
    public static function modify($req, $res, $next)
    {
        // Executar próximo middleware
        $response = $next($req, $res);
        
        // ✅ NOVO v1.1.4+: Enhanced headers with optimization info
        $res->header('X-API-Version', '1.1.4+');
        $res->header('X-Framework', 'PivotPHP');
        $res->header('X-Features', 'array-callables,json-optimization,enhanced-errors');
        $res->header('X-JsonPool-Threshold', '256-bytes');
        
        // Add performance metrics if available
        if (isset($req->logData)) {
            $res->header('X-Request-ID', $req->logData['request_id']);
        }
        
        return $response;
    }
}

class ErrorHandler
{
    public static function handle($req, $res, $next)
    {
        try {
            return $next($req, $res);
        } catch (ContextualException $e) {
            // ✅ NOVO v1.1.4+: Enhanced contextual error handling
            $requestId = $req->logData['request_id'] ?? uniqid('err_', true);
            
            error_log("❌ ContextualException [{$requestId}]: {$e->getMessage()}");
            error_log("📍 Context: " . json_encode($e->getContext()));
            
            $errorResponse = [
                'error' => true,
                'message' => $e->getMessage(),
                'category' => $e->getCategory(),
                'context' => $e->getContext(),
                'suggestions' => $e->getSuggestions(),
                'debug_info' => $e->getDebugInfo(),
                'request_id' => $requestId,
                'timestamp' => date('c'),
                'middleware' => 'ErrorHandler v1.1.4+'
            ];
            
            return $res->status($e->getStatusCode())->json($errorResponse);
            
        } catch (Exception $e) {
            // Standard exception handling
            $requestId = $req->logData['request_id'] ?? uniqid('err_', true);
            
            error_log("❌ Exception [{$requestId}]: {$e->getMessage()}");
            
            return $res->status(500)->json([
                'error' => true,
                'message' => 'Internal Server Error',
                'exception_class' => get_class($e),
                'original_message' => $e->getMessage(),
                'request_id' => $requestId,
                'timestamp' => date('c'),
                'middleware' => 'ErrorHandler v1.1.4+',
                'suggestion' => 'Check server logs for detailed error information'
            ]);
        }
    }
}

// ===============================================
// API CONTROLLERS v1.1.4+
// ===============================================

class ApiController
{
    public function createUser($req, $res)
    {
        $userData = $req->validatedData;
        
        // Simulate user creation
        $user = [
            'id' => rand(1000, 9999),
            'name' => $userData->name,
            'email' => $userData->email,
            'created_at' => date('c'),
            'status' => 'active'
        ];
        
        $response = [
            'message' => 'Usuário criado com sucesso',
            'user' => $user,
            'validation' => [
                'validated_data' => $userData,
                'middleware_applied' => ['InputValidator v1.1.4+']
            ],
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($user),
                'response_strategy' => 'User creation with automatic optimization'
            ]
        ];
        
        return $res->status(201)->json($response);
    }
    
    public function getData($req, $res)
    {
        $data = [
            'id' => 123,
            'name' => 'Produto Exemplo',
            'price' => '99.90',
            'category' => 'electronics',
            'features' => [
                'waterproof' => true,
                'wireless' => true,
                'warranty' => '2 years'
            ],
            'specifications' => [
                'weight' => '250g',
                'dimensions' => '10x5x2cm',
                'color' => 'black'
            ]
        ];
        
        // Armazenar dados para possível transformação
        $req->responseData = $data;
        
        $response = [
            'data' => $data,
            'format_info' => [
                'preferred_format' => $req->preferredFormat,
                'accept_header' => $req->acceptHeader,
                'available_formats' => ['json', 'xml', 'csv', 'text']
            ],
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($data),
                'content_negotiation' => 'Enhanced with JsonBufferPool awareness'
            ]
        ];
        
        return $res->json($response);
    }
    
    public function protectedEndpoint($req, $res)
    {
        return $res->json([
            'message' => 'Acesso autorizado com sucesso!',
            'authenticated_user' => $req->authenticatedUser,
            'api_token' => $req->apiToken,
            'middleware_applied' => [
                'RequestLogger v1.1.4+',
                'ResponseTimer v1.1.4+', 
                'ApiKeyValidator v1.1.4+'
            ],
            'security_info' => [
                'user_role' => $req->authenticatedUser['role'],
                'token_validation' => 'passed',
                'access_level' => 'authorized'
            ]
        ]);
    }
    
    public function transformData($req, $res)
    {
        return $res->json([
            'message' => 'Dados transformados com sucesso',
            'original_data' => $req->getBodyAsStdClass(),
            'transformed_data' => $req->transformedData,
            'transformation_log' => $req->transformationLog ?? [],
            'transformations_applied' => [
                'email' => 'lowercase + trim',
                'name' => 'title case + trim',
                'phone' => 'numbers only',
                'auto_fields' => ['transformed_at', 'ip_address', 'user_agent']
            ],
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($req->transformedData),
                'transformation_strategy' => 'Enhanced with detailed logging'
            ]
        ]);
    }
    
    public function performanceStats($req, $res)
    {
        $stats = JsonBufferPool::getStatistics();
        
        return $res->json([
            'title' => 'Middleware Performance Stats v1.1.4+',
            'framework_version' => Application::VERSION,
            'json_pool_stats' => $stats,
            'memory_usage' => [
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
            ],
            'middleware_improvements' => [
                'contextual_errors' => 'Enhanced diagnostics with suggestions',
                'automatic_optimization' => 'JsonBufferPool threshold-based pooling',
                'organized_structure' => 'Array callables for better maintainability',
                'performance_tracking' => 'Integrated monitoring and metrics'
            ],
            'timestamp' => date('c')
        ]);
    }
    
    public function errorDemo($req, $res)
    {
        // Simulate different types of errors for demonstration
        $errorType = $req->get('type', 'contextual');
        
        switch ($errorType) {
            case 'contextual':
                throw new ContextualException(
                    500,
                    'Demonstração de erro contextual v1.1.4+',
                    [
                        'error_type' => 'demonstration',
                        'endpoint' => '/error-demo',
                        'middleware_stack' => ['ErrorHandler', 'ResponseModifier'],
                        'request_details' => [
                            'method' => $req->method(),
                            'uri' => $req->uri(),
                            'ip' => $req->ip()
                        ]
                    ],
                    [
                        'Este é um erro de demonstração para mostrar o ErrorHandler',
                        'Erros contextuais fornecem informações detalhadas',
                        'Suggestions ajudam desenvolvedores a resolver problemas',
                        'Try different error types: ?type=standard'
                    ],
                    'DEMONSTRATION'
                );
                
            case 'standard':
                throw new Exception('Este é um erro padrão para demonstração do middleware ErrorHandler');
                
            default:
                return $res->json([
                    'message' => 'Erro demo endpoint',
                    'available_types' => ['contextual', 'standard'],
                    'example' => '/error-demo?type=contextual'
                ]);
        }
    }
}

// ===============================================
// APPLICATION SETUP v1.1.4+
// ===============================================

$app = new Application();

// ✅ Apply middleware using array callables
$app->use([RequestLogger::class, 'log']);
$app->use([ResponseTimer::class, 'time']);
$app->use([ErrorHandler::class, 'handle']);
$app->use([ResponseModifier::class, 'modify']);

// ✅ Initialize controllers
$middlewareController = new MiddlewareController();
$apiController = new ApiController();

// ===============================================
// ROUTES with Array Callables v1.1.4+
// ===============================================

// ✅ Main documentation (Array Callable)
$app->get('/', [$middlewareController, 'index']);

// ✅ Protected route (Array Callable + Middleware)
$app->get('/protected', [ApiKeyValidator::class, 'validate'], [$apiController, 'protectedEndpoint']);

// ✅ Content negotiation route (Array Callable + Middleware)
$app->get('/api/data', [ContentNegotiation::class, 'negotiate'], [$apiController, 'getData']);

// ✅ User creation with validation (Array Callable + Middleware)
$app->post('/api/users', 
    InputValidator::create([
        'name' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 2,
            'max_length' => 50
        ],
        'email' => [
            'required' => true,
            'type' => 'email'
        ],
        'age' => [
            'type' => 'number'
        ]
    ]), 
    [$apiController, 'createUser']
);

// ✅ Data transformation route (Array Callable + Middleware)
$app->post('/transform', [RequestTransformer::class, 'transform'], [$apiController, 'transformData']);

// ✅ Validation demonstration (Array Callable + Middleware)
$app->post('/validate',
    InputValidator::create([
        'name' => ['required' => true, 'type' => 'string', 'min_length' => 2],
        'email' => ['required' => true, 'type' => 'email'],
        'phone' => ['type' => 'string', 'pattern' => '/^[0-9+\-\s()]+$/']
    ]),
    function($req, $res) {
        return $res->json([
            'message' => 'Validation passed successfully!',
            'validated_data' => $req->validatedData,
            'validation_middleware' => 'InputValidator v1.1.4+',
            'note' => 'Enhanced validation with contextual error diagnostics'
        ]);
    }
);

// ✅ Error demonstration (Array Callable)
$app->get('/error-demo', [$apiController, 'errorDemo']);

// ✅ Performance stats (Array Callable)
$app->get('/performance-stats', [$apiController, 'performanceStats']);

// Complex middleware stack demonstration
$app->post('/complex', 
    [ApiKeyValidator::class, 'validate'],
    [ContentNegotiation::class, 'negotiate'],
    [RequestTransformer::class, 'transform'],
    InputValidator::create([
        'title' => ['required' => true, 'min_length' => 5],
        'content' => ['required' => true, 'min_length' => 10],
        'category' => ['required' => true, 'type' => 'string']
    ]),
    function ($req, $res) {
        $response = [
            'message' => 'Processado por stack completo de middleware v1.1.4+',
            'middleware_stack' => [
                'RequestLogger::log',
                'ResponseTimer::time', 
                'ErrorHandler::handle',
                'ResponseModifier::modify',
                'ApiKeyValidator::validate',
                'ContentNegotiation::negotiate',
                'RequestTransformer::transform',
                'InputValidator::create'
            ],
            'results' => [
                'authenticated_user' => $req->authenticatedUser,
                'validated_data' => $req->validatedData,
                'transformed_data' => $req->transformedData,
                'preferred_format' => $req->preferredFormat,
                'transformation_log' => $req->transformationLog ?? []
            ],
            'optimization_v114' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($req->validatedData),
                'complex_stack' => 'All middleware enhanced with v1.1.4+ features'
            ]
        ];
        
        return $res->json($response);
    }
);

// Migration comparison endpoint
$app->get('/migration-comparison', function($req, $res) {
    return $res->json([
        'title' => 'Middleware Migration: v1.1.3 → v1.1.4+',
        'old_approach' => [
            'middleware_definition' => 'function($req, $res, $next) { ... }',
            'error_handling' => 'Basic try-catch with generic messages',
            'organization' => 'Inline functions scattered throughout code',
            'validation' => 'Manual validation with basic error messages'
        ],
        'new_approach_v114' => [
            'middleware_definition' => '[MiddlewareClass::class, \'method\']',
            'error_handling' => 'ContextualException with detailed diagnostics',
            'organization' => 'Organized classes with static methods',
            'validation' => 'Enhanced validation with contextual error messages'
        ],
        'benefits' => [
            'better_organization' => 'Middleware organized in logical classes',
            'enhanced_errors' => 'Detailed error context and suggestions',
            'ide_support' => 'Full autocomplete and refactoring capabilities',
            'automatic_optimization' => 'JsonBufferPool integration',
            'better_debugging' => 'Request tracking and performance metrics'
        ],
        'migration_effort' => 'Moderate - restructure middleware into classes with array callables',
        'optimization_v114' => [
            'uses_pooling' => JsonBufferPool::shouldUsePooling([]),
            'migration_demo' => 'Live demonstration of v1.1.4+ features'
        ]
    ]);
});

$app->run();