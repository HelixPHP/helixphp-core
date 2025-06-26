<?php
// Exemplo de uso dos middlewares de segurança no Express PHP

require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middleware\Security\CsrfMiddleware;
use Express\Middleware\Security\XssMiddleware;
use Express\Middleware\Security\SecurityMiddleware;

// Cria a aplicação
$app = new ApiExpress();

// ========================================
// OPÇÃO 1: Usar middleware de segurança combinado
// ========================================

// Segurança básica (CSRF + XSS)
$app->use(SecurityMiddleware::create());

// Ou segurança estrita (com rate limiting)
// $app->use(SecurityMiddleware::strict());

// ========================================
// OPÇÃO 2: Usar middlewares individuais
// ========================================

// Middleware XSS (aplica primeiro)
// $app->use(new XssMiddleware([
//     'sanitizeInput' => true,
//     'securityHeaders' => true,
//     'excludeFields' => ['content', 'description'] // campos que permitem HTML
// ]));

// Middleware CSRF
// $app->use(new CsrfMiddleware([
//     'excludePaths' => ['/api/public'], // endpoints públicos
//     'generateTokenResponse' => true // incluir token na resposta
// ]));

// ========================================
// ROTAS DE EXEMPLO
// ========================================

// Rota para obter token CSRF
$app->get('/csrf-token', function($req, $res) {
    $token = CsrfMiddleware::getToken();
    $res->json([
        'csrf_token' => $token,
        'meta_tag' => CsrfMiddleware::metaTag(),
        'hidden_field' => CsrfMiddleware::hiddenField()
    ]);
});

// Rota pública (sem proteção CSRF)
$app->get('/api/public/status', function($req, $res) {
    $res->json(['status' => 'ok', 'timestamp' => time()]);
});

// Rota protegida que requer CSRF
$app->post('/api/user/create', function($req, $res) {
    // Dados já foram sanitizados pelo XssMiddleware
    $userData = $req->body;
    
    // Exemplo de validação adicional
    if (XssMiddleware::containsXss($userData['name'] ?? '')) {
        $res->status(400)->json(['error' => 'Invalid input detected']);
        return;
    }
    
    $res->json([
        'message' => 'User created successfully',
        'data' => $userData
    ]);
});

// Rota para upload (sanitiza URL)
$app->post('/api/upload', function($req, $res) {
    $file = $req->body;
    
    // Sanitiza URL se fornecida
    if (isset($file['callback_url'])) {
        $file['callback_url'] = XssMiddleware::cleanUrl($file['callback_url']);
    }
    
    $res->json([
        'message' => 'File uploaded',
        'callback_url' => $file['callback_url'] ?? null
    ]);
});

// Rota que retorna formulário HTML com proteção CSRF
$app->get('/form', function($req, $res) {
    $csrfField = CsrfMiddleware::hiddenField();
    $csrfMeta = CsrfMiddleware::metaTag();
    
    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <title>Formulário Seguro</title>
        {$csrfMeta}
        <meta charset='UTF-8'>
    </head>
    <body>
        <h1>Formulário com Proteção CSRF</h1>
        <form action='/api/user/create' method='POST'>
            {$csrfField}
            <div>
                <label>Nome:</label>
                <input type='text' name='name' required>
            </div>
            <div>
                <label>Email:</label>
                <input type='email' name='email' required>
            </div>
            <div>
                <label>Comentário:</label>
                <textarea name='comment'></textarea>
            </div>
            <button type='submit'>Enviar</button>
        </form>
        
        <script>
        // Exemplo de como usar o token CSRF em requisições AJAX
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

// Middleware de tratamento de erros
$app->use(function($req, $res, $next) {
    $res->status(404)->json(['error' => 'Route not found']);
});

// Inicia a aplicação
$app->run();
