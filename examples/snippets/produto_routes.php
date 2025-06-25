<?php

use Express\SRC\Controller\RouterInstance;

// Sub-router especializado para rotas de produto
$produtoRouter = new RouterInstance('/produto');
$produtoRouter->get(
    '/:sku',
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
        'tags' => ['Produto']
    ]
);
$produtoRouter->post(
    '',
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
        'tags' => ['Produto']
    ]
);

return $produtoRouter;
