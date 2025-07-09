<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Load shedding middleware to protect against overload
 */
class LoadShedder
{
    /**
     * Shedding strategies
     */
    public const STRATEGY_PRIORITY = 'priority';
    public const STRATEGY_RANDOM = 'random';
    public const STRATEGY_OLDEST = 'oldest';
    public const STRATEGY_ADAPTIVE = 'adaptive';

    /**
     * Configuration
     */
    private array $config = [
        'max_concurrent_requests' => 10000,
        'shed_strategy' => self::STRATEGY_PRIORITY,
        'shed_percentage' => 0.1, // Shed 10% when activated
        'activation_threshold' => 0.9, // Activate at 90% capacity
        'deactivation_threshold' => 0.7, // Deactivate at 70% capacity
        'check_interval' => 1, // Check every second
        'shed_response' => [
            'status' => 503,
            'body' => ['error' => 'Service temporarily at capacity'],
            'headers' => ['Retry-After' => '30'],
        ],
        'priority_thresholds' => [
            'system' => 1.0,    // Never shed
            'critical' => 0.95, // Shed at 95%
            'high' => 0.85,     // Shed at 85%
            'normal' => 0.75,   // Shed at 75%
            'low' => 0.65,      // Shed at 65%
            'batch' => 0.5,     // Shed at 50%
        ],
    ];

    /**
     * Current state
     */
    private array $state = [
        'active' => false,
        'current_requests' => 0,
        'shed_count' => 0,
        'accept_count' => 0,
        'last_check' => 0,
        'activation_time' => null,
        'load_history' => [],
    ];

    /**
     * Active requests tracking
     */
    private array $activeRequests = [];

    /**
     * Metrics
     */
    private array $metrics = [
        'total_requests' => 0,
        'shed_requests' => 0,
        'accepted_requests' => 0,
        'activations' => 0,
        'shed_by_strategy' => [],
        'shed_by_priority' => [],
    ];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
        $this->initializeMetrics();
    }

    /**
     * Initialize metrics
     */
    private function initializeMetrics(): void
    {
        $strategies = [
            self::STRATEGY_PRIORITY,
            self::STRATEGY_RANDOM,
            self::STRATEGY_OLDEST,
            self::STRATEGY_ADAPTIVE,
        ];

        foreach ($strategies as $strategy) {
            $this->metrics['shed_by_strategy'][$strategy] = 0;
        }

        $priorities = ['system', 'critical', 'high', 'normal', 'low', 'batch'];
        foreach ($priorities as $priority) {
            $this->metrics['shed_by_priority'][$priority] = 0;
        }
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, Response $response, callable $next): Response
    {
        $this->metrics['total_requests']++;

        // Check if should update shedding state
        $this->checkAndUpdateState();

        // Get request priority
        $priority = $request->getAttribute('traffic_priority', 50);
        $priorityClass = $request->getAttribute('traffic_class', 'normal');

        // Decide whether to shed this request
        if ($this->shouldShedRequest($priority, $priorityClass)) {
            return $this->shedRequest($response, $priorityClass);
        }

        // Accept request
        return $this->acceptRequest($request, $response, $next);
    }

    /**
     * Check and update shedding state
     */
    private function checkAndUpdateState(): void
    {
        $now = time();

        // Rate limit checks
        if ($now - $this->state['last_check'] < $this->config['check_interval']) {
            return;
        }

        $this->state['last_check'] = $now;

        // Calculate current load
        $load = $this->calculateLoad();

        // Update load history
        $this->updateLoadHistory($load);

        // Update shedding state
        if (!$this->state['active'] && $load >= $this->config['activation_threshold']) {
            $this->activateShedding();
        } elseif ($this->state['active'] && $load <= $this->config['deactivation_threshold']) {
            $this->deactivateShedding();
        }
    }

    /**
     * Calculate current load
     */
    private function calculateLoad(): float
    {
        $current = $this->state['current_requests'];
        $max = $this->config['max_concurrent_requests'];

        return $max > 0 ? $current / $max : 0.0;
    }

    /**
     * Update load history
     */
    private function updateLoadHistory(float $load): void
    {
        $this->state['load_history'][] = [
            'timestamp' => microtime(true),
            'load' => $load,
        ];

        // Keep only recent history (last minute)
        $cutoff = microtime(true) - 60;
        $this->state['load_history'] = array_filter(
            $this->state['load_history'],
            fn($entry) => $entry['timestamp'] > $cutoff
        );
    }

    /**
     * Activate shedding
     */
    private function activateShedding(): void
    {
        $this->state['active'] = true;
        $this->state['activation_time'] = microtime(true);
        $this->metrics['activations']++;

        // Log activation
        error_log(
            sprintf(
                "Load shedding activated - Current load: %.2f%%, Requests: %d/%d",
                $this->calculateLoad() * 100,
                $this->state['current_requests'],
                $this->config['max_concurrent_requests']
            )
        );
    }

    /**
     * Deactivate shedding
     */
    private function deactivateShedding(): void
    {
        $duration = microtime(true) - $this->state['activation_time'];

        $this->state['active'] = false;
        $this->state['activation_time'] = null;

        // Log deactivation
        error_log(
            sprintf(
                "Load shedding deactivated - Duration: %.2fs, Shed: %d requests",
                $duration,
                $this->state['shed_count']
            )
        );

        // Reset counters
        $this->state['shed_count'] = 0;
        $this->state['accept_count'] = 0;
    }

    /**
     * Should shed this request?
     */
    private function shouldShedRequest(int $priority, string $priorityClass): bool
    {
        // Never shed if not active
        if (!$this->state['active']) {
            return false;
        }

        // Apply strategy
        return match ($this->config['shed_strategy']) {
            self::STRATEGY_PRIORITY => $this->shouldShedByPriority($priority, $priorityClass),
            self::STRATEGY_RANDOM => $this->shouldShedByRandom(),
            self::STRATEGY_OLDEST => $this->shouldShedByOldest(),
            self::STRATEGY_ADAPTIVE => $this->shouldShedByAdaptive($priority, $priorityClass),
            default => false,
        };
    }

    /**
     * Priority-based shedding
     */
    private function shouldShedByPriority(int $priority, string $priorityClass): bool
    {
        $load = $this->calculateLoad();
        $threshold = $this->config['priority_thresholds'][$priorityClass] ?? 0.75;

        return $load >= $threshold;
    }

    /**
     * Random shedding
     */
    private function shouldShedByRandom(): bool
    {
        return mt_rand() / mt_getrandmax() < $this->config['shed_percentage'];
    }

    /**
     * Oldest request shedding
     */
    private function shouldShedByOldest(): bool
    {
        // In a real implementation, this would check request age
        // For now, use percentage-based shedding
        return $this->state['shed_count'] < ($this->state['current_requests'] * $this->config['shed_percentage']);
    }

    /**
     * Adaptive shedding based on multiple factors
     */
    private function shouldShedByAdaptive(int $priority, string $priorityClass): bool
    {
        $load = $this->calculateLoad();
        $loadTrend = $this->calculateLoadTrend();

        // Base shed probability on load
        $shedProbability = pow($load, 2); // Exponential increase

        // Adjust for load trend
        if ($loadTrend > 0) {
            // Load increasing - be more aggressive
            $shedProbability *= (1 + $loadTrend);
        }

        // Adjust for priority
        $priorityMultiplier = match ($priorityClass) {
            'system' => 0,      // Never shed
            'critical' => 0.1,  // Rarely shed
            'high' => 0.3,
            'normal' => 1.0,
            'low' => 2.0,
            'batch' => 3.0,
            default => 1.0,
        };

        $shedProbability *= $priorityMultiplier;

        // Cap probability
        $shedProbability = min($shedProbability, 0.95);

        return mt_rand() / mt_getrandmax() < $shedProbability;
    }

    /**
     * Calculate load trend
     */
    private function calculateLoadTrend(): float
    {
        if (count($this->state['load_history']) < 2) {
            return 0.0;
        }

        // Simple linear regression on recent load
        $recent = array_slice($this->state['load_history'], -10);

        if (count($recent) < 2) {
            return 0.0;
        }

        $firstLoad = $recent[0]['load'];
        $lastLoad = $recent[count($recent) - 1]['load'];
        $timeSpan = $recent[count($recent) - 1]['timestamp'] - $recent[0]['timestamp'];

        if ($timeSpan <= 0) {
            return 0.0;
        }

        // Rate of change per second
        return ($lastLoad - $firstLoad) / $timeSpan;
    }

    /**
     * Shed the request
     */
    private function shedRequest(Response $response, string $priorityClass): Response
    {
        $this->state['shed_count']++;
        $this->metrics['shed_requests']++;
        $this->metrics['shed_by_strategy'][$this->config['shed_strategy']]++;
        $this->metrics['shed_by_priority'][$priorityClass]++;

        $config = $this->config['shed_response'];

        return $response
            ->status($config['status'])
            ->json($config['body'])
            ->header('X-Load-Shed', 'true')
            ->header('X-Load-Shed-Reason', $this->config['shed_strategy']);

        foreach ($config['headers'] as $name => $value) {
            $response->header($name, (string) $value);
        }

        return $response;
    }

    /**
     * Accept the request
     */
    private function acceptRequest(Request $request, Response $response, callable $next): Response
    {
        $requestId = uniqid('req_', true);
        $startTime = microtime(true);

        // Track active request
        $this->activeRequests[$requestId] = [
            'start_time' => $startTime,
            'priority' => $request->getAttribute('traffic_priority', 50),
        ];

        $this->state['current_requests']++;
        $this->state['accept_count']++;
        $this->metrics['accepted_requests']++;

        try {
            // Process request
            $result = $next($request, $response);

            return $result;
        } finally {
            // Clean up
            unset($this->activeRequests[$requestId]);
            $this->state['current_requests']--;
        }
    }

    /**
     * Get current status
     */
    public function getStatus(): array
    {
        $load = $this->calculateLoad();

        return [
            'active' => $this->state['active'],
            'load' => round($load * 100, 2),
            'current_requests' => $this->state['current_requests'],
            'max_requests' => $this->config['max_concurrent_requests'],
            'shed_count' => $this->state['shed_count'],
            'accept_count' => $this->state['accept_count'],
            'strategy' => $this->config['shed_strategy'],
            'activation_time' => $this->state['activation_time'],
            'load_trend' => $this->calculateLoadTrend(),
        ];
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        $shedRate = $this->metrics['total_requests'] > 0
            ? $this->metrics['shed_requests'] / $this->metrics['total_requests']
            : 0.0;

        return array_merge(
            $this->metrics,
            [
                'shed_rate' => round($shedRate * 100, 2),
                'current_load' => round($this->calculateLoad() * 100, 2),
                'avg_load' => $this->getAverageLoad(),
                'peak_load' => $this->getPeakLoad(),
            ]
        );
    }

    /**
     * Get average load
     */
    private function getAverageLoad(): float
    {
        if (empty($this->state['load_history'])) {
            return 0.0;
        }

        $loads = array_column($this->state['load_history'], 'load');
        return round(array_sum($loads) / count($loads) * 100, 2);
    }

    /**
     * Get peak load
     */
    private function getPeakLoad(): float
    {
        if (empty($this->state['load_history'])) {
            return 0.0;
        }

        $loads = array_column($this->state['load_history'], 'load');
        return round(max($loads) * 100, 2);
    }

    /**
     * Update configuration
     */
    public function updateConfig(array $config): void
    {
        $this->config = array_merge($this->config, $config);

        // Validate configuration
        if ($this->config['deactivation_threshold'] >= $this->config['activation_threshold']) {
            throw new \InvalidArgumentException(
                'Deactivation threshold must be lower than activation threshold'
            );
        }
    }

    /**
     * Force activation (for testing)
     */
    public function forceActivate(): void
    {
        if (!$this->state['active']) {
            $this->activateShedding();
        }
    }

    /**
     * Force deactivation (for testing)
     */
    public function forceDeactivate(): void
    {
        if ($this->state['active']) {
            $this->deactivateShedding();
        }
    }
}
