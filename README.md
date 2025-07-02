# Express PHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/PSR--12%20%2F%20PSR--15-compliant-brightgreen)](https://www.php-fig.org/psr/psr-12/)
[![GitHub Issues](https://img.shields.io/github/issues/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/issues)
[![GitHub Stars](https://img.shields.io/github/stars/CAFernandes/express-php)](https://github.com/CAFernandes/express-php/stargazers)

> 📖 **Guia Completo v2.1.1**: Veja [FRAMEWORK_OVERVIEW_v2.0.1.md](FRAMEWORK_OVERVIEW_v2.0.1.md) para documentação detalhada, métricas de performance e otimizações avançadas

**Express PHP** é um microframework leve, rápido e seguro inspirado no Express.js para construir aplicações web modernas e APIs em PHP com arquitetura baseada em **Dependency Injection Container**.

> ⚡ **Alta Performance**: +52M ops/sec em CORS, +24M ops/sec em Response, cache integrado e roteamento otimizado!
> 🏗️ **Arquitetura Moderna**: DI Container, Service Providers, Event System e Extension System integrados!

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
- 🧩 **Sistema de Extensões**: Plugin system com auto-discovery, hooks e PSR-14
- 🔧 **Configuration Management**: Configuração robusta via arquivos e código
- 🔐 **Autenticação Multi-método**: JWT, Basic Auth, Bearer Token, API Key
- 🛡️ **Segurança Avançada**: CSRF, XSS, Rate Limiting, Headers de Segurança
- 📡 **Streaming**: Server-Sent Events, Upload de arquivos grandes
- 📚 **OpenAPI/Swagger**: Documentação automática de APIs
- ⚡ **Performance**: Cache integrado, pipeline otimizado de middlewares
- 🧪 **Qualidade**: 270+ testes, PHPStan Level 9, PSR-12

## 🧩 Sistema de Extensões v2.1.1

O Express-PHP v2.1.1 possui um sistema robusto de extensões/plugins com auto-discovery, hooks e integração PSR-14:

```php
// Extensão personalizada
class AnalyticsProvider extends ServiceProvider {
    public function register(): void {
        $this->app->singleton('analytics', AnalyticsService::class);
    }
    public function boot(): void {
        $this->app->addAction('request.received', function($context) {
            $this->app->make('analytics')->track('page_view', $context);
        });
    }
}

// Registro manual
$app->registerExtension('analytics', AnalyticsProvider::class);

// Auto-discovery via composer.json
{
    "extra": {
        "express-php": {
            "providers": ["Vendor\\Analytics\\AnalyticsProvider"]
        }
    }
}

// Sistema de hooks (actions e filters)
$app->addAction('user.login', function($context) {
    // Ação executada quando usuário faz login
});
$app->addFilter('response.data', function($data, $context) {
    $data['_meta'] = ['framework' => 'Express-PHP'];
    return $data;
});
```

**Recursos do Sistema de Extensões:**
- 🔍 **Auto-Discovery**: Detecta extensões automaticamente via Composer
- 🎣 **Hook System**: Actions e filters WordPress-style para extensibilidade
- 🏗️ **Service Providers**: Integração nativa com container PSR-11
- 📡 **PSR-14 Events**: Sistema de eventos padronizado
- ⚙️ **Configuration**: Configuração flexível por extensão
- 📊 **Management**: Enable/disable, stats e debugging

> 📚 **[Documentação Completa](docs/EXTENSION_SYSTEM.md)** | **[Exemplo Prático](examples/example_extension_system.php)** | **[Exemplo Avançado](examples/example_advanced_extension.php)**

## 📊 Performance Benchmarks

### Low Load Scenario

| Operação                        | Ops/segundo         |
|---------------------------------|---------------------|
| App Initialization              | 57,205              |
| Basic Route Registration (GET)  | 25,752              |
| Basic Route Registration (POST) | 27,609              |
| Route with Parameters (PUT)     | 24,630              |
| Complex Route Registration      | 18,060              |
| Route Pattern Matching          | 2,557,502           |
| Middleware Stack Creation       | 18,245              |
| Middleware Function Execution   | 2,046,002           |
| Security Middleware Creation    | 23,833              |
| CORS Headers Processing         | 32,263,877          |
| XSS Protection Logic            | 4,369,067           |
| JWT Token Generation            | 253,739             |
| JWT Token Validation            | 228,324             |
| Request Object Creation         | 272,357             |
| Response Object Creation        | 16,777,216          |
| Response JSON Setup (100 items) | 175,788             |
| JSON Encode (Small)             | 9,986,438           |
| JSON Encode (Large - 1000)      | 11,186              |
| JSON Decode (Large - 1000)      | 2,528               |
| CORS Config Processing          | 16,777,216          |
| CORS Headers Generation         | 52,428,800          |

### Normal Load Scenario

| Operação                        | Ops/segundo         |
|---------------------------------|---------------------|
| App Initialization              | 23,183              |
| Basic Route Registration (GET)  | 20,643              |
| Basic Route Registration (POST) | 19,935              |
| Route with Parameters (PUT)     | 10,732              |
| Complex Route Registration      | 21,691              |
| Route Pattern Matching          | 1,968,233           |
| Middleware Stack Creation       | 21,905              |
| Middleware Function Execution   | 2,004,925           |
| Security Middleware Creation    | 22,453              |
| CORS Headers Processing         | 37,117,735          |
| XSS Protection Logic            | 3,334,105           |
| JWT Token Generation            | 230,684             |
| JWT Token Validation            | 148,898             |
| Request Object Creation         | 176,335             |
| Response Object Creation        | 15,887,515          |
| Response JSON Setup (100 items) | 153,638             |
| JSON Encode (Small)             | 10,645,442          |
| JSON Encode (Large - 1000)      | 11,725              |
| JSON Decode (Large - 1000)      | 2,275               |
| CORS Config Processing          | 18,157,160          |
| CORS Headers Generation         | 38,479,853          |

### High Load Scenario

| Operação                        | Ops/segundo         |
|---------------------------------|---------------------|
| App Initialization              | 25,199              |
| Basic Route Registration (GET)  | 20,914              |
| Basic Route Registration (POST) | 23,176              |
| Route with Parameters (PUT)     | 23,356              |
| Complex Route Registration      | 21,879              |
| Route Pattern Matching          | 2,506,906           |
| Middleware Stack Creation       | 20,363              |
| Middleware Function Execution   | 1,465,413           |
| Security Middleware Creation    | 19,362              |
| CORS Headers Processing         | 44,667,774          |
| XSS Protection Logic            | 4,194,723           |
| JWT Token Generation            | 263,483             |
| JWT Token Validation            | 229,211             |
| Request Object Creation         | 268,096             |
| Response Object Creation        | 23,643,202          |
| Response JSON Setup (100 items) | 169,494             |
| JSON Encode (Small)             | 9,981,685           |
| JSON Encode (Large - 1000)      | 9,174               |
| JSON Decode (Large - 1000)      | 2,491               |
| CORS Config Processing          | 17,425,442          |
| CORS Headers Generation         | 47,393,266          |

> 📋 **[Ver relatório completo](benchmarks/reports/COMPREHENSIVE_PERFORMANCE_ANALYSIS.md)**

## 🛡️ Sistema de Autenticação

```php
// JWT simples
$app->use(AuthMiddleware::jwt('chave_secreta'));

// Múltiplos métodos
$app->use(new AuthMiddleware([
    'authMethods' => ['jwt', 'basic', 'apikey'],
    'jwtSecret' => 'chave_jwt',
    'routes' => ['/api/*'],
    'except' => ['/api/public']
]));

// Acesso aos dados do usuário
$app->get('/profile', function($req, $res) {
    $user = $req->user;
    $res->json(['profile' => $user]);
});
```

## 📚 Documentação e Guias

- [Índice da Documentação](docs/DOCUMENTATION_INDEX.md)
- [Guia de Implementação Rápida](docs/guides/QUICK_START_GUIDE.md)
- [Guia de Providers](docs/guides/PROVIDER_IMPLEMENTATION_GUIDE.md)
- [Sistema de Extensões](docs/EXTENSION_SYSTEM.md)
- [Benchmarks e Performance](benchmarks/README.md)
- [Exemplos de Uso](examples/README.md)

## 🎯 Exemplos Práticos

| Exemplo | Descrição |
|---------|-----------|
| **[⭐ Básico](examples/example_basic.php)** | API REST e conceitos fundamentais |
| **[🔐 Auth Completo](examples/example_auth.php)** | Sistema completo de autenticação |
| **[🔑 Auth Simples](examples/example_auth_simple.php)** | JWT básico e controle de acesso |
| **[🛡️ Middlewares](examples/example_middleware.php)** | CORS, rate limiting, validação |
| **[📚 OpenAPI](examples/example_openapi_docs.php)** | Swagger UI automático |
| **[🧩 Extensões](examples/example_extension_system.php)** | Sistema de plugins e hooks |
| **[🔧 Extensões Avançadas](examples/example_advanced_extension.php)** | Rate limiting, cache e auto-discovery |
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

> Todos os middlewares acima seguem o padrão PSR-15.

## 📊 Status do Projeto

- ✅ **Modernização Completa**: PHP 8.1+, tipagem strict, otimizações
- ✅ **Qualidade de Código**: PHPStan Level 9, PSR-12, pre-commit hooks
- ✅ **Performance**: Benchmarks otimizados, cache integrado
- ✅ **Segurança**: Middlewares de segurança, autenticação robusta
- ✅ **Documentação**: Guias completos, exemplos práticos
- ✅ **Testes**: 270+ testes, cobertura completa

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
