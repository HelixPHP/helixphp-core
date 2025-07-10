# PivotPHP Microframework

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue.svg)](https://php.net)
[![Latest Stable Version](https://poser.pugx.org/pivotphp/core/v/stable)](https://packagist.org/packages/pivotphp/core)
[![Total Downloads](https://poser.pugx.org/pivotphp/core/downloads)](https://packagist.org/packages/pivotphp/core)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-Level%209-brightgreen.svg)](https://phpstan.org/)
[![PSR-12](https://img.shields.io/badge/PSR--12%20%2F%20PSR--15-compliant-brightgreen)](https://www.php-fig.org/psr/psr-12/)
[![GitHub Issues](https://img.shields.io/github/issues/PivotPHP/pivotphp-core)](https://github.com/PivotPHP/pivotphp-core/issues)
[![GitHub Stars](https://img.shields.io/github/stars/PivotPHP/pivotphp-core)](https://github.com/PivotPHP/pivotphp-core/stargazers)

---

## 🚀 O que é o PivotPHP?

**PivotPHP** é um microframework moderno, leve e seguro, inspirado no Express.js, para construir APIs e aplicações web de alta performance em PHP. Ideal para validação de conceitos, estudos e desenvolvimento de aplicações que exigem produtividade, arquitetura desacoplada e extensibilidade real.

- **Alta Performance**: 2.57M ops/sec em CORS, 2.27M ops/sec em Response, 757K ops/sec roteamento, cache integrado.
- **Arquitetura Moderna**: DI Container, Service Providers, Event System, Extension System e PSR-15.
- **Segurança**: Middlewares robustos para CSRF, XSS, Rate Limiting, JWT, API Key e mais.
- **Extensível**: Sistema de plugins, hooks, providers e integração PSR-14.
- **Qualidade**: 335+ testes, PHPStan Level 9, PSR-12, cobertura completa.
- **🆕 v1.1.0**: High-Performance Edition com circuit breaker, load shedding e pooling avançado.
- **🚀 v1.1.1**: JSON Optimization Edition com pooling automático e 101k+ ops/sec sustentados.

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
- 🔄 **PSR-7 Híbrido**
- ♻️ **Object Pooling**
- 🚀 **JSON Optimization** (v1.1.1)
- ⚡ **Performance Extrema**
- 🧪 **Qualidade e Testes**

---

## 💡 Casos de Uso & Insights

- APIs RESTful de alta performance
- Gateways de autenticação JWT/API Key
- Microsserviços e aplicações desacopladas
- Sistemas extensíveis com plugins e hooks
- Plataformas que exigem segurança e performance

Veja exemplos práticos em [`examples/`](examples/), benchmarks reais em [`benchmarks/`](benchmarks/) e [relatório de performance completo](docs/performance/PERFORMANCE_REPORT_v1.0.0.md).

---

## 🚀 Início Rápido

### Instalação

```bash
composer require pivotphp/core
```

### Exemplo Básico

```php
<?php
require_once 'vendor/autoload.php';

use PivotPHP\Core\Core\Application;
use PivotPHP\Core\Http\Psr15\Middleware\{SecurityMiddleware, CorsMiddleware, AuthMiddleware};

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

// Rotas com validação regex
$app->get('/api/users/:id<\d+>', function($req, $res) {
    // Aceita apenas IDs numéricos
    $res->json(['user_id' => $req->param('id')]);
});

$app->get('/posts/:year<\d{4}>/:month<\d{2}>/:slug<slug>', function($req, $res) {
    // Validação de data e slug na rota
    $res->json([
        'year' => $req->param('year'),
        'month' => $req->param('month'),
        'slug' => $req->param('slug')
    ]);
});

$app->run();
```

### 🔄 Suporte PSR-7 Híbrido

O PivotPHP oferece **compatibilidade híbrida** com PSR-7, mantendo a facilidade da API Express.js enquanto implementa completamente as interfaces PSR-7:

```php
// API Express.js (familiar e produtiva)
$app->get('/api/users', function($req, $res) {
    $id = $req->param('id');
    $name = $req->input('name');
    return $res->json(['user' => $userService->find($id)]);
});

// PSR-7 nativo (para middleware PSR-15)
$app->use(function(ServerRequestInterface $request, ResponseInterface $response, $next) {
    $method = $request->getMethod();
    $uri = $request->getUri();
    $newRequest = $request->withAttribute('processed', true);
    return $next($newRequest, $response);
});

// Lazy loading e Object Pooling automático
use PivotPHP\Core\Http\Factory\OptimizedHttpFactory;

OptimizedHttpFactory::initialize([
    'enable_pooling' => true,
    'warm_up_pools' => true,
    'max_pool_size' => 100,
]);

// Objetos PSR-7 são reutilizados automaticamente
$request = OptimizedHttpFactory::createRequest('GET', '/api/users', '/api/users');
$response = OptimizedHttpFactory::createResponse();
```

**Benefícios da Implementação Híbrida:**
- ✅ **100% compatível** com middleware PSR-15
- ✅ **Imutabilidade** respeitada nos métodos `with*()`
- ✅ **Lazy loading** - objetos PSR-7 criados apenas quando necessário
- ✅ **Object pooling** - reutilização inteligente para melhor performance
- ✅ **API Express.js** mantida para produtividade
- ✅ **Zero breaking changes** - código existente funciona sem alterações

### 🚀 JSON Optimization (v1.1.1)

O PivotPHP v1.1.1 introduz um sistema revolucionário de otimização JSON que melhora drasticamente a performance através de buffer pooling inteligente:

```php
// Otimização automática - zero configuração necessária
$app->get('/api/users', function($req, $res) {
    $users = User::all(); // 1000+ usuários
    
    // Automaticamente usa pooling para datasets grandes
    return $res->json($users); // 101k+ ops/sec sustentados
});

// Controle manual para casos específicos
use PivotPHP\Core\Json\Pool\JsonBufferPool;

// Encoding direto com pooling
$json = JsonBufferPool::encodeWithPool($largeData);

// Configuração para alta carga de produção
JsonBufferPool::configure([
    'max_pool_size' => 500,
    'default_capacity' => 16384, // 16KB buffers
    'size_categories' => [
        'small' => 4096,   // 4KB
        'medium' => 16384, // 16KB
        'large' => 65536,  // 64KB
        'xlarge' => 262144 // 256KB
    ]
]);

// Monitoramento em tempo real
$stats = JsonBufferPool::getStatistics();
echo "Reuse rate: {$stats['reuse_rate']}%"; // Target: 80%+
echo "Operations: {$stats['total_operations']}";
```

**Características da Otimização JSON:**
- ✅ **Detecção automática** - ativa pooling para arrays 10+ elementos, objetos 5+ propriedades
- ✅ **Fallback inteligente** - dados pequenos usam `json_encode()` tradicional
- ✅ **101k+ ops/sec** sustentados em testes de carga contínua
- ✅ **100% reuso** de buffers em cenários de alta frequência
- ✅ **Zero configuração** - funciona automaticamente com código existente
- ✅ **Monitoramento integrado** - estatísticas detalhadas para otimização

### 📖 Documentação OpenAPI/Swagger

O PivotPHP inclui suporte integrado para geração automática de documentação OpenAPI:

```php
use PivotPHP\Core\Services\OpenApiExporter;

// Gerar documentação OpenAPI
$openapi = new OpenApiExporter($app);
$spec = $openapi->export();

// Servir documentação em endpoint
$app->get('/api/docs', function($req, $res) use ($openapi) {
    $res->json($openapi->export());
});

// Servir UI do Swagger
$app->get('/api/docs/ui', function($req, $res) {
    $res->html($openapi->getSwaggerUI());
});
```

---

## 📚 Documentação Completa

Acesse o [Índice da Documentação](docs/index.md) para navegar por todos os guias técnicos, exemplos, referências de API, middlewares, autenticação, performance e mais.

Principais links:
- [Guia de Implementação Básica](docs/implementations/usage_basic.md)
- [Guia com Middlewares Prontos](docs/implementations/usage_with_middleware.md)
- [Guia de Middleware Customizado](docs/implementations/usage_with_custom_middleware.md)
- [Referência Técnica](docs/technical/application.md)
- [Performance e Benchmarks](docs/performance/benchmarks/)

---

## 🧩 Extensões Oficiais

O PivotPHP possui um ecossistema rico de extensões que adicionam funcionalidades poderosas ao framework:

### 🗄️ Cycle ORM Extension
```bash
composer require pivotphp/cycle-orm
```

Integração completa com Cycle ORM para gerenciamento de banco de dados:
- Migrações automáticas
- Repositórios com query builder
- Relacionamentos (HasOne, HasMany, BelongsTo, ManyToMany)
- Suporte a transações
- Múltiplas conexões de banco

```php
use PivotPHP\CycleORM\CycleServiceProvider;

$app->register(new CycleServiceProvider([
    'dbal' => [
        'databases' => [
            'default' => ['connection' => 'mysql://user:pass@localhost/db']
        ]
    ]
]));

// Usar em rotas
$app->get('/users', function($req, $res) use ($container) {
    $users = $container->get('orm')
        ->getRepository(User::class)
        ->findAll();
    $res->json($users);
});
```

### ⚡ ReactPHP Extension
```bash
composer require pivotphp/reactphp
```

Runtime assíncrono para aplicações de longa duração:
- Servidor HTTP contínuo sem reinicializações
- Suporte a WebSocket (em breve)
- Operações I/O assíncronas
- Arquitetura orientada a eventos
- Timers e tarefas periódicas

```php
use PivotPHP\ReactPHP\ReactServiceProvider;

$app->register(new ReactServiceProvider([
    'server' => [
        'host' => '0.0.0.0',
        'port' => 8080
    ]
]));

// Executar servidor assíncrono
$app->runAsync(); // Em vez de $app->run()
```

### 🌐 Extensões da Comunidade

A comunidade PivotPHP está crescendo! Estamos animados para ver as extensões que serão criadas.

**Extensões Planejadas:**
- Gerador de documentação OpenAPI/Swagger
- Sistema de filas para jobs em background
- Cache avançado com múltiplos drivers
- Abstração para envio de emails
- Servidor WebSocket
- Suporte GraphQL

### 🔧 Criando Sua Própria Extensão

```php
namespace MeuProjeto\Providers;

use PivotPHP\Core\Providers\ServiceProvider;

class MinhaExtensaoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Registrar serviços
        $this->container->singleton('meu.servico', function() {
            return new MeuServico();
        });
    }

    public function boot(): void
    {
        // Lógica de inicialização
        $this->app->get('/minha-rota', function($req, $res) {
            $res->json(['extensao' => 'ativa']);
        });
    }
}
```

**Diretrizes para Extensões:**
1. Seguir convenção de nome: `pivotphp-{nome}`
2. Fornecer ServiceProvider estendendo `ServiceProvider`
3. Incluir testes de integração
4. Documentar no `/docs/extensions/`
5. Publicar no Packagist com tag `pivotphp-extension`

---

## 🔄 Compatibilidade PSR-7

O PivotPHP oferece suporte duplo para PSR-7, permitindo uso com projetos modernos (v2.x) e compatibilidade com ReactPHP (v1.x).

### Verificar versão atual
```bash
php scripts/switch-psr7-version.php --check
```

### Alternar entre versões
```bash
# Mudar para PSR-7 v1.x (compatível com ReactPHP)
php scripts/switch-psr7-version.php 1

# Mudar para PSR-7 v2.x (padrão moderno)
php scripts/switch-psr7-version.php 2
```

### Após alternar versões
```bash
# Atualizar dependências
composer update

# Validar o projeto
./scripts/validate_all.sh
```

Veja a [documentação completa sobre PSR-7](docs/technical/compatibility/psr7-dual-support.md) para mais detalhes.

---

## 🤝 Comunidade

Junte-se à nossa comunidade crescente de desenvolvedores:

- **Discord**: [Entre no nosso servidor](https://discord.gg/DMtxsP7z) - Obtenha ajuda, compartilhe ideias e conecte-se com outros desenvolvedores
- **GitHub Discussions**: [Inicie uma discussão](https://github.com/PivotPHP/pivotphp-core/discussions) - Compartilhe feedback e ideias
- **Twitter**: [@PivotPHP](https://twitter.com/pivotphp) - Siga para atualizações e anúncios

## 🤝 Como Contribuir

Quer ajudar a evoluir o PivotPHP? Veja o [Guia de Contribuição](CONTRIBUTING.md) ou acesse [`docs/contributing/`](docs/contributing/) para saber como abrir issues, enviar PRs ou criar extensões.

---

## 📄 Licença

Este projeto está licenciado sob a Licença MIT. Veja o arquivo [LICENSE](LICENSE) para detalhes.

---

*Desenvolvido com ❤️ para a comunidade PHP*
