# Express PHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](https://phpstan.org/)
[![GitHub Issues](https://img.shields.io/github/issues/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/issues)
[![GitHub Stars](https://img.shields.io/github/stars/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/stargazers)

**Express PHP** é um microframework leve, rápido e seguro inspirado no Express.js para construir aplicações web modernas e APIs em PHP com otimizações integradas e sistema nativo de autenticação.

> ⚡ **Otimizado**: Cache integrado, roteamento por grupos, pipeline de middlewares otimizado e CORS ultra-rápido!

## 🚀 Início Rápido

### Instalação

```bash
composer require cafernandes/express-php
```

### Exemplo Básico

```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Middleware\Security\SecurityMiddleware;
use Express\Middleware\Security\CorsMiddleware;

$app = new ApiExpress();

// Aplicar middlewares de segurança
$app->use(new SecurityMiddleware());
$app->use(new CorsMiddleware());

// Rota básica
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Olá Express PHP!']);
});

// Rota protegida com autenticação
$app->post('/api/users', function($req, $res) {
    // Dados automaticamente sanitizados pelo middleware de segurança
    $userData = $req->body;
    $res->json(['message' => 'Usuário criado', 'data' => $userData]);
});

$app->run();
```

## ✨ Principais Recursos

- 🔐 **Autenticação Multi-método**: JWT, Basic Auth, Bearer Token, API Key
- 🛡️ **Segurança Avançada**: CSRF, XSS, Rate Limiting, Headers de Segurança
- 📡 **Streaming**: Suporte completo para streaming de dados, SSE e arquivos grandes
- 📚 **Documentação OpenAPI/Swagger**: Geração automática de documentação
- 🎯 **Middlewares Modulares**: Sistema flexível de middlewares
- ⚡ **Performance**: Otimizado para alta performance
- 🧪 **Testado**: 186+ testes unitários e 100% de cobertura de código
- 📊 **Análise Estática**: PHPStan Level 8 compliance

## 📖 Documentação

- **[🚀 Guia de Início](docs/guides/starter/README.md)** - Comece aqui!
- **[📚 Documentação Completa](docs/README.md)** - Documentação detalhada
- **[🔐 Sistema de Autenticação](docs/pt-br/AUTH_MIDDLEWARE.md)** - Guia de autenticação
- **[📡 Streaming de Dados](docs/pt-br/STREAMING.md)** - Streaming e Server-Sent Events
- **[🛡️ Middlewares de Segurança](docs/guides/SECURITY_IMPLEMENTATION.md)** - Segurança
- **[📝 Exemplos Práticos](examples/)** - Exemplos prontos para usar

## 🎯 Exemplos de Aprendizado

O framework inclui exemplos práticos e funcionais para facilitar o aprendizado:

- **[⭐ Básico](examples/example_basic.php)** - API REST básica e conceitos fundamentais
- **[🔐 Autenticação Completa](examples/example_auth.php)** - Sistema completo de autenticação
- **[🔑 Autenticação Simples](examples/example_auth_simple.php)** - JWT básico e controle de acesso
- **[🛡️ Middlewares](examples/example_middleware.php)** - CORS, rate limiting e validação
- **[� Documentação OpenAPI](examples/example_openapi_docs.php)** - Swagger UI automático e especificação OpenAPI
- **[�🚀 App Completo](examples/example_complete_optimizations.php)** - Aplicação completa com todos os recursos

## 🛡️ Sistema de Autenticação

```php
// Autenticação JWT
$app->use(AuthMiddleware::jwt('sua_chave_secreta'));

// Múltiplos métodos de autenticação
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => 'sua_chave_jwt',
    'basicAuthCallback' => 'validarUsuario',
    'apiKeyCallback' => 'validarApiKey'
]));

// Acessar dados do usuário autenticado
$app->get('/profile', function($req, $res) {
    $user = $req->user; // dados do usuário autenticado
    $method = $req->auth['method']; // método de auth usado
    $res->json(['user' => $user, 'auth_method' => $method]);
});
```

## 📡 Streaming de Dados

O Express-PHP oferece suporte completo para streaming de dados em tempo real:

```php
// Streaming de texto simples
$app->get('/stream/text', function($req, $res) {
    $res->startStream('text/plain; charset=utf-8');

    for ($i = 1; $i <= 10; $i++) {
        $res->write("Chunk {$i}\n");
        sleep(1); // Simula processamento
    }

    $res->endStream();
});

// Server-Sent Events (SSE)
$app->get('/events', function($req, $res) {
    $res->sendEvent('Conexão estabelecida', 'connect');

    for ($i = 1; $i <= 10; $i++) {
        $data = ['counter' => $i, 'timestamp' => time()];
        $res->sendEvent($data, 'update', (string)$i);
        sleep(1);
    }
});

// Streaming de arquivos grandes
$app->get('/download/:file', function($req, $res) {
    $filePath = "/path/to/{$req->params['file']}";

    $headers = [
        'Content-Disposition' => 'attachment; filename="' . basename($filePath) . '"'
    ];

    $res->streamFile($filePath, $headers);
});

// Streaming de dados JSON
$app->get('/data/export', function($req, $res) {
    $res->startStream('application/json');
    $res->write('[');

    for ($i = 1; $i <= 1000; $i++) {
        if ($i > 1) $res->write(',');
        $res->writeJson(['id' => $i, 'data' => "Item {$i}"]);
    }

    $res->write(']');
    $res->endStream();
});
```

### Recursos de Streaming

- **Streaming de Texto**: Para logs e dados em tempo real
- **Server-Sent Events**: Para dashboards e notificações
- **Streaming de Arquivos**: Para downloads de arquivos grandes
- **Streaming de JSON**: Para exports e APIs de dados
- **Buffer Customizável**: Controle fino sobre performance
- **Heartbeat**: Manutenção de conexões SSE ativas

## 📚 Documentação OpenAPI/Swagger Nativa

O Express PHP possui um sistema nativo para gerar documentação OpenAPI 3.0 (Swagger) automaticamente das suas rotas. A documentação é criada a partir dos metadados definidos nas rotas.

### 🚀 Como Ativar

```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Utils\OpenApiExporter;
use Express\Routing\Router;

$app = new ApiExpress();

// Definir rotas com metadados para documentação
$app->get('/api/users', function($req, $res) {
    $res->json(['users' => []]);
}, [
    'summary' => 'Listar usuários',
    'description' => 'Retorna uma lista de todos os usuários cadastrados',
    'tags' => ['Usuários'],
    'responses' => [
        '200' => [
            'description' => 'Lista de usuários retornada com sucesso',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'users' => ['type' => 'array']
                        ]
                    ]
                ]
            ]
        ]
    ]
]);

$app->get('/api/users/:id', function($req, $res) {
    $id = $req->getParam('id');
    $res->json(['user' => ['id' => $id]]);
}, [
    'summary' => 'Buscar usuário por ID',
    'description' => 'Retorna os dados de um usuário específico',
    'tags' => ['Usuários'],
    'parameters' => [
        'id' => [
            'type' => 'integer',
            'description' => 'ID único do usuário',
            'required' => true
        ]
    ],
    'responses' => [
        '200' => [
            'description' => 'Usuário encontrado',
            'content' => [
                'application/json' => [
                    'schema' => [
                        'type' => 'object',
                        'properties' => [
                            'user' => [
                                'type' => 'object',
                                'properties' => [
                                    'id' => ['type' => 'integer'],
                                    'name' => ['type' => 'string'],
                                    'email' => ['type' => 'string']
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],
        '404' => ['description' => 'Usuário não encontrado']
    ]
]);

// Criar endpoint para servir a documentação
$app->get('/docs/openapi.json', function($req, $res) {
    $docs = OpenApiExporter::export(Router::class, 'https://api.example.com');
    $res->json($docs);
});

// Opcional: Servir interface Swagger UI
$app->get('/docs', function($req, $res) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>API Documentation</title>
        <link rel="stylesheet" type="text/css" href="https://unpkg.com/swagger-ui-dist@3.25.0/swagger-ui.css" />
    </head>
    <body>
        <div id="swagger-ui"></div>
        <script src="https://unpkg.com/swagger-ui-dist@3.25.0/swagger-ui-bundle.js"></script>
        <script>
        SwaggerUIBundle({
            url: "/docs/openapi.json",
            dom_id: "#swagger-ui",
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIBundle.presets.standalone
            ]
        });
        </script>
    </body>
    </html>';

    $res->send($html);
});

$app->run();
```

### 📋 Metadados Suportados

| Campo | Descrição | Exemplo |
|-------|-----------|---------|
| `summary` | Resumo da operação | `'Criar usuário'` |
| `description` | Descrição detalhada | `'Cria um novo usuário no sistema'` |
| `tags` | Grupos/categorias | `['Usuários', 'API v1']` |
| `parameters` | Parâmetros da rota | `['id' => ['type' => 'integer']]` |
| `queryParams` | Parâmetros de consulta | `['limit' => ['type' => 'integer']]` |
| `responses` | Respostas possíveis | `['200' => ['description' => 'OK']]` |
| `security` | Requisitos de autenticação | `[['bearerAuth' => []]]` |

### 🎯 Exemplo Avançado com Validação

```php
// POST com validação e documentação completa
$app->post('/api/users', function($req, $res) {
    $userData = $req->body;
    // Lógica de criação do usuário
    $res->status(201)->json(['message' => 'Usuário criado', 'id' => 123]);
}, [
    'summary' => 'Criar novo usuário',
    'description' => 'Cria um novo usuário no sistema com validação completa',
    'tags' => ['Usuários'],
    'requestBody' => [
        'required' => true,
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
                        'age' => [
                            'type' => 'integer',
                            'minimum' => 18,
                            'maximum' => 120,
                            'description' => 'Idade do usuário (opcional)'
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
                            'message' => ['type' => 'string'],
                            'id' => ['type' => 'integer']
                        ]
                    ]
                ]
            ]
        ],
        '400' => ['description' => 'Dados inválidos'],
        '409' => ['description' => 'Email já existe']
    ],
    'security' => [['bearerAuth' => []]]
]);
```

### 🔧 Configuração de Segurança

```php
// Adicionar esquemas de segurança à documentação
$docs = OpenApiExporter::export(Router::class, 'https://api.example.com');

// Adicionar definições de segurança
$docs['components']['securitySchemes'] = [
    'bearerAuth' => [
        'type' => 'http',
        'scheme' => 'bearer',
        'bearerFormat' => 'JWT'
    ],
    'apiKeyAuth' => [
        'type' => 'apiKey',
        'in' => 'header',
        'name' => 'X-API-Key'
    ]
];

// Aplicar segurança globalmente
$docs['security'] = [
    ['bearerAuth' => []],
    ['apiKeyAuth' => []]
];
```

### 💡 Dicas de Uso

1. **Acesse a documentação**: Vá para `/docs` para ver a interface Swagger UI
2. **JSON da API**: Endpoint `/docs/openapi.json` retorna a especificação OpenAPI
3. **Organize por tags**: Use tags para agrupar endpoints relacionados
4. **Documente erros**: Sempre inclua respostas de erro comuns (400, 401, 404, 500)
5. **Valide dados**: Use os schemas para documentar e validar entrada/saída
6. **Teste direto**: A interface Swagger permite testar endpoints diretamente

## ⚡ Performance & Benchmarks

O Express PHP foi projetado para máxima performance. Execute nossos benchmarks para ver os resultados:

```bash
# Benchmark rápido (100 iterações)
./benchmarks/run_benchmark.sh -q

# Benchmark completo (1000 iterações)
./benchmarks/run_benchmark.sh

# Benchmark extensivo (10000 iterações)
./benchmarks/run_benchmark.sh -f

# Todos os benchmarks + relatório abrangente
./benchmarks/run_benchmark.sh -a
```

### 🎯 Resultados de Performance (Última Atualização - PHP 8.1)

| Componente | Ops/Segundo | Tempo Médio | Grade |
|------------|-------------|-------------|-------|
| **CORS Headers Processing** | **45.6M** | **0.02 μs** | 🏆 |
| **Response Object Creation** | **20.9M** | **0.05 μs** | 🏆 |
| **JSON Encode (Small)** | **11.2M** | **0.09 μs** | 🥇 |
| **XSS Protection Logic** | **4.4M** | **0.23 μs** | 🥇 |
| **Route Pattern Matching** | **2.0M** | **0.49 μs** | 🥈 |
| **Middleware Execution** | **1.4M** | **0.74 μs** | 🥈 |
| **App Initialization** | **451K** | **2.22 μs** | 🥉 |

### ⚡ Características de Performance

- **Ultra Performance**: CORS com 45M+ operações/segundo
- **Baixo Overhead**: Apenas **1.36 KB** de memória por instância
- **Cache Inteligente**: Hit ratio de **98%** para grupos de rotas
- **Escalabilidade**: Performance linear até 50K+ operações
- **Memory Efficient**: Sistema de cache de apenas **2KB** total

📊 **[Ver Relatório Abrangente](benchmarks/reports/COMPREHENSIVE_PERFORMANCE_SUMMARY.md)** | 🛠️ **[Executar Benchmarks](benchmarks/README.md)**

## 📚 Documentação

### 🚀 **Guias Essenciais**
- **[📖 Guia de Implementação Rápida](docs/guides/QUICK_START_GUIDE.md)** - Setup completo em minutos
- **[🔧 Middleware Personalizado](docs/guides/CUSTOM_MIDDLEWARE_GUIDE.md)** - Crie middleware sob medida
- **[�️ Middlewares Padrão](docs/guides/STANDARD_MIDDLEWARES.md)** - Referência completa dos middlewares inclusos
- **[�🔒 Segurança](docs/guides/SECURITY_IMPLEMENTATION.md)** - Boas práticas de segurança
- **[📋 Índice Completo](docs/DOCUMENTATION_INDEX.md)** - Toda a documentação

### 📊 **Performance & Benchmarks**
- **[📈 Benchmarks Completos](benchmarks/reports/COMPREHENSIVE_PERFORMANCE_SUMMARY.md)** - Performance detalhada
- **[🔧 Como Executar Benchmarks](benchmarks/README.md)** - Testes de performance

### 🎯 **Para Começar**
1. **Iniciantes:** [Guia Rápido](docs/guides/QUICK_START_GUIDE.md) → Primeira API em 5 minutos
2. **Desenvolvedores:** [Middleware Avançado](docs/guides/CUSTOM_MIDDLEWARE_GUIDE.md) → Funcionalidades customizadas
3. **Produção:** [Segurança](docs/guides/SECURITY_IMPLEMENTATION.md) → Deploy seguro

## ⚙️ Requisitos

- **PHP**: 8.1.0 ou superior
- **Extensões**: json, session
- **Recomendado**: openssl, mbstring, fileinfo

## 🤝 Contribuição

Contribuições são bem-vindas! Veja nosso [guia de contribuição](CONTRIBUTING.md).

## 📄 Licença

Este projeto está licenciado sob a [Licença MIT](LICENSE).

## 🌟 Suporte

- [Issues](https://github.com/CAFernandes/express-php/issues) - Reportar bugs ou solicitar recursos
- [Discussions](https://github.com/CAFernandes/express-php/discussions) - Perguntas e discussões
- [Wiki](https://github.com/CAFernandes/express-php/wiki) - Documentação adicional

---

**🚀 Pronto para começar?** [Siga nosso guia de implementação rápida](docs/guides/QUICK_START_GUIDE.md)!
