<?php
// Security middlewares test
require_once __DIR__ . '/../vendor/autoload.php';

use Express\Middlewares\Security\CsrfMiddleware;
use Express\Middlewares\Security\XssMiddleware;
use Express\Middlewares\Security\SecurityMiddleware;

echo "=== SECURITY MIDDLEWARES TEST ===\n\n";

// Simulate session start
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 1. CSRF Test
echo "1. CSRF TEST:\n";
echo "----------------------------------------\n";

// Generate token
$token1 = CsrfMiddleware::getToken();
echo "Token gerado: $token1\n";

// Gerar token novamente (deve ser o mesmo)
$token2 = CsrfMiddleware::getToken();
echo "Token regenerado: $token2\n";
echo "Tokens are equal: " . ($token1 === $token2 ? "YES" : "NO") . "\n";

// Test validation
$validToken = $token1;
$invalidToken = 'invalid_token';

echo "Valid token validation: " . (hash_equals($_SESSION['csrf_token'], $validToken) ? "PASSED" : "FAILED") . "\n";
echo "Invalid token validation: " . (hash_equals($_SESSION['csrf_token'], $invalidToken) ? "FAILED" : "PASSED") . "\n";

// Test utility methods
echo "Campo hidden: " . htmlspecialchars(CsrfMiddleware::hiddenField()) . "\n";
echo "Meta tag: " . htmlspecialchars(CsrfMiddleware::metaTag()) . "\n";

echo "\n";

// 2. XSS Test
echo "2. XSS TEST:\n";
echo "----------------------------------------\n";

$testInputs = [
    'normal_text' => 'Hello world!',
    'safe_html' => '<p>Text with <strong>formatting</strong></p>',
    'malicious_script' => '<script>alert("XSS")</script>',
    'onclick_handler' => '<p onclick="alert(\'xss\')">Click here</p>',
    'javascript_url' => 'javascript:alert("evil")',
    'iframe_embed' => '<iframe src="http://evil.com"></iframe>',
    'mixed_content' => 'Normal text <script>evil()</script> more text <p>paragraph</p>'
];

foreach ($testInputs as $type => $input) {
    echo "\nType: $type\n";
    echo "Input: $input\n";
    
    // Detection test
    $containsXss = XssMiddleware::containsXss($input);
    echo "Contains XSS: " . ($containsXss ? "YES" : "NO") . "\n";
    
    // Complete sanitization
    $sanitized = XssMiddleware::sanitize($input);
    echo "Sanitized (no tags): $sanitized\n";
    
    // Sanitization with allowed tags
    $sanitizedWithTags = XssMiddleware::sanitize($input, '<p><strong><em>');
    echo "Sanitized (with tags): $sanitizedWithTags\n";
    
    // URL cleaning if applicable
    if (strpos($type, 'url') !== false || strpos($input, 'javascript:') !== false) {
        $cleanUrl = XssMiddleware::cleanUrl($input);
        echo "Clean URL: $cleanUrl\n";
    }
}

echo "\n";

// 3. Middleware configuration test
echo "3. CONFIGURATION TEST:\n";
echo "----------------------------------------\n";

// Test SecurityMiddleware
$securityConfigs = [
    'basic' => SecurityMiddleware::create(),
    'strict' => SecurityMiddleware::strict(),
    'csrf_only' => SecurityMiddleware::csrfOnly(),
    'xss_only' => SecurityMiddleware::xssOnly()
];

foreach ($securityConfigs as $name => $middleware) {
    echo "Configuration: $name - " . get_class($middleware) . " ✓\n";
}

echo "\n";

// 4. Request simulation test
echo "4. REQUEST SIMULATION:\n";
echo "----------------------------------------\n";

// Simulate input data with XSS
$_POST = [
    'name' => 'John <script>alert("xss")</script>',
    'email' => 'john@test.com',
    'comment' => '<p>Valid comment</p><script>evil()</script>',
    'csrf_token' => $token1
];

$_GET = [
    'search' => '<script>alert("get xss")</script>',
    'page' => '1'
];

echo "Original POST:\n";
print_r($_POST);

echo "\nOriginal GET:\n";
print_r($_GET);

// Simulate XssMiddleware application (manual sanitization)
$sanitizedPost = [];
foreach ($_POST as $key => $value) {
    if ($key === 'csrf_token') {
        $sanitizedPost[$key] = $value; // Don't sanitize CSRF token
    } else {
        $sanitizedPost[$key] = XssMiddleware::sanitize($value);
    }
}

$sanitizedGet = [];
foreach ($_GET as $key => $value) {
    $sanitizedGet[$key] = XssMiddleware::sanitize($value);
}

echo "\nSanitized POST:\n";
print_r($sanitizedPost);

echo "\nSanitized GET:\n";
print_r($sanitizedGet);

// Check CSRF token
$csrfValid = isset($sanitizedPost['csrf_token']) && 
             hash_equals($_SESSION['csrf_token'], $sanitizedPost['csrf_token']);

echo "\nCSRF Validation: " . ($csrfValid ? "VALID" : "INVALID") . "\n";

echo "\n=== TEST COMPLETED ===\n";
echo "All middlewares have been tested successfully!\n";
echo "Check the results above to confirm correct functionality.\n";
