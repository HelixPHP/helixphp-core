<?php
// Teste dos middlewares de segurança
require_once __DIR__ . '/../vendor/autoload.php';

use Express\SRC\Services\CsrfMiddleware;
use Express\SRC\Services\XssMiddleware;
use Express\SRC\Services\SecurityMiddleware;

echo "=== TESTE DOS MIDDLEWARES DE SEGURANÇA ===\n\n";

// Simular início de sessão
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// 1. Teste CSRF
echo "1. TESTE CSRF:\n";
echo "----------------------------------------\n";

// Gerar token
$token1 = CsrfMiddleware::getToken();
echo "Token gerado: $token1\n";

// Gerar token novamente (deve ser o mesmo)
$token2 = CsrfMiddleware::getToken();
echo "Token regenerado: $token2\n";
echo "Tokens são iguais: " . ($token1 === $token2 ? "SIM" : "NÃO") . "\n";

// Testar validação
$validToken = $token1;
$invalidToken = 'token_invalido';

echo "Validação token correto: " . (hash_equals($_SESSION['csrf_token'], $validToken) ? "PASSOU" : "FALHOU") . "\n";
echo "Validação token incorreto: " . (hash_equals($_SESSION['csrf_token'], $invalidToken) ? "FALHOU" : "PASSOU") . "\n";

// Testar métodos utilitários
echo "Campo hidden: " . htmlspecialchars(CsrfMiddleware::hiddenField()) . "\n";
echo "Meta tag: " . htmlspecialchars(CsrfMiddleware::metaTag()) . "\n";

echo "\n";

// 2. Teste XSS
echo "2. TESTE XSS:\n";
echo "----------------------------------------\n";

$testInputs = [
    'texto_normal' => 'Olá mundo!',
    'html_seguro' => '<p>Texto com <strong>formatação</strong></p>',
    'script_malicioso' => '<script>alert("XSS")</script>',
    'onclick_handler' => '<p onclick="alert(\'xss\')">Clique aqui</p>',
    'javascript_url' => 'javascript:alert("evil")',
    'iframe_embed' => '<iframe src="http://evil.com"></iframe>',
    'mixed_content' => 'Texto normal <script>evil()</script> mais texto <p>parágrafo</p>'
];

foreach ($testInputs as $type => $input) {
    echo "\nTipo: $type\n";
    echo "Entrada: $input\n";
    
    // Teste de detecção
    $containsXss = XssMiddleware::containsXss($input);
    echo "Contém XSS: " . ($containsXss ? "SIM" : "NÃO") . "\n";
    
    // Sanitização completa
    $sanitized = XssMiddleware::sanitize($input);
    echo "Sanitizado (sem tags): $sanitized\n";
    
    // Sanitização com tags permitidas
    $sanitizedWithTags = XssMiddleware::sanitize($input, '<p><strong><em>');
    echo "Sanitizado (com tags): $sanitizedWithTags\n";
    
    // Limpeza de URL se aplicável
    if (strpos($type, 'url') !== false || strpos($input, 'javascript:') !== false) {
        $cleanUrl = XssMiddleware::cleanUrl($input);
        echo "URL limpa: $cleanUrl\n";
    }
}

echo "\n";

// 3. Teste de configuração de middlewares
echo "3. TESTE DE CONFIGURAÇÃO:\n";
echo "----------------------------------------\n";

// Teste SecurityMiddleware
$securityConfigs = [
    'basic' => SecurityMiddleware::create(),
    'strict' => SecurityMiddleware::strict(),
    'csrf_only' => SecurityMiddleware::csrfOnly(),
    'xss_only' => SecurityMiddleware::xssOnly()
];

foreach ($securityConfigs as $name => $middleware) {
    echo "Configuração: $name - " . get_class($middleware) . " ✓\n";
}

echo "\n";

// 4. Teste de simulação de requisição
echo "4. SIMULAÇÃO DE REQUISIÇÃO:\n";
echo "----------------------------------------\n";

// Simular dados de entrada com XSS
$_POST = [
    'name' => 'João <script>alert("xss")</script>',
    'email' => 'joao@teste.com',
    'comment' => '<p>Comentário válido</p><script>evil()</script>',
    'csrf_token' => $token1
];

$_GET = [
    'search' => '<script>alert("get xss")</script>',
    'page' => '1'
];

echo "POST original:\n";
print_r($_POST);

echo "\nGET original:\n";
print_r($_GET);

// Simular aplicação do XssMiddleware (sanitização manual)
$sanitizedPost = [];
foreach ($_POST as $key => $value) {
    if ($key === 'csrf_token') {
        $sanitizedPost[$key] = $value; // Não sanitizar token CSRF
    } else {
        $sanitizedPost[$key] = XssMiddleware::sanitize($value);
    }
}

$sanitizedGet = [];
foreach ($_GET as $key => $value) {
    $sanitizedGet[$key] = XssMiddleware::sanitize($value);
}

echo "\nPOST sanitizado:\n";
print_r($sanitizedPost);

echo "\nGET sanitizado:\n";
print_r($sanitizedGet);

// Verificar token CSRF
$csrfValid = isset($sanitizedPost['csrf_token']) && 
             hash_equals($_SESSION['csrf_token'], $sanitizedPost['csrf_token']);

echo "\nValidação CSRF: " . ($csrfValid ? "VÁLIDA" : "INVÁLIDA") . "\n";

echo "\n=== TESTE CONCLUÍDO ===\n";
echo "Todos os middlewares foram testados com sucesso!\n";
echo "Verifique os resultados acima para confirmar o funcionamento correto.\n";
