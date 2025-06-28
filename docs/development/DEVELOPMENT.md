# Guia de Desenvolvimento - Express PHP Framework

## 🚀 Começando

Este guia fornece todas as informações necessárias para contribuir com o desenvolvimento do Express PHP Framework.

## 🛠️ Configuração do Ambiente

### Pré-requisitos
- **PHP 8.1+** (recomendado PHP 8.3)
- **Composer 2.x**
- **Git**
- **Xdebug** (para cobertura de testes)

### Instalação
```bash
# Clone o repositório
git clone https://github.com/express-php/framework.git
cd express-php-framework

# Instalar dependências
composer install

# Configurar hooks de pre-commit
chmod +x .git/hooks/pre-commit
```

## 📁 Estrutura do Projeto

```
express-php-framework/
├── src/                     # Código fonte principal
│   ├── Core/Application.php      # Classe principal
│   ├── Core/               # Componentes fundamentais
│   ├── Routing/            # Sistema de roteamento
│   ├── Middleware/         # Middlewares de segurança
│   ├── Http/               # Request/Response handling
│   ├── Authentication/     # Sistema de autenticação
│   ├── Cache/              # Interfaces de cache
│   ├── Database/           # Abstração de banco
│   ├── Streaming/          # Funcionalidades de streaming
│   ├── Support/            # Utilitários auxiliares
│   └── Validation/         # Sistema de validação
├── tests/                  # Testes automatizados
├── examples/               # Exemplos de uso
├── docs/                   # Documentação
├── benchmarks/             # Scripts de benchmark
├── config/                 # Configurações
└── scripts/                # Scripts auxiliares
```

## 🧪 Executando Testes

### Suite Completa
```bash
# Todos os testes
composer test

# Testes com cobertura
composer test-coverage

# Testes específicos
vendor/bin/phpunit tests/Core/
vendor/bin/phpunit --filter testCorsMiddleware
```

### Análise Estática
```bash
# PHPStan (nível máximo)
composer analyse

# Code Style (PSR-12)
composer style-check

# Correção automática de style
composer style-fix
```

## 📊 Benchmarks e Performance

### Executar Benchmarks
```bash
# Benchmark completo
./benchmarks/run_benchmark.sh

# Benchmark específico
./benchmarks/benchmark_cors.sh
./benchmarks/benchmark_routing.sh

# Profiling detalhado
php -d xdebug.mode=profile examples/benchmark_app.php
```

### Monitoramento de Performance
```php
// Ativar profiling em desenvolvimento
$app = new Application([
    'debug' => true,
    'profiling' => true
]);

// Obter estatísticas em runtime
$stats = [
    'router' => Router::getStats(),
    'cors' => CorsMiddleware::getStats(),
    'memory' => memory_get_peak_usage(true),
    'time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']
];
```

## 🏗️ Arquitetura e Princípios

### Design Patterns Utilizados

#### 1. Middleware Pattern
```php
interface MiddlewareInterface
{
    public function handle($request, $response, callable $next);
}

class CustomMiddleware implements MiddlewareInterface
{
    public function handle($request, $response, callable $next)
    {
        // Pre-processing
        $result = $next($request, $response);
        // Post-processing
        return $result;
    }
}
```

#### 2. Factory Pattern
```php
class MiddlewareFactory
{
    public static function create(string $type, array $config): MiddlewareInterface
    {
        return match($type) {
            'cors' => new CorsMiddleware($config),
            'auth' => new AuthMiddleware($config),
            'rate-limit' => new RateLimitMiddleware($config),
            default => throw new InvalidArgumentException("Unknown middleware: $type")
        };
    }
}
```

#### 3. Strategy Pattern
```php
interface AuthStrategy
{
    public function authenticate($request): ?array;
}

class JwtAuthStrategy implements AuthStrategy
{
    public function authenticate($request): ?array
    {
        $token = $this->extractToken($request);
        return JWTHelper::decode($token, $this->secret);
    }
}
```

### Princípios de Código

#### SOLID Principles
- **S** - Single Responsibility: Cada classe tem uma responsabilidade específica
- **O** - Open/Closed: Extensível via interfaces e herança
- **L** - Liskov Substitution: Subclasses substituíveis
- **I** - Interface Segregation: Interfaces específicas e coesas
- **D** - Dependency Inversion: Dependências via interfaces

#### Performance First
- Cache automático sempre que possível
- Lazy loading para componentes pesados
- Otimizações de memória e CPU
- Benchmarks contínuos

## 🔧 Desenvolvendo Funcionalidades

### Adicionando um Novo Middleware

#### 1. Criar a classe
```php
<?php
namespace Express\Middleware\Custom;

use Express\Middleware\Core\BaseMiddleware;

class MyCustomMiddleware extends BaseMiddleware
{
    private array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'default_option' => 'value'
        ], $config);
    }

    public function handle($request, $response, callable $next)
    {
        // Sua lógica aqui
        return $next($request, $response);
    }

    public static function create(array $config = []): callable
    {
        $instance = new self($config);
        return [$instance, 'handle'];
    }
}
```

#### 2. Criar testes
```php
<?php
namespace Express\Tests\Middleware;

use PHPUnit\Framework\TestCase;
use Express\Middleware\Custom\MyCustomMiddleware;

class MyCustomMiddlewareTest extends TestCase
{
    public function testMiddlewareExecution()
    {
        $middleware = new MyCustomMiddleware();

        $request = $this->createMockRequest();
        $response = $this->createMockResponse();
        $next = function() { return 'next called'; };

        $result = $middleware->handle($request, $response, $next);

        $this->assertEquals('next called', $result);
    }
}
```

#### 3. Documentar
```php
/**
 * My Custom Middleware
 *
 * Este middleware faz X, Y e Z para melhorar a funcionalidade A.
 *
 * @example
 * $app->use(MyCustomMiddleware::create([
 *     'option1' => 'value1',
 *     'option2' => true
 * ]));
 */
```

### Adicionando uma Nova Funcionalidade

#### 1. Design First
- Escreva a interface/contrato primeiro
- Documente o comportamento esperado
- Considere performance implications

#### 2. TDD (Test-Driven Development)
```php
// 1. Escreva o teste primeiro
public function testNewFeature()
{
    $result = $this->feature->doSomething();
    $this->assertEquals('expected', $result);
}

// 2. Implemente o mínimo necessário
public function doSomething()
{
    return 'expected';
}

// 3. Refatore e otimize
```

#### 3. Benchmark Integration
```php
public function benchmarkNewFeature(): array
{
    $iterations = 10000;
    $start = microtime(true);

    for ($i = 0; $i < $iterations; $i++) {
        $this->doSomething();
    }

    $time = microtime(true) - $start;

    return [
        'iterations' => $iterations,
        'total_time' => $time,
        'ops_per_second' => $iterations / $time
    ];
}
```

## 🐛 Debug e Troubleshooting

### Debug Mode
```php
$app = new Application(['debug' => true]);

// Logs detalhados
$app->use(function($req, $res, $next) {
    error_log("Request: {$req->method} {$req->path}");
    $start = microtime(true);

    $result = $next($req, $res);

    $time = microtime(true) - $start;
    error_log("Response time: {$time}s");

    return $result;
});
```

### Profiling
```bash
# Xdebug profiling
php -d xdebug.mode=profile -d xdebug.output_dir=/tmp examples/app.php

# Memory profiling
php -d memory_limit=128M examples/memory_test.php
```

### Common Issues

#### Performance Issues
1. **Cache não funcionando:** Verifique configurações de cache
2. **Memory leaks:** Use `unset()` em loops grandes
3. **Slow routes:** Otimize pattern matching

#### Security Issues
1. **CORS errors:** Configure origins corretamente
2. **Auth failures:** Verifique secrets e tokens
3. **CSRF issues:** Certifique-se de incluir tokens

## 📚 Convenções de Código

### Naming Conventions
```php
// Classes: PascalCase
class RouterMiddleware {}

// Métodos/Funções: camelCase
public function handleRequest() {}

// Variáveis: camelCase
$requestData = [];

// Constantes: UPPER_SNAKE_CASE
const DEFAULT_TIMEOUT = 30;

// Interfaces: Interface suffix
interface CacheInterface {}
```

### Documentation Standards
```php
/**
 * Breve descrição do método
 *
 * Descrição mais detalhada explicando o comportamento,
 * parâmetros especiais e casos de uso.
 *
 * @param string $param1 Descrição do parâmetro
 * @param array $param2 Array de configurações
 * @return bool Retorna true em caso de sucesso
 *
 * @throws InvalidArgumentException Se parâmetros inválidos
 * @throws RuntimeException Se operação falha
 *
 * @example
 * $result = $object->method('value', ['option' => true]);
 *
 * @since 1.0.0
 */
public function method(string $param1, array $param2 = []): bool
{
    // Implementation
}
```

## 🚀 Processo de Release

### Preparação
```bash
# 1. Atualizar CHANGELOG.md
# 2. Bump version no composer.json
# 3. Executar testes completos
composer test-complete

# 4. Executar benchmarks
./benchmarks/run_benchmark.sh

# 5. Validar projeto
composer validate-project
```

### Release
```bash
# 1. Commit de release
git add .
git commit -m "chore: Release v1.x.x"

# 2. Tag de versão
git tag -a v1.x.x -m "Release version 1.x.x"

# 3. Push
git push origin main --tags
```

## 🤝 Contribuindo

### Pull Request Process
1. Fork o repositório
2. Crie feature branch: `git checkout -b feature/nova-funcionalidade`
3. Commit suas mudanças: `git commit -m 'feat: adiciona nova funcionalidade'`
4. Execute testes: `composer test`
5. Push para branch: `git push origin feature/nova-funcionalidade`
6. Abra Pull Request

### Commit Message Format
```
type(scope): description

body

footer
```

**Types:**
- `feat:` Nova funcionalidade
- `fix:` Correção de bug
- `docs:` Documentação
- `style:` Formatação
- `refactor:` Refatoração
- `test:` Testes
- `chore:` Manutenção

## 📧 Suporte

- **Issues:** GitHub Issues
- **Discussões:** GitHub Discussions
- **Email:** dev@express-php.com
- **Documentation:** docs/
