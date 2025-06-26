# Express PHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](https://phpstan.org/)
[![GitHub Issues](https://img.shields.io/github/issues/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/issues)
[![GitHub Stars](https://img.shields.io/github/stars/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/stargazers)

**Express PHP** é um microframework leve, rápido e seguro inspirado no Express.js para construir aplicações web modernas e APIs em PHP com sistema nativo de autenticação multi-método.

> 🔐 **Novo na v1.0**: Sistema completo de autenticação com JWT, Basic Auth, Bearer Token, API Key e auto-detecção!

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
use Express\Middlewares\Security\SecurityMiddleware;
use Express\Middlewares\Core\CorsMiddleware;

$app = new ApiExpress();

// Aplicar middlewares de segurança
$app->use(SecurityMiddleware::create());
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
- � **Streaming**: Suporte completo para streaming de dados, SSE e arquivos grandes
- �📚 **Documentação OpenAPI/Swagger**: Geração automática de documentação
- 🎯 **Middlewares Modulares**: Sistema flexível de middlewares
- ⚡ **Performance**: Otimizado para alta performance
- 🧪 **Testado**: 186+ testes unitários e 100% de cobertura de código
- 📊 **Análise Estática**: PHPStan Level 8 compliance

## 📖 Documentação

- **[🚀 Guia de Início](docs/guides/starter/README.md)** - Comece aqui!
- **[📚 Documentação Completa](docs/README.md)** - Documentação detalhada
- **[🔐 Sistema de Autenticação](docs/pt-br/AUTH_MIDDLEWARE.md)** - Guia de autenticação
- **[� Streaming de Dados](docs/pt-br/STREAMING.md)** - Streaming e Server-Sent Events
- **[�🛡️ Middlewares de Segurança](docs/guides/SECURITY_IMPLEMENTATION.md)** - Segurança
- **[📝 Exemplos Práticos](examples/)** - Exemplos prontos para usar

## 🎯 Exemplos de Aprendizado

O framework inclui exemplos modulares para facilitar o aprendizado:

- **[Usuários](examples/example_user.php)** - Rotas de usuário e autenticação
- **[Produtos](examples/example_product.php)** - CRUD e parâmetros de rota
- **[Upload](examples/example_upload.php)** - Upload de arquivos
- **[Admin](examples/example_admin.php)** - Rotas administrativas
- **[Blog](examples/example_blog.php)** - Sistema de blog
- **[Streaming](examples/example_streaming.php)** - Streaming de dados e SSE
- **[Segurança](examples/example_security.php)** - Demonstração de middlewares
- **[Completo](examples/example_complete.php)** - Integração de todos os recursos

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

## ⚙️ Requisitos

- **PHP**: 7.4.0 ou superior
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

**🚀 Pronto para começar?** [Siga nosso guia de início rápido](docs/guides/starter/README.md)!
