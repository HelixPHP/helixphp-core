<?php
// Security middlewares usage example in Express PHP

require_once '../vendor/autoload.php';

use Express\SRC\ApiExpress;
use Express\SRC\Middlewares\Security\CsrfMiddleware;
use Express\SRC\Middlewares\Security\XssMiddleware;
use Express\SRC\Middlewares\Security\SecurityMiddleware;

// Create application
$app = new ApiExpress();

// ========================================
// OPTION 1: Use combined security middleware
// ========================================

// Basic security (CSRF + XSS)
$app->use(SecurityMiddleware::create());

// Or strict security (with rate limiting)
// $app->use(SecurityMiddleware::strict());

// ========================================
// OPTION 2: Use individual middlewares
// ========================================

// XSS Middleware (apply first)
// $app->use(new XssMiddleware([
//     'sanitizeInput' => true,
//     'securityHeaders' => true,
//     'excludeFields' => ['content', 'description'] // fields that allow HTML
// ]));

// CSRF Middleware
// $app->use(new CsrfMiddleware([
//     'excludePaths' => ['/api/public'], // public endpoints
//     'generateTokenResponse' => true // include token in response
// ]));

// ========================================
// EXAMPLE ROUTES
// ========================================

// Route to get CSRF token
$app->get('/csrf-token', function($req, $res) {
    $token = CsrfMiddleware::getToken();
    $res->json([
        'csrf_token' => $token,
        'meta_tag' => CsrfMiddleware::metaTag(),
        'hidden_field' => CsrfMiddleware::hiddenField()
    ]);
});

// Public route (no CSRF protection)
$app->get('/api/public/status', function($req, $res) {
    $res->json(['status' => 'ok', 'timestamp' => time()]);
});

// Protected route that requires CSRF
$app->post('/api/user/create', function($req, $res) {
    // Data has been sanitized by XssMiddleware
    $userData = $req->body;
    
    // Example of additional validation
    if (XssMiddleware::containsXss($userData['name'] ?? '')) {
        $res->status(400)->json(['error' => 'Invalid input detected']);
        return;
    }
    
    $res->json([
        'message' => 'User created successfully',
        'data' => $userData
    ]);
});

// Upload route (sanitize URL)
$app->post('/api/upload', function($req, $res) {
    $file = $req->body;
    
    // Sanitize URL if provided
    if (isset($file['callback_url'])) {
        $file['callback_url'] = XssMiddleware::cleanUrl($file['callback_url']);
    }
    
    $res->json([
        'message' => 'File uploaded',
        'callback_url' => $file['callback_url'] ?? null
    ]);
});

// Route that returns HTML form with CSRF protection
$app->get('/form', function($req, $res) {
    $csrfField = CsrfMiddleware::hiddenField();
    $csrfMeta = CsrfMiddleware::metaTag();
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Secure Form</title>
        {$csrfMeta}
        <meta charset='UTF-8'>
    </head>
    <body>
        <h1>Form with CSRF Protection</h1>
        <form action='/api/user/create' method='POST'>
            {$csrfField}
            <div>
                <label>Name:</label>
                <input type='text' name='name' required>
            </div>
            <div>
                <label>Email:</label>
                <input type='email' name='email' required>
            </div>
            <div>
                <label>Comment:</label>
                <textarea name='comment'></textarea>
            </div>
            <button type='submit'>Submit</button>
        </form>
        
        <script>
        // Example of how to use CSRF token in AJAX requests
        const csrfToken = document.querySelector('meta[name=\"csrf-token\"]').getAttribute('content');
        
        function makeSecureRequest(url, data) {
            return fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrfToken
                },
                body: JSON.stringify(data)
            });
        }
        </script>
    </body>
    </html>";
    
    $res->header('Content-Type', 'text/html; charset=UTF-8');
    $res->send($html);
});

// Error handling middleware
$app->use(function($req, $res, $next) {
    $res->status(404)->json(['error' => 'Route not found']);
});

// Start application
$app->run();
