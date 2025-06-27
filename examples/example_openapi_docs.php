<?php
/**
 * Exemplo de Documentação OpenAPI/Swagger - Express PHP
 *
 * Este exemplo demonstra como gerar documentação OpenAPI automática
 * para suas APIs usando o sistema nativo do Express PHP.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Express\ApiExpress;
use Express\Utils\OpenApiExporter;
use Express\Routing\Router;
use Express\Http\Request;
use Express\Http\Response;

// Criar aplicação
$app = new ApiExpress();

// ================================
// DEFINIR ROTAS COM DOCUMENTAÇÃO
// ================================

// Rota básica com documentação
$app->get('/', function(Request $req, Response $res) {
    $res->json([
        'message' => 'API Express PHP com documentação OpenAPI',
        'version' => '2.0',
        'docs' => '/docs',
        'api_spec' => '/docs/openapi.json'
    ]);
}, [
    'summary' => 'Informações da API',
    'description' => 'Retorna informações básicas sobre a API e links para documentação',
    'tags' => ['Sistema'],
    'responses' => [
        '200' => [
            'description' => 'Informações da API retornadas com sucesso',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'message' => ['type' => 'string'],
                            'version' => ['type' => 'string'],
                            'docs' => ['type' => 'string'],
                            'api_spec' => ['type' => 'string']
                        ]
                    ]
                ]
            ]
        ]
    ]
]);

// API de usuários com documentação completa
$app->get('/api/users', function(Request $req, Response $res) {
    // Simular busca de usuários com paginação
    $page = (int) ($req->query['page'] ?? 1);
    $limit = (int) ($req->query['limit'] ?? 10);

    $users = [
        ['id' => 1, 'name' => 'João Silva', 'email' => 'joao@example.com', 'status' => 'active'],
        ['id' => 2, 'name' => 'Maria Santos', 'email' => 'maria@example.com', 'status' => 'active'],
        ['id' => 3, 'name' => 'Pedro Costa', 'email' => 'pedro@example.com', 'status' => 'inactive']
    ];

    $res->json([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => count($users)
        ]
    ]);
}, [
    'summary' => 'Listar usuários',
    'description' => 'Retorna uma lista paginada de usuários cadastrados no sistema',
    'tags' => ['Usuários'],
    'queryParams' => [
        'page' => [
            'type' => 'integer',
            'description' => 'Número da página (padrão: 1)',
            'minimum' => 1
        ],
        'limit' => [
            'type' => 'integer',
            'description' => 'Itens por página (padrão: 10, máximo: 100)',
            'minimum' => 1,
            'maximum' => 100
        ],
        'status' => [
            'type' => 'string',
            'description' => 'Filtrar por status do usuário',
            'enum' => ['active', 'inactive', 'pending']
        ]
    ],
    'responses' => [
        '200' => [
            'description' => 'Lista de usuários retornada com sucesso',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'data' => [
                                'type' => 'array',
                                'items' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => ['type' => 'integer'],
                                        'name' => ['type' => 'string'],
                                        'email' => ['type' => 'string', 'format' => 'email'],
                                        'status' => ['type' => 'string', 'enum' => ['active', 'inactive', 'pending']]
                                    ]
                                ]
                            ],
                            'pagination' => [
                                'type' => 'object',
                                'properties' => [
                                    'page' => ['type' => 'integer'],
                                    'limit' => ['type' => 'integer'],
                                    'total' => ['type' => 'integer']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '400' => ['description' => 'Parâmetros inválidos']
    ]
]);

// Buscar usuário por ID
$app->get('/api/users/:id', function(Request $req, Response $res) {
    $id = (int) $req->getParam('id');

    if ($id <= 0) {
        $res->status(400)->json(['error' => 'ID deve ser um número positivo']);
        return;
    }

    // Simular busca no banco
    $user = [
        'id' => $id,
        'name' => 'Usuário ' . $id,
        'email' => "user{$id}@example.com",
        'status' => 'active',
        'created_at' => '2024-01-01T00:00:00Z'
    ];

    $res->json(['success' => true, 'data' => $user]);
}, [
    'summary' => 'Buscar usuário por ID',
    'description' => 'Retorna os dados detalhados de um usuário específico pelo seu ID',
    'tags' => ['Usuários'],
    'parameters' => [
        'id' => [
            'type' => 'integer',
            'description' => 'ID único do usuário',
            'required' => true,
            'minimum' => 1
        ]
    ],
    'responses' => [
        '200' => [
            'description' => 'Usuário encontrado com sucesso',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'email' => ['type' => 'string', 'format' => 'email'],
                                    'status' => ['type' => 'string'],
                                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '400' => ['description' => 'ID inválido'],
        '404' => ['description' => 'Usuário não encontrado']
    ]
]);

// Criar novo usuário
$app->post('/api/users', function(Request $req, Response $res) {
    $userData = $req->body;

    // Simular validação e criação
    $newUser = [
        'id' => rand(100, 999),
        'name' => $userData['name'] ?? '',
        'email' => $userData['email'] ?? '',
        'status' => 'active',
        'created_at' => date('c')
    ];

    $res->status(201)->json([
        'success' => true,
        'message' => 'Usuário criado com sucesso',
        'data' => $newUser
    ]);
}, [
    'summary' => 'Criar novo usuário',
    'description' => 'Cria um novo usuário no sistema com os dados fornecidos',
    'tags' => ['Usuários'],
    'requestBody' => [
        'required' => true,
        'description' => 'Dados do usuário a ser criado',
        'content' => [
            'application/json' => [
                'schema' => [
                    'type' => 'object',
                    'required' => ['name', 'email'],
                    'properties' => [
                        'name' => [
                            'type' => 'string',
                            'minLength' => 2,
                            'maxLength' => 100,
                            'description' => 'Nome completo do usuário'
                        ],
                        'email' => [
                            'type' => 'string',
                            'format' => 'email',
                            'description' => 'Email único do usuário'
                        ],
                        'phone' => [
                            'type' => 'string',
                            'pattern' => '^\+?[1-9]\d{1,14}$',
                            'description' => 'Telefone do usuário (opcional)'
                        ]
                    ]
                ]
            ]
        ]
    ],
    'responses' => [
        '201' => [
            'description' => 'Usuário criado com sucesso',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                            'data' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'email' => ['type' => 'string'],
                                    'status' => ['type' => 'string'],
                                    'created_at' => ['type' => 'string', 'format' => 'date-time']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '400' => ['description' => 'Dados inválidos ou faltando'],
        '409' => ['description' => 'Email já existe no sistema']
    ]
]);

// ================================
// ENDPOINTS DE DOCUMENTAÇÃO
// ================================

// Endpoint para retornar a especificação OpenAPI
$app->get('/docs/openapi.json', function(Request $req, Response $res) {
    // Gerar documentação OpenAPI a partir das rotas
    $docs = OpenApiExporter::export(Router::class, 'http://localhost:8080');

    // Personalizar informações da API
    $docs['info'] = [
        'title' => 'Express PHP API',
        'description' => 'API de exemplo demonstrando documentação OpenAPI automática',
        'version' => '1.0.0',
        'contact' => [
            'name' => 'Suporte da API',
            'email' => 'suporte@example.com'
        ]
    ];

    // Adicionar esquemas de segurança (opcional)
    $docs['components']['securitySchemes'] = [
        'bearerAuth' => [
            'type' => 'http',
            'scheme' => 'bearer',
            'bearerFormat' => 'JWT',
            'description' => 'Token JWT para autenticação'
        ],
        'apiKeyAuth' => [
            'type' => 'apiKey',
            'in' => 'header',
            'name' => 'X-API-Key',
            'description' => 'Chave da API para autenticação'
        ]
    ];

    $res->json($docs);
});

// Interface Swagger UI para visualizar a documentação
$app->get('/docs', function(Request $req, Response $res) {
    $html = '<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentação da API - Express PHP</title>
    <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui.css" />
    <style>
        .swagger-ui .topbar { display: none; }
        .swagger-ui .info { margin: 20px 0; }
        .swagger-ui .info .title { color: #3b82f6; }
    </style>
</head>
<body>
    <div id="swagger-ui"></div>
    <script src="https://unpkg.com/swagger-ui-dist@5.10.3/swagger-ui-bundle.js"></script>
    <script>
        window.onload = function() {
            SwaggerUIBundle({
                url: "/docs/openapi.json",
                dom_id: "#swagger-ui",
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIBundle.presets.standalone
                ],
                plugins: [
                    SwaggerUIBundle.plugins.DownloadUrl
                ],
                layout: "StandaloneLayout",
                tryItOutEnabled: true,
                filter: true,
                supportedSubmitMethods: ["get", "post", "put", "delete", "patch"]
            });
        };
    </script>
</body>
</html>';

    $res->send($html);
});

// ================================
// EXECUTAR APLICAÇÃO
// ================================

echo "\n🚀 Iniciando servidor Express PHP com documentação OpenAPI...\n";
echo "📚 Documentação disponível em: http://localhost:8080/docs\n";
echo "📋 Especificação OpenAPI: http://localhost:8080/docs/openapi.json\n";
echo "🔗 API de usuários: http://localhost:8080/api/users\n\n";

$app->run();
