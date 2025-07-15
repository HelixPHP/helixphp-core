<?php

/**
 * 🚨 PivotPHP v1.1.4+ - Enhanced Error Handling Demo
 * 
 * Demonstra o sistema avançado de diagnóstico de erros:
 * • ContextualException com contexto detalhado
 * • Error categorization
 * • Suggestions for resolution
 * • Debug information
 * • Development vs Production modes
 * 
 * ✨ Novidades v1.1.4+:
 * • Enhanced error diagnostics
 * • Contextual exception handling
 * • Automatic error categorization
 * • Built-in troubleshooting suggestions
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/09-error-handling/enhanced-errors-v114.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl http://localhost:8000/error/route-not-found
 * curl http://localhost:8000/error/invalid-parameter/abc
 * curl http://localhost:8000/error/handler-error
 * curl http://localhost:8000/error/middleware-error
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Exceptions\Enhanced\ContextualException;
use PivotPHP\Core\Utils\CallableResolver;

// ===============================================
// ERROR DEMO CONTROLLERS
// ===============================================

class ErrorDemoController
{
    public function index($req, $res)
    {
        return $res->json([
            'title' => 'Enhanced Error Handling Demo v1.1.4+',
            'description' => 'Demonstra o sistema avançado de diagnóstico de erros',
            'features_v114' => [
                'contextual_exceptions' => 'Detailed error context and suggestions',
                'error_categorization' => 'Automatic categorization of error types',
                'development_mode' => 'Rich debug information in development',
                'production_mode' => 'Clean error messages for production',
                'troubleshooting_suggestions' => 'Built-in suggestions for common issues'
            ],
            'demo_endpoints' => [
                'GET /error/route-not-found' => 'Route not found error with suggestions',
                'GET /error/invalid-parameter/abc' => 'Parameter validation error',
                'GET /error/handler-error' => 'Handler execution error',
                'GET /error/middleware-error' => 'Middleware error with context',
                'GET /error/custom-error' => 'Custom contextual error',
                'GET /error/validation' => 'Validation error with multiple fields',
                'GET /debug/stack-trace' => 'Error with full stack trace',
                'GET /production/error' => 'Production-mode error (clean)'
            ],
            'error_categories' => [
                'ROUTING' => 'Route-related errors (404, method not allowed)',
                'PARAMETER' => 'Parameter validation and type errors',
                'HANDLER' => 'Controller/handler execution errors',
                'MIDDLEWARE' => 'Middleware processing errors',
                'VALIDATION' => 'Data validation and business rule errors',
                'AUTHENTICATION' => 'Auth and permission errors',
                'SYSTEM' => 'System and infrastructure errors'
            ],
            'error_information' => [
                'context' => 'Detailed information about error circumstances',
                'suggestions' => 'Actionable suggestions for resolution',
                'debug_info' => 'Technical details for debugging (dev mode only)',
                'category' => 'Error classification for systematic handling',
                'request_id' => 'Unique identifier for error tracking'
            ]
        ]);
    }
    
    public function routeNotFoundDemo($req, $res)
    {
        // Simulate available routes for better error context
        $availableRoutes = [
            'GET /',
            'GET /error/invalid-parameter/:id',
            'GET /error/handler-error',
            'GET /error/middleware-error',
            'GET /debug/stack-trace'
        ];
        
        throw ContextualException::routeNotFound(
            'GET',
            '/non-existent-route',
            $availableRoutes
        );
    }
    
    public function invalidParameterDemo($req, $res)
    {
        $id = $req->param('id');
        
        // Demonstrate parameter validation error
        if (!is_numeric($id)) {
            throw ContextualException::parameterError(
                'id',
                'integer',
                $id,
                '/error/invalid-parameter/:id'
            );
        }
        
        return $res->json([
            'message' => 'Parameter is valid',
            'id' => (int) $id
        ]);
    }
    
    public function handlerErrorDemo($req, $res)
    {
        try {
            // Simulate trying to call a non-existent method
            $invalidCallable = [NonExistentController::class, 'nonExistentMethod'];
            CallableResolver::resolve($invalidCallable);
        } catch (Exception $e) {
            throw ContextualException::handlerError(
                'array_callable',
                $e->getMessage(),
                [
                    'class' => NonExistentController::class,
                    'method' => 'nonExistentMethod',
                    'callable_type' => 'array',
                    'validation_failed' => true
                ]
            );
        }
    }
    
    public function middlewareErrorDemo($req, $res)
    {
        $middlewareStack = [
            'AuthMiddleware',
            'CorsMiddleware',
            'ErrorDemoMiddleware'
        ];
        
        throw ContextualException::middlewareError(
            'ErrorDemoMiddleware',
            'Simulated middleware processing error',
            $middlewareStack
        );
    }
    
    public function customErrorDemo($req, $res)
    {
        // Create custom contextual error
        $context = [
            'user_id' => 123,
            'action' => 'custom_operation',
            'resource' => 'demo_resource',
            'timestamp' => time(),
            'request_data' => $req->query()
        ];
        
        $suggestions = [
            'Verify user has permission for this operation',
            'Check if the resource exists and is accessible',
            'Ensure all required parameters are provided',
            'Try again with valid authentication credentials'
        ];
        
        throw new ContextualException(
            403,
            'Custom operation failed due to insufficient permissions',
            $context,
            $suggestions,
            'CUSTOM_OPERATION'
        );
    }
    
    public function validationErrorDemo($req, $res)
    {
        // Simulate complex validation error
        $validationErrors = [
            'name' => 'Name is required and must be at least 2 characters',
            'email' => 'Email format is invalid',
            'age' => 'Age must be between 18 and 120',
            'password' => 'Password must contain at least 8 characters with uppercase, lowercase and numbers'
        ];
        
        $context = [
            'validation_rules' => [
                'name' => 'required|min:2|max:100',
                'email' => 'required|email',
                'age' => 'required|integer|between:18,120',
                'password' => 'required|min:8|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/'
            ],
            'received_data' => $req->body() ?: ['empty' => 'no data received'],
            'validation_engine' => 'PivotPHP Enhanced Validation v1.1.4+'
        ];
        
        $suggestions = [
            'Provide all required fields: name, email, age, password',
            'Ensure email is in valid format (user@domain.com)',
            'Age must be a number between 18 and 120',
            'Password must be strong: 8+ chars, uppercase, lowercase, numbers',
            'Check API documentation for exact field requirements'
        ];
        
        throw new ContextualException(
            422,
            'Validation failed for multiple fields',
            $context,
            $suggestions,
            'VALIDATION'
        );
    }
    
    public function stackTraceDemo($req, $res)
    {
        // Set development mode to show stack trace
        putenv('APP_ENV=development');
        define('PIVOTPHP_DEBUG', true);
        
        // Create error with stack trace
        throw new ContextualException(
            500,
            'Development error with full stack trace',
            [
                'debug_mode' => true,
                'development_environment' => true,
                'stack_trace_enabled' => true
            ],
            [
                'This error includes full stack trace for debugging',
                'Stack trace is only shown in development mode',
                'In production, only clean error messages are displayed'
            ],
            'DEBUG'
        );
    }
    
    public function productionErrorDemo($req, $res)
    {
        // Set production mode to show clean errors
        putenv('APP_ENV=production');
        
        // Create error that would be cleaned in production
        throw new ContextualException(
            500,
            'Production error with clean output',
            [
                'production_mode' => true,
                'sensitive_data' => 'This will be hidden in production',
                'internal_error_code' => 'ERR_PROD_001'
            ],
            [
                'Contact support if the issue persists',
                'Check system status page for known issues',
                'Verify your request parameters and try again'
            ],
            'PRODUCTION'
        );
    }
}

class DebugController
{
    public function errorAnalysis($req, $res)
    {
        return $res->json([
            'title' => 'Error Analysis Tools v1.1.4+',
            'description' => 'Ferramentas para análise e debug de erros',
            'contextual_exception_benefits' => [
                'detailed_context' => 'Rich contextual information about error circumstances',
                'actionable_suggestions' => 'Specific suggestions for resolving the issue',
                'error_categorization' => 'Systematic classification of error types',
                'environment_awareness' => 'Different output for development vs production',
                'debug_information' => 'Technical details for effective troubleshooting'
            ],
            'error_handling_workflow' => [
                'error_occurs' => 'Exception is thrown with context',
                'categorization' => 'Error is automatically categorized',
                'context_gathering' => 'Relevant context information is collected',
                'suggestion_generation' => 'Actionable suggestions are generated',
                'output_formatting' => 'Response is formatted based on environment',
                'logging' => 'Error details are logged for analysis'
            ],
            'development_vs_production' => [
                'development' => [
                    'full_context' => true,
                    'suggestions' => true,
                    'debug_info' => true,
                    'stack_trace' => true,
                    'technical_details' => true
                ],
                'production' => [
                    'clean_messages' => true,
                    'user_friendly' => true,
                    'no_sensitive_data' => true,
                    'minimal_technical_info' => true,
                    'tracking_ids' => true
                ]
            ],
            'integration_examples' => [
                'api_responses' => 'Consistent error format for API consumers',
                'logging_systems' => 'Rich context for log analysis',
                'monitoring_tools' => 'Categorized errors for better alerting',
                'user_interfaces' => 'User-friendly error messages with guidance'
            ]
        ]);
    }
}

// ===============================================
// ERROR HANDLING MIDDLEWARE
// ===============================================

class ErrorHandlingMiddleware
{
    public static function globalErrorHandler($req, $res, $next)
    {
        try {
            return $next($req, $res);
        } catch (ContextualException $e) {
            // Enhanced error handling for ContextualException
            $isDevelopment = ($_ENV['APP_ENV'] ?? 'development') === 'development' || 
                           defined('PIVOTPHP_DEBUG') && PIVOTPHP_DEBUG === true;
            
            $errorResponse = [
                'error' => true,
                'status' => $e->getStatusCode(),
                'message' => $e->getMessage(),
                'category' => $e->getCategory(),
                'request_id' => uniqid('err_', true),
                'timestamp' => date('c')
            ];
            
            if ($isDevelopment) {
                $errorResponse['context'] = $e->getContext();
                $errorResponse['suggestions'] = $e->getSuggestions();
                $errorResponse['debug'] = $e->getDebugInfo();
                $errorResponse['file'] = $e->getFile();
                $errorResponse['line'] = $e->getLine();
            } else {
                // Production mode - clean, user-friendly errors
                $errorResponse['help'] = [
                    'If this problem persists, please contact support',
                    'Include the request_id when reporting this issue'
                ];
                if (!empty($e->getSuggestions())) {
                    $errorResponse['suggestions'] = array_slice($e->getSuggestions(), 0, 2); // Only first 2 suggestions
                }
            }
            
            // Log the full error details
            error_log("ContextualException [{$e->getCategory()}]: " . $e->getMessage());
            error_log("Context: " . json_encode($e->getContext()));
            
            return $res->status($e->getStatusCode())->json($errorResponse);
            
        } catch (Exception $e) {
            // Standard exception handling
            $errorResponse = [
                'error' => true,
                'status' => 500,
                'message' => 'Internal Server Error',
                'request_id' => uniqid('err_', true),
                'timestamp' => date('c')
            ];
            
            $isDevelopment = ($_ENV['APP_ENV'] ?? 'development') === 'development';
            
            if ($isDevelopment) {
                $errorResponse['debug'] = [
                    'exception_class' => get_class($e),
                    'original_message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ];
            }
            
            error_log("Unhandled Exception: " . $e->getMessage());
            
            return $res->status(500)->json($errorResponse);
        }
    }
    
    public static function requestLogger($req, $res, $next)
    {
        $requestId = uniqid('req_', true);
        $res->header('X-Request-ID', $requestId);
        
        error_log("Request [{$requestId}]: {$req->method()} {$req->uri()}");
        
        $start = microtime(true);
        $response = $next($req, $res);
        $duration = round((microtime(true) - $start) * 1000, 2);
        
        $res->header('X-Response-Time', $duration . 'ms');
        
        return $response;
    }
}

// ===============================================
// APPLICATION SETUP
// ===============================================

$app = new Application();

// Apply error handling middleware
$app->use([ErrorHandlingMiddleware::class, 'requestLogger']);
$app->use([ErrorHandlingMiddleware::class, 'globalErrorHandler']);

// Initialize controllers
$errorController = new ErrorDemoController();
$debugController = new DebugController();

// ===============================================
// ROUTES - Enhanced Error Handling Demo
// ===============================================

// Main demo routes
$app->get('/', [$errorController, 'index']);

// Error demonstration routes
$app->get('/error/route-not-found', [$errorController, 'routeNotFoundDemo']);
$app->get('/error/invalid-parameter/:id', [$errorController, 'invalidParameterDemo']);
$app->get('/error/handler-error', [$errorController, 'handlerErrorDemo']);
$app->get('/error/middleware-error', [$errorController, 'middlewareErrorDemo']);
$app->get('/error/custom-error', [$errorController, 'customErrorDemo']);
$app->get('/error/validation', [$errorController, 'validationErrorDemo']);

// Debug and analysis routes
$app->get('/debug/stack-trace', [$errorController, 'stackTraceDemo']);
$app->get('/debug/analysis', [$debugController, 'errorAnalysis']);
$app->get('/production/error', [$errorController, 'productionErrorDemo']);

// Interactive error testing
$app->get('/test/error/:type', function($req, $res) {
    $type = $req->param('type');
    
    switch($type) {
        case 'simple':
            throw new Exception('Simple exception for testing');
            
        case 'contextual':
            throw new ContextualException(
                400,
                'Test contextual error',
                ['test_type' => 'interactive', 'user_choice' => $type],
                ['This is a test error', 'Try different error types'],
                'TEST'
            );
            
        case 'validation':
            throw ContextualException::parameterError(
                'type',
                'valid error type',
                $type,
                '/test/error/:type'
            );
            
        case 'auth':
            throw new ContextualException(
                401,
                'Authentication required for this test',
                ['endpoint' => '/test/error/auth', 'required_auth' => true],
                ['Provide valid authentication', 'Check your API credentials'],
                'AUTHENTICATION'
            );
            
        case 'forbidden':
            throw new ContextualException(
                403,
                'Access forbidden for this test resource',
                ['user_role' => 'guest', 'required_role' => 'admin'],
                ['Contact administrator for access', 'Verify your permissions'],
                'AUTHORIZATION'
            );
            
        default:
            return $res->json([
                'message' => 'Valid error type required',
                'available_types' => ['simple', 'contextual', 'validation', 'auth', 'forbidden'],
                'example' => '/test/error/contextual'
            ]);
    }
});

// Error statistics endpoint
$app->get('/stats/errors', function($req, $res) {
    // This would typically come from a real error tracking system
    $errorStats = [
        'total_errors_today' => rand(10, 100),
        'error_rate_percent' => rand(1, 5),
        'most_common_categories' => [
            'VALIDATION' => rand(30, 50) . '%',
            'ROUTING' => rand(20, 30) . '%',
            'PARAMETER' => rand(10, 20) . '%',
            'HANDLER' => rand(5, 15) . '%',
            'SYSTEM' => rand(1, 10) . '%'
        ],
        'resolution_suggestions_effectiveness' => [
            'users_who_retried_successfully' => rand(60, 80) . '%',
            'support_tickets_reduced' => rand(40, 60) . '%',
            'average_resolution_time' => rand(5, 15) . ' minutes'
        ]
    ];
    
    return $res->json([
        'title' => 'Error Handling Statistics',
        'description' => 'Impact of enhanced error diagnostics v1.1.4+',
        'statistics' => $errorStats,
        'contextual_exception_benefits' => [
            'faster_debugging' => 'Detailed context reduces investigation time',
            'better_user_experience' => 'Clear suggestions help users resolve issues',
            'reduced_support_load' => 'Self-service resolution reduces support tickets',
            'improved_monitoring' => 'Categorized errors enable better alerting'
        ],
        'timestamp' => date('c')
    ]);
});

// Environment switcher for testing
$app->get('/env/:mode', function($req, $res) {
    $mode = $req->param('mode');
    
    if (!in_array($mode, ['development', 'production'])) {
        return $res->status(400)->json([
            'error' => 'Invalid environment mode',
            'valid_modes' => ['development', 'production']
        ]);
    }
    
    putenv("APP_ENV={$mode}");
    
    if ($mode === 'development') {
        define('PIVOTPHP_DEBUG', true);
    }
    
    return $res->json([
        'message' => "Environment switched to {$mode} mode",
        'mode' => $mode,
        'debug_enabled' => $mode === 'development',
        'error_detail_level' => $mode === 'development' ? 'full' : 'minimal',
        'test_suggestion' => "Try /error/custom-error to see {$mode} error output"
    ]);
});

$app->run();