<?php

/**
 * 📁 PivotPHP v1.1.3 - Static Files and Routes
 * 
 * Demonstrates two distinct approaches:
 * 1. $app->staticFiles() - Serves actual files from disk using StaticFileManager
 * 2. $app->static() - Pre-compiled static responses using StaticRouteManager
 * 
 * 🚀 How to run:
 * php -S localhost:8000 examples/02-routing/static-files.php
 * 
 * 🧪 How to test:
 * # Static files (served from disk)
 * curl http://localhost:8000/public/test.json      # File serving
 * curl http://localhost:8000/assets/app.css        # CSS file
 * curl http://localhost:8000/docs/readme.txt       # Text file
 * 
 * # Static routes (pre-compiled responses)
 * curl http://localhost:8000/api/static/health     # Optimized response
 * curl http://localhost:8000/api/static/version    # Static data
 * curl http://localhost:8000/api/static/metrics    # Pre-computed metrics
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Routing\SimpleStaticFileManager;

// 🎯 Create Application
$app = new Application();

// 📁 Create some test static files for demonstration
$staticDir = __DIR__ . '/static-demo';
if (!is_dir($staticDir)) {
    mkdir($staticDir, 0755, true);
    mkdir($staticDir . '/css', 0755, true);
    mkdir($staticDir . '/js', 0755, true);
    mkdir($staticDir . '/docs', 0755, true);
    
    // Create demo files
    file_put_contents($staticDir . '/test.json', json_encode([
        'message' => 'This is a static JSON file',
        'served_by' => 'SimpleStaticFileManager',
        'framework' => 'PivotPHP v1.1.3'
    ], JSON_PRETTY_PRINT));
    
    file_put_contents($staticDir . '/css/app.css', "
/* Demo CSS file served by PivotPHP */
body {
    font-family: Arial, sans-serif;
    background-color: #f5f5f5;
    color: #333;
}

.framework-demo {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 8px;
    background: white;
}
");
    
    file_put_contents($staticDir . '/js/app.js', "
// Demo JavaScript file served by PivotPHP
console.log('PivotPHP v1.1.3 - Static file serving working!');

function showFrameworkInfo() {
    return {
        framework: 'PivotPHP',
        version: '1.1.3',
        feature: 'Static file serving',
        performance: '+116% improvement'
    };
}
");
    
    file_put_contents($staticDir . '/docs/readme.txt', "
PivotPHP v1.1.3 - Static Files Demo

This file is served directly by SimpleStaticFileManager.

Features demonstrated:
- Static file serving with proper MIME types
- Directory organization
- Security (path traversal prevention)
- Performance optimization
- Integration with main application

Static files are served efficiently while maintaining
the framework's principle of simplicity over premature optimization.
");
}

// 🏠 Home route - Static files overview
$app->get('/', function($req, $res) {
    return $res->json([
        'title' => 'PivotPHP v1.1.3 - Static Files & Routes Demo',
        'two_approaches' => [
            'staticFiles()' => 'Serves actual files from disk using StaticFileManager',
            'static()' => 'Pre-compiled responses using StaticRouteManager for maximum performance'
        ],
        'static_file_serving' => [
            'method' => '$app->staticFiles()',
            'purpose' => 'Serve actual files from filesystem',
            'examples' => [
                'GET /public/test.json' => 'Demo JSON file from disk',
                'GET /assets/app.css' => 'Demo CSS file from disk',
                'GET /assets/app.js' => 'Demo JavaScript file from disk',
                'GET /docs/readme.txt' => 'Demo text file from disk'
            ],
            'features' => [
                'automatic_mime_detection' => true,
                'security_checks' => true,
                'cache_headers' => true,
                'directory_traversal_protection' => true
            ]
        ],
        'static_routes' => [
            'method' => '$app->static()',
            'purpose' => 'Pre-computed responses for maximum performance',
            'examples' => [
                'GET /api/static/health' => 'Static health check (pre-compiled)',
                'GET /api/static/version' => 'Static version info (optimized)',
                'GET /api/static/metrics' => 'Static metrics endpoint (cached)'
            ],
            'benefits' => [
                'zero_processing_time' => true,
                'pre_computed_responses' => true,
                'maximum_throughput' => true,
                'minimal_memory_usage' => true
            ]
        ]
    ]);
});

// 📁 Register static file directories using $app->staticFiles()
// This uses StaticFileManager to serve actual files from disk
try {
    // Register /public route to serve files from static-demo directory
    $app->staticFiles('/public', $staticDir, [
        'index' => ['index.html', 'index.htm'],
        'dotfiles' => 'ignore',
        'extensions' => false,
        'fallthrough' => true,
        'redirect' => true
    ]);
    
    // Register /assets route for CSS/JS files
    $app->staticFiles('/assets', $staticDir, [
        'index' => false,
        'dotfiles' => 'deny'
    ]);
    
    // Register /docs route for documentation files  
    $app->staticFiles('/docs', $staticDir . '/docs');
    
} catch (Exception $e) {
    // Handle directory registration errors gracefully
    $app->get('/static-error', function($req, $res) use ($e) {
        return $res->status(500)->json([
            'error' => 'Static file setup failed',
            'message' => $e->getMessage(),
            'suggestion' => 'Check directory permissions and paths'
        ]);
    });
}

// 🚀 Static Routes using $app->static() - Pre-compiled responses
// These use StaticRouteManager for maximum performance optimization

// Health check with static response
$app->static('/api/static/health', function($req, $res) {
    return $res->json([
        'status' => 'healthy',
        'framework' => 'PivotPHP',
        'version' => '1.1.3',
        'uptime' => 'demo',
        'static_route' => true,
        'optimized' => 'StaticRouteManager'
    ]);
}, [
    'cache_control' => 'public, max-age=300' // 5 minutes cache
]);

// Version info with static response
$app->static('/api/static/version', function($req, $res) {
    return $res->json([
        'framework' => 'PivotPHP Core',
        'version' => '1.1.3',
        'edition' => 'Performance Optimization & Array Callables',
        'php_version' => PHP_VERSION,
        'features' => [
            'array_callables' => true,
            'performance_boost' => '+116%',
            'object_pooling' => true,
            'json_optimization' => true
        ],
        'static_route' => true
    ]);
}, [
    'cache_control' => 'public, max-age=3600' // 1 hour cache
]);

// Metrics with static response
$app->static('/api/static/metrics', function($req, $res) {
    return $res->json([
        'framework_metrics' => [
            'throughput_improvement' => '+116%',
            'object_pool_reuse' => '100%',
            'json_operations' => '505K ops/sec',
            'memory_efficiency' => 'optimized'
        ],
        'static_route_benefits' => [
            'no_dynamic_processing' => true,
            'pre_computed_response' => true,
            'minimal_memory_usage' => true,
            'maximum_throughput' => true
        ],
        'generated_at' => date('c'),
        'static_route' => true
    ]);
}, [
    'cache_control' => 'public, max-age=60' // 1 minute cache
]);

// 📊 Static implementation details
$app->get('/static-info', function($req, $res) {
    return $res->json([
        'app_staticFiles_method' => [
            'purpose' => 'Serve actual files from disk',
            'uses' => 'StaticFileManager class',
            'api_call' => '$app->staticFiles(\'/assets\', \'./public/assets\')',
            'features' => [
                'mime_type_detection' => 'Automatic based on file extension',
                'security' => 'Path traversal prevention',
                'cache_headers' => 'ETag and Last-Modified support',
                'index_files' => 'index.html, index.htm support'
            ],
            'use_cases' => [
                'css_js_files' => 'Frontend assets',
                'images' => 'Static images',
                'documents' => 'PDFs, text files',
                'downloads' => 'Static downloads'
            ]
        ],
        'app_static_method' => [
            'purpose' => 'Pre-compiled responses for maximum performance',
            'uses' => 'StaticRouteManager class',
            'api_call' => '$app->static(\'/api/health\', function($req, $res) { ... })',
            'optimization' => 'Pre-computed responses for maximum performance',
            'use_cases' => [
                'health_checks' => 'Service monitoring endpoints',
                'version_info' => 'Application metadata',
                'metrics' => 'Performance indicators',
                'api_status' => 'Service availability'
            ],
            'benefits' => [
                'zero_processing_time' => 'Response pre-computed',
                'maximum_throughput' => 'No dynamic processing',
                'minimal_memory' => 'Optimized memory usage',
                'cache_friendly' => 'HTTP cache headers'
            ]
        ],
        'when_to_use' => [
            'staticFiles' => 'When you need to serve actual files (CSS, JS, images, documents)',
            'static' => 'When you have fixed data that never changes (health, version, status)'
        ],
        'performance_comparison' => [
            'staticFiles' => 'Fast file serving with MIME detection and security',
            'static' => 'Ultra-fast pre-computed responses with zero processing',
            'recommendation' => 'Use static() for data, staticFiles() for files'
        ]
    ]);
});

// 🔧 Static files listing endpoint
$app->get('/files/list', function($req, $res) {
    return $res->json([
        'demo_static_paths' => [
            '/public' => 'Static files from demo directory',
            '/assets' => 'CSS and JS files',
            '/docs' => 'Documentation files'
        ],
        'static_route_examples' => [
            '/api/static/health',
            '/api/static/version', 
            '/api/static/metrics'
        ],
        'note' => 'Static files are served directly without PHP processing for maximum performance'
    ]);
});

// 🧪 Performance comparison endpoint
$app->get('/performance/static-vs-dynamic', function($req, $res) {
    // Simulate dynamic route processing time
    $dynamicStart = microtime(true);
    usleep(100); // Simulate some processing time
    $dynamicEnd = microtime(true);
    $dynamicTime = ($dynamicEnd - $dynamicStart) * 1000;
    
    // Static route would have ~0ms processing time (pre-computed)
    $staticTime = 0.001; // Essentially instantaneous
    
    return $res->json([
        'performance_comparison' => [
            'dynamic_route' => [
                'processing_time_ms' => round($dynamicTime, 3),
                'benefits' => ['flexible', 'real-time data', 'customizable'],
                'use_cases' => ['user-specific data', 'real-time calculations', 'database queries']
            ],
            'static_route' => [
                'processing_time_ms' => $staticTime,
                'benefits' => ['maximum_speed', 'minimal_cpu', 'cacheable'],
                'use_cases' => ['health checks', 'version info', 'fixed responses']
            ],
            'recommendation' => 'Use static routes for fixed responses, dynamic for real-time data'
        ],
        'framework_philosophy' => [
            'principle' => 'Right tool for the right job',
            'approach' => 'Simple, practical solutions over complex optimizations',
            'result' => 'High performance with maintainable code'
        ]
    ]);
});

// Clean up demo files on shutdown (optional)
register_shutdown_function(function() use ($staticDir) {
    if (is_dir($staticDir)) {
        // Recursively remove demo directory
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($staticDir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($files as $fileinfo) {
            $todo = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $todo($fileinfo->getRealPath());
        }
        rmdir($staticDir);
    }
});

// 🚀 Run the application
$app->run();