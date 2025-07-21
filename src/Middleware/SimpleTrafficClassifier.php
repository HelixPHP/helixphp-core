<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Simple Traffic Classification Middleware
 *
 * Following 'Simplicidade sobre Otimização Prematura' principle
 * Provides basic request prioritization without enterprise complexity
 */
class SimpleTrafficClassifier
{
    /**
     * Simple priority levels
     */
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_NORMAL = 'normal';
    public const PRIORITY_LOW = 'low';

    /**
     * Simple classification rules
     */
    private array $rules = [];

    /**
     * Default priority
     */
    private string $defaultPriority = self::PRIORITY_NORMAL;

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        if (isset($config['rules'])) {
            $this->rules = $config['rules'];
        }

        if (isset($config['default_priority'])) {
            $this->defaultPriority = $config['default_priority'];
        }
    }

    /**
     * Add a simple classification rule
     */
    public function addRule(string $pattern, string $priority): void
    {
        $this->rules[$pattern] = $priority;
    }

    /**
     * Classify a request
     */
    public function classify(Request $request): string
    {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Check simple URI patterns
        foreach ($this->rules as $pattern => $priority) {
            if (strpos($uri, $pattern) !== false) {
                return $priority;
            }
        }

        // Simple method-based classification (only if no custom default is set)
        if ($this->defaultPriority === self::PRIORITY_NORMAL) {
            if ($method === 'GET') {
                return self::PRIORITY_NORMAL;
            } elseif (in_array($method, ['POST', 'PUT', 'DELETE'])) {
                return self::PRIORITY_HIGH;
            }
        }

        return $this->defaultPriority;
    }

    /**
     * Middleware handler
     */
    public function __invoke(Request $request, Response $response, callable $next): Response
    {
        $priority = $this->classify($request);
        $request = $request->withAttribute('priority', $priority);

        return $next($request, $response);
    }

    /**
     * Get simple statistics
     */
    public function getStats(): array
    {
        return [
            'rules_count' => count($this->rules),
            'default_priority' => $this->defaultPriority,
            'available_priorities' => [
                self::PRIORITY_HIGH,
                self::PRIORITY_NORMAL,
                self::PRIORITY_LOW,
            ],
        ];
    }
}
