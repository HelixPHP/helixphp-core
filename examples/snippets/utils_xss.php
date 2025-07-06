<?php
// Exemplo de proteção XSS e sanitização de dados
use Helix\Http\Psr15\Middleware\XssMiddleware;

// Dados potencialmente perigosos
$maliciousInput = '<script>alert("XSS")</script><p>Texto normal</p>';
$javascriptUrl = 'javascript:alert("XSS")';
$normalText = 'Texto seguro com <strong>formatação</strong>';

echo "=== SANITIZAÇÃO XSS ===\n";

// Sanitização básica (remove todas as tags)
$cleaned = XssMiddleware::sanitize($maliciousInput);
echo "Entrada maliciosa: $maliciousInput\n";
echo "Sanitizada (sem tags): $cleaned\n\n";

// Sanitização permitindo algumas tags
$cleanedWithTags = XssMiddleware::sanitize($normalText, '<strong><em><p>');
echo "Texto normal: $normalText\n";
echo "Sanitizado (com tags permitidas): $cleanedWithTags\n\n";

// Limpeza de URLs
$cleanUrl = XssMiddleware::cleanUrl($javascriptUrl);
echo "URL maliciosa: $javascriptUrl\n";
echo "URL limpa: $cleanUrl\n\n";

// Verificação de conteúdo XSS
$inputs = [
    'Texto normal',
    '<script>alert("xss")</script>',
    '<p onclick="alert()">Texto</p>',
    'javascript:void(0)',
    '<iframe src="evil.com"></iframe>'
];

echo "=== DETECÇÃO XSS ===\n";
foreach ($inputs as $input) {
    $isXss = XssMiddleware::containsXss($input) ? 'SIM' : 'NÃO';
    echo "Entrada: $input\n";
    echo "Contém XSS: $isXss\n\n";
}

// Exemplo de sanitização de array
$formData = [
    'name' => 'João <script>alert("xss")</script>',
    'email' => 'joao@teste.com',
    'comment' => '<p>Comentário <strong>válido</strong></p><script>evil()</script>',
    'url' => 'javascript:alert("evil")'
];

echo "=== SANITIZAÇÃO DE FORMULÁRIO ===\n";
echo "Dados originais:\n";
print_r($formData);

// Sanitização manual de cada campo
$sanitizedData = [];
foreach ($formData as $key => $value) {
    if ($key === 'comment') {
        // Permite algumas tags HTML no comentário
        $sanitizedData[$key] = XssMiddleware::sanitize($value, '<p><strong><em>');
    } elseif ($key === 'url') {
        // Limpa URLs
        $sanitizedData[$key] = XssMiddleware::cleanUrl($value);
    } else {
        // Sanitização padrão
        $sanitizedData[$key] = XssMiddleware::sanitize($value);
    }
}

echo "\nDados sanitizados:\n";
print_r($sanitizedData);
