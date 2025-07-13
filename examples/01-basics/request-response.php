<?php

/**
 * 📡 PivotPHP - Request/Response Avançado
 * 
 * Demonstra manipulação completa de Request e Response
 * Headers, cookies, status codes e parsing de dados
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/01-basics/request-response.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl -X POST http://localhost:8000/data -H "Content-Type: application/json" -d '{"name":"test"}'
 * curl http://localhost:8000/headers -H "User-Agent: TestClient/1.0"
 * curl http://localhost:8000/cookie
 */

require_once dirname(__DIR__, 2) . '/pivotphp-core/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📄 Página inicial com informações do request
$app->get('/', function ($req, $res) {
    return $res->json([
        'message' => 'Demonstração de Request/Response',
        'request_info' => [
            'method' => $req->method(),
            'uri' => $req->uri(),
            'headers' => $req->headers(),
            'query_params' => $req->query(),
            'user_agent' => $req->header('User-Agent'),
            'ip' => $req->ip(),
            'timestamp' => date('Y-m-d H:i:s')
        ],
        'available_endpoints' => [
            'GET /' => 'Informações do request',
            'POST /data' => 'Receber dados JSON',
            'GET /headers' => 'Manipular headers',
            'GET /cookie' => 'Trabalhar com cookies',
            'GET /status/:code' => 'Testar códigos de status',
            'GET /download' => 'Download de arquivo'
        ]
    ]);
});

// 📨 POST - Receber e processar dados
$app->post('/data', function ($req, $res) {
    $contentType = $req->header('Content-Type');
    
    // Processar diferentes tipos de conteúdo
    if (strpos($contentType, 'application/json') !== false) {
        $data = $req->getBodyAsStdClass();
        $type = 'JSON';
    } elseif (strpos($contentType, 'application/x-www-form-urlencoded') !== false) {
        $data = $req->body();
        $type = 'Form Data';
    } else {
        $data = $req->body();
        $type = 'Raw Data';
    }
    
    return $res->json([
        'received_data' => $data,
        'content_type' => $contentType,
        'data_type' => $type,
        'content_length' => $req->header('Content-Length'),
        'processed_at' => date('Y-m-d H:i:s')
    ]);
});

// 🔧 Headers customizados
$app->get('/headers', function ($req, $res) {
    // Ler headers do request
    $userAgent = $req->header('User-Agent');
    $accept = $req->header('Accept');
    
    // Definir headers de resposta
    $res->header('X-Framework', 'PivotPHP');
    $res->header('X-Version', Application::VERSION);
    $res->header('X-Response-Time', microtime(true));
    
    return $res->json([
        'request_headers' => [
            'user_agent' => $userAgent,
            'accept' => $accept,
            'all_headers' => $req->headers()
        ],
        'response_headers_set' => [
            'X-Framework' => 'PivotPHP',
            'X-Version' => Application::VERSION,
            'X-Response-Time' => 'Current microtime'
        ]
    ]);
});

// 🍪 Trabalhar com cookies
$app->get('/cookie', function ($req, $res) {
    // Ler cookie existente
    $existingCookie = $req->cookie('pivot_session');
    
    // Criar novo cookie
    $sessionId = uniqid('pivot_', true);
    $res->cookie('pivot_session', $sessionId, [
        'expires' => time() + 3600, // 1 hora
        'path' => '/',
        'httponly' => true,
        'secure' => false // true em HTTPS
    ]);
    
    return $res->json([
        'existing_cookie' => $existingCookie,
        'new_session_id' => $sessionId,
        'all_cookies' => $req->cookies(),
        'cookie_info' => 'Cookie set for 1 hour'
    ]);
});

// 📊 Testar diferentes códigos de status
$app->get('/status/:code', function ($req, $res) {
    $code = (int) $req->param('code');
    
    $statusMessages = [
        200 => 'OK - Sucesso',
        201 => 'Created - Recurso criado',
        400 => 'Bad Request - Requisição inválida',
        401 => 'Unauthorized - Não autorizado',
        403 => 'Forbidden - Proibido',
        404 => 'Not Found - Não encontrado',
        422 => 'Unprocessable Entity - Dados inválidos',
        500 => 'Internal Server Error - Erro interno'
    ];
    
    $message = $statusMessages[$code] ?? 'Status Code Personalizado';
    
    return $res->status($code)->json([
        'status_code' => $code,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

// 📥 Simular download de arquivo
$app->get('/download', function ($req, $res) {
    $filename = $req->get('file', 'sample.txt');
    $content = "Este é um arquivo de exemplo do PivotPHP\nGerado em: " . date('Y-m-d H:i:s');
    
    $res->header('Content-Type', 'application/octet-stream');
    $res->header('Content-Disposition', "attachment; filename=\"{$filename}\"");
    $res->header('Content-Length', strlen($content));
    
    return $res->send($content);
});

// 🔍 Query parameters avançados
$app->get('/search', function ($req, $res) {
    $query = $req->get('q', '');
    $page = (int) $req->get('page', 1);
    $limit = (int) $req->get('limit', 10);
    $sort = $req->get('sort', 'name');
    $order = $req->get('order', 'asc');
    
    // Simular resultados
    $totalResults = 250;
    $totalPages = ceil($totalResults / $limit);
    
    return $res->json([
        'search_params' => [
            'query' => $query,
            'page' => $page,
            'limit' => $limit,
            'sort' => $sort,
            'order' => $order
        ],
        'results' => [
            'total' => $totalResults,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'per_page' => $limit,
            'from' => ($page - 1) * $limit + 1,
            'to' => min($page * $limit, $totalResults)
        ],
        'example_url' => '/search?q=test&page=2&limit=20&sort=created_at&order=desc'
    ]);
});

$app->run();