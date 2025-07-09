<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Pool\Strategies;

use PivotPHP\Core\Http\Pool\Psr7Pool;

/**
 * Elastic expansion strategy for pool overflow
 * Temporarily allows pool to grow beyond normal limits
 */
class ElasticExpansion implements OverflowStrategy
{
    /**
     * Configuration
     */
    private array $config;

    /**
     * Elastic objects tracking
     */
    private array $elasticObjects = [];

    /**
     * Metrics
     */
    private array $metrics = [
        'elastic_creates' => 0,
        'elastic_returns' => 0,
        'max_elastic_size' => 0,
        'current_elastic' => 0,
    ];

    /**
     * Constructor
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Check if elastic expansion can handle this situation
     */
    public function canHandle(string $type, array $context): bool
    {
        // Check if we're within emergency limits
        if (!isset($context[$type])) {
            return false;
        }

        $state = $context[$type];
        $currentTotal = $state['current_size'] + $this->metrics['current_elastic'];

        return $currentTotal < $this->config['emergency_limit'];
    }

    /**
     * Handle by creating elastic object
     */
    public function handle(string $type, array $params): mixed
    {
        $this->metrics['elastic_creates']++;
        $this->metrics['current_elastic']++;

        if ($this->metrics['current_elastic'] > $this->metrics['max_elastic_size']) {
            $this->metrics['max_elastic_size'] = $this->metrics['current_elastic'];
        }

        // Create new object with elastic marker
        $object = $this->createElasticObject($type, $params);

        // Track elastic object
        $id = spl_object_id($object);
        $this->elasticObjects[$id] = [
            'type' => $type,
            'created_at' => microtime(true),
            'ttl' => $this->calculateTTL(),
        ];

        return $object;
    }

    /**
     * Create elastic object based on type
     */
    private function createElasticObject(string $type, array $params): mixed
    {
        return match ($type) {
            'request' => Psr7Pool::borrowRequest(),
            'response' => Psr7Pool::borrowResponse(),
            'uri' => Psr7Pool::borrowUri(),
            'stream' => Psr7Pool::borrowStream(),
            default => throw new \InvalidArgumentException("Unknown type: $type"),
        };
    }

    /**
     * Calculate TTL for elastic object
     */
    private function calculateTTL(): int
    {
        // Shorter TTL during high stress
        $stressFactor = min($this->metrics['current_elastic'] / 100, 1.0);
        $baseTTL = 300; // 5 minutes base

        return (int) ($baseTTL * (1 - $stressFactor * 0.8));
    }

    /**
     * Return elastic object
     */
    public function returnElastic(mixed $object): void
    {
        $id = spl_object_id($object);

        if (isset($this->elasticObjects[$id])) {
            unset($this->elasticObjects[$id]);
            $this->metrics['elastic_returns']++;
            $this->metrics['current_elastic']--;
        }
    }

    /**
     * Clean expired elastic objects
     */
    public function cleanExpired(): int
    {
        $now = microtime(true);
        $cleaned = 0;

        foreach ($this->elasticObjects as $id => $info) {
            if ($now - $info['created_at'] > $info['ttl']) {
                unset($this->elasticObjects[$id]);
                $this->metrics['current_elastic']--;
                $cleaned++;
            }
        }

        return $cleaned;
    }

    /**
     * Get metrics
     */
    public function getMetrics(): array
    {
        return array_merge(
            $this->metrics,
            [
                'elastic_objects' => count($this->elasticObjects),
                'oldest_elastic_age' => $this->getOldestAge(),
            ]
        );
    }

    /**
     * Get age of oldest elastic object
     */
    private function getOldestAge(): float
    {
        if (empty($this->elasticObjects)) {
            return 0.0;
        }

        $now = microtime(true);
        $oldest = PHP_FLOAT_MAX;

        foreach ($this->elasticObjects as $info) {
            $age = $now - $info['created_at'];
            if ($age < $oldest) {
                $oldest = $age;
            }
        }

        return $oldest;
    }
}
