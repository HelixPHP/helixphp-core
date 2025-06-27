# Objetos e Classes - Express PHP Framework

## 🏗️ Arquitetura Principal

O Express PHP Framework é organizado em uma arquitetura modular com classes bem definidas e otimizadas.

## 📋 Classes Principais

### 🚀 ApiExpress
Classe principal do framework que gerencia toda a aplicação.

```php
namespace Express;

class ApiExpress
{
    // Propriedades principais
    private Router $router;
    private Container $container;
    private array $middleware = [];

    // Métodos HTTP
    public function get(string $path, callable $handler, ...$middlewares): void
    public function post(string $path, callable $handler, ...$middlewares): void
    public function put(string $path, callable $handler, ...$middlewares): void
    public function delete(string $path, callable $handler, ...$middlewares): void
    public function patch(string $path, callable $handler, ...$middlewares): void
    public function options(string $path, callable $handler, ...$middlewares): void

    // Agrupamento e middleware
    public function group(string $prefix, callable $callback): void
    public function use(callable $middleware): void

    // Inicialização
    public function listen(int $port = 8000): void
}
```

### 🛣️ Router
Sistema de roteamento otimizado com cache automático.

```php
namespace Express\Routing;

class Router
{
    // Cache de rotas
    private static array $routeCache = [];
    private static array $groupCache = [];

    // Registro de rotas
    public static function addRoute(string $method, string $path, callable $handler): void
    public static function group(string $prefix, callable $callback): void

    // Resolução de rotas
    public static function resolve(string $method, string $path): ?array
    public static function identify(string $method, string $path): ?array

    // Estatísticas e performance
    public static function getStats(): array
    public static function getGroupStats(): array
    public static function warmupCache(): void
}
```

### 📨 Request
Objeto que representa a requisição HTTP.

```php
namespace Express\Http;

class Request
{
    public string $method;
    public string $path;
    public array $params = [];
    public array $query = [];
    public array $body = [];
    public array $files = [];
    public HeaderRequest $headers;

    public function __construct(array $serverData = null)
    public function param(string $key, mixed $default = null): mixed
    public function query(string $key, mixed $default = null): mixed
    public function body(string $key, mixed $default = null): mixed
    public function file(string $key): ?array
    public function ip(): string
    public function userAgent(): string
}
```

### 📤 Response
Objeto que representa a resposta HTTP.

```php
namespace Express\Http;

class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private mixed $body = null;

    public function status(int $code): self
    public function header(string $name, string $value): self
    public function json(mixed $data): self
    public function text(string $content): self
    public function html(string $content): self
    public function send(): void

    // Streaming
    public function startStream(): self
    public function write(string $data): self
    public function writeJson(mixed $data): self
    public function endStream(): self
}
```

## 🔧 Middleware e Segurança

### 🛡️ CorsMiddleware
Middleware CORS otimizado com cache de headers.

```php
namespace Express\Middleware\Security;

class CorsMiddleware extends BaseMiddleware
{
    // Cache estático para performance
    private static array $preCompiledHeaders = [];
    private static array $compiledHeaderStrings = [];

    public function __construct(array $options = [])
    public function handle($request, $response, callable $next)

    // Métodos estáticos otimizados
    public static function create(array $config = []): callable
    public static function simple(string $origin = '*'): callable
    public static function development(): self
    public static function production(array $allowedOrigins): self

    // Performance e estatísticas
    public static function getStats(): array
    public static function benchmark(int $iterations = 10000): array
}
```

### 🔐 AuthMiddleware
Sistema de autenticação flexível.

```php
namespace Express\Middleware\Security;

class AuthMiddleware extends BaseMiddleware
{
    public static function jwt(array $config): callable
    public static function bearer(array $config): callable
    public static function basic(array $config): callable
    public static function custom(callable $validator): callable

    public function handle($request, $response, callable $next)
}
```

### 🚦 RateLimitMiddleware
Rate limiting inteligente por IP ou usuário.

```php
namespace Express\Middleware\Security;

class RateLimitMiddleware extends BaseMiddleware
{
    public static function create(array $config): callable
    public function handle($request, $response, callable $next)

    // Configurações padrão
    private const DEFAULT_CONFIG = [
        'max_requests' => 100,
        'window' => 3600,
        'storage' => 'memory'
    ];
}
```

## 🔧 Utilitários e Helpers

### 🛠️ JWTHelper
Utilitário para trabalhar com JSON Web Tokens.

```php
namespace Express\Authentication;

class JWTHelper
{
    public static function encode(array $payload, string $secret): string
    public static function decode(string $token, string $secret): ?array
    public static function isValid(string $token, string $secret): bool
    public static function getPayload(string $token): ?array
    public static function isExpired(string $token, int $leeway = 0): bool
    public static function generateSecret(int $length = 32): string
    public static function createRefreshToken(mixed $userId): string
}
```

### 📊 Cache
Sistema de cache flexível com múltiplos drivers.

```php
namespace Express\Cache;

interface CacheInterface
{
    public function get(string $key, mixed $default = null): mixed
    public function set(string $key, mixed $value, ?int $ttl = null): bool
    public function delete(string $key): bool
    public function clear(): bool
    public function has(string $key): bool
}

class FileCache implements CacheInterface
class MemoryCache implements CacheInterface
```

### 🗄️ Database
Conexão e operações de banco de dados.

```php
namespace Express\Database;

class Database
{
    public function __construct(array $config)
    public function query(string $sql, array $params = []): array
    public function execute(string $sql, array $params = []): bool
    public function insert(string $table, array $data): int
    public function update(string $table, array $data, array $where): int
    public function delete(string $table, array $where): int
    public function beginTransaction(): void
    public function commit(): void
    public function rollback(): void
}
```

## 🌊 Streaming e Eventos

### 📡 Streaming
Sistema avançado de streaming de dados.

```php
namespace Express\Streaming;

class StreamingResponse
{
    public function startStream(): self
    public function setBufferSize(int $size): self
    public function write(string $data): self
    public function writeJson(mixed $data): self
    public function sendEvent(string $event, mixed $data): self
    public function sendHeartbeat(): self
    public function endStream(): self
    public function streamFile(string $path): self
    public function streamResource($resource): self
}
```

### 📨 Events
Sistema de eventos para comunicação entre componentes.

```php
namespace Express\Events;

class EventDispatcher
{
    public function dispatch(Event $event): void
    public function addListener(string $eventName, callable $listener): void
    public function removeListener(string $eventName, callable $listener): void
    public function getListeners(string $eventName = null): array
}

class Event
{
    public function __construct(string $name, array $data = [])
    public function getName(): string
    public function getData(): array
    public function isPropagationStopped(): bool
    public function stopPropagation(): void
}
```

## 🔍 Validação

### ✅ Validator
Sistema de validação robusto e extensível.

```php
namespace Express\Validation;

class Validator
{
    public static function make(array $data, array $rules): self
    public function validate(): bool
    public function fails(): bool
    public function errors(): array
    public function firstError(string $field = null): ?string

    // Regras disponíveis
    private array $availableRules = [
        'required', 'string', 'numeric', 'email',
        'min', 'max', 'in', 'regex', 'confirmed'
    ];
}
```

## 📊 Performance e Benchmarks

### 📈 Estatísticas
Todas as classes principais oferecem métodos de estatísticas:

```php
// Estatísticas do Router
Router::getStats(); // Cache hits, misses, routes count
Router::getGroupStats(); // Group performance data

// Estatísticas do CORS
CorsMiddleware::getStats(); // Cache usage, memory consumption

// Benchmarks
CorsMiddleware::benchmark(10000); // Performance testing
Router::benchmarkGroupAccess('/api', 1000); // Group performance
```

## 🛠️ Configuração e Container

### 📦 Container
Container de dependências simples e eficiente.

```php
namespace Express\Core;

class Container
{
    public function bind(string $abstract, callable $concrete): void
    public function singleton(string $abstract, callable $concrete): void
    public function make(string $abstract): mixed
    public function has(string $abstract): bool
}
```

### ⚙️ Config
Sistema de configuração centralizado.

```php
namespace Express\Core;

class Config
{
    public static function set(string $key, mixed $value): void
    public static function get(string $key, mixed $default = null): mixed
    public static function has(string $key): bool
    public static function all(): array
    public static function load(string $file): void
}
```

## 📚 Exemplos de Uso

Para ver exemplos práticos de uso de cada classe, consulte:

- [examples/](../../examples/) - Exemplos básicos
- [examples/snippets/](../../examples/snippets/) - Snippets de código
- [tests/](../../tests/) - Testes unitários com exemplos de uso
