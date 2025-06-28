# Express PHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%208-brightgreen.svg)](https://phpstan.org/)
[![GitHub Issues](https://img.shields.io/github/issues/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/issues)
[![GitHub Stars](https://img.shields.io/github/stars/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/stargazers)

> 📖 **Complete v2.0.1 Guide**: See [FRAMEWORK_OVERVIEW_v2.0.1.md](FRAMEWORK_OVERVIEW_v2.0.1.md) for comprehensive documentation with performance metrics and advanced optimizations

**Express PHP** é um microframework leve, rápido e seguro inspirado no Express.js para construir aplicações web modernas e APIs em PHP com arquitetura moderna baseada em **Dependency Injection Container**.

> ⚡ **Alta Performance**: +47M ops/sec em CORS, +20M ops/sec em Response, cache integrado e roteamento otimizado!
> 🏗️ **Arquitetura v3.0**: DI Container, Service Providers e Event System integrados!

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
use Express\Middleware\Security\{SecurityMiddleware, CorsMiddleware, AuthMiddleware};

$app = new Application();

// Middlewares de segurança
$app->use(new SecurityMiddleware());
$app->use(new CorsMiddleware());
$app->use(AuthMiddleware::jwt('sua_chave_secreta'));

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

## ✨ Principais Recursos

- 🏗️ **Arquitetura Moderna**: Dependency Injection Container e Service Providers
- 🎪 **Event System**: Sistema de eventos nativo para extensibilidade
- 🔧 **Configuration Management**: Configuração robusta via arquivos e código
- 🔐 **Autenticação Multi-método**: JWT, Basic Auth, Bearer Token, API Key
- 🛡️ **Segurança Avançada**: CSRF, XSS, Rate Limiting, Headers de Segurança
- 📡 **Streaming**: Server-Sent Events, Upload de arquivos grandes
- 📚 **OpenAPI/Swagger**: Documentação automática de APIs
- ⚡ **Performance**: Cache integrado, pipeline otimizado de middlewares
- 🧪 **Qualidade**: 245+ testes, PHPStan Level 8, PSR-12

## 📊 Performance Benchmarks

| Operação | Ops/segundo | Tempo médio |
|----------|-------------|-------------|
| CORS Headers | 47.6M+ | 0.02 μs |
| Response Creation | 20.3M+ | 0.05 μs |
| Route Matching | 2.8M+ | 0.36 μs |
| Middleware Execution | 2.0M+ | 0.49 μs |
| App Initialization | 579K+ | 1.72 μs |

> 📋 **[Ver relatório completo](docs/implementation/COMPREHENSIVE_PERFORMANCE_SUMMARY_2025-06-27.md)**

## 🛡️ Sistema de Autenticação

```php
// JWT simples
$app->use(AuthMiddleware::jwt('chave_secreta'));

// Múltiplos métodos
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => 'chave_jwt',
    'routes' => ['/api/*'], // proteger apenas /api/*
    'except' => ['/api/public'] // exceto rotas públicas
]));

// Acesso aos dados do usuário
$app->get('/profile', function($req, $res) {
    $user = $req->user; // dados do usuário autenticado
    $res->json(['profile' => $user]);
});
```

## 📖 Documentação

- **[🚀 Guia de Início Rápido](docs/guides/QUICK_START_GUIDE.md)** - Setup em 5 minutos
- **[📚 Documentação Completa](docs/DOCUMENTATION_INDEX.md)** - Índice completo
- **[🔐 Sistema de Autenticação](docs/pt-br/AUTH_MIDDLEWARE.md)** - Guia detalhado
- **[🛡️ Segurança](docs/guides/SECURITY_IMPLEMENTATION.md)** - Implementação segura
- **[📡 Streaming](docs/pt-br/STREAMING.md)** - Server-Sent Events
- **[🔧 Pre-commit Hooks](docs/guides/PRECOMMIT_SETUP.md)** - Validação de qualidade

## 🎯 Exemplos Práticos

| Exemplo | Descrição |
|---------|-----------|
| **[⭐ Básico](examples/example_basic.php)** | API REST e conceitos fundamentais |
| **[🔐 Auth Completo](examples/example_auth.php)** | Sistema completo de autenticação |
| **[🔑 Auth Simples](examples/example_auth_simple.php)** | JWT básico e controle de acesso |
| **[🛡️ Middlewares](examples/example_middleware.php)** | CORS, rate limiting, validação |
| **[📚 OpenAPI](examples/example_openapi_docs.php)** | Swagger UI automático |
| **[🚀 App Completo](examples/example_complete_optimizations.php)** | Aplicação com todos os recursos |

## 🔧 Desenvolvimento e Qualidade

### Validação Pre-commit

```bash
# Instalar hooks de qualidade
composer run precommit:install

# Testar validações
composer run precommit:test

# Verificar qualidade do código
composer run quality:check
```

### Scripts Disponíveis

```bash
composer test           # Executar testes
composer phpstan        # Análise estática
composer cs:check       # Verificar PSR-12
composer cs:fix         # Corrigir PSR-12
composer benchmark      # Executar benchmarks
```

## 🛠️ Middlewares Inclusos

| Middleware | Descrição |
|------------|-----------|
| **SecurityMiddleware** | Headers de segurança (XSS, CSRF, etc.) |
| **CorsMiddleware** | Cross-Origin Resource Sharing |
| **AuthMiddleware** | Autenticação multi-método |
| **RateLimitMiddleware** | Controle de taxa de requisições |
| **ValidationMiddleware** | Validação de dados de entrada |

## 📊 Status do Projeto

- ✅ **Modernização Completa**: PHP 8.1+, tipagem strict, otimizações
- ✅ **Qualidade de Código**: PHPStan Level 8, PSR-12, pre-commit hooks
- ✅ **Performance**: Benchmarks otimizados, cache integrado
- ✅ **Segurança**: Middlewares de segurança, autenticação robusta
- ✅ **Documentação**: Guias completos, exemplos práticos
- ✅ **Testes**: 245+ testes, cobertura completa

## 🤝 Contribuindo

1. Fork o projeto
2. Crie sua feature branch (`git checkout -b feature/nova-feature`)
3. Configure os hooks: `composer run precommit:install`
4. Commit suas mudanças (`git commit -m 'Add: nova feature'`)
5. Push para a branch (`git push origin feature/nova-feature`)
6. Abra um Pull Request

## 📄 Licença

Este projeto está licenciado sob a Licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.

## 🔗 Links Úteis

- **[Documentação](docs/DOCUMENTATION_INDEX.md)** - Documentação completa
- **[Exemplos](examples/)** - Códigos de exemplo
- **[Benchmarks](benchmarks/)** - Testes de performance
- **[Issues](https://github.com/CAFernandes/express-php/issues)** - Reportar problemas
- **[Releases](https://github.com/CAFernandes/express-php/releases)** - Versões disponíveis

---

*Desenvolvido com ❤️ para a comunidade PHP*
