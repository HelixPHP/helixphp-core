# 📦 Como Publicar e Usar o Express PHP

## 🚀 Publicando no Packagist (Composer)

### 1. Preparação do Repositório

#### Pré-requisitos:
- ✅ Repositório Git público (GitHub, GitLab, etc.)
- ✅ Conta no [Packagist.org](https://packagist.org)
- ✅ Tags de versão no Git
- ✅ Composer.json válido

#### Estrutura do projeto:
```
express-php/
├── composer.json          # ✅ Configuração do Composer
├── README.md              # ✅ Documentação principal
├── LICENSE                # ✅ Licença MIT
├── src/                   # ✅ Código fonte
│   ├── ApiExpress.php
│   ├── Middlewares/
│   └── Helpers/
├── examples/              # ✅ Exemplos práticos
├── tests/                 # ✅ Testes unitários
└── docs/                  # ✅ Documentação

```

### 2. Verificação do composer.json

O arquivo já está configurado corretamente:

```json
{
    "name": "express-php/microframework",
    "description": "Express PHP - A lightweight, fast, and secure microframework inspired by Express.js for building modern PHP web applications and APIs",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": ">=8.1.0",
        "ext-json": "*",
        "ext-session": "*"
    },
    "autoload": {
        "psr-4": {
            "Express\\": "src/"
        }
    }
}
```

### 3. Criando Tags de Versão

```bash
# Criar tag para primeira versão
git tag -a v1.0.0 -m "Release v1.0.0 - Middleware de Autenticação"
git push origin v1.0.0

# Para versões futuras
git tag -a v1.1.0 -m "Release v1.1.0 - Novas funcionalidades"
git push origin v1.1.0
```

### 4. Submetendo ao Packagist

1. **Acesse:** https://packagist.org
2. **Faça login** com GitHub/GitLab
3. **Clique em "Submit"**
4. **Cole a URL do repositório:** `https://github.com/CAFernandes/express-php`
5. **Clique em "Check"**
6. **Se tudo estiver correto, clique em "Submit"**

### 5. Configuração de Auto-Update

Para atualização automática quando criar novas tags:

1. **No GitHub:** Settings → Webhooks → Add webhook
2. **URL:** https://packagist.org/api/github?username=SEU_USERNAME&apiToken=SEU_TOKEN
3. **Content type:** application/json
4. **Events:** Just the push event

---

## 🏗️ Usando em um Projeto Real

### 1. Instalação via Composer

```bash
# Criar novo projeto
mkdir meu-projeto-api
cd meu-projeto-api

# Inicializar Composer
composer init

# Instalar Express PHP
composer require express-php/microframework

# Instalar dependências opcionais
composer require firebase/php-jwt  # Para JWT
```

### 2. Estrutura do Projeto Real

```
meu-projeto-api/
├── composer.json
├── .env
├── .htaccess
├── index.php              # Entrada da aplicação
├── config/
│   ├── database.php
│   └── auth.php
├── src/
│   ├── Controllers/
│   ├── Models/
│   ├── Middleware/
│   └── Services/
├── routes/
│   ├── api.php
│   ├── auth.php
│   └── admin.php
└── public/
    └── index.php
```

### 3. Configuração Básica

#### public/index.php
```php
<?php
require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\AuthMiddleware;
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Core\CorsMiddleware;
use Express\Middlewares\Core\ErrorHandlerMiddleware;

// Carregar variáveis de ambiente
if (file_exists('../.env')) {
    $env = parse_ini_file('../.env');
    foreach ($env as $key => $value) {
        $_ENV[$key] = $value;
    }
}

// Criar aplicação
$app = new ApiExpress();

// Middleware de erro global
$app->use(new ErrorHandlerMiddleware());

// CORS
$app->use(new CorsMiddleware([
    'origin' => $_ENV['CORS_ORIGIN'] ?? '*',
    'credentials' => true
]));

// Segurança global
$app->use(SecurityMiddleware::create());

// Autenticação para rotas protegidas
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'apikey'],
    'jwtSecret' => $_ENV['JWT_SECRET'],
    'apiKeyCallback' => 'App\\Services\\AuthService::validateApiKey',
    'excludePaths' => ['/public', '/auth', '/docs', '/health']
]));

// Carregar rotas
require_once '../routes/api.php';
require_once '../routes/auth.php';
require_once '../routes/admin.php';

// Rota de saúde
$app->get('/health', function($req, $res) {
    $res->json([
        'status' => 'healthy',
        'timestamp' => time(),
        'version' => '1.0.0'
    ]);
});

// Executar aplicação
$app->run();
```

#### .env
```env
# Database
DB_HOST=localhost
DB_NAME=meu_projeto
DB_USER=root
DB_PASS=senha123

# JWT
JWT_SECRET=sua_chave_jwt_super_secreta_aqui
REFRESH_SECRET=sua_chave_refresh_super_secreta_aqui
JWT_EXPIRE_TIME=3600

# CORS
CORS_ORIGIN=https://meuapp.com

# Environment
APP_ENV=production
APP_DEBUG=false
```

### 4. Serviços de Autenticação

#### src/Services/AuthService.php
```php
<?php
namespace App\Services;

use Express\Helpers\JWTHelper;
use App\Models\User;
use App\Models\ApiKey;

class AuthService
{
    public static function login($username, $password)
    {
        $user = User::findByUsername($username);

        if ($user && password_verify($password, $user['password'])) {
            $token = JWTHelper::encode([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role'],
                'permissions' => $user['permissions']
            ], $_ENV['JWT_SECRET'], [
                'expiresIn' => (int)$_ENV['JWT_EXPIRE_TIME']
            ]);

            $refreshToken = JWTHelper::createRefreshToken(
                $user['id'],
                $_ENV['REFRESH_SECRET']
            );

            return [
                'success' => true,
                'user' => $user,
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'expires_in' => (int)$_ENV['JWT_EXPIRE_TIME']
            ];
        }

        return ['success' => false, 'message' => 'Invalid credentials'];
    }

    public static function validateApiKey($apiKey)
    {
        $key = ApiKey::findByKey($apiKey);

        if ($key && $key['active']) {
            return [
                'id' => $key['id'],
                'name' => $key['name'],
                'permissions' => explode(',', $key['permissions']),
                'rate_limit' => $key['rate_limit']
            ];
        }

        return false;
    }

    public static function refreshToken($refreshToken)
    {
        $payload = JWTHelper::validateRefreshToken(
            $refreshToken,
            $_ENV['REFRESH_SECRET']
        );

        if ($payload) {
            $user = User::findById($payload['user_id']);

            if ($user) {
                $newToken = JWTHelper::encode([
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'role' => $user['role'],
                    'permissions' => $user['permissions']
                ], $_ENV['JWT_SECRET']);

                return [
                    'success' => true,
                    'access_token' => $newToken,
                    'expires_in' => (int)$_ENV['JWT_EXPIRE_TIME']
                ];
            }
        }

        return ['success' => false, 'message' => 'Invalid refresh token'];
    }
}
```

### 5. Rotas de Autenticação

#### routes/auth.php
```php
<?php
use App\Services\AuthService;

// Login
$app->post('/auth/login', function($req, $res) {
    $username = $req->body['username'] ?? '';
    $password = $req->body['password'] ?? '';

    if (empty($username) || empty($password)) {
        $res->status(400)->json([
            'error' => 'Username and password are required'
        ]);
        return;
    }

    $result = AuthService::login($username, $password);

    if ($result['success']) {
        $res->json($result);
    } else {
        $res->status(401)->json($result);
    }
});

// Refresh Token
$app->post('/auth/refresh', function($req, $res) {
    $refreshToken = $req->body['refresh_token'] ?? '';

    if (empty($refreshToken)) {
        $res->status(400)->json([
            'error' => 'Refresh token is required'
        ]);
        return;
    }

    $result = AuthService::refreshToken($refreshToken);

    if ($result['success']) {
        $res->json($result);
    } else {
        $res->status(401)->json($result);
    }
});

// Logout (invalidar token)
$app->post('/auth/logout', function($req, $res) {
    // Em uma implementação real, você pode:
    // 1. Adicionar o token a uma blacklist
    // 2. Remover refresh token do banco
    // 3. Log da ação

    $res->json(['message' => 'Logged out successfully']);
});

// Verificar token
$app->get('/auth/me', function($req, $res) {
    // Esta rota é protegida automaticamente pelo AuthMiddleware
    $res->json([
        'user' => $req->user,
        'auth_method' => $req->auth['method'],
        'authenticated_at' => time()
    ]);
});
```

### 6. API Routes

#### routes/api.php
```php
<?php
use App\Controllers\UserController;
use App\Controllers\ProductController;
use App\Middleware\RequirePermission;

// Rotas públicas
$app->get('/public/status', function($req, $res) {
    $res->json(['status' => 'API is running', 'timestamp' => time()]);
});

// Rotas de usuários (protegidas)
$app->get('/api/users',
    RequirePermission::check('users.read'),
    [UserController::class, 'index']
);

$app->get('/api/users/:id',
    RequirePermission::check('users.read'),
    [UserController::class, 'show']
);

$app->post('/api/users',
    RequirePermission::check('users.create'),
    [UserController::class, 'create']
);

$app->put('/api/users/:id',
    RequirePermission::check('users.update'),
    [UserController::class, 'update']
);

$app->delete('/api/users/:id',
    RequirePermission::check('users.delete'),
    [UserController::class, 'delete']
);

// Rotas de produtos
$app->get('/api/products', [ProductController::class, 'index']);
$app->get('/api/products/:id', [ProductController::class, 'show']);
$app->post('/api/products',
    RequirePermission::check('products.create'),
    [ProductController::class, 'create']
);
```

### 7. Controllers

#### src/Controllers/UserController.php
```php
<?php
namespace App\Controllers;

use App\Models\User;

class UserController
{
    public static function index($req, $res)
    {
        $users = User::all();
        $res->json([
            'users' => $users,
            'total' => count($users),
            'requested_by' => $req->user['username']
        ]);
    }

    public static function show($req, $res)
    {
        $id = $req->params->id;
        $user = User::findById($id);

        if (!$user) {
            $res->status(404)->json(['error' => 'User not found']);
            return;
        }

        $res->json(['user' => $user]);
    }

    public static function create($req, $res)
    {
        $data = $req->body;

        // Validação
        if (empty($data['username']) || empty($data['email'])) {
            $res->status(400)->json([
                'error' => 'Username and email are required'
            ]);
            return;
        }

        // Hash da senha
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $user = User::create($data);

        $res->status(201)->json([
            'message' => 'User created successfully',
            'user' => $user,
            'created_by' => $req->user['username']
        ]);
    }

    public static function update($req, $res)
    {
        $id = $req->params->id;
        $data = $req->body;

        $user = User::findById($id);
        if (!$user) {
            $res->status(404)->json(['error' => 'User not found']);
            return;
        }

        // Hash da senha se fornecida
        if (!empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $updatedUser = User::update($id, $data);

        $res->json([
            'message' => 'User updated successfully',
            'user' => $updatedUser,
            'updated_by' => $req->user['username']
        ]);
    }

    public static function delete($req, $res)
    {
        $id = $req->params->id;

        $user = User::findById($id);
        if (!$user) {
            $res->status(404)->json(['error' => 'User not found']);
            return;
        }

        User::delete($id);

        $res->json([
            'message' => 'User deleted successfully',
            'deleted_by' => $req->user['username']
        ]);
    }
}
```

### 8. Middleware Customizado

#### src/Middleware/RequirePermission.php
```php
<?php
namespace App\Middleware;

class RequirePermission
{
    public static function check($permission)
    {
        return function($req, $res, $next) use ($permission) {
            // Verifica se está autenticado
            if (!isset($req->user)) {
                $res->status(401)->json(['error' => 'Authentication required']);
                return;
            }

            // Verifica permissões
            $userPermissions = $req->user['permissions'] ?? [];

            if (!in_array($permission, $userPermissions) && !in_array('*', $userPermissions)) {
                $res->status(403)->json([
                    'error' => 'Permission denied',
                    'required_permission' => $permission,
                    'user_permissions' => $userPermissions
                ]);
                return;
            }

            $next();
        };
    }
}
```

### 9. Models (Exemplo com PDO)

#### src/Models/User.php
```php
<?php
namespace App\Models;

use PDO;

class User
{
    private static function getConnection()
    {
        static $pdo = null;

        if ($pdo === null) {
            $pdo = new PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }

        return $pdo;
    }

    public static function all()
    {
        $stmt = self::getConnection()->query(
            "SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function findById($id)
    {
        $stmt = self::getConnection()->prepare(
            "SELECT id, username, email, role, permissions, created_at FROM users WHERE id = ?"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function findByUsername($username)
    {
        $stmt = self::getConnection()->prepare(
            "SELECT * FROM users WHERE username = ?"
        );
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public static function create($data)
    {
        $stmt = self::getConnection()->prepare(
            "INSERT INTO users (username, email, password, role, permissions) VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $data['username'],
            $data['email'],
            $data['password'],
            $data['role'] ?? 'user',
            json_encode($data['permissions'] ?? ['users.read'])
        ]);

        return self::findById(self::getConnection()->lastInsertId());
    }

    public static function update($id, $data)
    {
        $fields = [];
        $values = [];

        foreach ($data as $key => $value) {
            if (in_array($key, ['username', 'email', 'password', 'role', 'permissions'])) {
                $fields[] = "$key = ?";
                $values[] = $key === 'permissions' ? json_encode($value) : $value;
            }
        }

        $values[] = $id;

        $stmt = self::getConnection()->prepare(
            "UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?"
        );
        $stmt->execute($values);

        return self::findById($id);
    }

    public static function delete($id)
    {
        $stmt = self::getConnection()->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
```

### 10. Configuração do Servidor

#### .htaccess (Apache)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ public/index.php [QSA,L]
```

#### nginx.conf (Nginx)
```nginx
server {
    listen 80;
    server_name meuapp.com;
    root /var/www/meu-projeto-api/public;

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
}
```

---

## 🧪 Testando a API

### 1. Login
```bash
curl -X POST http://localhost/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"senha123"}'
```

### 2. Acessar rota protegida
```bash
curl -X GET http://localhost/api/users \
  -H "Authorization: Bearer SEU_JWT_TOKEN"
```

### 3. Usando API Key
```bash
curl -X GET http://localhost/api/users \
  -H "X-API-Key: sua_api_key"
```

---

## 📊 Vantagens do Express PHP

✅ **Fácil de usar** - Sintaxe familiar do Express.js
✅ **Seguro por padrão** - Middlewares de segurança integrados
✅ **Autenticação robusta** - Múltiplos métodos nativos
✅ **Altamente configurável** - Flexível para qualquer projeto
✅ **Bem documentado** - Exemplos e guias completos
✅ **Testes incluídos** - Cobertura de testes abrangente
✅ **Produção pronto** - Otimizado para performance

---

## 📈 Monitoramento e Analytics

### 1. Logs de Acesso e Autenticação

#### src/Services/LogService.php
```php
<?php
namespace App\Services;

class LogService
{
    public static function logAuth($event, $data = [])
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'data' => $data
        ];

        $logFile = __DIR__ . '/../../logs/auth.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }

    public static function logApiAccess($endpoint, $method, $statusCode, $user = null)
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'user_id' => $user['id'] ?? null,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];

        $logFile = __DIR__ . '/../../logs/api.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}
```

### 2. Middleware de Logging

#### src/Middleware/LoggingMiddleware.php
```php
<?php
namespace App\Middleware;

use App\Services\LogService;

class LoggingMiddleware
{
    public function __invoke($req, $res, $next)
    {
        $startTime = microtime(true);

        // Continua para o próximo middleware
        $next();

        // Calcula tempo de resposta
        $responseTime = microtime(true) - $startTime;

        // Log da requisição
        LogService::logApiAccess(
            $req->path ?? $_SERVER['REQUEST_URI'],
            $req->method ?? $_SERVER['REQUEST_METHOD'],
            http_response_code(),
            $req->user ?? null
        );

        // Adiciona header de tempo de resposta
        $res->header('X-Response-Time', round($responseTime * 1000, 2) . 'ms');
    }
}
```

---

## 🔒 Configurações de Produção

### 1. Configuração de Segurança

#### config/security.php
```php
<?php
return [
    'jwt' => [
        'secret' => $_ENV['JWT_SECRET'],
        'algorithm' => 'HS256',
        'expire_time' => 3600, // 1 hora
        'refresh_expire_time' => 2592000 // 30 dias
    ],

    'rate_limiting' => [
        'enabled' => true,
        'max_requests' => 100,
        'time_window' => 3600, // 1 hora
        'auth_attempts' => [
            'max_attempts' => 5,
            'lockout_time' => 900 // 15 minutos
        ]
    ],

    'cors' => [
        'allowed_origins' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
        'credentials' => true
    ],

    'api_keys' => [
        'header_name' => 'X-API-Key',
        'query_param' => 'api_key',
        'max_requests_per_hour' => 1000
    ]
];
```

### 2. Configuração de Base de Dados

#### config/database.php
```php
<?php
return [
    'default' => 'mysql',

    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'port' => $_ENV['DB_PORT'] ?? '3306',
            'database' => $_ENV['DB_NAME'],
            'username' => $_ENV['DB_USER'],
            'password' => $_ENV['DB_PASS'],
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        ]
    ]
};
```

### 3. Script de Deploy

#### deploy.sh
```bash
#!/bin/bash

# Script de deploy para produção
echo "🚀 Iniciando deploy do Express PHP API..."

# Backup da versão atual
if [ -d "/var/www/api-backup" ]; then
    rm -rf /var/www/api-backup
fi

if [ -d "/var/www/meu-projeto-api" ]; then
    cp -r /var/www/meu-projeto-api /var/www/api-backup
    echo "✅ Backup criado"
fi

# Baixar nova versão
cd /tmp
git clone https://github.com/meu-usuario/meu-projeto-api.git
cd meu-projeto-api

# Instalar dependências
composer install --no-dev --optimize-autoloader

# Copiar arquivos
rsync -av --exclude='.git' --exclude='logs' . /var/www/meu-projeto-api/

# Restaurar configurações
cp /var/www/api-backup/.env /var/www/meu-projeto-api/
cp -r /var/www/api-backup/logs /var/www/meu-projeto-api/ 2>/dev/null || true

# Permissões
chown -R www-data:www-data /var/www/meu-projeto-api
chmod -R 755 /var/www/meu-projeto-api
chmod -R 777 /var/www/meu-projeto-api/logs

# Reiniciar serviços
systemctl reload nginx
systemctl reload php8.1-fpm

# Teste básico
sleep 2
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/health)

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Deploy concluído com sucesso!"
    rm -rf /tmp/meu-projeto-api
else
    echo "❌ Deploy falhou! Restaurando backup..."
    rm -rf /var/www/meu-projeto-api
    mv /var/www/api-backup /var/www/meu-projeto-api
    systemctl reload nginx
    exit 1
fi
```

---

## 🐳 Deploy com Docker

### 1. Dockerfile

```dockerfile
FROM php:8.1-apache

# Instalar extensões necessárias
RUN docker-php-ext-install pdo pdo_mysql json session

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar Apache
RUN a2enmod rewrite
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Copiar código
WORKDIR /var/www/html
COPY . .

# Instalar dependências
RUN composer install --no-dev --optimize-autoloader

# Configurar permissões
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
```

### 2. docker-compose.yml

```yaml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8080:80"
    environment:
      - DB_HOST=db
      - DB_NAME=express_app
      - DB_USER=root
      - DB_PASS=senha123
      - JWT_SECRET=sua_chave_jwt_super_secreta
    volumes:
      - ./logs:/var/www/html/logs
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: senha123
      MYSQL_DATABASE: express_app
    volumes:
      - db_data:/var/lib/mysql
      - ./database/init.sql:/docker-entrypoint-initdb.d/init.sql
    ports:
      - "3306:3306"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"
    volumes:
      - redis_data:/data

  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
      - "443:443"
    volumes:
      - ./nginx.conf:/etc/nginx/nginx.conf
      - ./ssl:/etc/nginx/ssl
    depends_on:
      - app

volumes:
  db_data:
  redis_data:
```

### 3. Comandos Docker

```bash
# Build da imagem
docker build -t meu-projeto-api .

# Subir os serviços
docker-compose up -d

# Ver logs
docker-compose logs -f app

# Executar comandos no container
docker-compose exec app php artisan migrate
docker-compose exec app composer install

# Parar serviços
docker-compose down
```

---

## 🚀 CI/CD com GitHub Actions

### 1. .github/workflows/ci.yml

```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [ main, develop ]
  pull_request:
    branches: [ main ]

jobs:
  test:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: test_db
        ports:
          - 3306:3306
        options: --health-cmd="mysqladmin ping" --health-interval=10s --health-timeout=5s --health-retries=3

    steps:
    - uses: actions/checkout@v3

    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.1'
        extensions: mbstring, xml, ctype, iconv, intl, pdo, pdo_mysql, dom, filter, gd, iconv, json, mbstring, pdo

    - name: Cache Composer packages
      id: composer-cache
      uses: actions/cache@v3
      with:
        path: vendor
        key: ${{ runner.os }}-php-${{ hashFiles('**/composer.lock') }}
        restore-keys: |
          ${{ runner.os }}-php-

    - name: Install dependencies
      run: composer install --prefer-dist --no-progress

    - name: Run PHPStan
      run: ./vendor/bin/phpstan analyse

    - name: Run PHPUnit tests
      run: ./vendor/bin/phpunit
      env:
        DB_HOST: 127.0.0.1
        DB_NAME: test_db
        DB_USER: root
        DB_PASS: password
        JWT_SECRET: test_jwt_secret

    - name: Upload coverage to Codecov
      uses: codecov/codecov-action@v3
      if: matrix.php-version == '8.1'
      with:
        file: ./coverage.xml

  deploy:
    needs: test
    runs-on: ubuntu-latest
    if: github.ref == 'refs/heads/main'

    steps:
    - uses: actions/checkout@v3

    - name: Deploy to production
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.KEY }}
        script: |
          cd /var/www/meu-projeto-api
          git pull origin main
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan cache:clear
          sudo systemctl reload apache2
```

### 2. Deploy Automático

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
  release:
    types: [published]

jobs:
  deploy:
    runs-on: ubuntu-latest

    steps:
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.5
      with:
        host: ${{ secrets.PROD_HOST }}
        username: ${{ secrets.PROD_USER }}
        key: ${{ secrets.PROD_SSH_KEY }}
        script: |
          cd /var/www/production
          git fetch --tags
          git checkout ${{ github.event.release.tag_name }}
          composer install --no-dev --optimize-autoloader
          php bin/migrate.php
          sudo systemctl reload nginx
```

---

## 📊 Monitoramento e Métricas

### 1. Middleware de Métricas

```php
<?php
// src/Middlewares/Core/MetricsMiddleware.php
namespace Express\Middlewares\Core;

class MetricsMiddleware
{
    private $config;

    public function __construct($config = [])
    {
        $this->config = array_merge([
            'enabled' => true,
            'logFile' => 'logs/metrics.log',
            'includeMemory' => true,
            'includeExecutionTime' => true
        ], $config);
    }

    public function handle($req, $res, $next)
    {
        if (!$this->config['enabled']) {
            return $next();
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage();

        // Executar próximo middleware
        $next();

        // Calcular métricas
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $metrics = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $req->method,
            'path' => $req->path,
            'status_code' => $res->getStatusCode(),
            'execution_time' => round(($endTime - $startTime) * 1000, 2), // ms
            'memory_usage' => round(($endMemory - $startMemory) / 1024 / 1024, 2), // MB
            'peak_memory' => round(memory_get_peak_usage() / 1024 / 1024, 2), // MB
            'user_agent' => $req->headers['User-Agent'] ?? 'Unknown',
            'ip' => $req->ip ?? '0.0.0.0'
        ];

        // Log das métricas
        $this->logMetrics($metrics);

        // Enviar para sistema de monitoramento (opcional)
        $this->sendToMonitoring($metrics);
    }

    private function logMetrics($metrics)
    {
        $logEntry = json_encode($metrics) . "\n";
        file_put_contents($this->config['logFile'], $logEntry, FILE_APPEND | LOCK_EX);
    }

    private function sendToMonitoring($metrics)
    {
        // Integração com Prometheus, DataDog, New Relic, etc.
        if (isset($_ENV['MONITORING_WEBHOOK'])) {
            $this->sendWebhook($_ENV['MONITORING_WEBHOOK'], $metrics);
        }
    }

    private function sendWebhook($url, $data)
    {
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];

        $context = stream_context_create($options);
        @file_get_contents($url, false, $context);
    }
}
```

### 2. Endpoint de Métricas

```php
// routes/monitoring.php
$app->get('/metrics', function($req, $res) {
    $metrics = [
        'system' => [
            'php_version' => PHP_VERSION,
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
        ],
        'application' => [
            'version' => '1.0.0',
            'environment' => $_ENV['APP_ENV'] ?? 'production',
            'uptime' => $this->getUptime(),
            'requests_total' => $this->getRequestCount(),
            'errors_total' => $this->getErrorCount()
        ],
        'performance' => [
            'average_response_time' => $this->getAverageResponseTime(),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2),
            'cpu_usage' => $this->getCpuUsage(),
            'disk_usage' => $this->getDiskUsage()
        ]
    ];

    $res->json($metrics);
});
```

### 3. Configuração do Prometheus

```yaml
# prometheus.yml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'express-php'
    static_configs:
      - targets: ['localhost:8080']
    metrics_path: '/metrics'
    scrape_interval: 30s
```

---

## 🔒 Segurança em Produção

### 1. Configurações de Segurança

```php
// config/security.php
return [
    'headers' => [
        'X-Frame-Options' => 'DENY',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'X-Permitted-Cross-Domain-Policies' => 'none',
        'Cross-Origin-Embedder-Policy' => 'require-corp',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Resource-Policy' => 'same-origin'
    ],

    'rate_limiting' => [
        'enabled' => true,
        'requests_per_minute' => 60,
        'burst_limit' => 100,
        'storage' => 'redis' // redis, file, memory
    ],

    'cors' => [
        'origin' => explode(',', $_ENV['CORS_ORIGINS'] ?? '*'),
        'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
        'headers' => ['Content-Type', 'Authorization', 'X-API-Key'],
        'credentials' => true,
        'max_age' => 86400
    ],

    'csrf' => [
        'enabled' => true,
        'token_lifetime' => 3600,
        'exclude_routes' => ['/api/*'] // APIs geralmente não usam CSRF
    ]
];
```

### 2. Middleware de Segurança Avançado

```php
// src/Middlewares/Security/SecurityHeadersMiddleware.php
namespace Express\Middlewares\Security;

class SecurityHeadersMiddleware
{
    public function handle($req, $res, $next)
    {
        // Headers de segurança
        $securityHeaders = [
            'X-Frame-Options' => 'DENY',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Cross-Origin-Embedder-Policy' => 'require-corp',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'Cross-Origin-Resource-Policy' => 'same-origin'
        ];

        foreach ($securityHeaders as $header => $value) {
            $res->setHeader($header, $value);
        }

        // HSTS apenas em HTTPS
        if ($req->isSecure()) {
            $res->setHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP dinâmico baseado no ambiente
        $csp = $this->buildCSP($req);
        $res->setHeader('Content-Security-Policy', $csp);

        $next();
    }

    private function buildCSP($req)
    {
        $isDev = ($_ENV['APP_ENV'] ?? 'production') === 'development';

        $directives = [
            "default-src 'self'",
            "script-src 'self'" . ($isDev ? " 'unsafe-eval' 'unsafe-inline'" : ""),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self'",
            "connect-src 'self'",
            "frame-ancestors 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];

        return implode('; ', $directives);
    }
}
```

### 3. Auditoria e Logs de Segurança

```php
// src/Services/SecurityAuditService.php
namespace Express\Services;

class SecurityAuditService
{
    public static function logSecurityEvent($type, $details, $severity = 'medium')
    {
        $event = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'severity' => $severity,
            'details' => $details,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'session_id' => session_id(),
            'request_id' => uniqid()
        ];

        // Log local
        error_log(json_encode($event), 3, 'logs/security.log');

        // Alertas críticos
        if ($severity === 'critical') {
            self::sendSecurityAlert($event);
        }

        // SIEM integration
        if (isset($_ENV['SIEM_ENDPOINT'])) {
            self::sendToSIEM($event);
        }
    }

    public static function detectSuspiciousActivity($req)
    {
        $suspicious = [];

        // Rate limiting
        if (self::isRateLimited($req->ip)) {
            $suspicious[] = 'rate_limit_exceeded';
        }

        // SQL Injection patterns
        if (self::containsSQLInjection($req->body)) {
            $suspicious[] = 'sql_injection_attempt';
        }

        // XSS patterns
        if (self::containsXSS($req->body)) {
            $suspicious[] = 'xss_attempt';
        }

        // Directory traversal
        if (self::containsDirectoryTraversal($req->path)) {
            $suspicious[] = 'directory_traversal';
        }

        if (!empty($suspicious)) {
            self::logSecurityEvent('suspicious_activity', [
                'patterns' => $suspicious,
                'request' => [
                    'method' => $req->method,
                    'path' => $req->path,
                    'body' => $req->body
                ]
            ], 'high');
        }

        return $suspicious;
    }

    private static function sendSecurityAlert($event)
    {
        // Email, Slack, webhook, etc.
        if (isset($_ENV['SECURITY_WEBHOOK'])) {
            $payload = [
                'text' => "🚨 SECURITY ALERT: {$event['type']}",
                'attachments' => [
                    [
                        'color' => 'danger',
                        'fields' => [
                            ['title' => 'Severity', 'value' => $event['severity'], 'short' => true],
                            ['title' => 'IP', 'value' => $event['ip'], 'short' => true],
                            ['title' => 'Details', 'value' => json_encode($event['details'])]
                        ]
                    ]
                ]
            ];

            $ch = curl_init($_ENV['SECURITY_WEBHOOK']);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        }
    }
}
```

---

## 🌍 Configuração para Produção

### 1. Otimizações de Performance

```php
// config/production.php
return [
    'cache' => [
        'enabled' => true,
        'driver' => 'redis', // redis, file, memory
        'ttl' => 3600,
        'prefix' => 'express_app:'
    ],

    'session' => [
        'driver' => 'redis',
        'lifetime' => 120, // minutos
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax'
    ],

    'compression' => [
        'enabled' => true,
        'level' => 6,
        'threshold' => 1024 // bytes
    ],

    'opcache' => [
        'enabled' => true,
        'validate_timestamps' => false,
        'max_accelerated_files' => 10000,
        'memory_consumption' => 256,
        'interned_strings_buffer' => 16
    ]
];
```

### 2. php.ini para Produção

```ini
; php.ini settings for production

; Security
expose_php = Off
display_errors = Off
display_startup_errors = Off
log_errors = On
error_log = /var/log/php/error.log

; Performance
opcache.enable = 1
opcache.validate_timestamps = 0
opcache.max_accelerated_files = 10000
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.fast_shutdown = 1

; Memory
memory_limit = 256M
max_execution_time = 30
max_input_time = 60

; File uploads
file_uploads = On
upload_max_filesize = 10M
max_file_uploads = 20
post_max_size = 10M

; Session
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = "Lax"
session.use_strict_mode = 1
session.gc_maxlifetime = 7200

; Security
allow_url_fopen = Off
allow_url_include = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen,curl_exec,curl_multi_exec,parse_ini_file,show_source
```

### 3. Nginx Configuration

```nginx
# /etc/nginx/sites-available/express-php
server {
    listen 80;
    server_name api.meuapp.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name api.meuapp.com;

    root /var/www/express-php/public;
    index index.php;

    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/api.meuapp.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.meuapp.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;

    # Security Headers
    add_header X-Frame-Options "DENY" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;

    # Rate Limiting
    limit_req_zone $binary_remote_addr zone=api:10m rate=60r/m;
    limit_req zone=api burst=100 nodelay;

    # Compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types text/plain text/css text/xml text/javascript application/json application/javascript application/xml+rss;

    # Main location
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP handling
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;

        # Security
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    # Static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }

    # Deny access to sensitive files
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    location ~ /(vendor|tests|storage|logs|config)/ {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Monitoring endpoints
    location = /health {
        access_log off;
        allow 127.0.0.1;
        allow 10.0.0.0/8;
        allow 172.16.0.0/12;
        allow 192.168.0.0/16;
        deny all;
    }
}
```

### 4. Backup e Disaster Recovery

```bash
#!/bin/bash
# scripts/backup.sh

# Configurações
BACKUP_DIR="/backups/express-php"
DB_NAME="express_app"
APP_DIR="/var/www/express-php"
DATE=$(date +%Y%m%d_%H%M%S)

# Criar diretório de backup
mkdir -p "$BACKUP_DIR/$DATE"

# Backup do banco de dados
mysqldump -u root -p$DB_PASSWORD $DB_NAME > "$BACKUP_DIR/$DATE/database.sql"

# Backup dos arquivos da aplicação
tar -czf "$BACKUP_DIR/$DATE/application.tar.gz" -C "$APP_DIR" .

# Backup de logs importantes
cp -r /var/log/nginx "$BACKUP_DIR/$DATE/nginx_logs"
cp -r /var/log/php "$BACKUP_DIR/$DATE/php_logs"

# Manter apenas os últimos 30 dias
find "$BACKUP_DIR" -type d -mtime +30 -exec rm -rf {} +

# Upload para S3 (opcional)
if [ -n "$AWS_S3_BUCKET" ]; then
    aws s3 sync "$BACKUP_DIR/$DATE" "s3://$AWS_S3_BUCKET/backups/express-php/$DATE"
fi

echo "Backup completed: $BACKUP_DIR/$DATE"
```

---

## 📈 Análise e Relatórios

### 1. Dashboard de Métricas

```php
// routes/dashboard.php
$app->get('/dashboard/metrics', function($req, $res) {
    $metrics = [
        'requests' => [
            'total' => $this->getMetric('requests_total'),
            'last_24h' => $this->getMetric('requests_24h'),
            'success_rate' => $this->getMetric('success_rate'),
            'avg_response_time' => $this->getMetric('avg_response_time')
        ],
        'authentication' => [
            'jwt_tokens_issued' => $this->getMetric('jwt_issued'),
            'failed_logins' => $this->getMetric('failed_logins'),
            'active_sessions' => $this->getMetric('active_sessions')
        ],
        'security' => [
            'blocked_requests' => $this->getMetric('blocked_requests'),
            'rate_limited' => $this->getMetric('rate_limited'),
            'suspicious_activity' => $this->getMetric('suspicious_activity')
        ],
        'performance' => [
            'memory_usage' => $this->getSystemMetric('memory'),
            'cpu_usage' => $this->getSystemMetric('cpu'),
            'disk_usage' => $this->getSystemMetric('disk')
        ]
    ];

    $res->json($metrics);
});
```

### 2. Log Analysis Script

```php
// scripts/analyze_logs.php
<?php
require_once '../vendor/autoload.php';

class LogAnalyzer
{
    public function analyzeMetrics($logFile)
    {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES);
        $metrics = [];

        foreach ($lines as $line) {
            $data = json_decode($line, true);
            if (!$data) continue;

            $hour = date('H', strtotime($data['timestamp']));

            // Requests por hora
            $metrics['requests_per_hour'][$hour] = ($metrics['requests_per_hour'][$hour] ?? 0) + 1;

            // Tempo de resposta médio
            $metrics['response_times'][] = $data['execution_time'];

            // Status codes
            $status = $data['status_code'];
            $metrics['status_codes'][$status] = ($metrics['status_codes'][$status] ?? 0) + 1;

            // Endpoints mais acessados
            $path = $data['path'];
            $metrics['popular_endpoints'][$path] = ($metrics['popular_endpoints'][$path] ?? 0) + 1;
        }

        // Calcular médias e estatísticas
        $metrics['avg_response_time'] = array_sum($metrics['response_times']) / count($metrics['response_times']);
        $metrics['success_rate'] = ($metrics['status_codes']['200'] ?? 0) / array_sum($metrics['status_codes']) * 100;

        // Ordenar endpoints por popularidade
        arsort($metrics['popular_endpoints']);
        $metrics['top_endpoints'] = array_slice($metrics['popular_endpoints'], 0, 10, true);

        return $metrics;
    }

    public function generateReport($metrics)
    {
        $report = "# Relatório de Análise - " . date('Y-m-d H:i:s') . "\n\n";

        $report .= "## Performance\n";
        $report .= "- Tempo de resposta médio: " . round($metrics['avg_response_time'], 2) . "ms\n";
        $report .= "- Taxa de sucesso: " . round($metrics['success_rate'], 2) . "%\n\n";

        $report .= "## Endpoints Mais Acessados\n";
        foreach ($metrics['top_endpoints'] as $endpoint => $count) {
            $report .= "- {$endpoint}: {$count} requests\n";
        }

        $report .= "\n## Distribuição de Status Codes\n";
        foreach ($metrics['status_codes'] as $code => $count) {
            $report .= "- {$code}: {$count}\n";
        }

        return $report;
    }
}

// Executar análise
$analyzer = new LogAnalyzer();
$metrics = $analyzer->analyzeMetrics('logs/metrics.log');
$report = $analyzer->generateReport($metrics);

echo $report;
file_put_contents('reports/daily_report_' . date('Y-m-d') . '.md', $report);
```

---

## 🔧 Manutenção e Troubleshooting

### 1. Scripts de Manutenção

```bash
#!/bin/bash
# scripts/maintenance.sh

echo "🔧 Iniciando manutenção do Express PHP..."

# Limpeza de logs antigos
find logs/ -name "*.log" -mtime +30 -delete
echo "✅ Logs antigos removidos"

# Limpeza de cache
php scripts/clear_cache.php
echo "✅ Cache limpo"

# Otimização do banco de dados
mysql -u root -p$DB_PASSWORD -e "OPTIMIZE TABLE users, api_keys, sessions;"
echo "✅ Banco otimizado"

# Verificação de segurança
php scripts/security_check.php
echo "✅ Verificação de segurança concluída"

# Backup automático
./scripts/backup.sh
echo "✅ Backup realizado"

echo "🎉 Manutenção concluída!"
```

### 2. Health Check Avançado

```php
// scripts/health_check.php
<?php
require_once '../vendor/autoload.php';

class HealthChecker
{
    public function checkAll()
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'filesystem' => $this->checkFilesystem(),
            'memory' => $this->checkMemory(),
            'disk_space' => $this->checkDiskSpace(),
            'ssl_certificate' => $this->checkSSL(),
            'external_services' => $this->checkExternalServices()
        ];

        $allHealthy = array_reduce($checks, function($carry, $check) {
            return $carry && $check['status'] === 'ok';
        }, true);

        return [
            'overall_status' => $allHealthy ? 'healthy' : 'unhealthy',
            'timestamp' => date('Y-m-d H:i:s'),
            'checks' => $checks
        ];
    }

    private function checkDatabase()
    {
        try {
            $pdo = new PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
                $_ENV['DB_USER'],
                $_ENV['DB_PASS']
            );

            $stmt = $pdo->query('SELECT 1');
            $result = $stmt->fetch();

            return [
                'status' => 'ok',
                'message' => 'Database connection successful',
                'response_time' => $this->measureTime(function() use ($pdo) {
                    $pdo->query('SELECT 1');
                })
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }
    }

    private function checkSSL()
    {
        $domain = $_ENV['APP_DOMAIN'] ?? 'localhost';

        try {
            $context = stream_context_create(['ssl' => ['capture_peer_cert' => true]]);
            $socket = stream_socket_client("ssl://{$domain}:443", $errno, $errstr, 30, STREAM_CLIENT_CONNECT, $context);

            if (!$socket) {
                return ['status' => 'error', 'message' => 'SSL connection failed'];
            }

            $cert = stream_context_get_params($socket)['options']['ssl']['peer_certificate'];
            $certInfo = openssl_x509_parse($cert);

            $expiryDate = $certInfo['validTo_time_t'];
            $daysToExpiry = ceil(($expiryDate - time()) / 86400);

            if ($daysToExpiry < 30) {
                return [
                    'status' => 'warning',
                    'message' => "SSL certificate expires in {$daysToExpiry} days"
                ];
            }

            return [
                'status' => 'ok',
                'message' => 'SSL certificate is valid',
                'expires_in_days' => $daysToExpiry
            ];
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'SSL check failed: ' . $e->getMessage()
            ];
        }
    }

    private function measureTime($callback)
    {
        $start = microtime(true);
        $callback();
        return round((microtime(true) - $start) * 1000, 2);
    }
}

// Executar verificação
$checker = new HealthChecker();
$health = $checker->checkAll();

echo json_encode($health, JSON_PRETTY_PRINT) . "\n";

// Alertar se não estiver saudável
if ($health['overall_status'] !== 'healthy') {
    // Enviar notificação
    error_log("HEALTH CHECK FAILED: " . json_encode($health));

    // Webhook para Slack/Discord
    if (isset($_ENV['ALERT_WEBHOOK'])) {
        $payload = [
            'text' => '🚨 Health Check Failed',
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        [
                            'title' => 'Status',
                            'value' => $health['overall_status'],
                            'short' => true
                        ],
                        [
                            'title' => 'Failed Checks',
                            'value' => implode(', ', array_keys(array_filter($health['checks'], function($check) {
                                return $check['status'] !== 'ok';
                            }))),
                            'short' => false
                        ]
                    ]
                ]
            ]
        ];

        $ch = curl_init($_ENV['ALERT_WEBHOOK']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_exec($ch);
        curl_close($ch);
    }

    exit(1);
}

exit(0);
```

---

**Agora você tem um microframework PHP completo e pronto para produção!** 🚀
