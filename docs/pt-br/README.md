# Express PHP Microframework - Documentação Completa

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.0%2B-blue.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](https://phpstan.org/)

**Express PHP** é um microframework leve, rápido e seguro inspirado no Express.js para construir aplicações web e APIs modernas em PHP com sistema nativo de autenticação multi-método.

> 🔐 **Novo na v1.0**: Sistema completo de autenticação com JWT, Basic Auth, Bearer Token, API Key e auto-detecção!

## 🚀 Início Rápido

### Instalação
```bash
composer require cafernandes/express-php
```

### Hello World
```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;

$app = new ApiExpress();

$app->get('/', function($req, $res) {
    $res->json(['message' => 'Olá Express PHP!']);
});

$app->run();
```

**💡 Primeiro projeto?** Siga nosso **[Guia de Início](../guides/starter/README.md)** completo!

## ✨ Principais Recursos

### 🔐 Sistema de Autenticação Multi-método
```php
// JWT simples
$app->use(AuthMiddleware::jwt('sua_chave_secreta'));

// Múltiplos métodos
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey', 'bearer'],
    'jwtSecret' => 'sua_chave_jwt',
    'basicAuthCallback' => 'validarUsuario',
    'apiKeyCallback' => 'validarApiKey'
]));

// Acessar dados do usuário
$app->get('/profile', function($req, $res) {
    $user = $req->user; // Dados do usuário autenticado
    $method = $req->auth['method']; // Método usado
    $res->json(['user' => $user, 'auth_method' => $method]);
});
```

### 🛡️ Middlewares de Segurança
```php
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Security\CsrfMiddleware;
use Express\Middlewares\Security\XssMiddleware;
use Express\Middlewares\Core\CorsMiddleware;
use Express\Middlewares\Core\RateLimitMiddleware;

// Segurança completa em uma linha
$app->use(SecurityMiddleware::create());

// Ou configure individualmente
$app->use(new CsrfMiddleware());
$app->use(new XssMiddleware());
$app->use(new CorsMiddleware());
$app->use(new RateLimitMiddleware());
```

### 📚 Documentação OpenAPI/Swagger Automática
```php
use Express\Middlewares\Core\OpenApiDocsMiddleware;

$app->use('/docs', new OpenApiDocsMiddleware([
    'title' => 'Minha API',
    'version' => '1.0.0'
]));

// Rotas com metadados para documentação
$app->get('/api/users', function($req, $res) {
    $res->json(['users' => []]);
}, [
    'tags' => ['Users'],
    'summary' => 'Listar usuários',
    'responses' => [
        '200' => ['description' => 'Lista de usuários']
    ]
]);

// Acesse /docs para ver a documentação interativa
```

## 🎯 Exemplos de Aprendizado

Explore os exemplos na pasta `examples/` para aprender diferentes aspectos:

| Exemplo | Arquivo | O que ensina |
|---------|---------|--------------|
| 👥 **Usuários** | `example_user.php` | Sistema de usuários, autenticação, perfis |
| 📦 **Produtos** | `example_product.php` | CRUD completo, parâmetros de rota, validação |
| 📤 **Upload** | `example_upload.php` | Upload de arquivos, validação, storage |
| 🔐 **Admin** | `example_admin.php` | Área administrativa, permissões, dashboards |
| 📝 **Blog** | `example_blog.php` | Sistema de blog, categorias, comentários |
| 🛡️ **Segurança** | `example_security.php` | Todos os middlewares de segurança |
| 🏗️ **Completo** | `example_complete.php` | Integração de todos os recursos |

### Como usar os exemplos
```bash
# Testar exemplo específico
php examples/example_user.php
# Acessar: http://localhost:8000

# Ver documentação do exemplo
php examples/example_complete.php
# Acessar: http://localhost:8000/docs
```

## 📖 CRUD Completo - Exemplo Prático

```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Security\AuthMiddleware;

$app = new ApiExpress();

// Middlewares globais
$app->use(SecurityMiddleware::create());

// Autenticação para rotas /api/*
$app->use('/api', AuthMiddleware::jwt('chave_secreta'));

// Simulação de dados
$users = [
    ['id' => 1, 'name' => 'João', 'email' => 'joao@email.com'],
    ['id' => 2, 'name' => 'Maria', 'email' => 'maria@email.com']
];

// LOGIN (rota pública)
$app->post('/login', function($req, $res) {
    $username = $req->body['username'] ?? '';
    $password = $req->body['password'] ?? '';

    if ($username === 'admin' && $password === 'senha123') {
        $token = AuthMiddleware::generateJWT([
            'user_id' => 1,
            'username' => $username
        ], 'chave_secreta');

        $res->json(['token' => $token]);
    } else {
        $res->status(401)->json(['error' => 'Credenciais inválidas']);
    }
});

// CRUD ENDPOINTS (protegidos)
$app->get('/api/users', function($req, $res) use ($users) {
    $res->json($users);
});

$app->get('/api/users/:id', function($req, $res) use ($users) {
    $id = (int)$req->params['id'];
    $user = array_filter($users, fn($u) => $u['id'] === $id);

    if (empty($user)) {
        return $res->status(404)->json(['error' => 'Usuário não encontrado']);
    }

    $res->json(array_values($user)[0]);
});

$app->post('/api/users', function($req, $res) use (&$users) {
    $data = $req->body;
    $newUser = [
        'id' => count($users) + 1,
        'name' => $data['name'],
        'email' => $data['email']
    ];
    $users[] = $newUser;

    $res->status(201)->json($newUser);
});

$app->put('/api/users/:id', function($req, $res) use (&$users) {
    $id = (int)$req->params['id'];
    $data = $req->body;

    foreach ($users as &$user) {
        if ($user['id'] === $id) {
            $user = array_merge($user, $data);
            return $res->json($user);
        }
    }

    $res->status(404)->json(['error' => 'Usuário não encontrado']);
});

$app->delete('/api/users/:id', function($req, $res) use (&$users) {
    $id = (int)$req->params['id'];
    $users = array_filter($users, fn($u) => $u['id'] !== $id);

    $res->status(204)->send();
});

$app->run();
```

### Testando a API
```bash
# 1. Login
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"senha123"}'

# 2. Listar usuários (com token)
curl -X GET http://localhost:8000/api/users \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"

# 3. Criar usuário
curl -X POST http://localhost:8000/api/users \
  -H "Authorization: Bearer SEU_TOKEN_AQUI" \
  -H "Content-Type: application/json" \
  -d '{"name":"Pedro","email":"pedro@email.com"}'
```

## 🛠️ Recursos Avançados

### 📊 Agrupamento de Rotas
```php
// Agrupar rotas com prefixo e middlewares
$app->use('/api/v1', AuthMiddleware::jwt('chave'));

$app->get('/api/v1/users', function($req, $res) {
    // Rota protegida automaticamente
});

$app->get('/api/v1/products', function($req, $res) {
    // Rota protegida automaticamente
});
```

### 🔄 Middleware Customizado
```php
// Middleware de log
$logMiddleware = function($req, $res, $next) {
    error_log("Request: {$req->method} {$req->path}");
    $next();
    error_log("Response: {$res->getStatusCode()}");
};

$app->use($logMiddleware);
```

### 📤 Upload de Arquivos
```php
$app->post('/upload', function($req, $res) {
    if (!isset($_FILES['arquivo'])) {
        return $res->status(400)->json(['error' => 'Nenhum arquivo enviado']);
    }

    $arquivo = $_FILES['arquivo'];
    $destino = 'uploads/' . basename($arquivo['name']);

    if (move_uploaded_file($arquivo['tmp_name'], $destino)) {
        $res->json(['message' => 'Upload realizado', 'file' => $destino]);
    } else {
        $res->status(500)->json(['error' => 'Erro no upload']);
    }
});
```

## 🔧 Configuração e Deploy

### Estrutura de Projeto Recomendada
```
meu-projeto/
├── public/
│   ├── index.php          # Ponto de entrada
│   └── .htaccess         # Configuração Apache
├── app/
│   ├── Controllers/      # Controladores
│   ├── Middlewares/      # Middlewares customizados
│   └── Models/          # Modelos
├── config/
│   └── app.php          # Configurações
├── storage/
│   ├── logs/            # Logs
│   └── uploads/         # Arquivos
├── .env                 # Variáveis de ambiente
└── composer.json        # Dependências
```

### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

### Nginx
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

## 📋 Referência Rápida da API

### Objetos Principais

| Objeto | Descrição | Principais Métodos |
|--------|-----------|-------------------|
| `$req` | Request | `body`, `params`, `query`, `headers`, `user` |
| `$res` | Response | `json()`, `send()`, `status()`, `header()` |
| `$app` | Application | `get()`, `post()`, `put()`, `delete()`, `use()` |

### Middlewares Disponíveis

| Middleware | Função | Uso |
|------------|--------|-----|
| `SecurityMiddleware` | Segurança completa | `SecurityMiddleware::create()` |
| `AuthMiddleware` | Autenticação | `AuthMiddleware::jwt('secret')` |
| `CorsMiddleware` | CORS | `new CorsMiddleware()` |
| `CsrfMiddleware` | CSRF Protection | `new CsrfMiddleware()` |
| `XssMiddleware` | XSS Protection | `new XssMiddleware()` |
| `RateLimitMiddleware` | Rate Limiting | `new RateLimitMiddleware()` |

## 🏆 Qualidade e Testes

- ✅ **186 testes unitários** - 100% de cobertura
- ✅ **PHPStan Level 8** - Máxima análise estática
- ✅ **PSR-12** - Code style padronizado
- ✅ **PHP 7.4+** - Compatibilidade ampla
- ✅ **Zero dependências** obrigatórias
- ✅ **CI/CD completo** - GitHub Actions

## 🆘 Suporte e Comunidade

- **[Issues](https://github.com/CAFernandes/express-php/issues)** - Reportar bugs
- **[Discussions](https://github.com/CAFernandes/express-php/discussions)** - Perguntas e ideias
- **[Wiki](https://github.com/CAFernandes/express-php/wiki)** - Documentação adicional
- **[Contributing](../../CONTRIBUTING.md)** - Como contribuir

## 📚 Documentação Adicional

- **[🚀 Guia de Início](../guides/starter/README.md)** - Tutorial completo para iniciantes
- **[🔐 Sistema de Autenticação](AUTH_MIDDLEWARE.md)** - Guia detalhado de autenticação
- **[🛡️ Middlewares de Segurança](../guides/SECURITY_IMPLEMENTATION.md)** - Segurança avançada
- **[📊 Referência de Objetos](objetos.md)** - API completa do framework
- **[🌍 English Documentation](../en/README.md)** - English version

---

**🎉 Pronto para começar?** [Siga nosso guia de início](../guides/starter/README.md) ou explore os [exemplos práticos](../../examples/)!
