# PSR-7 Version Compatibility

## Overview

PivotPHP Core v1.0.1 is designed with PSR-7 v2.0 but allows installation with either PSR-7 v1.x or v2.x through composer constraints.

## Composer Configuration

```json
{
    "require": {
        "psr/http-message": "^1.1|^2.0"
    }
}
```

## Important Notes

### Current Implementation

PivotPHP Core's HTTP message implementations (`ServerRequest`, `Request`, `Response`, `Stream`, etc.) are built following PSR-7 v2.0 specifications, which include:

- Return type declarations on interface methods
- Parameter type declarations
- Stricter type safety

### Compatibility with PSR-7 v1.x Projects

While composer allows installation alongside PSR-7 v1.x, the actual PivotPHP Core classes require PSR-7 v2.0 interfaces at runtime. This means:

1. **Direct Usage**: Projects using PivotPHP Core directly should use PSR-7 v2.0
2. **Mixed Environments**: Projects that need to use both PivotPHP Core and libraries requiring PSR-7 v1.x (like ReactPHP) will need:
   - A PSR-7 bridge/adapter layer
   - Or separate PSR-7 implementations for different parts of the application

### ReactPHP Integration Example

If you need to integrate PivotPHP Core with ReactPHP (which uses PSR-7 v1.x), consider:

```php
// Use a PSR-7 bridge to convert between versions
use Acme\Psr7Bridge;

// ReactPHP PSR-7 v1.x request
$reactRequest = $event->getRequest();

// Convert to PSR-7 v2.0 for PivotPHP
$pivotRequest = Psr7Bridge::fromV1ToV2($reactRequest);

// Process with PivotPHP
$pivotResponse = $app->handle($pivotRequest);

// Convert back to PSR-7 v1.x for ReactPHP
$reactResponse = Psr7Bridge::fromV2ToV1($pivotResponse);
```

## Future Considerations

Future versions of PivotPHP Core may include:

1. **Conditional Loading**: Automatic detection and loading of appropriate implementations based on installed PSR-7 version
2. **Built-in Bridge**: Native bridge classes for seamless conversion between PSR-7 versions
3. **Adapter Pattern**: Factory methods that return compatible implementations

## Recommendations

- For new projects: Use PSR-7 v2.0 for better type safety
- For existing projects with PSR-7 v1.x dependencies: Consider using a bridge library or waiting for native dual-version support
- For maximum compatibility: Implement your own PSR-7 adapter layer specific to your needs

## Related Resources

- [PSR-7 HTTP Message Interface](https://www.php-fig.org/psr/psr-7/)
- [PSR-7 v2.0 Migration Guide](https://www.php-fig.org/psr/psr-7/migration-guide/)
- [PivotPHP HTTP Implementation](../http/README.md)