<?php
// AVISO: Este arquivo integra todos os sub-routers e middlewares do Express PHP para demonstração completa.
// Para estudar ou desenvolver, prefira os exemplos menores em examples/exemplo_*.php.
// Cada exemplo modulariza um contexto (usuário, produto, upload, admin, blog) para facilitar o aprendizado e evitar execução desnecessária.

namespace Express\Test;

// Configuração para URLs amigáveis sem .php
// Garante que PATH_INFO seja definido corretamente
if (!isset($_SERVER['PATH_INFO']) && isset($_SERVER['REQUEST_URI'])) {
    $requestUri = $_SERVER['REQUEST_URI'];
    $scriptName = $_SERVER['SCRIPT_NAME'];

    // Remove a extensão .php do SCRIPT_NAME se presente
    $scriptNameClean = preg_replace('/\.php$/', '', $scriptName);

    // Extrai o PATH_INFO da REQUEST_URI
    if (strpos($requestUri, $scriptNameClean) === 0) {
        $pathInfo = substr($requestUri, strlen($scriptNameClean));
        // Remove query string se presente
        $pathInfo = strtok($pathInfo, '?');
        $_SERVER['PATH_INFO'] = $pathInfo ?: '/';
    }
}

$path = __DIR__;
$path = explode(DIRECTORY_SEPARATOR, $path);
$path = array_slice($path, 0, count($path) - 1);
$path = implode(DIRECTORY_SEPARATOR, $path);
require_once $path . '/vendor/autoload.php';
// require_once 'layout.php';

// use Express\Controller\App;
use Express\ApiExpress;
use Express\Controller\Router;
use Express\Controller\RouterInstance;
use Express\Middlewares\Core\CorsMiddleware;
use Express\Middlewares\Core\OpenApiDocsMiddleware;
use Express\Middlewares\Core\ErrorHandlerMiddleware;

$baseUrl = "https://{$_SERVER['SSL_TLS_SNI']}{$_SERVER['SCRIPT_NAME']}";
substr($baseUrl, -1) === '/' && $baseUrl = substr($baseUrl, 0, -1);
substr($baseUrl, -4) === '.php' && $baseUrl = substr($baseUrl, 0, -4);

$app = new ApiExpress($baseUrl);

// Middleware global de tratamento de erros (deve ser o primeiro)
$app->use(new ErrorHandlerMiddleware());

// Middleware padrão de CORS
$app->use(new CorsMiddleware());

// Exemplo de middleware global
$app->use(function ($request, $response, $next) {
    // Exemplo: logar método e caminho
    // error_log("[Middleware] " . $request->method . ' ' . $request->pathCallable);
    // Exemplo: adicionar cabeçalho customizado
    $response->header('X-Powered-By', 'ExpressPHP');
    $next();
});

// ===================== ROTAS DE USUÁRIO EM SUB-ROUTER =====================
$userRouter = new RouterInstance('/user');
// Middleware de grupo para todas as rotas que começam com /user
$userRouter->use(function ($request, $response, $next) {
    $response->header('X-Group', 'user');
    $next();
});
$userRouter->get(
    '/:id',
    function ($request, $response, $next) {
        // Middleware de rota: exemplo de autenticação
        if (!$request->headers->hasHeader('authorization')) {
            $response->status(401)->json([
                'error' => 'Unauthorized',
                'message' => 'Authorization header is missing or empty',
                'headers' => $request->headers->authorization,
                'validation' => true
            ]);
            return;
        }
        $next();
    },
    function ($request, $response, $next) {
        // aqui quero adicionar uma informação no request e enviar para o próximo middleware
        $request->params->rotina = 'default';
        $next();
    },
    function ($request, $response) {
        $response->status(200)->json([
            'message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}, Rotina: {$request->params->rotina}",
        ]);
    },
    ['tags' => ['User']]
);
$userRouter->get('/:id/:rotina', function ($request, $response) {
    $response->status(200)->json(['message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}, Rotina: {$request->params->rotina}"]);
}, ['tags' => ['User']]);
$userRouter->post('/:id', function ($request, $response) {
    $response->status(200)->json(['message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}"]);
}, ['tags' => ['User']]);
$userRouter->post('/:id/:rotina', function ($request, $response) {
    $response->status(200)->json(['message' => "{$request->method}: User-Agent: {$request->headers->userAgent}, User ID: {$request->params->id}, Rotina: {$request->params->rotina}"]);
}, ['tags' => ['User']]);
// Exemplo de método HTTP customizado
Router::addHttpMethod('CUSTOM');
$userRouter->custom('/custom/:id', function ($request, $response) {
    $response->status(200)->json([
        'message' => 'Método CUSTOM executado',
        'id' => $request->params->id
    ]);
}, ['tags' => ['User']]);
// Exemplo de rota com metadados para OpenAPI
$userRouter->get(
    '/produto/:sku',
    function ($request, $response) {
        $response->json([
            'sku' => $request->params->sku,
            'nome' => 'Produto Exemplo'
        ]);
    },
    [
        'summary' => 'Consulta de produto por SKU',
        'description' => 'Retorna informações detalhadas de um produto a partir do SKU informado.',
        'parameters' => [
            'sku' => [
                'in' => 'path',
                'type' => 'string',
                'description' => 'SKU do produto',
                'required' => true
            ],
            'token' => [
                'in' => 'query',
                'type' => 'string',
                'description' => 'Token de autenticação',
                'required' => false
            ]
        ],
        'responses' => [
            '200' => [
                'description' => 'Produto encontrado',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'sku' => ['type' => 'string'],
                                'nome' => ['type' => 'string']
                            ]
                        ],
                        'examples' => [
                            'exemplo' => [
                                'summary' => 'Exemplo de resposta',
                                'value' => [
                                    'sku' => 'ABC123',
                                    'nome' => 'Produto Exemplo'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '404' => [
                'description' => 'Produto não encontrado'
            ]
        ],
        'examples' => [
            'request' => [
                'summary' => 'Exemplo de requisição',
                'value' => [
                    'sku' => 'ABC123',
                    'token' => 'seu-token-aqui'
                ]
            ]
        ],
        'tags' => ['User']
    ]
);
// Exemplo de rota POST com metadados
$userRouter->post(
    '/produto',
    function ($request, $response) {
        $response->status(201)->json([
            'message' => 'Produto criado',
            'body' => $request->body
        ]);
    },
    [
        'summary' => 'Criação de produto',
        'description' => 'Cria um novo produto com os dados enviados no corpo da requisição.',
        'parameters' => [
            'body' => [
                'in' => 'body',
                'type' => 'object',
                'description' => 'Dados do produto',
                'required' => true
            ]
        ],
        'requestBody' => [
            'required' => true,
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'sku' => ['type' => 'string'],
                            'nome' => ['type' => 'string']
                        ],
                        'required' => ['sku', 'nome']
                    ],
                    'examples' => [
                        'exemplo' => [
                            'summary' => 'Exemplo de corpo de requisição',
                            'value' => [
                                'sku' => 'ABC123',
                                'nome' => 'Produto Exemplo'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'responses' => [
            '201' => [
                'description' => 'Produto criado com sucesso',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'message' => ['type' => 'string'],
                                'body' => ['type' => 'object']
                            ]
                        ],
                        'examples' => [
                            'exemplo' => [
                                'summary' => 'Exemplo de resposta',
                                'value' => [
                                    'message' => 'Produto criado',
                                    'body' => [
                                        'sku' => 'ABC123',
                                        'nome' => 'Produto Exemplo'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '400' => [
                'description' => 'Dados inválidos'
            ]
        ],
        'tags' => ['User']
    ]
);
// Exemplo upload com exemplos
$uploadRouter = new RouterInstance('/upload');
$uploadRouter->post(
    '',
    function ($request, $response) {
        if (empty($request->files)) {
            $response->status(400)->json(['error' => 'Nenhum arquivo enviado']);
            return;
        }
        $arquivos = [];
        foreach ($request->files as $file) {
            $arquivos[] = [
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => $file['size']
            ];
        }
        $response->json(['arquivos' => $arquivos]);
    },
    [
        'summary' => 'Upload de arquivos',
        'description' => 'Recebe arquivos enviados via multipart/form-data.',
        'requestBody' => [
            'required' => true,
            'content' => [
                'multipart/form-data' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'file' => [
                                'type' => 'string',
                                'format' => 'binary',
                                'description' => 'Arquivo para upload'
                            ]
                        ],
                        'required' => ['file']
                    ],
                    'examples' => [
                        'exemplo' => [
                            'summary' => 'Exemplo de upload',
                            'value' => [
                                'file' => '(arquivo.bin)'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'responses' => [
            '200' => [
                'description' => 'Upload realizado com sucesso',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'arquivos' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'type' => ['type' => 'string'],
                                            'size' => ['type' => 'integer']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'examples' => [
                            'exemplo' => [
                                'summary' => 'Exemplo de resposta',
                                'value' => [
                                    'arquivos' => [
                                        [
                                            'name' => 'foto.png',
                                            'type' => 'image/png',
                                            'size' => 123456
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '400' => ['description' => 'Nenhum arquivo enviado']
        ],
        'tags' => ['Upload']
    ]
);

// ===================== FIM ROTAS DE USUÁRIO =====================

// Exemplo de sub-router modular para rotas de admin
// (Importação já feita no topo: use Express\SRC\Controller\RouterInstance;)

$adminRouter = new RouterInstance('/admin');
$adminRouter->use(function ($request, $response, $next) {
    $response->header('X-Admin', 'true');
    $next();
});
$adminRouter->get('/dashboard', function ($request, $response) {
    $response->json(['message' => 'Bem-vindo ao painel admin!']);
}, ['tags' => ['Admin']]);
$adminRouter->get(
    '/logs',
    function ($request, $response, $next) {
        if (!$request->headers->hasHeader('authorization')) {
            $response->status(401)->json(['error' => 'Acesso negado ao log']);
            return;
        }
        $next();
    },
    function ($request, $response) {
        $response->json(['logs' => ['log1', 'log2']]);
    },
    ['tags' => ['Admin']]
);
// Sub-router para uploads
$uploadRouter = new RouterInstance('/upload');
$uploadRouter->post(
    '',
    function ($request, $response) {
        if (empty($request->files)) {
            $response->status(400)->json(['error' => 'Nenhum arquivo enviado']);
            return;
        }
        $arquivos = [];
        foreach ($request->files as $file) {
            $arquivos[] = [
                'name' => $file['name'],
                'type' => $file['type'],
                'size' => $file['size']
            ];
        }
        $response->json(['arquivos' => $arquivos]);
    },
    [
        'summary' => 'Upload de arquivos',
        'description' => 'Recebe arquivos enviados via multipart/form-data.',
        'requestBody' => [
            'required' => true,
            'content' => [
                'multipart/form-data' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'file' => [
                                'type' => 'string',
                                'format' => 'binary',
                                'description' => 'Arquivo para upload'
                            ]
                        ],
                        'required' => ['file']
                    ],
                    'examples' => [
                        'exemplo' => [
                            'summary' => 'Exemplo de upload',
                            'value' => [
                                'file' => '(arquivo.bin)'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'responses' => [
            '200' => [
                'description' => 'Upload realizado com sucesso',
                'content' => [
                    'application/json' => [
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'arquivos' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'type' => ['type' => 'string'],
                                            'size' => ['type' => 'integer']
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'examples' => [
                            'exemplo' => [
                                'summary' => 'Exemplo de resposta',
                                'value' => [
                                    'arquivos' => [
                                        [
                                            'name' => 'foto.png',
                                            'type' => 'image/png',
                                            'size' => 123456
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            '400' => ['description' => 'Nenhum arquivo enviado']
        ],
        'tags' => ['Upload']
    ]
);
// Sub-router para blog
$blogRouter = new RouterInstance('/blog');
$blogRouter->get('/posts', function ($request, $response) {
    $response->json(['area' => 'blog', 'posts' => ['Post 1', 'Post 2']]);
}, ['tags' => ['Blog']]);
$blogRouter->post('/posts', function ($request, $response) {
    $response->status(201)->json(['message' => 'Novo post criado', 'body' => $request->body]);
}, ['tags' => ['Blog']]);

// Integração dos sub-routers no app principal
$app->use($userRouter);
$app->use($adminRouter);
$app->use($uploadRouter);
$app->use($blogRouter);

// Ativa documentação automática padrão de forma correta
new OpenApiDocsMiddleware($app, [
    Router::class,
    $userRouter,
    $adminRouter,
    $uploadRouter,
    $blogRouter
]);

// Rota para servir a documentação OpenAPI na raiz
// $app->get('/docs/openapi.json', function (
//     $request, $response
// ) use ($userRouter, $adminRouter, $uploadRouter, $blogRouter) {
//     try {
//         // Diagnóstico: mostrar rotas agregadas
//         $rotas = [
//             'Router' => is_string(Router::class) && method_exists(Router::class, 'getRoutes') ? Router::getRoutes() : [],
//             'userRouter' => method_exists($userRouter, 'getRoutes') ? $userRouter->getRoutes() : [],
//             'adminRouter' => method_exists($adminRouter, 'getRoutes') ? $adminRouter->getRoutes() : [],
//             'uploadRouter' => method_exists($uploadRouter, 'getRoutes') ? $uploadRouter->getRoutes() : [],
//             'blogRouter' => method_exists($blogRouter, 'getRoutes') ? $blogRouter->getRoutes() : [],
//         ];
//         if (empty($rotas['Router']) && empty($rotas['userRouter']) && empty($rotas['adminRouter']) && empty($rotas['uploadRouter']) && empty($rotas['blogRouter'])) {
//             header('Content-Type: text/plain', true, 500);
//             echo "[ExpressPHP] Nenhuma rota registrada nos routers.\n";
//             var_dump($rotas);
//             exit;
//         }
//         $openapi = OpenApiExporter::export([
//             Router::class,
//             $userRouter,
//             $adminRouter,
//             $uploadRouter,
//             $blogRouter
//         ]);
//         if (!$openapi || !is_array($openapi)) {
//             header('Content-Type: text/plain', true, 500);
//             echo "[ExpressPHP] Erro: OpenApiExporter não retornou um array válido.\n";
//             var_dump($openapi);
//             exit;
//         }
//         header('Content-Type: application/json; charset=utf-8');
//         echo json_encode($openapi, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
//         exit;
//     } catch (\Throwable $e) {
//         $html = '<h2>Erro ao gerar documentação OpenAPI</h2>';
//         $html .= '<pre>' . htmlspecialchars($e->getMessage() . "\n" . $e->getTraceAsString()) . '</pre>';
//         header('Content-Type: text/html', true, 500);
//         echo $html;
//         exit;
//     }
// });
// // Rota para servir a documentação Swagger UI em /docs/index
// $app->get('/docs/index', function($request, $response) {
//     header('Content-Type: text/html; charset=utf-8');
//     echo '<!DOCTYPE html>';
//     echo '<html lang="pt-br"><head><meta charset="UTF-8"><title>Documentação da API - Express PHP</title>';
//     echo '<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist/swagger-ui.css">';
//     echo '<style>html,body{height:100%;margin:0;}#swagger-ui{height:100vh;}</style>';
//     echo '</head><body><div id="swagger-ui"></div>';
//     echo '<script src="https://unpkg.com/swagger-ui-dist/swagger-ui-bundle.js"></script>';
//     echo '<script>window.onload=function(){SwaggerUIBundle({url:"/docs/openapi.json",dom_id:"#swagger-ui",presets:[SwaggerUIBundle.presets.apis,SwaggerUIBundle.SwaggerUIStandalonePreset],layout:"BaseLayout",docExpansion:"list",deepLinking:true,filter:true,showExtensions:true,showCommonExtensions:true});};</script>';
//     echo '</body></html>';
//     exit;
// });
$app->run();
