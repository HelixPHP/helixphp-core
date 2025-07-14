<?php

declare(strict_types=1);

namespace PivotPHP\Core\Exceptions\Enhanced;

use PivotPHP\Core\Exceptions\HttpException;

/**
 * ContextualException - Exception com contexto detalhado para debug
 *
 * Fornece informações contextuais detalhadas para facilitar o debug
 * em ambientes de desenvolvimento e produção.
 */
class ContextualException extends HttpException
{
    private array $context = [];
    private array $suggestions = [];
    private ?string $category = null;
    private ?string $debugInfo = null;

    public function __construct(
        int $statusCode,
        string $message,
        array $context = [],
        array $suggestions = [],
        ?string $category = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct($statusCode, $message, [], $previous);

        $this->context = $context;
        $this->suggestions = $suggestions;
        $this->category = $category;
        $this->generateDebugInfo();
    }

    /**
     * Get contextual information about the error
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * Get suggestions for fixing the error
     */
    public function getSuggestions(): array
    {
        return $this->suggestions;
    }

    /**
     * Get error category
     */
    public function getCategory(): ?string
    {
        return $this->category;
    }

    /**
     * Get debug information
     */
    public function getDebugInfo(): ?string
    {
        return $this->debugInfo;
    }

    /**
     * Generate comprehensive debug information
     */
    private function generateDebugInfo(): void
    {
        $debug = [];

        // Basic error information
        $debug[] = "ERROR: {$this->getMessage()}";
        $debug[] = "STATUS: {$this->getStatusCode()}";

        if ($this->category) {
            $debug[] = "CATEGORY: {$this->category}";
        }

        // Context information
        if (!empty($this->context)) {
            $debug[] = "\nCONTEXT:";
            foreach ($this->context as $key => $value) {
                $debug[] = "  {$key}: " . $this->formatValue($value);
            }
        }

        // Suggestions
        if (!empty($this->suggestions)) {
            $debug[] = "\nSUGGESTIONS:";
            foreach ($this->suggestions as $i => $suggestion) {
                $debug[] = "  " . ($i + 1) . ". {$suggestion}";
            }
        }

        // Stack trace for development
        if (self::isDevelopmentMode()) {
            $debug[] = "\nSTACK TRACE:";
            $debug[] = $this->getTraceAsString();
        }

        $this->debugInfo = implode("\n", $debug);
    }

    /**
     * Format value for debug output
     */
    private function formatValue(mixed $value): string
    {
        if (is_array($value)) {
            $result = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            return $result !== false ? $result : 'Array (unable to encode)';
        }

        if (is_object($value)) {
            return get_class($value) . ' (object)';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_null($value)) {
            return 'null';
        }

        return is_scalar($value) ? (string) $value : gettype($value);
    }

    /**
     * Check if we're in development mode
     */
    private static function isDevelopmentMode(): bool
    {
        // Check common development indicators
        return (
            ($_ENV['APP_ENV'] ?? '') === 'development' ||
            ($_ENV['APP_DEBUG'] ?? false) === true ||
            ini_get('display_errors') === '1' ||
            defined('PIVOTPHP_DEBUG') && PIVOTPHP_DEBUG === true
        );
    }

    /**
     * Convert to array for JSON responses
     */
    public function toArray(): array
    {
        $data = [
            'error' => true,
            'status' => $this->getStatusCode(),
            'message' => $this->getMessage(),
            'category' => $this->category,
        ];

        if (self::isDevelopmentMode()) {
            $data['context'] = $this->context;
            $data['suggestions'] = $this->suggestions;
            $data['debug'] = $this->debugInfo;
            $data['file'] = $this->getFile();
            $data['line'] = $this->getLine();
        }

        return $data;
    }

    /**
     * Factory method for routing errors
     */
    public static function routeNotFound(
        string $method,
        string $path,
        array $availableRoutes = []
    ): self {
        $context = [
            'method' => $method,
            'path' => $path,
            'available_routes' => $availableRoutes
        ];

        $suggestions = [
            "Verify the route exists and matches the exact path: {$path}",
            "Check if the HTTP method {$method} is correct",
            "Ensure route registration is before the request handling"
        ];

        if (!empty($availableRoutes)) {
            $suggestions[] = "Available routes: " . implode(', ', array_slice($availableRoutes, 0, 5));
        }

        return new self(
            404,
            "Route not found: {$method} {$path}",
            $context,
            $suggestions,
            'ROUTING'
        );
    }

    /**
     * Factory method for handler errors
     */
    public static function handlerError(
        string $handlerType,
        string $error,
        array $handlerInfo = []
    ): self {
        $context = [
            'handler_type' => $handlerType,
            'handler_info' => $handlerInfo,
            'error' => $error
        ];

        $suggestions = [
            "Verify the handler is callable and properly defined",
            "Check method signature: function(Request \$request, Response \$response)",
            "Ensure the class/method exists and is accessible"
        ];

        return new self(
            500,
            "Handler execution failed: {$error}",
            $context,
            $suggestions,
            'HANDLER'
        );
    }

    /**
     * Factory method for parameter errors
     */
    public static function parameterError(
        string $paramName,
        string $expectedType,
        mixed $actualValue,
        string $route
    ): self {
        $context = [
            'parameter' => $paramName,
            'expected_type' => $expectedType,
            'actual_value' => $actualValue,
            'actual_type' => gettype($actualValue),
            'route' => $route
        ];

        $suggestions = [
            "Verify the parameter '{$paramName}' matches the expected format",
            "Check route constraints if defined",
            "Ensure URL encoding is correct"
        ];

        return new self(
            400,
            "Parameter validation failed for '{$paramName}': expected {$expectedType}",
            $context,
            $suggestions,
            'PARAMETER'
        );
    }

    /**
     * Factory method for middleware errors
     */
    public static function middlewareError(
        string $middlewareName,
        string $error,
        array $middlewareStack = []
    ): self {
        $context = [
            'middleware' => $middlewareName,
            'error' => $error,
            'middleware_stack' => $middlewareStack
        ];

        $suggestions = [
            "Check middleware implementation and dependencies",
            "Verify middleware is properly registered",
            "Ensure middleware returns Response or calls next()"
        ];

        return new self(
            500,
            "Middleware '{$middlewareName}' failed: {$error}",
            $context,
            $suggestions,
            'MIDDLEWARE'
        );
    }
}
