<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Circuit Breaker middleware to prevent cascade failures
 */
class CircuitBreaker
{
    /**
     * Circuit states
     */
    public const STATE_CLOSED = 'closed';      // Normal operation
    public const STATE_OPEN = 'open';          // Failing, reject requests
    public const STATE_HALF_OPEN = 'half_open'; // Testing recovery

    /**
     * Configuration
     */
    private array $config = [
        'failure_threshold' => 50,          // Failures per minute to open
        'success_threshold' => 10,          // Successes in half-open to close
        'timeout' => 30,                    // Seconds before half-open
        'half_open_requests' => 10,         // Max requests in half-open
        'excluded_paths' => ['/health', '/metrics'],
        'failure_status_codes' => [500, 502, 503, 504],
        'slow_threshold' => 5000,           // 5 seconds is "slow"
        'volume_threshold' => 20,           // Min requests before opening
        'error_percentage_threshold' => 50,  // Error % to open circuit
    ];

    /**
     * Circuit states by service/path
     */
    private array $circuits = [];

    /**
     * Global metrics
     */
    private array $metrics = [
        'total_requests' => 0,
        'total_failures' => 0,
        'total_successes' => 0,
        'total_timeouts' => 0,
        'circuit_opens' => 0,
        'circuit_closes' => 0,
        'rejected_requests' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * Handle the request
     */
    public function handle(Request $request, Response $response, callable $next): Response
    {
        $this->metrics['total_requests']++;

        // Check if path is excluded
        if ($this->isExcluded($request->getPathCallable())) {
            return $next($request, $response);
        }

        // Get circuit for this request
        $circuitName = $this->getCircuitName($request);
        $circuit = $this->getOrCreateCircuit($circuitName);

        // Check circuit state
        $state = $this->getCircuitState($circuit);

        if ($state === self::STATE_OPEN) {
            return $this->rejectRequest($response, $circuit);
        }

        if ($state === self::STATE_HALF_OPEN) {
            if ($circuit['half_open_requests'] >= $this->config['half_open_requests']) {
                return $this->rejectRequest($response, $circuit);
            }
            $circuit['half_open_requests']++;
        }

        // Execute request with monitoring
        return $this->executeWithMonitoring($request, $response, $next, $circuit);
    }

    /**
     * Check if path is excluded
     */
    private function isExcluded(string $path): bool
    {
        foreach ($this->config['excluded_paths'] as $excluded) {
            if (str_starts_with($path, $excluded)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get circuit name for request
     */
    private function getCircuitName(Request $request): string
    {
        // Could be more sophisticated - by service, endpoint, etc.
        // For now, use path pattern
        $path = $request->path ?? $request->getPathCallable();

        // Normalize path to circuit name
        $path = trim($path, '/');
        if ($path === '') {
            return 'default';
        }
        
        $parts = explode('/', $path);

        // Group by first two segments (e.g., /api/users/* becomes api_users)
        $circuitParts = array_slice($parts, 0, 2);

        return implode('_', $circuitParts);
    }

    /**
     * Get or create circuit
     */
    private function getOrCreateCircuit(string $name): array
    {
        if (!isset($this->circuits[$name])) {
            $this->circuits[$name] = [
                'name' => $name,
                'state' => self::STATE_CLOSED,
                'failures' => [],
                'successes' => [],
                'last_failure_time' => null,
                'last_success_time' => null,
                'opened_at' => null,
                'half_open_requests' => 0,
                'consecutive_successes' => 0,
                'consecutive_failures' => 0,
                'total_requests' => 0,
                'total_failures' => 0,
                'total_successes' => 0,
            ];
        }

        return $this->circuits[$name];
    }

    /**
     * Get current circuit state
     */
    private function getCircuitState(array &$circuit): string
    {
        $now = time();

        // Update state based on current conditions
        switch ($circuit['state']) {
            case self::STATE_CLOSED:
                if ($this->shouldOpen($circuit)) {
                    $this->openCircuit($circuit);
                }
                break;

            case self::STATE_OPEN:
                if ($circuit['opened_at'] && ($now - $circuit['opened_at']) >= $this->config['timeout']) {
                    $this->halfOpenCircuit($circuit);
                }
                break;

            case self::STATE_HALF_OPEN:
                // State will be updated based on request results
                break;
        }

        return $circuit['state'];
    }

    /**
     * Should open circuit?
     */
    private function shouldOpen(array $circuit): bool
    {
        // Clean old entries
        $this->cleanOldEntries($circuit);

        // Check volume threshold
        $recentRequests = count($circuit['failures']) + count($circuit['successes']);
        if ($recentRequests < $this->config['volume_threshold']) {
            return false;
        }

        // Check failure threshold
        $recentFailures = count($circuit['failures']);
        if ($recentFailures >= $this->config['failure_threshold']) {
            return true;
        }

        // Check error percentage
        if ($recentRequests > 0) {
            $errorPercentage = ($recentFailures / $recentRequests) * 100;
            if ($errorPercentage >= $this->config['error_percentage_threshold']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Clean old entries from circuit
     */
    private function cleanOldEntries(array &$circuit): void
    {
        $cutoff = time() - 60; // Keep last minute

        $circuit['failures'] = array_filter(
            $circuit['failures'],
            fn($timestamp) => $timestamp > $cutoff
        );

        $circuit['successes'] = array_filter(
            $circuit['successes'],
            fn($timestamp) => $timestamp > $cutoff
        );
    }

    /**
     * Open the circuit
     */
    private function openCircuit(array &$circuit): void
    {
        $circuit['state'] = self::STATE_OPEN;
        $circuit['opened_at'] = time();
        $circuit['consecutive_successes'] = 0;
        $this->metrics['circuit_opens']++;

        error_log(
            sprintf(
                "Circuit breaker opened for '%s' - Failures: %d, Error rate: %.1f%%",
                $circuit['name'],
                count($circuit['failures']),
                $this->getErrorRate($circuit)
            )
        );
    }

    /**
     * Half-open the circuit
     */
    private function halfOpenCircuit(array &$circuit): void
    {
        $circuit['state'] = self::STATE_HALF_OPEN;
        $circuit['half_open_requests'] = 0;
        $circuit['consecutive_successes'] = 0;

        error_log(
            sprintf(
                "Circuit breaker half-opened for '%s' - Testing recovery",
                $circuit['name']
            )
        );
    }

    /**
     * Close the circuit
     */
    private function closeCircuit(array &$circuit): void
    {
        $circuit['state'] = self::STATE_CLOSED;
        $circuit['opened_at'] = null;
        $circuit['consecutive_failures'] = 0;
        $circuit['half_open_requests'] = 0;
        $this->metrics['circuit_closes']++;

        error_log(
            sprintf(
                "Circuit breaker closed for '%s' - Service recovered",
                $circuit['name']
            )
        );
    }

    /**
     * Execute request with monitoring
     */
    private function executeWithMonitoring(
        Request $request,
        Response $response,
        callable $next,
        array &$circuit
    ): Response {
        $startTime = microtime(true);
        $circuit['total_requests']++;

        try {
            // Execute the request
            $result = $next($request, $response);

            // Check response status
            $elapsed = (microtime(true) - $startTime) * 1000; // ms

            if ($this->isFailure($result, $elapsed)) {
                $this->recordFailure($circuit);
            } else {
                $this->recordSuccess($circuit);
            }

            // Add circuit info to response headers
            $result->header('X-Circuit-State', $circuit['state']);
            $result->header('X-Circuit-Name', $circuit['name']);

            return $result;
        } catch (\Throwable $e) {
            // Record failure
            $this->recordFailure($circuit);

            // Re-throw the exception
            throw $e;
        }
    }

    /**
     * Check if response is a failure
     */
    private function isFailure(Response $response, float $elapsed): bool
    {
        // Check status code
        if (in_array($response->getStatusCode(), $this->config['failure_status_codes'])) {
            return true;
        }

        // Check if slow
        if ($elapsed > $this->config['slow_threshold']) {
            return true;
        }

        return false;
    }

    /**
     * Record failure
     */
    private function recordFailure(array &$circuit): void
    {
        $now = time();

        $circuit['failures'][] = $now;
        $circuit['last_failure_time'] = $now;
        $circuit['consecutive_failures']++;
        $circuit['consecutive_successes'] = 0;
        $circuit['total_failures']++;

        $this->metrics['total_failures']++;

        // Update state based on failure
        if ($circuit['state'] === self::STATE_HALF_OPEN) {
            // Single failure in half-open reopens circuit
            $this->openCircuit($circuit);
        }
    }

    /**
     * Record success
     */
    private function recordSuccess(array &$circuit): void
    {
        $now = time();

        $circuit['successes'][] = $now;
        $circuit['last_success_time'] = $now;
        $circuit['consecutive_successes']++;
        $circuit['consecutive_failures'] = 0;
        $circuit['total_successes']++;

        $this->metrics['total_successes']++;

        // Update state based on success
        if ($circuit['state'] === self::STATE_HALF_OPEN) {
            if ($circuit['consecutive_successes'] >= $this->config['success_threshold']) {
                $this->closeCircuit($circuit);
            }
        }
    }

    /**
     * Reject request due to open circuit
     */
    private function rejectRequest(Response $response, array $circuit): Response
    {
        $this->metrics['rejected_requests']++;

        $timeUntilRetry = max(
            0,
            $this->config['timeout'] - (time() - $circuit['opened_at'])
        );

        return $response
            ->status(503)
            ->json(
                [
                    'error' => 'Service temporarily unavailable',
                    'circuit_state' => $circuit['state'],
                    'circuit_name' => $circuit['name'],
                    'retry_after' => $timeUntilRetry,
                ]
            )
            ->header('X-Circuit-State', $circuit['state'])
            ->header('X-Circuit-Name', $circuit['name'])
            ->header('Retry-After', (string) $timeUntilRetry);
    }

    /**
     * Get error rate for circuit
     */
    private function getErrorRate(array $circuit): float
    {
        $total = count($circuit['failures']) + count($circuit['successes']);

        if ($total === 0) {
            return 0.0;
        }

        return (count($circuit['failures']) / $total) * 100;
    }

    /**
     * Get circuit status
     */
    public function getCircuitStatus(?string $name = null): array
    {
        if ($name !== null) {
            return isset($this->circuits[$name])
                ? $this->formatCircuitStatus($this->circuits[$name])
                : ['error' => 'Circuit not found'];
        }

        // Return all circuits
        $status = [];
        foreach ($this->circuits as $circuit) {
            $status[$circuit['name']] = $this->formatCircuitStatus($circuit);
        }

        return $status;
    }

    /**
     * Format circuit status
     */
    private function formatCircuitStatus(array $circuit): array
    {
        $this->cleanOldEntries($circuit);

        return [
            'state' => $circuit['state'],
            'error_rate' => round($this->getErrorRate($circuit), 2),
            'recent_failures' => count($circuit['failures']),
            'recent_successes' => count($circuit['successes']),
            'consecutive_failures' => $circuit['consecutive_failures'],
            'consecutive_successes' => $circuit['consecutive_successes'],
            'total_requests' => $circuit['total_requests'],
            'total_failures' => $circuit['total_failures'],
            'total_successes' => $circuit['total_successes'],
            'opened_at' => $circuit['opened_at'],
            'time_until_retry' => $circuit['state'] === self::STATE_OPEN
                ? max(0, $this->config['timeout'] - (time() - $circuit['opened_at']))
                : null,
        ];
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        $activeCircuits = array_filter(
            $this->circuits,
            fn($c) => $c['state'] !== self::STATE_CLOSED
        );

        return array_merge(
            $this->metrics,
            [
                'total_circuits' => count($this->circuits),
                'open_circuits' => count(array_filter($this->circuits, fn($c) => $c['state'] === self::STATE_OPEN)),
                'half_open_circuits' => count(
                    array_filter($this->circuits, fn($c) => $c['state'] === self::STATE_HALF_OPEN)
                ),
                'success_rate' => $this->calculateSuccessRate(),
                'rejection_rate' => $this->calculateRejectionRate(),
            ]
        );
    }

    /**
     * Calculate global success rate
     */
    private function calculateSuccessRate(): float
    {
        $total = $this->metrics['total_successes'] + $this->metrics['total_failures'];

        if ($total === 0) {
            return 100.0;
        }

        return round(($this->metrics['total_successes'] / $total) * 100, 2);
    }

    /**
     * Calculate rejection rate
     */
    private function calculateRejectionRate(): float
    {
        if ($this->metrics['total_requests'] === 0) {
            return 0.0;
        }

        return round(($this->metrics['rejected_requests'] / $this->metrics['total_requests']) * 100, 2);
    }

    /**
     * Reset circuit
     */
    public function resetCircuit(string $name): void
    {
        if (isset($this->circuits[$name])) {
            $this->circuits[$name] = $this->getOrCreateCircuit($name);
            $this->circuits[$name]['state'] = self::STATE_CLOSED;
        }
    }

    /**
     * Force circuit state (for testing)
     */
    public function forceState(string $name, string $state): void
    {
        $circuit = &$this->getOrCreateCircuit($name);

        switch ($state) {
            case self::STATE_OPEN:
                $this->openCircuit($circuit);
                break;
            case self::STATE_HALF_OPEN:
                $this->halfOpenCircuit($circuit);
                break;
            case self::STATE_CLOSED:
                $this->closeCircuit($circuit);
                break;
        }
    }
}
