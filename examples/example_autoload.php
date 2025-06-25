<?php
/**
 * Example demonstrating PSR-4 autoload usage in Express PHP
 * Run: php examples/example_autoload.php
 */

// Load Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';

// Import classes using PSR-4 namespaces
use Express\ApiExpress;
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Core\CorsMiddleware;
use Express\Middlewares\Core\RateLimitMiddleware;

echo "=== Express PHP PSR-4 Autoload Demo ===\n\n";

// Create Express app instance
$app = new ApiExpress();

// Add security middleware
$app->use(SecurityMiddleware::create());

// Add CORS middleware
$app->use(new CorsMiddleware());

// Add rate limiting
$app->use(new RateLimitMiddleware([
    'max' => 100,
    'window' => 60
]));

// Simple route
$app->get('/', function($req, $res) {
    $res->json([
        'message' => 'Express PHP with PSR-4 autoload working!',
        'features' => [
            'PSR-4 Autoloading',
            'Security Middlewares',
            'CORS Support',
            'Rate Limiting'
        ],
        'php_version' => PHP_VERSION,
        'timestamp' => date('c')
    ]);
});

// API route
$app->get('/api/status', function($req, $res) {
    $res->json([
        'status' => 'ok',
        'autoload' => 'PSR-4',
        'framework' => 'Express PHP',
        'version' => '2.0.0'
    ]);
});

echo "✅ Express PHP app configured successfully!\n";
echo "📦 PSR-4 autoload working\n";
echo "🔒 Security middlewares loaded\n";
echo "🌐 CORS middleware loaded\n";
echo "⏱️ Rate limiting middleware loaded\n\n";

echo "To test the app, run it on a web server:\n";
echo "php -S localhost:8000 examples/example_autoload.php\n";
echo "Then visit: http://localhost:8000\n\n";

// Only run the server if called directly from command line
if (php_sapi_name() === 'cli-server') {
    $app->run();
}
