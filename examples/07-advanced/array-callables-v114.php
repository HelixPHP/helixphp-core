<?php

/**
 * 🎯 PivotPHP v1.1.4+ - Array Callables Demonstration
 * 
 * Demonstra todas as funcionalidades dos array callables nativos v1.1.4+:
 * • Sintaxes suportadas vs não suportadas
 * • Validação automática
 * • Error handling contextual
 * • Performance comparison
 * • Best practices
 * 
 * ✨ Novidades v1.1.4+:
 * • CallableResolver com validação robusta
 * • Enhanced error diagnostics
 * • Automatic validation de métodos públicos/privados
 * • Performance optimization
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/07-advanced/array-callables-v114.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/demo/static-method
 * curl http://localhost:8000/demo/instance-method
 * curl http://localhost:8000/demo/validation
 * curl http://localhost:8000/demo/performance
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Utils\CallableResolver;
use PivotPHP\Core\Json\Pool\JsonBufferPool;
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;

// ===============================================
// DEMO CONTROLLERS
// ===============================================

class UserController
{
    private array $users;
    
    public function __construct()
    {
        $this->users = [
            1 => ['id' => 1, 'name' => 'João Silva', 'email' => 'joao@example.com'],
            2 => ['id' => 2, 'name' => 'Maria Santos', 'email' => 'maria@example.com'],
            3 => ['id' => 3, 'name' => 'Pedro Oliveira', 'email' => 'pedro@example.com']
        ];
    }
    
    // ✅ Método público - Funciona com array callable
    public function index($req, $res)
    {
        return $res->json([
            'message' => 'Array callable funcionando! ✅',
            'method' => 'UserController::index',
            'type' => 'instance_method',
            'data' => array_values($this->users),
            'optimization' => [
                'uses_pooling' => JsonBufferPool::shouldUsePooling($this->users),
                'array_callable_validation' => 'passed'
            ]
        ]);
    }
    
    public function show($req, $res)
    {
        $id = (int) $req->param('id');
        
        if (!isset($this->users[$id])) {
            // Enhanced error with context
            throw ContextualException::parameterError(
                'id',
                'existing user ID',
                $id,
                '/users/:id'
            );
        }
        
        return $res->json([
            'message' => 'User found with array callable! ✅',
            'method' => 'UserController::show',
            'user' => $this->users[$id]
        ]);
    }
    
    // ✅ Método estático - Funciona com array callable
    public static function staticMethod($req, $res)
    {
        return $res->json([
            'message' => 'Static method via array callable! ✅',
            'method' => 'UserController::staticMethod',
            'type' => 'static_method',
            'features_v114' => [
                'array_callables' => 'Native support',
                'static_methods' => 'Fully supported',
                'validation' => 'Automatic'
            ]
        ]);
    }
    
    // ❌ Método privado - NÃO funciona com array callable
    private function privateMethod($req, $res)
    {
        return $res->json(['this' => 'should_not_work']);
    }
    
    // ❌ Método protegido - NÃO funciona com array callable
    protected function protectedMethod($req, $res)
    {
        return $res->json(['this' => 'should_not_work']);
    }
}

class ProductController
{
    // ✅ Dependency injection via constructor
    private $logger;
    
    public function __construct($logger = null)
    {
        $this->logger = $logger ?: function($msg) { error_log($msg); };
    }
    
    public function list($req, $res)
    {
        ($this->logger)('ProductController::list called via array callable');
        
        // Generate large dataset to demonstrate JsonBufferPool
        $products = array_fill(0, 100, [
            'id' => rand(1, 1000),
            'name' => 'Product ' . rand(1, 100),
            'description' => str_repeat('Lorem ipsum dolor sit amet. ', 10),
            'price' => rand(10, 1000) + rand(0, 99) / 100,
            'category' => ['electronics', 'books', 'clothing'][rand(0, 2)],
            'tags' => ['tag1', 'tag2', 'tag3'],
            'metadata' => [
                'created_at' => date('c'),
                'updated_at' => date('c'),
                'status' => 'active'
            ]
        ]);
        
        return $res->json([
            'message' => 'Large dataset via array callable! ✅',
            'method' => 'ProductController::list',
            'products' => $products,
            'optimization' => [
                'data_size' => count($products) . ' products',
                'uses_pooling' => JsonBufferPool::shouldUsePooling($products),
                'performance_note' => 'JsonBufferPool automatically optimizes large responses',
                'v114_features' => 'Automatic threshold detection'
            ],
            'pool_stats' => JsonBufferPool::getStatistics()
        ]);
    }
}

class ValidationDemoController
{
    // ✅ Método público válido
    public function validMethod($req, $res)
    {
        return $res->json([
            'status' => 'success',
            'message' => 'This method is public and callable ✅',
            'validation' => 'passed'
        ]);
    }
    
    // ❌ Método privado inválido
    private function invalidMethod($req, $res)
    {
        return $res->json([
            'status' => 'error',
            'message' => 'This should never be reached'
        ]);
    }
}

// Controller que não existe (para demonstrar erro)
class NonExistentController
{
    // Este controller será usado apenas para demonstrar erros
}

// ===============================================
// UTILITY FUNCTIONS
// ===============================================

function benchmarkCallableTypes($iterations = 1000)
{
    $results = [];
    
    // Test closure
    $closure = function($req, $res) { return 'closure'; };
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        is_callable($closure);
    }
    $results['closure'] = microtime(true) - $start;
    
    // Test array callable
    $arrayCallable = [UserController::class, 'staticMethod'];
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        is_callable($arrayCallable);
    }
    $results['array_callable'] = microtime(true) - $start;
    
    // Test string function
    $stringFunction = 'strlen';
    $start = microtime(true);
    for ($i = 0; $i < $iterations; $i++) {
        is_callable($stringFunction);
    }
    $results['string_function'] = microtime(true) - $start;
    
    return $results;
}

function demonstrateCallableValidation()
{
    $tests = [];
    
    // ✅ Valid array callables
    $validTests = [
        [UserController::class, 'index'],
        [UserController::class, 'staticMethod'],
        [new UserController(), 'index']
    ];
    
    foreach ($validTests as $callable) {
        try {
            CallableResolver::resolve($callable);
            $tests[] = [
                'callable' => is_object($callable[0]) ? get_class($callable[0]) . '::' . $callable[1] : implode('::', $callable),
                'status' => 'valid',
                'message' => 'Array callable validation passed ✅'
            ];
        } catch (Exception $e) {
            $tests[] = [
                'callable' => implode('::', $callable),
                'status' => 'invalid',
                'message' => $e->getMessage()
            ];
        }
    }
    
    // ❌ Invalid array callables
    $invalidTests = [
        [ValidationDemoController::class, 'invalidMethod'], // private method
        ['NonExistentClass', 'method'], // class doesn't exist
        [ValidationDemoController::class, 'nonExistentMethod'] // method doesn't exist
    ];
    
    foreach ($invalidTests as $callable) {
        try {
            CallableResolver::resolve($callable);
            $tests[] = [
                'callable' => implode('::', $callable),
                'status' => 'unexpected_valid',
                'message' => 'This should have failed!'
            ];
        } catch (Exception $e) {
            $tests[] = [
                'callable' => implode('::', $callable),
                'status' => 'correctly_invalid',
                'message' => 'Correctly rejected: ' . $e->getMessage() . ' ✅'
            ];
        }
    }
    
    return $tests;
}

// ===============================================
// APPLICATION SETUP
// ===============================================

$app = new Application();

// Initialize controllers with dependency injection
$userController = new UserController();
$productController = new ProductController(function($msg) {
    error_log("[ARRAY_CALLABLE_DEMO] $msg");
});
$validationController = new ValidationDemoController();

// ===============================================
// ROUTES - Array Callables v1.1.4+
// ===============================================

// Home page with documentation
$app->get('/', function($req, $res) {
    return $res->json([
        'title' => 'PivotPHP v1.1.4+ Array Callables Demo',
        'description' => 'Demonstração completa dos novos array callables nativos',
        'features' => [
            'native_array_callables' => 'Support for [Controller::class, \'method\']',
            'automatic_validation' => 'CallableResolver validates public/private methods',
            'enhanced_errors' => 'ContextualException with detailed diagnostics',
            'performance_optimized' => 'Zero overhead validation'
        ],
        'demo_endpoints' => [
            'GET /' => 'This documentation',
            'GET /demo/static-method' => 'Static method via array callable',
            'GET /demo/instance-method' => 'Instance method via array callable',
            'GET /demo/large-response' => 'Large response with JsonBufferPool optimization',
            'GET /demo/validation' => 'Validation demonstration',
            'GET /demo/performance' => 'Performance benchmarks',
            'GET /users' => 'Users list via array callable',
            'GET /users/:id' => 'User detail with error handling',
            'GET /error-demo' => 'Error handling demonstration'
        ],
        'syntax_examples' => [
            'supported' => [
                'instance_method' => '[$controller, \'method\']',
                'static_method' => '[Controller::class, \'method\']',
                'closure' => 'function($req, $res) { ... }',
                'named_function' => '\'functionName\''
            ],
            'not_supported' => [
                'string_format' => '\'Controller@method\' - Use array callable instead!',
                'brace_params' => '"/route/{param}" - Use colon syntax ":param"'
            ]
        ]
    ]);
});

// ✅ Array callables - Static method
$app->get('/demo/static-method', [UserController::class, 'staticMethod']);

// ✅ Array callables - Instance method
$app->get('/demo/instance-method', [$userController, 'index']);

// ✅ Array callables - Large response for JsonBufferPool demo
$app->get('/demo/large-response', [$productController, 'list']);

// ✅ Array callables - Users resource
$app->get('/users', [$userController, 'index']);
$app->get('/users/:id<\\d+>', [$userController, 'show']);

// Validation demonstration
$app->get('/demo/validation', function($req, $res) {
    $validationResults = demonstrateCallableValidation();
    
    return $res->json([
        'title' => 'Array Callable Validation Demo v1.1.4+',
        'description' => 'Demonstra a validação automática de array callables',
        'validation_results' => $validationResults,
        'callableResolver_features' => [
            'public_method_validation' => 'Only public methods are allowed',
            'class_existence_check' => 'Verifies class exists before validation',
            'method_existence_check' => 'Verifies method exists in class',
            'accessibility_validation' => 'Ensures method is publicly accessible',
            'enhanced_error_messages' => 'Detailed error messages with suggestions'
        ],
        'summary' => [
            'total_tests' => count($validationResults),
            'valid_callables' => count(array_filter($validationResults, fn($r) => $r['status'] === 'valid')),
            'correctly_rejected' => count(array_filter($validationResults, fn($r) => $r['status'] === 'correctly_invalid'))
        ]
    ]);
});

// Performance demonstration
$app->get('/demo/performance', function($req, $res) {
    $iterations = (int) $req->get('iterations', 10000);
    $benchmarkResults = benchmarkCallableTypes($iterations);
    
    return $res->json([
        'title' => 'Array Callable Performance Demo v1.1.4+',
        'description' => 'Compara performance de diferentes tipos de callables',
        'iterations' => $iterations,
        'benchmark_results_seconds' => $benchmarkResults,
        'benchmark_results_milliseconds' => array_map(fn($time) => round($time * 1000, 3), $benchmarkResults),
        'performance_notes' => [
            'array_callables' => 'Optimized validation with minimal overhead',
            'caching' => 'CallableResolver caches validation results internally',
            'zero_runtime_cost' => 'Validation happens only during route registration',
            'production_ready' => 'No performance impact on request handling'
        ],
        'recommendations' => [
            'use_array_callables' => 'For better code organization and IDE support',
            'prefer_static_methods' => 'For stateless operations',
            'use_instance_methods' => 'For stateful operations with dependency injection'
        ]
    ]);
});

// ✅ Valid array callable
$app->get('/demo/valid-callable', [$validationController, 'validMethod']);

// ❌ Error demonstration - This will show enhanced error diagnostics
$app->get('/error-demo', function($req, $res) {
    try {
        // Try to register an invalid array callable
        $invalidCallable = [ValidationDemoController::class, 'invalidMethod'];
        CallableResolver::resolve($invalidCallable);
        
        return $res->json([
            'error' => 'This should not happen - invalid callable was accepted!'
        ]);
    } catch (Exception $e) {
        // This will demonstrate enhanced error diagnostics
        if ($e instanceof ContextualException) {
            return $res->status(400)->json([
                'demonstration' => 'Enhanced Error Diagnostics v1.1.4+',
                'error_type' => 'ContextualException',
                'message' => $e->getMessage(),
                'category' => $e->getCategory(),
                'context' => $e->getContext(),
                'suggestions' => $e->getSuggestions(),
                'debug_info' => $e->getDebugInfo()
            ]);
        } else {
            return $res->status(400)->json([
                'demonstration' => 'Standard Exception',
                'error_type' => get_class($e),
                'message' => $e->getMessage(),
                'note' => 'This shows how enhanced errors provide more context than standard exceptions'
            ]);
        }
    }
});

// Migration examples - showing old vs new syntax
$app->get('/demo/migration', function($req, $res) {
    return $res->json([
        'title' => 'Migration Guide - Array Callables v1.1.4+',
        'migration_examples' => [
            'before_v114' => [
                'description' => 'How routes were defined before v1.1.4',
                'code' => 'function($req, $res) { $controller = new UserController(); return $controller->index($req, $res); }',
                'issues' => [
                    'verbose_syntax' => 'Required closure wrapper for every route',
                    'manual_instantiation' => 'Had to manually create controller instances',
                    'no_validation' => 'No automatic validation of callable validity',
                    'harder_testing' => 'More complex to test individual controller methods'
                ]
            ],
            'after_v114' => [
                'description' => 'Clean array callable syntax in v1.1.4+',
                'code' => '[UserController::class, \'index\']',
                'benefits' => [
                    'clean_syntax' => 'Direct array callable support',
                    'automatic_validation' => 'CallableResolver validates at registration time',
                    'enhanced_errors' => 'Detailed error messages with context and suggestions',
                    'performance_optimized' => 'Zero runtime overhead after validation',
                    'ide_support' => 'Better IDE completion and refactoring support'
                ]
            ]
        ],
        'migration_checklist' => [
            'replace_closure_wrappers' => 'Replace closures with array callables where appropriate',
            'update_controller_methods' => 'Ensure all methods are public',
            'use_class_constants' => 'Use ControllerClass::class instead of string names',
            'test_thoroughly' => 'Verify all routes work with new syntax',
            'update_documentation' => 'Update route documentation to reflect new syntax'
        ]
    ]);
});

// Comparison endpoint - shows all supported syntaxes working together
$app->get('/demo/syntax-comparison', function($req, $res) {
    return $res->json([
        'title' => 'All Supported Route Handler Syntaxes v1.1.4+',
        'syntaxes' => [
            'array_callable_static' => [
                'example' => '[UserController::class, \'staticMethod\']',
                'endpoint' => '/demo/static-method',
                'description' => 'Static method via array callable',
                'benefits' => ['Clean syntax', 'IDE support', 'No instantiation needed']
            ],
            'array_callable_instance' => [
                'example' => '[$controller, \'method\']',
                'endpoint' => '/demo/instance-method',
                'description' => 'Instance method via array callable',
                'benefits' => ['Dependency injection support', 'Stateful operations', 'Clean syntax']
            ],
            'closure' => [
                'example' => 'function($req, $res) { return $res->json([...]); }',
                'endpoint' => '/',
                'description' => 'Anonymous function/closure',
                'benefits' => ['Inline logic', 'Quick prototyping', 'No separate class needed']
            ],
            'named_function' => [
                'example' => '\'namedFunction\'',
                'endpoint' => 'N/A in this demo',
                'description' => 'Named function reference',
                'benefits' => ['Simple functions', 'Global utilities', 'Functional programming style']
            ]
        ],
        'not_supported' => [
            'string_controller_method' => [
                'example' => '\'UserController@index\'',
                'reason' => 'Not considered callable by PHP',
                'migration' => 'Use [UserController::class, \'index\'] instead'
            ],
            'brace_syntax' => [
                'example' => '/route/{param}',
                'reason' => 'Reserved for regex definitions',
                'migration' => 'Use /route/:param instead'
            ]
        ]
    ]);
});

$app->run();