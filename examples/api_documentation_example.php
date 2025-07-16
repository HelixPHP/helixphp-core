<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Middleware\Http\ApiDocumentationMiddleware;

// Create application
$app = new Application();

// Add automatic API documentation middleware
$app->use(new ApiDocumentationMiddleware([
    'docs_path' => '/docs',        // JSON endpoint
    'swagger_path' => '/swagger',  // Swagger UI endpoint
    'base_url' => 'http://localhost:8080',
    'enabled' => true
]));

// Add some example routes with documentation
$app->get('/users', function($req, $res) {
    /**
     * Get all users
     * @summary List all users
     * @description Returns a list of all users in the system
     * @tags Users
     * @response 200 array List of users
     */
    return $res->json([
        ['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com'],
        ['id' => 2, 'name' => 'Jane Smith', 'email' => 'jane@example.com']
    ]);
});

$app->get('/users/:id', function($req, $res) {
    /**
     * Get user by ID
     * @summary Get a specific user
     * @description Returns a single user by their ID
     * @tags Users
     * @param int id User ID
     * @response 200 object User object
     * @response 404 object User not found
     */
    $userId = $req->param('id');
    
    if ($userId === '1') {
        return $res->json(['id' => 1, 'name' => 'John Doe', 'email' => 'john@example.com']);
    }
    
    return $res->status(404)->json(['error' => 'User not found']);
});

$app->post('/users', function($req, $res) {
    /**
     * Create new user
     * @summary Create a new user
     * @description Creates a new user in the system
     * @tags Users
     * @body object User data
     * @response 201 object Created user
     * @response 400 object Validation error
     */
    $userData = $req->getBody();
    
    // Simulate user creation
    $newUser = [
        'id' => 3,
        'name' => $userData['name'] ?? 'Unknown',
        'email' => $userData['email'] ?? 'unknown@example.com'
    ];
    
    return $res->status(201)->json($newUser);
});

$app->get('/products', function($req, $res) {
    /**
     * Get all products
     * @summary List all products
     * @description Returns a list of all products
     * @tags Products
     * @query int limit Maximum number of products to return
     * @query int offset Number of products to skip
     * @response 200 array List of products
     */
    return $res->json([
        ['id' => 1, 'name' => 'Laptop', 'price' => 999.99],
        ['id' => 2, 'name' => 'Mouse', 'price' => 29.99]
    ]);
});

$app->get('/health', function($req, $res) {
    /**
     * Health check
     * @summary API health check
     * @description Returns the health status of the API
     * @tags System
     * @response 200 object Health status
     */
    return $res->json([
        'status' => 'healthy',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => '1.2.0'
    ]);
});

// Add info route
$app->get('/', function($req, $res) {
    return $res->json([
        'message' => 'PivotPHP Core API Documentation Example',
        'docs' => 'Visit /swagger for interactive documentation',
        'json' => 'Visit /docs for OpenAPI JSON',
        'version' => '1.2.0'
    ]);
});

// Run the application
$app->run();