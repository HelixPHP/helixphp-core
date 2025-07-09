<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Traffic classification middleware for prioritizing requests
 */
class TrafficClassifier
{
    /**
     * Priority levels
     */
    public const PRIORITY_SYSTEM = 100;
    public const PRIORITY_CRITICAL = 90;
    public const PRIORITY_HIGH = 75;
    public const PRIORITY_NORMAL = 50;
    public const PRIORITY_LOW = 25;
    public const PRIORITY_BATCH = 10;

    /**
     * Classification rules
     */
    private array $rules = [];

    /**
     * Default priority
     */
    private int $defaultPriority = self::PRIORITY_NORMAL;

    /**
     * Classification metrics
     */
    private array $metrics = [
        'total_classified' => 0,
        'by_priority' => [],
        'by_rule' => [],
        'unmatched' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        if (isset($config['rules'])) {
            $this->rules = $this->compileRules($config['rules']);
        }

        if (isset($config['default_priority'])) {
            $this->defaultPriority = $config['default_priority'];
        }

        $this->initializeMetrics();
    }

    /**
     * Initialize metrics
     */
    private function initializeMetrics(): void
    {
        $priorities = [
            'system' => 0,
            'critical' => 0,
            'high' => 0,
            'normal' => 0,
            'low' => 0,
            'batch' => 0,
        ];

        $this->metrics['by_priority'] = $priorities;
    }

    /**
     * Compile rules for efficient matching
     */
    private function compileRules(array $rules): array
    {
        $compiled = [];

        foreach ($rules as $index => $rule) {
            $compiled[] = [
                'index' => $index,
                'name' => $rule['name'] ?? "rule_$index",
                'conditions' => $this->compileConditions($rule),
                'priority' => $this->normalizePriority($rule['priority'] ?? 'normal'),
                'metadata' => $rule['metadata'] ?? [],
            ];
        }

        // Sort rules by specificity (more conditions = higher specificity)
        usort(
            $compiled,
            function ($a, $b) {
                return count($b['conditions']) <=> count($a['conditions']);
            }
        );

        return $compiled;
    }

    /**
     * Compile rule conditions
     */
    private function compileConditions(array $rule): array
    {
        $conditions = [];

        // Path pattern matching
        if (isset($rule['pattern'])) {
            $conditions[] = [
                'type' => 'path_pattern',
                'pattern' => $this->compilePathPattern($rule['pattern']),
            ];
        }

        // Exact path matching
        if (isset($rule['path'])) {
            $conditions[] = [
                'type' => 'path_exact',
                'path' => $rule['path'],
            ];
        }

        // Method matching
        if (isset($rule['method'])) {
            $conditions[] = [
                'type' => 'method',
                'methods' => is_array($rule['method']) ? $rule['method'] : [$rule['method']],
            ];
        }

        // Header matching
        if (isset($rule['header'])) {
            foreach ($rule['header'] as $name => $value) {
                $conditions[] = [
                    'type' => 'header',
                    'name' => $name,
                    'value' => $value,
                ];
            }
        }

        // User agent matching
        if (isset($rule['user_agent'])) {
            $conditions[] = [
                'type' => 'user_agent',
                'pattern' => $rule['user_agent'],
            ];
        }

        // IP range matching
        if (isset($rule['ip_range'])) {
            $conditions[] = [
                'type' => 'ip_range',
                'ranges' => is_array($rule['ip_range']) ? $rule['ip_range'] : [$rule['ip_range']],
            ];
        }

        // Custom condition
        if (isset($rule['custom']) && is_callable($rule['custom'])) {
            $conditions[] = [
                'type' => 'custom',
                'callback' => $rule['custom'],
            ];
        }

        return $conditions;
    }

    /**
     * Compile path pattern to regex
     */
    private function compilePathPattern(string $pattern): string
    {
        // Convert wildcards to regex
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = str_replace('//', '/', $pattern);

        return '#^' . $pattern . '$#i';
    }

    /**
     * Normalize priority value
     */
    private function normalizePriority(mixed $priority): int
    {
        if (is_int($priority)) {
            return max(0, min(100, $priority));
        }

        return match (strtolower((string) $priority)) {
            'system' => self::PRIORITY_SYSTEM,
            'critical' => self::PRIORITY_CRITICAL,
            'high' => self::PRIORITY_HIGH,
            'normal' => self::PRIORITY_NORMAL,
            'low' => self::PRIORITY_LOW,
            'batch' => self::PRIORITY_BATCH,
            default => self::PRIORITY_NORMAL,
        };
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Classify the request
        $classification = $this->classify($request);

        // Add classification to request attributes
        $request->setAttribute('traffic_priority', $classification['priority']);
        $request->setAttribute('traffic_class', $classification['class']);
        $request->setAttribute('traffic_metadata', $classification['metadata']);

        // Add priority header for downstream services
        $response->header('X-Traffic-Priority', (string) $classification['priority']);
        $response->header('X-Traffic-Class', $classification['class']);

        // Update metrics
        $this->updateMetrics($classification);

        return $next($request, $response);
    }

    /**
     * Classify a request
     */
    public function classify(Request $request): array
    {
        $this->metrics['total_classified']++;

        // Check each rule
        foreach ($this->rules as $rule) {
            if ($this->matchesRule($request, $rule)) {
                return [
                    'priority' => $rule['priority'],
                    'class' => $this->getPriorityClass($rule['priority']),
                    'rule' => $rule['name'],
                    'metadata' => $rule['metadata'],
                ];
            }
        }

        // No rule matched
        $this->metrics['unmatched']++;

        return [
            'priority' => $this->defaultPriority,
            'class' => $this->getPriorityClass($this->defaultPriority),
            'rule' => 'default',
            'metadata' => [],
        ];
    }

    /**
     * Check if request matches a rule
     */
    private function matchesRule(Request $request, array $rule): bool
    {
        foreach ($rule['conditions'] as $condition) {
            if (!$this->matchesCondition($request, $condition)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if request matches a condition
     */
    private function matchesCondition(Request $request, array $condition): bool
    {
        return match ($condition['type']) {
            'path_pattern' => $this->matchesPathPattern($request, $condition['pattern']),
            'path_exact' => $request->getPathCallable() === $condition['path'],
            'method' => in_array($request->getMethod(), $condition['methods']),
            'header' => $this->matchesHeader($request, $condition['name'], $condition['value']),
            'user_agent' => $this->matchesUserAgent($request, $condition['pattern']),
            'ip_range' => $this->matchesIpRange($request, $condition['ranges']),
            'custom' => $condition['callback']($request),
            default => false,
        };
    }

    /**
     * Match path pattern
     */
    private function matchesPathPattern(Request $request, string $pattern): bool
    {
        return preg_match($pattern, $request->getPathCallable()) === 1;
    }

    /**
     * Match header
     */
    private function matchesHeader(Request $request, string $name, string $value): bool
    {
        $headerValue = $request->getHeaders()->get($name);

        if ($headerValue === null) {
            return false;
        }

        // Support wildcards in header values
        if (str_contains($value, '*')) {
            $pattern = '#^' . str_replace('*', '.*', $value) . '$#i';
            return preg_match($pattern, $headerValue) === 1;
        }

        return strcasecmp($headerValue, $value) === 0;
    }

    /**
     * Match user agent
     */
    private function matchesUserAgent(Request $request, string $pattern): bool
    {
        $userAgent = $request->userAgent();

        if (empty($userAgent)) {
            return false;
        }

        return stripos($userAgent, $pattern) !== false;
    }

    /**
     * Match IP range
     */
    private function matchesIpRange(Request $request, array $ranges): bool
    {
        $clientIp = $request->ip();

        foreach ($ranges as $range) {
            if ($this->ipInRange($clientIp, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP is in range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            // CIDR notation
            [$subnet, $mask] = explode('/', $range);
            $subnet = ip2long($subnet);
            $ip = ip2long($ip);
            $mask = -1 << (32 - (int) $mask);
            $subnet &= $mask;

            return ($ip & $mask) === $subnet;
        }

        // Single IP
        return $ip === $range;
    }

    /**
     * Get priority class name
     */
    private function getPriorityClass(int $priority): string
    {
        return match (true) {
            $priority >= self::PRIORITY_SYSTEM => 'system',
            $priority >= self::PRIORITY_CRITICAL => 'critical',
            $priority >= self::PRIORITY_HIGH => 'high',
            $priority >= self::PRIORITY_NORMAL => 'normal',
            $priority >= self::PRIORITY_LOW => 'low',
            default => 'batch',
        };
    }

    /**
     * Update metrics
     */
    private function updateMetrics(array $classification): void
    {
        $class = $classification['class'];
        $this->metrics['by_priority'][$class]++;

        $rule = $classification['rule'];
        if (!isset($this->metrics['by_rule'][$rule])) {
            $this->metrics['by_rule'][$rule] = 0;
        }
        $this->metrics['by_rule'][$rule]++;
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        return array_merge(
            $this->metrics,
            [
                'rules_count' => count($this->rules),
                'classification_rate' => $this->getClassificationRate(),
                'priority_distribution' => $this->getPriorityDistribution(),
            ]
        );
    }

    /**
     * Get classification rate
     */
    private function getClassificationRate(): float
    {
        $total = $this->metrics['total_classified'];

        if ($total === 0) {
            return 0.0;
        }

        return ($total - $this->metrics['unmatched']) / $total;
    }

    /**
     * Get priority distribution
     */
    private function getPriorityDistribution(): array
    {
        $total = array_sum($this->metrics['by_priority']);

        if ($total === 0) {
            return array_fill_keys(array_keys($this->metrics['by_priority']), 0.0);
        }

        $distribution = [];
        foreach ($this->metrics['by_priority'] as $class => $count) {
            $distribution[$class] = round($count / $total * 100, 2);
        }

        return $distribution;
    }

    /**
     * Add classification rule
     */
    public function addRule(array $rule): void
    {
        $this->rules[] = $this->compileRules([$rule])[0];
    }

    /**
     * Remove classification rule
     */
    public function removeRule(string $name): void
    {
        $this->rules = array_filter($this->rules, fn($rule) => $rule['name'] !== $name);
    }

    /**
     * Get active rules
     */
    public function getRules(): array
    {
        return array_map(
            fn($rule) => [
                'name' => $rule['name'],
                'priority' => $rule['priority'],
                'conditions' => count($rule['conditions']),
                'matches' => $this->metrics['by_rule'][$rule['name']] ?? 0,
            ],
            $this->rules
        );
    }
}
