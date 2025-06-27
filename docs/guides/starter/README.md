# 🚀 Guia de Início - Express PHP

Este guia irá te ajudar a começar com o Express PHP em poucos minutos!

## 📋 Pré-requisitos

- PHP 8.0 ou superior
- Composer
- Servidor web (Apache, Nginx) ou PHP built-in server

## 🛠️ Instalação

### 1. Criar um novo projeto

```bash
# Criar diretório do projeto
mkdir meu-app-express
cd meu-app-express

# Instalar Express PHP via Composer
composer require cafernandes/express-php
```

### 2. Estrutura do projeto recomendada

```
meu-app-express/
├── vendor/                 # Dependências do Composer
├── public/                 # Arquivos públicos
│   └── index.php          # Ponto de entrada da aplicação
├── app/                   # Código da aplicação
│   ├── Controllers/       # Controladores
│   ├── Middlewares/       # Middlewares customizados
│   └── Models/           # Modelos
├── config/               # Configurações
├── .env                  # Variáveis de ambiente
└── composer.json         # Dependências do projeto
```

## 🎯 Primeiro App - Hello World

### 1. Criar o arquivo principal

Crie `public/index.php`:

```php
<?php
require_once '../vendor/autoload.php';

use Express\ApiExpress;

$app = new ApiExpress();

// Rota básica
$app->get('/', function($req, $res) {
    $res->json(['message' => 'Olá Express PHP!']);
});

// Iniciar servidor
$app->run();
```

### 2. Testar a aplicação

```bash
# Navegar para a pasta public
cd public

# Iniciar servidor PHP built-in
php -S localhost:8000

# Acessar no navegador: http://localhost:8000
```

## 🛡️ Adicionando Segurança

### 1. App com middlewares de segurança

```php
<?php
require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Core\CorsMiddleware;

$app = new ApiExpress();

// Middleware de segurança (CSRF, XSS, Headers)
$app->use(SecurityMiddleware::create());

// CORS para APIs
$app->use(new CorsMiddleware([
    'origin' => ['http://localhost:3000', 'https://meuapp.com'],
    'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
    'allowedHeaders' => ['Content-Type', 'Authorization']
]));

// Rotas protegidas
$app->get('/', function($req, $res) {
    $res->json(['message' => 'API segura funcionando!']);
});

$app->post('/api/data', function($req, $res) {
    // Dados automaticamente sanitizados pelo SecurityMiddleware
    $data = $req->body;
    $res->json(['received' => $data]);
});

$app->run();
```

## 🔐 Sistema de Autenticação

### 1. Configuração JWT

```php
<?php
require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\AuthMiddleware;

$app = new ApiExpress();

// Rota de login (sem autenticação)
$app->post('/login', function($req, $res) {
    $username = $req->body['username'] ?? '';
    $password = $req->body['password'] ?? '';

    // Validar credenciais (implementar sua lógica)
    if ($username === 'admin' && $password === 'senha123') {
        $token = AuthMiddleware::generateJWT([
            'user_id' => 1,
            'username' => $username,
            'role' => 'admin'
        ], 'sua_chave_secreta');

        $res->json(['token' => $token]);
    } else {
        $res->status(401)->json(['error' => 'Credenciais inválidas']);
    }
});

// Middleware de autenticação para rotas protegidas
$app->use('/api', AuthMiddleware::jwt('sua_chave_secreta'));

// Rotas protegidas
$app->get('/api/profile', function($req, $res) {
    $user = $req->user; // Dados do usuário do token JWT
    $res->json(['user' => $user]);
});

$app->get('/api/admin', function($req, $res) {
    $user = $req->user;

    if ($user['role'] !== 'admin') {
        return $res->status(403)->json(['error' => 'Acesso negado']);
    }

    $res->json(['message' => 'Área administrativa']);
});

$app->run();
```

### 2. Testando a autenticação

```bash
# 1. Fazer login para obter token
curl -X POST http://localhost:8000/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"senha123"}'

# Resposta: {"token":"eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."}

# 2. Usar token nas rotas protegidas
curl -X GET http://localhost:8000/api/profile \
  -H "Authorization: Bearer SEU_TOKEN_AQUI"
```

## 📊 CRUD Completo

### 1. API de usuários

```php
<?php
require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Security\AuthMiddleware;

$app = new ApiExpress();

// Middlewares globais
$app->use(SecurityMiddleware::create());

// Simulação de banco de dados
$users = [
    ['id' => 1, 'name' => 'João', 'email' => 'joao@email.com'],
    ['id' => 2, 'name' => 'Maria', 'email' => 'maria@email.com']
];

// Rotas públicas
$app->post('/login', function($req, $res) {
    // Lógica de login...
    $token = AuthMiddleware::generateJWT(['user_id' => 1], 'chave_secreta');
    $res->json(['token' => $token]);
});

// Aplicar autenticação às rotas /api/*
$app->use('/api', AuthMiddleware::jwt('chave_secreta'));

// CRUD de usuários
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
            $user['name'] = $data['name'] ?? $user['name'];
            $user['email'] = $data['email'] ?? $user['email'];
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

## 📚 Documentação Automática

### 1. Habilitando OpenAPI/Swagger

```php
<?php
require_once '../vendor/autoload.php';

use Express\ApiExpress;
use Express\Middlewares\Core\OpenApiDocsMiddleware;

$app = new ApiExpress();

// Configurar documentação automática
$app->use('/docs', new OpenApiDocsMiddleware([
    'title' => 'Minha API',
    'version' => '1.0.0',
    'description' => 'API construída com Express PHP'
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

$app->run();

// Acesse: http://localhost:8000/docs para ver a documentação
```

## 🎯 Próximos Passos

1. **[Exemplos Avançados](../../examples/)** - Explore exemplos mais complexos
2. **[Middlewares](../SECURITY_IMPLEMENTATION.md)** - Aprenda sobre middlewares
3. **[Autenticação](../../pt-br/AUTH_MIDDLEWARE.md)** - Guia completo de autenticação
4. **[Deploy](../PUBLISHING_GUIDE.md)** - Como fazer deploy da aplicação

## 🐛 Problemas Comuns

### Erro 404 em todas as rotas

Certifique-se de que o servidor está configurado para enviar todas as requisições para `index.php`:

**Apache (.htaccess):**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]
```

**Nginx:**
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### Problemas de CORS

Use o middleware CORS:
```php
$app->use(new CorsMiddleware([
    'origin' => '*',
    'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS']
]));
```

## 💡 Dicas de Performance

1. **Use cache de rotas** em produção
2. **Configure OPcache** no PHP
3. **Use CDN** para arquivos estáticos
4. **Monitore** com APM tools

---

**🎉 Parabéns!** Você agora tem uma base sólida para desenvolver com Express PHP.

Para mais informações, consulte a **[documentação completa](../../README.md)**.
