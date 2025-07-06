<?php

declare(strict_types=1);

namespace Helix\Http\Psr7\Cache;

use Helix\Utils\SerializationCache;

/**
 * Advanced Probabilistic Cache Strategy
 *
 * Implements intelligent caching with probabilistic warming,
 * adaptive TTL, and statistical learning for optimal performance.
 *
 * @package Helix\Http\Psr7\Cache
 * @since 2.2.0
 */
class ProbabilisticCache
{
    /**
     * Cache storage
     *
     * @var array<string, array>
     */
    private static array $cache = [];

    /**
     * Access patterns for statistical learning
     *
     * @var array<string, array>
     */
    private static array $accessPatterns = [];

    /**
     * Cache warming probabilities
     *
     * @var array<string, float>
     */
    private static array $warmingProbabilities = [];

    /**
     * Cache statistics
     *
     * @var array<string, int>
     */
    private static array $stats = [
        'hits' => 0,
        'misses' => 0,
        'evictions' => 0,
        'preemptive_loads' => 0,
        'probability_adjustments' => 0
    ];

    /**
     * Configuration
     */
    private const DEFAULT_TTL = 3600; // 1 hour
    private const MIN_TTL = 60;       // 1 minute
    private const MAX_TTL = 86400;    // 24 hours
    private const BETA = 1.0;         // Variance factor for probabilistic early expiration
    private const LEARNING_RATE = 0.1; // Rate of adaptation for probabilities

    /**
     * Get value from cache with probabilistic early expiration
     *
     * @param string $key
     * @param callable|null $loader Fallback loader function
     * @return mixed
     */
    public static function get(string $key, ?callable $loader = null)
    {
        $now = time();

        // Record access pattern
        self::recordAccess($key);

        if (!isset(self::$cache[$key])) {
            self::$stats['misses']++;

            if ($loader !== null) {
                $value = $loader();
                $ttl = self::calculateAdaptiveTTL($key);
                self::set($key, $value, $ttl);
                return $value;
            }

            return null;
        }

        $entry = self::$cache[$key];
        $expiry = $entry['expiry'];
        $delta = $expiry - $now;

        // Probabilistic early expiration (XFetch algorithm)
        if ($delta <= 0) {
            // Expired
            unset(self::$cache[$key]);
            self::$stats['misses']++;

            if ($loader !== null) {
                $value = $loader();
                $ttl = self::calculateAdaptiveTTL($key);
                self::set($key, $value, $ttl);
                return $value;
            }

            return null;
        }

        // Probabilistic refresh for frequently accessed items
        if ($loader !== null && self::shouldPreemptivelyRefresh($key, $delta, $entry['ttl'])) {
            // Asynchronous refresh simulation (in real implementation, use queue/background job)
            $value = $loader();
            $ttl = self::calculateAdaptiveTTL($key);
            self::set($key, $value, $ttl);
            self::$stats['preemptive_loads']++;
            return $value;
        }

        self::$stats['hits']++;
        return $entry['value'];
    }

    /**
     * Set value in cache
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @return void
     */
    public static function set(string $key, $value, ?int $ttl = null): void
    {
        if ($ttl === null) {
            $ttl = self::calculateAdaptiveTTL($key);
        }

        self::$cache[$key] = [
            'value' => $value,
            'ttl' => $ttl,
            'expiry' => time() + $ttl,
            'created_at' => time(),
            'access_count' => 0,
            'last_access' => time()
        ];

        // Update warming probability
        self::updateWarmingProbability($key);

        // Cleanup old entries if memory pressure
        self::conditionalCleanup();
    }

    /**
     * Probabilistic cache warming based on access patterns
     *
     * @param int $maxItems Maximum items to warm
     * @return int Number of items warmed
     */
    public static function warmCache(int $maxItems = 10): int
    {
        $warmed = 0;
        $candidates = self::getWarmingCandidates();

        foreach (array_slice($candidates, 0, $maxItems) as $key => $probability) {
            if (mt_rand(0, 100) / 100 <= $probability) {
                // In real implementation, trigger background loading
                self::$stats['preemptive_loads']++;
                $warmed++;
            }
        }

        return $warmed;
    }

    /**
     * Calculate adaptive TTL based on access patterns
     *
     * @param string $key
     * @return int
     */
    private static function calculateAdaptiveTTL(string $key): int
    {
        if (!isset(self::$accessPatterns[$key])) {
            return self::DEFAULT_TTL;
        }

        $pattern = self::$accessPatterns[$key];
        $accessFrequency = $pattern['access_count'] / max(1, $pattern['time_span']);

        // Higher frequency = longer TTL (up to maximum)
        $adaptiveTTL = (int)(self::DEFAULT_TTL * (1 + log(1 + $accessFrequency)));

        return max(self::MIN_TTL, min(self::MAX_TTL, $adaptiveTTL));
    }

    /**
     * Determine if item should be preemptively refreshed
     *
     * @param string $key
     * @param int $delta Time until expiry
     * @param int $ttl Original TTL
     * @return bool
     */
    private static function shouldPreemptivelyRefresh(string $key, int $delta, int $ttl): bool
    {
        // XFetch algorithm implementation
        $probability = self::BETA * log(mt_rand(1, PHP_INT_MAX) / PHP_INT_MAX) * $delta;

        // Increase probability for frequently accessed items
        $accessFrequency = self::getAccessFrequency($key);
        $adjustedProbability = $probability * (1 + $accessFrequency / 10);

        return $adjustedProbability < -1;
    }

    /**
     * Record access pattern for learning
     *
     * @param string $key
     * @return void
     */
    private static function recordAccess(string $key): void
    {
        $now = time();

        if (!isset(self::$accessPatterns[$key])) {
            self::$accessPatterns[$key] = [
                'first_access' => $now,
                'last_access' => $now,
                'access_count' => 1,
                'time_span' => 1,
                'intervals' => []
            ];
        } else {
            $pattern = &self::$accessPatterns[$key];
            $interval = $now - $pattern['last_access'];

            $pattern['intervals'][] = $interval;
            $pattern['access_count']++;
            $pattern['last_access'] = $now;
            $pattern['time_span'] = $now - $pattern['first_access'];

            // Keep only recent intervals for pattern analysis
            if (count($pattern['intervals']) > 100) {
                array_shift($pattern['intervals']);
            }
        }

        // Update cache entry access info if exists
        if (isset(self::$cache[$key])) {
            self::$cache[$key]['access_count']++;
            self::$cache[$key]['last_access'] = $now;
        }
    }

    /**
     * Update warming probability based on access patterns
     *
     * @param string $key
     * @return void
     */
    private static function updateWarmingProbability(string $key): void
    {
        if (!isset(self::$accessPatterns[$key])) {
            self::$warmingProbabilities[$key] = 0.1; // Default low probability
            return;
        }

        $pattern = self::$accessPatterns[$key];
        $frequency = $pattern['access_count'] / max(1, $pattern['time_span']);

        // Calculate probability based on frequency and regularity
        $regularity = self::calculateAccessRegularity($pattern['intervals']);
        $probability = min(0.9, $frequency * $regularity);

        // Apply learning rate for gradual adjustment
        if (isset(self::$warmingProbabilities[$key])) {
            $currentProb = self::$warmingProbabilities[$key];
            self::$warmingProbabilities[$key] =
                $currentProb + self::LEARNING_RATE * ($probability - $currentProb);
        } else {
            self::$warmingProbabilities[$key] = $probability;
        }

        self::$stats['probability_adjustments']++;
    }

    /**
     * Calculate access regularity (inverse of variance)
     *
     * @param array $intervals
     * @return float
     */
    private static function calculateAccessRegularity(array $intervals): float
    {
        if (count($intervals) < 2) {
            return 0.5; // Medium regularity for insufficient data
        }

        $mean = array_sum($intervals) / count($intervals);
        $variance = 0;

        foreach ($intervals as $interval) {
            $variance += pow($interval - $mean, 2);
        }

        $variance = $variance / count($intervals);

        // Return inverse of coefficient of variation (normalized regularity)
        return $mean > 0 ? 1 / (1 + sqrt($variance) / $mean) : 0;
    }

    /**
     * Get access frequency for a key
     *
     * @param string $key
     * @return float
     */
    private static function getAccessFrequency(string $key): float
    {
        if (!isset(self::$accessPatterns[$key])) {
            return 0;
        }

        $pattern = self::$accessPatterns[$key];
        return $pattern['access_count'] / max(1, $pattern['time_span']);
    }

    /**
     * Get candidates for cache warming
     *
     * @return array<string, float>
     */
    private static function getWarmingCandidates(): array
    {
        // Sort by warming probability descending
        arsort(self::$warmingProbabilities);

        return array_filter(
            self::$warmingProbabilities,
            function ($probability) {
                return $probability > 0.3; // Only warm high-probability items
            }
        );
    }

    /**
     * Conditional cleanup based on memory usage
     *
     * @return void
     */
    private static function conditionalCleanup(): void
    {
        $cacheSize = count(self::$cache);

        if ($cacheSize > 1000) { // Threshold for cleanup
            $now = time();
            $toRemove = [];

            foreach (self::$cache as $key => $entry) {
                // Remove expired entries
                if ($entry['expiry'] < $now) {
                    $toRemove[] = $key;
                }
            }

            // If still too many, remove least recently used
            if ($cacheSize - count($toRemove) > 800) {
                $lruCandidates = self::$cache;
                uasort($lruCandidates, function ($a, $b) {
                    return $a['last_access'] <=> $b['last_access'];
                });

                $additional = array_slice(array_keys($lruCandidates), 0, $cacheSize - 800);
                $toRemove = array_merge($toRemove, $additional);
            }

            foreach ($toRemove as $key) {
                unset(self::$cache[$key]);
                self::$stats['evictions']++;
            }
        }
    }

    /**
     * Clear all cache
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
        self::$accessPatterns = [];
        self::$warmingProbabilities = [];
    }

    /**
     * Get cache statistics
     *
     * @return array
     */
    public static function getStats(): array
    {
        $hitRate = self::$stats['hits'] + self::$stats['misses'] > 0
            ? self::$stats['hits'] / (self::$stats['hits'] + self::$stats['misses'])
            : 0;

        return array_merge(self::$stats, [
            'hit_rate' => $hitRate,
            'cache_size' => count(self::$cache),
            'patterns_tracked' => count(self::$accessPatterns),
            'warming_candidates' => count(self::getWarmingCandidates())
        ]);
    }

    /**
     * Get memory usage information
     *
     * @return array
     */
    public static function getMemoryInfo(): array
    {
        return [
            'cache_memory_usage' => memory_get_usage(),
            'cache_entries' => count(self::$cache),
            'pattern_entries' => count(self::$accessPatterns),
            'probability_entries' => count(self::$warmingProbabilities)
        ];
    }
}
