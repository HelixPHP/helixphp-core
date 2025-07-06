# Express PHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/PSR--12%20%2F%20PSR--15-compliant-brightgreen)](https://www.php-fig.org/psr/psr-12/)
[![GitHub Issues](https://img.shields.io/github/issues/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/issues)
[![GitHub Stars](https://img.shields.io/github/stars/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/stargazers)

---

## 🚀 O que é o Express PHP?

**Express PHP** é um microframework moderno, leve e seguro, inspirado no Express.js, para construir APIs e aplicações web de alta performance em PHP. Foco em produtividade, arquitetura desacoplada e extensibilidade real.

- **Alta Performance**: 2.57M ops/sec em CORS, 2.27M ops/sec em Response, 757K ops/sec roteamento, cache integrado.
- **Arquitetura Moderna**: DI Container, Service Providers, Event System, Extension System e PSR-15.
- **Segurança**: Middlewares robustos para CSRF, XSS, Rate Limiting, JWT, API Key e mais.
- **Extensível**: Sistema de plugins, hooks, providers e integração PSR-14.
- **Qualidade**: 270+ testes, PHPStan Level 9, PSR-12, cobertura completa.

---

## ✨ Principais Recursos

- 🏗️ **DI Container & Providers**
- 🎪 **Event System**
- 🧩 **Sistema de Extensões**
- 🔧 **Configuração flexível**
- 🔐 **Autenticação Multi-método**
- 🛡️ **Segurança Avançada**
- 📡 **Streaming & SSE**
- 📚 **OpenAPI/Swagger**
- ⚡ **Performance**
- 🧪 **Qualidade e Testes**

---

## 💡 Casos de Uso & Insights

- APIs RESTful de alta performance
- Gateways de autenticação JWT/API Key
- Microsserviços e aplicações desacopladas
- Sistemas extensíveis com plugins e hooks
- Plataformas que exigem segurança e performance

Veja exemplos práticos em [`examples/`](examples/), benchmarks reais em [`benchmarks/`](benchmarks/) e [relatório de performance completo](docs/performance/PERFORMANCE_REPORT_v2.1.3.md).

---

## 🚀 Início Rápido

### Instalação

```bash
composer require cafernandes/express-php
```

### Exemplo Básico

```php
<?php
require_once 'vendor/autoload.php';

use Express\Core\Application;
use Express\Http\Psr15\Middleware\{SecurityMiddleware, CorsMiddleware, AuthMiddleware};

$app = new Application();

// Middlewares de segurança (PSR-15)
$app->use(new SecurityMiddleware());
$app->use(new CorsMiddleware());
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt'],
    'jwtSecret' => 'sua_chave_secreta'
]));

// API RESTful
$app->get('/api/users', function($req, $res) {
    $res->json(['users' => $userService->getAll()]);
});

$app->post('/api/users', function($req, $res) {
    $user = $userService->create($req->body);
    $res->status(201)->json(['user' => $user]);
});

$app->run();
```

---

## 📚 Documentação Completa

Acesse o [Índice da Documentação](docs/index.md) para navegar por todos os guias técnicos, exemplos, referências de API, middlewares, autenticação, performance e mais.

Principais links:
- [Guia de Implementação Básica](docs/implementions/usage_basic.md)
- [Guia com Middlewares Prontos](docs/implementions/usage_with_middleware.md)
- [Guia de Middleware Customizado](docs/implementions/usage_with_custom_middleware.md)
- [Referência Técnica](docs/techinical/application.md)
- [Performance e Benchmarks](docs/performance/benchmarks/)

---

## 🤝 Como Contribuir

Quer ajudar a evoluir o Express PHP? Veja o [Guia de Contribuição](CONTRIBUTING.md) ou acesse [`docs/contributing/`](docs/contributing/) para saber como abrir issues, enviar PRs ou criar extensões.

---

## 📄 Licença

Este projeto está licenciado sob a Licença MIT. Veja o arquivo [LICENSE](LICENSE) para detalhes.

---

*Desenvolvido com ❤️ para a comunidade PHP*
