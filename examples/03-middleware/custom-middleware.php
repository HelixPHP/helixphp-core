<?php

/**
 * 🔧 PivotPHP - Middleware Personalizados
 * 
 * Demonstra criação e uso de middleware customizados no PivotPHP
 * Middleware de logging, autenticação, validação e transformação
 * 
 * 🚀 Como executar:
 * php -S localhost:8000 examples/03-middleware/custom-middleware.php
 * 
 * 🧪 Como testar:
 * curl http://localhost:8000/
 * curl -X POST http://localhost:8000/api/users -H "Content-Type: application/json" -d '{"name":"João"}'
 * curl -H "Authorization: Bearer valid-token" http://localhost:8000/protected
 * curl -H "Accept: application/xml" http://localhost:8000/api/data
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use PivotPHP\Core\Core\Application;

$app = new Application();

// 📋 Página inicial
$app->get('/', function ($req, $res) {
    return $res->json([
        'title' => 'PivotPHP - Custom Middleware Examples',
        'description' => 'Demonstrações de middleware personalizados',
        'middleware_examples' => [
            'Request Logger' => 'Log de todas as requisições',
            'Response Timer' => 'Medição de tempo de resposta',
            'API Key Validator' => 'Validação de chave de API',
            'Content Negotiation' => 'Negociação de conteúdo (JSON/XML)',
            'Input Validator' => 'Validação de dados de entrada',
            'Request Transformer' => 'Transformação de dados da requisição',
            'Response Modifier' => 'Modificação de resposta antes do envio',
            'Error Handler' => 'Tratamento personalizado de erros'
        ],
        'test_endpoints' => [
            'GET /' => 'Esta página com logs',
            'POST /api/users' => 'Criação com validação',
            'GET /protected' => 'Rota protegida por API key',
            'GET /api/data' => 'Negociação de conteúdo',
            'POST /validate' => 'Validação de entrada',
            'GET /transform' => 'Transformação de dados',
            'GET /error-demo' => 'Demonstração de erro customizado'
        ]
    ]);
});

// ===============================================
// 📝 MIDDLEWARE: Request Logger
// ===============================================

$requestLogger = function ($req, $res, $next) {
    $startTime = microtime(true);
    $method = $req->method();
    $uri = $req->uri();
    $ip = $req->ip();
    $userAgent = $req->header('User-Agent') ?? 'Unknown';
    
    // Log da requisição
    error_log("🔍 [{$method}] {$uri} - IP: {$ip} - UA: " . substr($userAgent, 0, 50));
    
    // Adicionar dados de log ao request
    $req->logData = [
        'start_time' => $startTime,
        'method' => $method,
        'uri' => $uri,
        'ip' => $ip,
        'user_agent' => $userAgent
    ];
    
    // Continuar para próximo middleware
    $response = $next($req, $res);
    
    // Log pós-processamento
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    error_log("✅ [{$method}] {$uri} - {$duration}ms");
    
    return $response;
};

// ===============================================
// ⏱️ MIDDLEWARE: Response Timer
// ===============================================

$responseTimer = function ($req, $res, $next) {
    $startTime = microtime(true);
    
    // Executar próximo middleware
    $response = $next($req, $res);
    
    // Calcular tempo e adicionar header
    $endTime = microtime(true);
    $duration = round(($endTime - $startTime) * 1000, 2);
    
    $res->header('X-Response-Time', $duration . 'ms');
    $res->header('X-Processed-At', date('c'));
    
    return $response;
};

// ===============================================
// 🔐 MIDDLEWARE: API Key Validator
// ===============================================

$apiKeyValidator = function ($req, $res, $next) {
    $apiKey = $req->header('Authorization');
    
    // Verificar se API key está presente
    if (!$apiKey) {
        return $res->status(401)->json([
            'error' => 'API Key obrigatória',
            'required_header' => 'Authorization: Bearer <api-key>',
            'middleware' => 'ApiKeyValidator'
        ]);
    }
    
    // Extrair token
    if (!str_starts_with($apiKey, 'Bearer ')) {
        return $res->status(401)->json([
            'error' => 'Formato de API Key inválido',
            'expected_format' => 'Bearer <api-key>',
            'provided_format' => substr($apiKey, 0, 20) . '...',
            'middleware' => 'ApiKeyValidator'
        ]);
    }
    
    $token = substr($apiKey, 7);
    
    // Validar token (simulado)
    $validTokens = ['valid-token', 'admin-token', 'user-token'];
    
    if (!in_array($token, $validTokens)) {
        return $res->status(403)->json([
            'error' => 'API Key inválida',
            'provided_token' => $token,
            'valid_tokens_hint' => 'Use: valid-token, admin-token, ou user-token',
            'middleware' => 'ApiKeyValidator'
        ]);
    }
    
    // Adicionar informações do usuário ao request
    $userInfo = [
        'valid-token' => ['id' => 1, 'name' => 'Usuario Teste', 'role' => 'user'],
        'admin-token' => ['id' => 2, 'name' => 'Admin User', 'role' => 'admin'],
        'user-token' => ['id' => 3, 'name' => 'Regular User', 'role' => 'user']
    ];
    
    $req->authenticatedUser = $userInfo[$token];
    $req->apiToken = $token;
    
    return $next($req, $res);
};

// ===============================================
// 🔄 MIDDLEWARE: Content Negotiation
// ===============================================

$contentNegotiation = function ($req, $res, $next) {
    $acceptHeader = $req->header('Accept') ?? 'application/json';
    
    // Determinar formato preferido
    $preferredFormat = 'json'; // default
    
    if (strpos($acceptHeader, 'application/xml') !== false) {
        $preferredFormat = 'xml';
    } elseif (strpos($acceptHeader, 'text/csv') !== false) {
        $preferredFormat = 'csv';
    } elseif (strpos($acceptHeader, 'text/plain') !== false) {
        $preferredFormat = 'text';
    }
    
    // Adicionar informações ao request
    $req->preferredFormat = $preferredFormat;
    $req->acceptHeader = $acceptHeader;
    
    // Executar próximo middleware
    $response = $next($req, $res);
    
    // Verificar se resposta precisa ser transformada
    if (isset($req->responseData) && $preferredFormat !== 'json') {
        $data = $req->responseData;
        
        switch ($preferredFormat) {
            case 'xml':
                $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n<data>\n";
                foreach ($data as $key => $value) {
                    $xml .= "  <{$key}>" . htmlspecialchars($value) . "</{$key}>\n";
                }
                $xml .= '</data>';
                
                $res->header('Content-Type', 'application/xml');
                return $res->send($xml);
                
            case 'csv':
                $csv = implode(',', array_keys($data)) . "\n";
                $csv .= implode(',', array_values($data));
                
                $res->header('Content-Type', 'text/csv');
                return $res->send($csv);
                
            case 'text':
                $text = '';
                foreach ($data as $key => $value) {
                    $text .= "{$key}: {$value}\n";
                }
                
                $res->header('Content-Type', 'text/plain');
                return $res->send(trim($text));
        }
    }
    
    return $response;
};

// ===============================================
// ✅ MIDDLEWARE: Input Validator
// ===============================================

$inputValidator = function ($rules) {
    return function ($req, $res, $next) use ($rules) {
        $data = $req->getBodyAsStdClass();
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data->$field ?? null;
            
            // Required validation
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field][] = "Campo {$field} é obrigatório";
                continue;
            }
            
            if (!empty($value)) {
                // Type validation
                if (isset($rule['type'])) {
                    switch ($rule['type']) {
                        case 'string':
                            if (!is_string($value)) {
                                $errors[$field][] = "Campo {$field} deve ser string";
                            }
                            break;
                        case 'number':
                            if (!is_numeric($value)) {
                                $errors[$field][] = "Campo {$field} deve ser numérico";
                            }
                            break;
                        case 'email':
                            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                                $errors[$field][] = "Campo {$field} deve ser um email válido";
                            }
                            break;
                    }
                }
                
                // Length validation
                if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                    $errors[$field][] = "Campo {$field} deve ter pelo menos {$rule['min_length']} caracteres";
                }
                
                if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                    $errors[$field][] = "Campo {$field} deve ter no máximo {$rule['max_length']} caracteres";
                }
                
                // Pattern validation
                if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                    $errors[$field][] = "Campo {$field} não atende ao padrão exigido";
                }
            }
        }
        
        if (!empty($errors)) {
            return $res->status(422)->json([
                'error' => 'Dados inválidos',
                'validation_errors' => $errors,
                'middleware' => 'InputValidator',
                'rules_applied' => $rules
            ]);
        }
        
        // Adicionar dados validados ao request
        $req->validatedData = $data;
        
        return $next($req, $res);
    };
};

// ===============================================
// 🔄 MIDDLEWARE: Request Transformer
// ===============================================

$requestTransformer = function ($req, $res, $next) {
    $body = $req->getBodyAsStdClass();
    
    // Transformações automáticas
    if (isset($body->email)) {
        $body->email = strtolower(trim($body->email));
    }
    
    if (isset($body->name)) {
        $body->name = ucwords(strtolower(trim($body->name)));
    }
    
    if (isset($body->phone)) {
        // Limpar telefone: remover tudo exceto números
        $body->phone = preg_replace('/[^0-9]/', '', $body->phone);
    }
    
    // Adicionar campos automáticos
    $body->transformed_at = date('c');
    $body->ip_address = $req->ip();
    
    // Substituir dados no request
    $req->transformedData = $body;
    
    return $next($req, $res);
};

// ===============================================
// 📊 MIDDLEWARE: Response Modifier
// ===============================================

$responseModifier = function ($req, $res, $next) {
    // Executar próximo middleware
    $response = $next($req, $res);
    
    // Adicionar headers padrão
    $res->header('X-API-Version', '1.0');
    $res->header('X-Server', 'PivotPHP');
    $res->header('X-Request-ID', uniqid('req_', true));
    
    return $response;
};

// ===============================================
// 🚨 MIDDLEWARE: Error Handler
// ===============================================

$errorHandler = function ($req, $res, $next) {
    try {
        return $next($req, $res);
    } catch (Exception $e) {
        error_log("❌ Error: " . $e->getMessage());
        
        return $res->status(500)->json([
            'error' => 'Erro interno do servidor',
            'message' => $e->getMessage(),
            'timestamp' => date('c'),
            'request_id' => uniqid('err_', true),
            'middleware' => 'ErrorHandler'
        ]);
    }
};

// ===============================================
// APLICAR MIDDLEWARE GLOBAIS
// ===============================================

// Middleware aplicados a todas as rotas
$app->use($requestLogger);
$app->use($responseTimer);
$app->use($errorHandler);
$app->use($responseModifier);

// ===============================================
// ROTAS COM MIDDLEWARE ESPECÍFICOS
// ===============================================

// Rota protegida por API key
$app->get('/protected', $apiKeyValidator, function ($req, $res) {
    return $res->json([
        'message' => 'Acesso autorizado!',
        'authenticated_user' => $req->authenticatedUser,
        'api_token' => $req->apiToken,
        'middleware_applied' => ['requestLogger', 'responseTimer', 'apiKeyValidator']
    ]);
});

// Rota com negociação de conteúdo
$app->get('/api/data', $contentNegotiation, function ($req, $res) {
    $data = [
        'id' => 123,
        'name' => 'Produto Exemplo',
        'price' => '99.90',
        'category' => 'electronics'
    ];
    
    // Armazenar dados para possível transformação
    $req->responseData = $data;
    
    return $res->json([
        'data' => $data,
        'format_info' => [
            'preferred_format' => $req->preferredFormat,
            'accept_header' => $req->acceptHeader,
            'available_formats' => ['json', 'xml', 'csv', 'text']
        ]
    ]);
});

// Rota com validação de entrada
$app->post('/api/users', 
    $inputValidator([
        'name' => [
            'required' => true,
            'type' => 'string',
            'min_length' => 2,
            'max_length' => 50
        ],
        'email' => [
            'required' => true,
            'type' => 'email'
        ],
        'age' => [
            'type' => 'number',
            'min_value' => 18,
            'max_value' => 120
        ]
    ]), 
    function ($req, $res) {
        return $res->status(201)->json([
            'message' => 'Usuário criado com sucesso',
            'validated_data' => $req->validatedData,
            'middleware_applied' => ['inputValidator']
        ]);
    }
);

// Rota com transformação de dados
$app->post('/transform', $requestTransformer, function ($req, $res) {
    return $res->json([
        'message' => 'Dados transformados com sucesso',
        'original_data' => $req->getBodyAsStdClass(),
        'transformed_data' => $req->transformedData,
        'transformations_applied' => [
            'email' => 'lowercase + trim',
            'name' => 'title case + trim',
            'phone' => 'numbers only',
            'auto_fields' => ['transformed_at', 'ip_address']
        ]
    ]);
});

// Demonstração de erro para testar error handler
$app->get('/error-demo', function ($req, $res) {
    throw new Exception('Este é um erro de demonstração do middleware ErrorHandler');
});

// Middleware stack complexo
$app->post('/complex', 
    $apiKeyValidator,
    $contentNegotiation,
    $requestTransformer,
    $inputValidator([
        'title' => ['required' => true, 'min_length' => 5],
        'content' => ['required' => true, 'min_length' => 10]
    ]),
    function ($req, $res) {
        return $res->json([
            'message' => 'Processado por stack completo de middleware',
            'middleware_stack' => [
                'requestLogger',
                'responseTimer', 
                'errorHandler',
                'responseModifier',
                'apiKeyValidator',
                'contentNegotiation',
                'requestTransformer',
                'inputValidator'
            ],
            'authenticated_user' => $req->authenticatedUser,
            'validated_data' => $req->validatedData,
            'transformed_data' => $req->transformedData,
            'preferred_format' => $req->preferredFormat
        ]);
    }
);

$app->run();