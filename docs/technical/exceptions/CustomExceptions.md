# Exceptions Customizadas no PivotPHP

O PivotPHP oferece um conjunto de exceções customizadas para diferentes cenários de erro, permitindo um tratamento mais específico e informativo.

## Exceções Principais do Framework

### HttpException

A exceção base para erros HTTP com códigos de status específicos.

```php
use PivotPHP\Core\Exceptions\HttpException;

// Uso básico
throw new HttpException(404, 'Resource not found');

// Com headers customizados
throw new HttpException(
    status: 403,
    message: 'Access denied',
    headers: ['X-Reason' => 'Insufficient permissions']
);

// Lançar exceção baseada em condição
$app->get('/api/users/:id', function($req, $res) {
    $id = $req->param('id');
    $user = findUser($id);

    if (!$user) {
        throw new HttpException(404, "User with ID {$id} not found");
    }

    return $res->json($user);
});
```

#### Métodos da HttpException

```php
$exception = new HttpException(422, 'Validation failed');

// Obter código de status
$code = $exception->getStatusCode(); // 422

// Obter headers
$headers = $exception->getHeaders(); // []

// Adicionar headers
$exception->addHeader('X-Error-Type', 'validation');
$exception->setHeaders(['Content-Type' => 'application/json']);

// Usar na resposta
if ($exception instanceof HttpException) {
    foreach ($exception->getHeaders() as $name => $value) {
        $res->header($name, $value);
    }

    return $res->status($exception->getStatusCode())
              ->json(['error' => $exception->getMessage()]);
}
```

## Criando Exceções Customizadas

### ValidationException

Para erros de validação de dados com detalhes específicos.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class ValidationException extends HttpException
{
    private array $errors;

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        ?\Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct(422, $message, [], $previous);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addError(string $field, string $message): self
    {
        $this->errors[$field][] = $message;
        return $this;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}

// Uso
function validateUser($data) {
    $errors = [];

    if (empty($data['name'])) {
        $errors['name'][] = 'Name is required';
    }

    if (empty($data['email'])) {
        $errors['email'][] = 'Email is required';
    } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'][] = 'Email must be valid';
    }

    if (!empty($errors)) {
        throw new ValidationException('Validation failed', $errors);
    }
}

$app->post('/api/users', function($req, $res) {
    try {
        validateUser($req->body);
        $user = createUser($req->body);
        return $res->status(201)->json($user);

    } catch (ValidationException $e) {
        return $res->status(422)->json([
            'error' => true,
            'message' => $e->getMessage(),
            'errors' => $e->getErrors()
        ]);
    }
});
```

### AuthenticationException

Para erros de autenticação.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class AuthenticationException extends HttpException
{
    public function __construct(
        string $message = 'Authentication required',
        ?\Throwable $previous = null
    ) {
        parent::__construct(401, $message, [
            'WWW-Authenticate' => 'Bearer'
        ], $previous);
    }
}

// Uso
$app->use('/api/protected', function($req, $res, $next) {
    $token = $req->headers->get('Authorization');

    if (!$token) {
        throw new AuthenticationException('Authentication token required');
    }

    if (!str_starts_with($token, 'Bearer ')) {
        throw new AuthenticationException('Invalid token format');
    }

    $tokenValue = substr($token, 7);
    $user = validateToken($tokenValue);

    if (!$user) {
        throw new AuthenticationException('Invalid or expired token');
    }

    $req->user = $user;
    return $next();
});
```

### AuthorizationException

Para erros de autorização/permissões.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class AuthorizationException extends HttpException
{
    private string $requiredPermission;

    public function __construct(
        string $message = 'Access denied',
        string $requiredPermission = '',
        ?\Throwable $previous = null
    ) {
        $this->requiredPermission = $requiredPermission;
        parent::__construct(403, $message, [], $previous);
    }

    public function getRequiredPermission(): string
    {
        return $this->requiredPermission;
    }
}

// Uso
function requirePermission(string $permission) {
    return function($req, $res, $next) use ($permission) {
        $user = $req->user ?? null;

        if (!$user) {
            throw new AuthenticationException();
        }

        if (!$user->hasPermission($permission)) {
            throw new AuthorizationException(
                "Permission '{$permission}' required",
                $permission
            );
        }

        return $next();
    };
}

$app->get('/api/admin/users', requirePermission('admin.users.read'),
    function($req, $res) {
        return $res->json(getAllUsers());
    }
);
```

### DatabaseException

Para erros relacionados ao banco de dados.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class DatabaseException extends HttpException
{
    private string $query;
    private array $parameters;

    public function __construct(
        string $message = 'Database error',
        string $query = '',
        array $parameters = [],
        ?\Throwable $previous = null
    ) {
        $this->query = $query;
        $this->parameters = $parameters;
        parent::__construct(500, $message, [], $previous);
    }

    public function getQuery(): string
    {
        return $this->query;
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }
}

// Uso
function executeQuery(string $sql, array $params = []) {
    try {
        $db = app('database');
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;

    } catch (\PDOException $e) {
        throw new DatabaseException(
            'Query execution failed',
            $sql,
            $params,
            $e
        );
    }
}

$app->setErrorHandler(function($error, $req, $res) {
    if ($error instanceof DatabaseException) {
        $logger = app('logger');
        $logger->error('Database error', [
            'message' => $error->getMessage(),
            'query' => $error->getQuery(),
            'parameters' => $error->getParameters(),
            'original_error' => $error->getPrevious()?->getMessage()
        ]);

        return $res->status(500)->json([
            'error' => true,
            'message' => 'A database error occurred',
            'code' => 500
        ]);
    }

    // Outros handlers...
});
```

### RateLimitException

Para controle de taxa de requisições.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class RateLimitException extends HttpException
{
    private int $limit;
    private int $remaining;
    private int $resetTime;

    public function __construct(
        int $limit,
        int $remaining = 0,
        int $resetTime = 0,
        string $message = 'Rate limit exceeded'
    ) {
        $this->limit = $limit;
        $this->remaining = $remaining;
        $this->resetTime = $resetTime;

        $headers = [
            'X-RateLimit-Limit' => (string)$limit,
            'X-RateLimit-Remaining' => (string)$remaining,
            'X-RateLimit-Reset' => (string)$resetTime,
            'Retry-After' => (string)($resetTime - time())
        ];

        parent::__construct(429, $message, $headers);
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getRemaining(): int
    {
        return $this->remaining;
    }

    public function getResetTime(): int
    {
        return $this->resetTime;
    }
}

// Uso em middleware
class RateLimitMiddleware
{
    public function __invoke($req, $res, $next)
    {
        $ip = $req->ip();
        $key = "rate_limit:{$ip}";
        $cache = app('cache');

        $limit = 100; // requests per hour
        $window = 3600; // 1 hour

        $current = $cache->get($key, 0);
        $resetTime = time() + $window;

        if ($current >= $limit) {
            throw new RateLimitException(
                limit: $limit,
                remaining: 0,
                resetTime: $resetTime
            );
        }

        $cache->set($key, $current + 1, $window);

        // Adicionar headers informativos
        $res->header('X-RateLimit-Limit', (string)$limit);
        $res->header('X-RateLimit-Remaining', (string)($limit - $current - 1));
        $res->header('X-RateLimit-Reset', (string)$resetTime);

        return $next();
    }
}
```

### NotificationException

Para erros que precisam de notificação.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class NotificationException extends HttpException
{
    private array $recipients;
    private string $severity;

    public function __construct(
        string $message,
        array $recipients = [],
        string $severity = 'error',
        int $statusCode = 500,
        ?\Throwable $previous = null
    ) {
        $this->recipients = $recipients;
        $this->severity = $severity;
        parent::__construct($statusCode, $message, [], $previous);
    }

    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }

    public function shouldNotify(): bool
    {
        return !empty($this->recipients);
    }
}

// Handler para NotificationException
$app->setErrorHandler(function($error, $req, $res) {
    if ($error instanceof NotificationException && $error->shouldNotify()) {
        $notifier = app('notifier');

        foreach ($error->getRecipients() as $recipient) {
            $notifier->send($recipient, [
                'subject' => 'Application Error',
                'message' => $error->getMessage(),
                'severity' => $error->getSeverity(),
                'context' => [
                    'path' => $req->path(),
                    'time' => date('Y-m-d H:i:s')
                ]
            ]);
        }
    }

    // Tratamento normal do erro...
});
```

## Exceções por Contexto

### FileException

Para operações de arquivo.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class FileException extends HttpException
{
    private string $filename;
    private string $operation;

    public function __construct(
        string $message,
        string $filename = '',
        string $operation = '',
        ?\Throwable $previous = null
    ) {
        $this->filename = $filename;
        $this->operation = $operation;
        parent::__construct(500, $message, [], $previous);
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getOperation(): string
    {
        return $this->operation;
    }
}

// Uso
function uploadFile($file, $destination) {
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new FileException(
            'Invalid uploaded file',
            $file['name'],
            'upload'
        );
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new FileException(
            'Failed to move uploaded file',
            $destination,
            'move'
        );
    }
}
```

### ConfigurationException

Para erros de configuração.

```php
<?php

namespace App\Exceptions;

use PivotPHP\Core\Exceptions\HttpException;

class ConfigurationException extends HttpException
{
    private string $configKey;

    public function __construct(
        string $message,
        string $configKey = '',
        ?\Throwable $previous = null
    ) {
        $this->configKey = $configKey;
        parent::__construct(500, $message, [], $previous);
    }

    public function getConfigKey(): string
    {
        return $this->configKey;
    }
}

// Uso
function getRequiredConfig(string $key) {
    $value = app('config')->get($key);

    if ($value === null) {
        throw new ConfigurationException(
            "Required configuration '{$key}' not found",
            $key
        );
    }

    return $value;
}
```

## Helpers para Exceções

### Exception Factory

```php
class ExceptionFactory
{
    public static function notFound(string $resource, $id = null): HttpException
    {
        $message = $id ? "{$resource} with ID {$id} not found" : "{$resource} not found";
        return new HttpException(404, $message);
    }

    public static function unauthorized(string $reason = ''): AuthenticationException
    {
        $message = $reason ? "Unauthorized: {$reason}" : 'Unauthorized';
        return new AuthenticationException($message);
    }

    public static function forbidden(string $action = ''): AuthorizationException
    {
        $message = $action ? "Forbidden: {$action}" : 'Forbidden';
        return new AuthorizationException($message);
    }

    public static function validation(array $errors): ValidationException
    {
        return new ValidationException('Validation failed', $errors);
    }

    public static function rateLimited(int $limit, int $reset): RateLimitException
    {
        return new RateLimitException($limit, 0, $reset);
    }
}

// Uso
$app->get('/api/users/:id', function($req, $res) {
    $id = $req->param('id');
    $user = findUser($id);

    if (!$user) {
        throw ExceptionFactory::notFound('User', $id);
    }

    return $res->json($user);
});
```

### Exception Context

```php
trait ExceptionContext
{
    private array $context = [];

    public function setContext(array $context): self
    {
        $this->context = $context;
        return $this;
    }

    public function addContext(string $key, $value): self
    {
        $this->context[$key] = $value;
        return $this;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}

class ContextualException extends HttpException
{
    use ExceptionContext;

    public function __construct(
        int $statusCode,
        string $message,
        array $context = [],
        ?\Throwable $previous = null
    ) {
        $this->setContext($context);
        parent::__construct($statusCode, $message, [], $previous);
    }
}

// Uso
throw (new ContextualException(400, 'Invalid request'))
    ->addContext('user_id', $userId)
    ->addContext('action', 'create_order')
    ->addContext('timestamp', time());
```

## Testing Exceptions

### Exception Testing

```php
use PHPUnit\Framework\TestCase;

class ExceptionTest extends TestCase
{
    public function testValidationException()
    {
        $errors = ['email' => ['Email is required']];
        $exception = new ValidationException('Validation failed', $errors);

        $this->assertEquals(422, $exception->getStatusCode());
        $this->assertEquals('Validation failed', $exception->getMessage());
        $this->assertEquals($errors, $exception->getErrors());
        $this->assertTrue($exception->hasErrors());
    }

    public function testHttpExceptionWithHeaders()
    {
        $exception = new HttpException(403, 'Access denied', [
            'X-Reason' => 'Insufficient permissions'
        ]);

        $this->assertEquals(403, $exception->getStatusCode());
        $this->assertArrayHasKey('X-Reason', $exception->getHeaders());
    }

    public function testRateLimitException()
    {
        $limit = 100;
        $resetTime = time() + 3600;
        $exception = new RateLimitException($limit, 0, $resetTime);

        $headers = $exception->getHeaders();
        $this->assertEquals('100', $headers['X-RateLimit-Limit']);
        $this->assertEquals('0', $headers['X-RateLimit-Remaining']);
    }
}
```

## Boas Práticas

### 1. Hierarquia de Exceções

```php
// Base exception para sua aplicação
abstract class AppException extends HttpException
{
    protected string $errorCode;

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }
}

// Exceções específicas
class UserException extends AppException
{
    protected string $errorCode = 'USER_ERROR';
}

class OrderException extends AppException
{
    protected string $errorCode = 'ORDER_ERROR';
}
```

### 2. Internationalization

```php
class LocalizedException extends HttpException
{
    public function __construct(
        string $messageKey,
        array $parameters = [],
        int $statusCode = 500,
        string $locale = 'en'
    ) {
        $translator = app('translator');
        $message = $translator->translate($messageKey, $parameters, $locale);

        parent::__construct($statusCode, $message);
    }
}

// Uso
throw new LocalizedException('user.not_found', ['id' => $userId], 404);
```

### 3. Exception Logging

```php
trait LoggableException
{
    public function log(): void
    {
        $logger = app('logger');

        $context = [
            'exception' => static::class,
            'message' => $this->getMessage(),
            'file' => $this->getFile(),
            'line' => $this->getLine()
        ];

        if (method_exists($this, 'getContext')) {
            $context = array_merge($context, $this->getContext());
        }

        $logger->error('Exception occurred', $context);
    }
}
```

As exceções customizadas permitem um tratamento de erros mais específico e informativo, melhorando tanto a experiência do desenvolvedor quanto do usuário final da API.
