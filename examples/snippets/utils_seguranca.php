<?php
// Exemplo de configuração e uso dos middlewares de segurança
// use Express\ApiExpress;
use Express\Core\Application;
use Express\Http\Psr15\Middleware\SecurityHeadersMiddleware;


$app = new Application();

echo "=== CONFIGURAÇÕES DE SEGURANÇA ===\n\n";

// 1. Segurança básica (recomendado para a maioria dos casos)
echo "1. Configuração Básica:\n";
$basicSecurity = SecurityHeadersMiddleware::create();
echo "- Proteção CSRF ativada\n";
echo "- Proteção XSS ativada\n";
echo "- Cabeçalhos de segurança incluídos\n\n";

// 2. Segurança estrita (máxima proteção)
echo "2. Configuração Estrita:\n";
$strictSecurity = SecurityHeadersMiddleware::strict();
echo "- Proteção CSRF ativada\n";
echo "- Proteção XSS ativada\n";
echo "- Rate limiting ativado\n";
echo "- Segurança de sessão aprimorada\n";
echo "- Content Security Policy rigorosa\n\n";

// 3. Configuração personalizada
echo "3. Configuração Personalizada:\n";
$customSecurity = new SecurityHeadersMiddleware([
    'enableCsrf' => true,
    'enableXss' => true,
    'rateLimiting' => false,
    'csrf' => [
        'excludePaths' => ['/api/webhook', '/api/public'],
        'generateTokenResponse' => true
    ],
    'xss' => [
        'excludeFields' => ['content', 'html_content'],
        'allowedTags' => '<p><br><strong><em><ul><ol><li><a>'
    ]
]);
echo "- CSRF com caminhos excluídos\n";
echo "- XSS com campos excluídos\n";
echo "- Tags HTML específicas permitidas\n\n";

// 4. Middlewares individuais
echo "4. Middlewares Individuais:\n";

// Apenas CSRF
$csrfOnly = SecurityHeadersMiddleware::csrfOnly([
    'csrf' => [
        'headerName' => 'X-CSRF-Token',
        'fieldName' => 'csrf_token',
        'methods' => ['POST', 'PUT', 'DELETE']
    ]
]);
echo "- Apenas proteção CSRF\n";

// Apenas XSS
$xssOnly = SecurityHeadersMiddleware::xssOnly([
    'xss' => [
        'sanitizeInput' => true,
        'securityHeaders' => true
    ]
]);
echo "- Apenas proteção XSS\n\n";

// 5. Aplicação dos middlewares
echo "5. Como Aplicar:\n";
echo "
// No início da aplicação (antes das rotas)
\$app->use(SecurityHeadersMiddleware::create());

// Ou configuração específica
\$app->use(new SecurityHeadersMiddleware([
    'enableCsrf' => true,
    'enableXss' => true,
    'rateLimiting' => true
]));

// Para APIs públicas (apenas XSS)
\$app->use('/api/public', SecurityHeadersMiddleware::xssOnly());

// Para formulários web (CSRF + XSS)
\$app->use('/forms', SecurityHeadersMiddleware::create());
";

echo "\n=== CABEÇALHOS DE SEGURANÇA INCLUÍDOS ===\n";
echo "- X-XSS-Protection: 1; mode=block\n";
echo "- X-Content-Type-Options: nosniff\n";
echo "- X-Frame-Options: DENY\n";
echo "- Referrer-Policy: strict-origin-when-cross-origin\n";
echo "- Content-Security-Policy: (configurável)\n";
echo "- Access-Control-* (CORS seguro)\n\n";

echo "=== MÉTODOS UTILITÁRIOS ===\n";
echo "
// Obter token CSRF atual
\$token = CsrfMiddleware::getToken();

// Gerar campo hidden para formulários
\$hiddenField = CsrfMiddleware::hiddenField();

// Gerar meta tag para AJAX
\$metaTag = CsrfMiddleware::metaTag();

// Sanitizar entrada manualmente
\$clean = XssMiddleware::sanitize(\$input);

// Verificar se contém XSS
\$hasXss = XssMiddleware::containsXss(\$input);

// Limpar URLs
\$safeUrl = XssMiddleware::cleanUrl(\$url);
";

echo "\n=== CONFIGURAÇÃO RECOMENDADA ===\n";
echo "Para aplicações web completas:\n";
echo "
\$app->use(SecurityMiddleware::strict([
    'csrf' => [
        'excludePaths' => ['/api/webhook'],
        'generateTokenResponse' => true
    ],
    'xss' => [
        'excludeFields' => ['rich_content'],
        'contentSecurityPolicy' => \"default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';\"
    ]
]));
";
