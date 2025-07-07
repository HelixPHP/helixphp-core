<?php

use PivotPHP\Core\Routing\RouterInstance;

// Sub-router especializado para upload de arquivos
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

return $uploadRouter;
