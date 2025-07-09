# HTTP Components Documentation

## Overview

PivotPHP Core provides a complete PSR-7 compliant HTTP message implementation with high-performance optimizations.

## Core Components

### PSR-7 Implementation

- **ServerRequest**: Server-side HTTP request representation
- **Request**: Client-side HTTP request
- **Response**: HTTP response with fluent interface
- **Stream**: Efficient stream handling for message bodies
- **Uri**: URI manipulation and parsing
- **UploadedFile**: File upload handling

### Key Features

1. **Full PSR-7 Compliance**: Implements all PSR-7 interfaces
2. **Performance Optimized**: Object pooling, header caching
3. **Memory Efficient**: Lazy loading, stream management
4. **Type Safe**: PHPStan Level 9 compliance

## PSR-7 Version Compatibility

PivotPHP Core v1.0.1 uses PSR-7 v2.0 interfaces but allows installation with both v1.x and v2.x. See [PSR-7 Version Compatibility](../compatibility/psr7-versions.md) for details.

## Usage Examples

### Creating Requests

```php
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Stream;

// From globals
$request = ServerRequest::fromGlobals();

// Manual creation
$request = new ServerRequest(
    'POST',
    '/api/users',
    Stream::createFromString('{"name":"John"}'),
    ['Content-Type' => ['application/json']]
);
```

### Creating Responses

```php
use PivotPHP\Core\Http\Psr7\Response;

// JSON response
$response = new Response();
$response = $response
    ->withStatus(200)
    ->withHeader('Content-Type', 'application/json')
    ->withBody(Stream::createFromString('{"status":"ok"}'));

// Using factory methods (if available)
$response = Response::json(['status' => 'ok']);
```

### Stream Handling

```php
use PivotPHP\Core\Http\Psr7\Stream;

// From string
$stream = Stream::createFromString('Hello World');

// From file
$stream = Stream::createFromFile('/path/to/file.txt');

// Operations
$content = $stream->getContents();
$stream->rewind();
$stream->write('Additional content');
```

## Performance Considerations

### Object Pooling

PivotPHP Core uses object pools for frequently created objects:

- Header value arrays
- Stream instances
- Small response objects

### Header Optimization

Headers are normalized and cached to avoid repeated string operations:

```php
// Efficient header handling
$response = $response
    ->withHeader('X-Custom', 'value1')
    ->withAddedHeader('X-Custom', 'value2');
```

## Best Practices

1. **Immutability**: All PSR-7 objects are immutable. Use the returned instance:
   ```php
   // Correct
   $response = $response->withStatus(404);
   
   // Incorrect (original unchanged)
   $response->withStatus(404);
   ```

2. **Stream Reuse**: When possible, reuse stream instances:
   ```php
   $stream = Stream::createFromString('');
   foreach ($data as $chunk) {
       $stream->write($chunk);
   }
   ```

3. **Header Validation**: Use built-in validation for security:
   ```php
   // Validates header name and values
   $response = $response->withHeaderStrict('X-Custom', $value);
   ```

## Integration with Middleware

PivotPHP's HTTP components work seamlessly with PSR-15 middleware:

```php
class CustomMiddleware implements MiddlewareInterface
{
    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        // Modify request
        $request = $request->withAttribute('custom', 'value');
        
        // Process
        $response = $handler->handle($request);
        
        // Modify response
        return $response->withHeader('X-Processed', 'true');
    }
}
```

## Advanced Features

### Custom Stream Implementations

```php
class EncryptedStream extends Stream
{
    protected function write(string $string): int
    {
        $encrypted = encrypt($string);
        return parent::write($encrypted);
    }
    
    public function getContents(): string
    {
        $contents = parent::getContents();
        return decrypt($contents);
    }
}
```

### Request Attributes

```php
// Store route parameters
$request = $request->withAttribute('route.params', [
    'controller' => 'UserController',
    'action' => 'show',
    'id' => 123
]);

// Retrieve in controller
$params = $request->getAttribute('route.params', []);
```

## Testing

```php
use PHPUnit\Framework\TestCase;
use PivotPHP\Core\Http\Psr7\ServerRequest;
use PivotPHP\Core\Http\Psr7\Response;

class HttpTest extends TestCase
{
    public function testRequest(): void
    {
        $request = new ServerRequest('GET', '/test');
        
        $this->assertEquals('GET', $request->getMethod());
        $this->assertEquals('/test', $request->getUri()->getPath());
    }
    
    public function testResponse(): void
    {
        $response = (new Response())
            ->withStatus(201)
            ->withHeader('Location', '/resource/123');
            
        $this->assertEquals(201, $response->getStatusCode());
        $this->assertEquals('/resource/123', $response->getHeaderLine('Location'));
    }
}
```

## Related Documentation

- [PSR-7 Version Compatibility](../compatibility/psr7-versions.md)
- [Middleware Development](../middleware/README.md)
- [Routing System](../routing/router.md)
- [Performance Optimization](../../performance/README.md)