# Express-PHP Framework - Versão Modular 2.0

Framework PHP moderno inspirado no Express.js, agora com arquitetura totalmente modularizada.

## 🚀 Características Principais

- **Arquitetura Modular**: Sistema organizado em módulos independentes
- **Injeção de Dependência**: Container IoC integrado
- **Middleware Pipeline**: Sistema robusto de middlewares
- **Roteamento Avançado**: Suporte a parâmetros, grupos e sub-routers
- **Streaming HTTP**: Server-Sent Events e streaming de dados
- **Segurança Integrada**: CORS, XSS, CSRF, Rate Limiting, JWT
- **Documentação Automática**: Geração automática de OpenAPI
- **PSR-4 Compliant**: Autoloading moderno

## 📁 Arquitetura Modular

```
src/
├── Core/                    # Núcleo do framework
│   ├── Application.php      # Aplicação principal
│   ├── Container.php        # Container de injeção de dependência
│   └── Config.php          # Gerenciamento de configuração
├── Http/                    # Camada HTTP
│   ├── Request.php          # Requisições HTTP
│   ├── Response.php         # Respostas HTTP
│   └── HeaderRequest.php    # Manipulação de headers
├── Routing/                 # Sistema de roteamento
│   ├── Router.php           # Roteador principal
│   ├── Route.php            # Representação de rota
│   └── RouteCollection.php  # Coleção de rotas
├── Middleware/              # Middlewares
│   ├── Core/               # Middlewares fundamentais
│   └── Security/           # Middlewares de segurança
├── Authentication/          # Sistema de autenticação
│   └── JWTHelper.php       # Utilitários JWT
├── Utils/                   # Utilitários
│   ├── Arr.php             # Manipulação de arrays
│   └── Utils.php           # Funções auxiliares
└── Exceptions/              # Exceções customizadas
    └── HttpException.php    # Exceções HTTP
```

## 🛠️ Instalação

```bash
composer install
```

## 📚 Exemplos de Uso

### Aplicação Básica

```php
<?php
require_once 'vendor/autoload.php';

use Express\ApiExpress;
use Express\Middleware\Security\CorsMiddleware;

$app = new ApiExpress('http://localhost:8000');

// Middleware CORS
$app->use(CorsMiddleware::development());

// Rota básica
$app->get('/', function($req, $res) {
    return $res->json(['message' => 'Hello Express-PHP!']);
});

// Rota com parâmetros
$app->get('/users/:id', function($req, $res) {
    $userId = $req->param('id');
    return $res->json(['user' => ['id' => $userId]]);
});

$app->listen(8000);
```

### Segurança Avançada

```php
use Express\Middleware\Security\AuthMiddleware;
use Express\Middleware\Security\SecurityMiddleware;
use Express\Middleware\Security\XssMiddleware;
use Express\Middleware\Security\CsrfMiddleware;

// Headers de segurança
$app->use(new SecurityMiddleware());

// Proteção XSS
$app->use(new XssMiddleware(['sanitizeInput' => true]));

// Autenticação JWT
$jwtAuth = AuthMiddleware::jwt('sua_chave_secreta');
$app->use('/api', $jwtAuth);

// Proteção CSRF
$app->use('/sensitive', new CsrfMiddleware());
```

### Streaming HTTP

```php
// Server-Sent Events
$app->get('/events', function($req, $res) {
    $res->startStream('text/event-stream');

    for ($i = 1; $i <= 10; $i++) {
        $res->sendEvent([
            'data' => ['message' => "Update #$i"],
            'event' => 'update'
        ]);
        sleep(1);
    }

    $res->endStream();
});

// Streaming de JSON
$app->get('/stream/data', function($req, $res) {
    $res->startStream('application/json');
    $res->write('{"items":[');

    for ($i = 1; $i <= 1000; $i++) {
        if ($i > 1) $res->write(',');
        $res->writeJson(['id' => $i, 'data' => "Item $i"]);
    }

    $res->write(']}');
    $res->endStream();
});
```

## 🔒 Middlewares de Segurança

### CORS (Cross-Origin Resource Sharing)
```php
$corsMiddleware = new CorsMiddleware([
    'origin' => ['https://meuapp.com'],
    'methods' => ['GET', 'POST'],
    'credentials' => true
]);
```

### Autenticação JWT
```php
$jwtAuth = AuthMiddleware::jwt('chave_secreta');
$basicAuth = AuthMiddleware::basic($validationCallback);
$customAuth = AuthMiddleware::custom($customCallback);
```

### Proteção XSS
```php
// Detectar XSS
$hasXss = XssMiddleware::containsXss($input);

// Sanitizar conteúdo
$clean = XssMiddleware::sanitize($input, '<p><strong>');

// Limpar URLs
$safeUrl = XssMiddleware::cleanUrl($url);
```

### Proteção CSRF
```php
// Gerar token
$token = CsrfMiddleware::getToken();

// Campo HTML
$field = CsrfMiddleware::hiddenField();

// Meta tag
$meta = CsrfMiddleware::metaTag();
```

### Rate Limiting
```php
$rateLimiter = new RateLimitMiddleware([
    'windowMs' => 60000,      // 1 minuto
    'maxRequests' => 100,     // 100 requisições
    'message' => 'Limite excedido'
]);
```

## 📝 Exemplos Disponíveis

| Arquivo | Descrição |
|---------|-----------|
| `example_modular.php` | Aplicação básica modular |
| `example_security_new.php` | Demonstração completa de segurança |
| `example_streaming_new.php` | Streaming HTTP e SSE |
| `example_auth.php` | Sistema de autenticação |
| `app.php` | Aplicação completa integrada |

## 🧪 Executar Exemplos

```bash
# Servidor de desenvolvimento
cd examples
php -S localhost:8000 example_modular.php

# Exemplo de segurança
php -S localhost:8000 example_security_new.php

# Exemplo de streaming
php -S localhost:8000 example_streaming_new.php
```

## ✅ Testes

```bash
# Executar todos os testes
./vendor/bin/phpunit

# Testes específicos
./vendor/bin/phpunit tests/Security/
./vendor/bin/phpunit tests/Core/
```

### Status dos Testes

- ✅ **208 testes** executados
- ✅ **Middlewares de segurança**: 100% funcionais
- ✅ **Sistema de roteamento**: Totalmente operacional
- ✅ **HTTP streaming**: Implementado e testado
- ✅ **Autenticação JWT**: Completa e segura

## 🔧 Configuração

### Container de Injeção de Dependência

```php
use Express\Core\Container;

$container = new Container();
$container->bind('database', function() {
    return new PDO('sqlite:app.db');
});

$db = $container->resolve('database');
```

### Configuração Global

```php
use Express\Core\Config;

Config::set('app.name', 'Minha Aplicação');
Config::set('jwt.secret', 'chave_super_secreta');

$appName = Config::get('app.name');
```

## 🚀 Recursos Avançados

### Grupos de Rotas

```php
$api = $app->Router();
$api->get('/users', $userController);
$api->post('/users', $userController);

$app->use('/api/v1', $api);
```

### Middleware Customizado

```php
$customMiddleware = function($req, $res, $next) {
    // Lógica do middleware
    error_log("Request: {$req->method} {$req->path}");
    return $next($req, $res);
};

$app->use($customMiddleware);
```

### Documentação OpenAPI

```php
use Express\Utils\OpenApiExporter;

// Gerar documentação automática
$openapi = OpenApiExporter::export('Express\Routing\Router');
file_put_contents('api-docs.json', json_encode($openapi, JSON_PRETTY_PRINT));
```

## 📖 Migração da Versão 1.x

A versão 2.0 introduz mudanças significativas na arquitetura:

### Namespaces Atualizados
```php
// Antes (v1.x)
use Express\Controller\Router;
use Express\Middlewares\Security\AuthMiddleware;

// Agora (v2.x)
use Express\Routing\Router;
use Express\Middleware\Security\AuthMiddleware;
```

### Nova Estrutura de Arquivos
- `SRC/` → `src/`
- Organização modular por funcionalidade
- PSR-4 autoloading completo

## 📄 Licença

MIT License - veja [LICENSE](LICENSE) para detalhes.

## 🤝 Contribuindo

1. Fork o projeto
2. Crie uma branch para sua feature
3. Commit suas mudanças
4. Push para a branch
5. Abra um Pull Request

## 📞 Suporte

- 📧 Email: suporte@express-php.com
- 💬 Issues: [GitHub Issues](https://github.com/express-php/express-php/issues)
- 📚 Documentação: [Wiki](https://github.com/express-php/express-php/wiki)

---

**Express-PHP 2.0** - Framework PHP moderno, seguro e performático. 🚀
