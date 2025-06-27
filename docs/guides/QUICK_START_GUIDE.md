# 🚀 Guia de Implementação Rápida

## Express PHP Framework - Setup e Primeiros Passos

*Última atualização: 27 de Junho de 2025*

---

## 📋 Índice

1. [Instalação](#instalação)
2. [Configuração Básica](#configuração-básica)
3. [Primeira API](#primeira-api)
4. [Rotas e Controllers](#rotas-e-controllers)
5. [Middleware Essencial](#middleware-essencial)
6. [Validação e Segurança](#validação-e-segurança)
7. [Deploy em Produção](#deploy-em-produção)
8. [Exemplos Práticos](#exemplos-práticos)

---

## 🔧 Instalação

### Via Composer (Recomendado)

```bash
composer require cfernandes/express-php
```

### Via Git Clone

```bash
git clone https://github.com/cfernandes/express-php.git
cd express-php
composer install
```

### Requisitos Mínimos
- PHP 8.1+
- Composer
- Nginx/Apache (para produção)

---

## ⚙️ Configuração Básica

### 1. Estrutura de Projeto Recomendada

```
projeto/
├── public/
│   ├── index.php           # Ponto de entrada
│   └── .htaccess          # Rewrite rules (Apache)
├── app/
│   ├── Controllers/       # Controllers da aplicação
│   ├── Middleware/        # Middleware personalizado
│   └── Models/           # Modelos de dados
├── config/
│   └── app.php           # Configurações
├── storage/
│   └── logs/             # Arquivos de log
└── composer.json
```

### 2. Arquivo de Entrada (`public/index.php`)

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ExpressPhp\ApiExpress;

// Criar aplicação
$app = new ApiExpress();

// Configurações básicas
$app->config([
    'debug' => $_ENV['APP_DEBUG'] ?? false,
    'cors' => [
        'enabled' => true,
        'origins' => ['*'],
        'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'headers' => ['Content-Type', 'Authorization']
    ]
]);

// Middleware globais
$app->use(function($req, $res, $next) {
    $res->setHeader('X-Powered-By', 'Express-PHP');
    $next();
});

// Rotas básicas
$app->get('/', function($req, $res) {
    $res->json([
        'message' => 'Express PHP API funcionando!',
        'version' => '1.0.0',
        'timestamp' => date('Y-m-d H:i:s')
    ]);
});

$app->get('/health', function($req, $res) {
    $res->json([
        'status' => 'OK',
        'memory' => round(memory_get_usage(true) / 1024 / 1024, 2) . 'MB',
        'uptime' => date('c')
    ]);
});

// Rotas da API
require_once __DIR__ . '/../app/routes.php';

// Iniciar servidor
$app->listen($_ENV['PORT'] ?? 8000);
```

### 3. Configuração de Rotas (`app/routes.php`)

```php
<?php

// Importar controllers
use App\Controllers\{UserController, AuthController, PostController};

// Rotas de autenticação
$app->post('/auth/login', [AuthController::class, 'login']);
$app->post('/auth/register', [AuthController::class, 'register']);
$app->post('/auth/refresh', [AuthController::class, 'refresh']);

// Rotas protegidas
$app->group('/api', function($router) {
    // Middleware de autenticação para todas as rotas do grupo
    $router->use(new App\Middleware\JWTAuthMiddleware(
        $_ENV['JWT_SECRET'] ?? 'your-secret-key'
    ));

    // Usuários
    $router->get('/users', [UserController::class, 'index']);
    $router->get('/users/:id', [UserController::class, 'show']);
    $router->post('/users', [UserController::class, 'store']);
    $router->put('/users/:id', [UserController::class, 'update']);
    $router->delete('/users/:id', [UserController::class, 'destroy']);

    // Posts
    $router->get('/posts', [PostController::class, 'index']);
    $router->post('/posts', [PostController::class, 'store']);
    $router->get('/posts/:id', [PostController::class, 'show']);
    $router->put('/posts/:id', [PostController::class, 'update']);
    $router->delete('/posts/:id', [PostController::class, 'destroy']);
});

// Rota catch-all para 404
$app->use(function($req, $res) {
    $res->status(404)->json([
        'error' => 'Endpoint não encontrado',
        'path' => $req->getPath(),
        'method' => $req->getMethod()
    ]);
});
```

---

## 🎯 Primeira API

### Controller Básico (`app/Controllers/UserController.php`)

```php
<?php

namespace App\Controllers;

use ExpressPhp\Http\Request;
use ExpressPhp\Http\Response;

class UserController
{
    private array $users = [
        ['id' => 1, 'name' => 'João Silva', 'email' => 'joao@email.com'],
        ['id' => 2, 'name' => 'Maria Santos', 'email' => 'maria@email.com']
    ];

    public function index(Request $req, Response $res): void
    {
        $page = (int)($req->getQueryParam('page') ?? 1);
        $limit = (int)($req->getQueryParam('limit') ?? 10);

        $offset = ($page - 1) * $limit;
        $users = array_slice($this->users, $offset, $limit);

        $res->json([
            'data' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => count($this->users)
            ]
        ]);
    }

    public function show(Request $req, Response $res): void
    {
        $id = (int)$req->getParam('id');

        $user = array_filter($this->users, fn($u) => $u['id'] === $id);

        if (empty($user)) {
            $res->status(404)->json(['error' => 'Usuário não encontrado']);
            return;
        }

        $res->json(['data' => array_values($user)[0]]);
    }

    public function store(Request $req, Response $res): void
    {
        $data = $req->getBody();

        // Validação básica
        if (empty($data['name']) || empty($data['email'])) {
            $res->status(400)->json([
                'error' => 'Nome e email são obrigatórios'
            ]);
            return;
        }

        $newUser = [
            'id' => max(array_column($this->users, 'id')) + 1,
            'name' => $data['name'],
            'email' => $data['email']
        ];

        $this->users[] = $newUser;

        $res->status(201)->json([
            'message' => 'Usuário criado com sucesso',
            'data' => $newUser
        ]);
    }

    public function update(Request $req, Response $res): void
    {
        $id = (int)$req->getParam('id');
        $data = $req->getBody();

        $userIndex = array_search($id, array_column($this->users, 'id'));

        if ($userIndex === false) {
            $res->status(404)->json(['error' => 'Usuário não encontrado']);
            return;
        }

        if (isset($data['name'])) {
            $this->users[$userIndex]['name'] = $data['name'];
        }

        if (isset($data['email'])) {
            $this->users[$userIndex]['email'] = $data['email'];
        }

        $res->json([
            'message' => 'Usuário atualizado com sucesso',
            'data' => $this->users[$userIndex]
        ]);
    }

    public function destroy(Request $req, Response $res): void
    {
        $id = (int)$req->getParam('id');

        $userIndex = array_search($id, array_column($this->users, 'id'));

        if ($userIndex === false) {
            $res->status(404)->json(['error' => 'Usuário não encontrado']);
            return;
        }

        unset($this->users[$userIndex]);
        $this->users = array_values($this->users);

        $res->json(['message' => 'Usuário removido com sucesso']);
    }
}
```

---

## 🔐 Middleware Essencial

### 1. CORS Middleware (Já incluído)

```php
$app->use(new ExpressPhp\Middleware\CorsMiddleware([
    'origins' => ['http://localhost:3000', 'https://meusite.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'headers' => ['Content-Type', 'Authorization']
]));
```

### 2. Rate Limiting

```php
use App\Middleware\RateLimitMiddleware;

$app->use(new RateLimitMiddleware(
    maxRequests: 100,    // 100 requests
    windowTime: 3600     // por hora
));
```

### 3. Request Logging

```php
$app->use(function($req, $res, $next) {
    $start = microtime(true);

    error_log(sprintf(
        '[%s] %s %s - IP: %s',
        date('Y-m-d H:i:s'),
        $req->getMethod(),
        $req->getPath(),
        $req->getServerParam('REMOTE_ADDR')
    ));

    $next();

    $duration = round((microtime(true) - $start) * 1000, 2);
    error_log(sprintf(
        '[%s] Response: %d - Duration: %sms',
        date('Y-m-d H:i:s'),
        $res->getStatusCode(),
        $duration
    ));
});
```

---

## ✅ Validação e Segurança

### 1. Validação de Input

```php
use App\Middleware\ValidationMiddleware;

$app->post('/users', new ValidationMiddleware([
    'name' => ['required', 'min:2', 'max:100'],
    'email' => ['required', 'email'],
    'age' => ['numeric', 'min:18']
]), [UserController::class, 'store']);
```

### 2. Sanitização de Dados

```php
function sanitizeInput(array $data): array
{
    $sanitized = [];

    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        } else {
            $sanitized[$key] = $value;
        }
    }

    return $sanitized;
}
```

### 3. Headers de Segurança

```php
$app->use(function($req, $res, $next) {
    $res->setHeader('X-Content-Type-Options', 'nosniff')
        ->setHeader('X-Frame-Options', 'DENY')
        ->setHeader('X-XSS-Protection', '1; mode=block')
        ->setHeader('Strict-Transport-Security', 'max-age=31536000')
        ->setHeader('Content-Security-Policy', "default-src 'self'");

    $next();
});
```

---

## 🚀 Deploy em Produção

### 1. Configuração do Nginx

```nginx
server {
    listen 80;
    server_name api.meusite.com;
    root /var/www/express-php/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Headers de segurança
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
}
```

### 2. Configuração do Apache (`.htaccess`)

```apache
RewriteEngine On

# Redirect to front controller
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [QSA,L]

# Security headers
Header always set X-Frame-Options "SAMEORIGIN"
Header always set X-XSS-Protection "1; mode=block"
Header always set X-Content-Type-Options "nosniff"
```

### 3. Variáveis de Ambiente (`.env`)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.meusite.com

JWT_SECRET=sua-chave-super-secreta-aqui
JWT_EXPIRATION=3600

DB_HOST=localhost
DB_NAME=express_php
DB_USER=api_user
DB_PASS=senha-super-segura

CORS_ORIGINS=https://meusite.com,https://app.meusite.com
```

### 4. Script de Deploy

```bash
#!/bin/bash

# deploy.sh
echo "🚀 Iniciando deploy..."

# Backup
cp -r /var/www/express-php /var/www/express-php-backup-$(date +%Y%m%d_%H%M%S)

# Atualizar código
git pull origin main

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Otimizações
composer dump-autoload --optimize

# Permissões
chown -R www-data:www-data /var/www/express-php
chmod -R 755 /var/www/express-php

# Restart services
systemctl reload nginx
systemctl reload php8.1-fpm

echo "✅ Deploy concluído!"
```

---

## 📚 Exemplos Práticos

### 1. API de Blog Simples

```php
// Rota para listar posts
$app->get('/posts', function($req, $res) {
    $page = (int)($req->getQueryParam('page') ?? 1);
    $category = $req->getQueryParam('category');

    $posts = [/* seus posts aqui */];

    if ($category) {
        $posts = array_filter($posts, fn($p) => $p['category'] === $category);
    }

    $res->json([
        'data' => array_slice($posts, ($page - 1) * 10, 10),
        'total' => count($posts),
        'page' => $page
    ]);
});

// Upload de arquivo
$app->post('/upload', function($req, $res) {
    $file = $req->getFile('image');

    if (!$file) {
        $res->status(400)->json(['error' => 'Nenhum arquivo enviado']);
        return;
    }

    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowedTypes)) {
        $res->status(400)->json(['error' => 'Tipo de arquivo não permitido']);
        return;
    }

    $filename = uniqid() . '_' . $file['name'];
    $uploadPath = '/var/www/uploads/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $res->json([
            'message' => 'Arquivo enviado com sucesso',
            'url' => '/uploads/' . $filename
        ]);
    } else {
        $res->status(500)->json(['error' => 'Erro ao salvar arquivo']);
    }
});
```

### 2. WebSocket simples

```php
// Para aplicações que precisam de tempo real
$app->ws('/chat', function($connection, $req) {
    $connection->send(json_encode([
        'type' => 'welcome',
        'message' => 'Conectado ao chat!'
    ]));

    $connection->on('message', function($msg) use ($connection) {
        $data = json_decode($msg, true);

        // Broadcast para todos os clientes
        $connection->broadcast(json_encode([
            'type' => 'message',
            'user' => $data['user'],
            'text' => $data['text'],
            'timestamp' => time()
        ]));
    });
});
```

### 3. Cache Redis

```php
use Predis\Client;

$redis = new Client();

$app->get('/cached-data', function($req, $res) use ($redis) {
    $cacheKey = 'expensive-operation';

    $cached = $redis->get($cacheKey);
    if ($cached) {
        $res->setHeader('X-Cache', 'HIT')
            ->json(json_decode($cached, true));
        return;
    }

    // Operação custosa
    $data = performExpensiveOperation();

    // Cache por 1 hora
    $redis->setex($cacheKey, 3600, json_encode($data));

    $res->setHeader('X-Cache', 'MISS')
        ->json($data);
});
```

---

## 🔧 Comandos Úteis

### Desenvolvimento
```bash
# Servidor de desenvolvimento
php -S localhost:8000 -t public/

# Executar testes
vendor/bin/phpunit

# Análise de código
vendor/bin/phpstan analyse src/
```

### Produção
```bash
# Otimizar Composer
composer install --no-dev --optimize-autoloader

# Limpar cache
rm -rf storage/cache/*

# Verificar status
systemctl status nginx php8.1-fpm
```

---

**Express PHP Framework** está pronto para uso em produção com performance otimizada e arquitetura flexível! 🚀
