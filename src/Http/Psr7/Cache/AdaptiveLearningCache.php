<?php

declare(strict_types=1);

namespace Helix\Http\Psr7\Cache;

/**
 * Adaptive Learning Cache Strategy
 *
 * Implements machine learning-inspired caching that adapts to usage patterns
 * and optimizes cache behavior based on historical data.
 *
 * @package Helix\Http\Psr7\Cache
 * @since 2.2.0
 */
class AdaptiveLearningCache
{
    /**
     * Cache storage with metadata
     *
     * @var array<string, array>
     */
    private static array $cache = [];

    /**
     * Learning model for each cache key
     *
     * @var array<string, array>
     */
    private static array $learningModels = [];

    /**
     * Global cache behavior statistics
     *
     * @var array<string, mixed>
     */
    private static array $globalStats = [
        'total_requests' => 0,
        'cache_hits' => 0,
        'cache_misses' => 0,
        'adaptations_made' => 0,
        'prediction_accuracy' => 0.0,
        'learning_cycles' => 0
    ];

    /**
     * Cache configuration that adapts over time
     *
     * @var array<string, mixed>
     */
    private static array $adaptiveConfig = [
        'default_ttl' => 3600,
        'max_cache_size' => 1000,
        'learning_window' => 100,
        'adaptation_threshold' => 0.05,
        'eviction_strategy' => 'lru_adaptive'
    ];

    /**
     * Feature extractors for learning
     *
     * @var array<string, callable>
     */
    private static array $featureExtractors = [];

    /**
     * Initialize the learning cache system
     */
    public static function initialize(): void
    {
        self::initializeFeatureExtractors();
        self::loadPersistedModels();
    }

    /**
     * Get value with adaptive learning
     *
     * @param string $key
     * @param callable|null $loader
     * @param array $context Additional context for learning
     * @return mixed
     */
    public static function get(string $key, ?callable $loader = null, array $context = [])
    {
        self::$globalStats['total_requests']++;

        $prediction = self::predictCacheUtility($key, $context);
        $hit = self::attemptCacheRetrieval($key);

        if ($hit !== null) {
            self::$globalStats['cache_hits']++;
            self::recordSuccessfulPrediction($key, true, $prediction);
            self::updateLearningModel($key, $context, true);
            return $hit['value'];
        }

        self::$globalStats['cache_misses']++;
        self::recordSuccessfulPrediction($key, false, $prediction);

        if ($loader === null) {
            return null;
        }

        $value = $loader();
        $shouldCache = self::shouldCacheBasedOnLearning($key, $context, $prediction);

        if ($shouldCache) {
            $adaptiveTTL = self::calculateAdaptiveTTL($key, $context);
            self::set($key, $value, $adaptiveTTL, $context);
        }

        self::updateLearningModel($key, $context, false);

        return $value;
    }

    /**
     * Set value with learning
     *
     * @param string $key
     * @param mixed $value
     * @param int|null $ttl
     * @param array $context
     * @return void
     */
    public static function set(string $key, $value, ?int $ttl = null, array $context = []): void
    {
        if ($ttl === null) {
            $ttl = self::calculateAdaptiveTTL($key, $context);
        }

        $now = time();
        $entry = [
            'value' => $value,
            'ttl' => $ttl,
            'expiry' => $now + $ttl,
            'created_at' => $now,
            'access_count' => 0,
            'last_access' => $now,
            'context' => $context,
            'predicted_utility' => self::predictCacheUtility($key, $context),
            'actual_utility' => 0
        ];

        self::$cache[$key] = $entry;
        self::enforceAdaptiveCacheSize();
        self::scheduleModelUpdate($key, $context);
    }

    /**
     * Predict cache utility using learning model
     *
     * @param string $key
     * @param array $context
     * @return float Utility score between 0 and 1
     */
    private static function predictCacheUtility(string $key, array $context): float
    {
        if (!isset(self::$learningModels[$key])) {
            return 0.5; // Default neutral prediction
        }

        $model = self::$learningModels[$key];
        $features = self::extractFeatures($key, $context);

        return self::evaluateModel($model, $features);
    }

    /**
     * Extract features for machine learning
     *
     * @param string $key
     * @param array $context
     * @return array
     */
    private static function extractFeatures(string $key, array $context): array
    {
        $features = [
            'hour_of_day' => (int)date('H'),
            'day_of_week' => (int)date('w'),
            'key_length' => strlen($key),
            'key_hash' => crc32($key) % 1000,
            'context_size' => count($context),
            'time_since_start' => time() - ($_SERVER['REQUEST_TIME'] ?? time())
        ];

        // Add custom feature extractors
        foreach (self::$featureExtractors as $name => $extractor) {
            try {
                $features[$name] = $extractor($key, $context);
            } catch (\Throwable $e) {
                $features[$name] = 0; // Default on error
            }
        }

        // Add historical features if available
        if (isset(self::$learningModels[$key]['history']) && !empty(self::$learningModels[$key]['history'])) {
            $history = self::$learningModels[$key]['history'];
            $features['avg_ttl'] = array_sum(array_column($history, 'ttl')) / count($history);
            $timeDiff = max(1, time() - $history[0]['timestamp']);
            $features['access_frequency'] = count($history) / $timeDiff;
        } else {
            $features['avg_ttl'] = 0;
            $features['access_frequency'] = 0;
        }

        return $features;
    }

    /**
     * Evaluate model prediction
     *
     * @param array $model
     * @param array $features
     * @return float
     */
    private static function evaluateModel(array $model, array $features): float
    {
        if (!isset($model['weights']) || !isset($model['bias'])) {
            return 0.5;
        }

        $score = $model['bias'];

        foreach ($model['weights'] as $feature => $weight) {
            if (isset($features[$feature])) {
                $score += $weight * $features[$feature];
            }
        }

        // Apply sigmoid activation for probability
        return 1 / (1 + exp(-$score));
    }

    /**
     * Update learning model with new data point
     *
     * @param string $key
     * @param array $context
     * @param bool $wasHit
     * @return void
     */
    private static function updateLearningModel(string $key, array $context, bool $wasHit): void
    {
        if (!isset(self::$learningModels[$key])) {
            self::$learningModels[$key] = [
                'weights' => [],
                'bias' => 0.0,
                'history' => [],
                'learning_rate' => 0.01,
                'accuracy' => 0.5
            ];
        }

        $model = &self::$learningModels[$key];
        $features = self::extractFeatures($key, $context);
        $target = $wasHit ? 1.0 : 0.0;
        $prediction = self::evaluateModel($model, $features);

        // Gradient descent update
        $error = $target - $prediction;
        $learningRate = $model['learning_rate'];

        // Update bias
        $model['bias'] += $learningRate * $error;

        // Update weights
        foreach ($features as $feature => $value) {
            if (!isset($model['weights'][$feature])) {
                $model['weights'][$feature] = 0.0;
            }
            $model['weights'][$feature] += $learningRate * $error * $value;
        }

        // Add to history
        $model['history'][] = [
            'timestamp' => time(),
            'features' => $features,
            'target' => $target,
            'prediction' => $prediction,
            'ttl' => $context['ttl'] ?? self::$adaptiveConfig['default_ttl']
        ];

        // Trim history to learning window
        if (count($model['history']) > self::$adaptiveConfig['learning_window']) {
            array_shift($model['history']);
        }

        // Update model accuracy
        self::updateModelAccuracy($key);

        self::$globalStats['learning_cycles']++;

        // Adapt learning rate based on performance
        self::adaptLearningRate($key);
    }

    /**
     * Calculate adaptive TTL based on learned patterns
     *
     * @param string $key
     * @param array $context
     * @return int
     */
    private static function calculateAdaptiveTTL(string $key, array $context): int
    {
        $baseTTL = self::$adaptiveConfig['default_ttl'];
        $safeTTL = is_int($baseTTL) ? $baseTTL : 3600;

        if (!isset(self::$learningModels[$key])) {
            return $safeTTL;
        }

        $model = self::$learningModels[$key];

        if (empty($model['history'])) {
            return $safeTTL;
        }

        // Calculate TTL based on access patterns
        $recentHistory = array_slice($model['history'], -20); // Last 20 accesses
        $avgInterval = self::calculateAverageAccessInterval($recentHistory);

        if ($avgInterval > 0) {
            // TTL should be proportional to access interval
            $adaptiveTTL = (int)($avgInterval * 2); // Cache for 2x the average interval

            // Apply bounds
            $adaptiveTTL = max(60, min(86400, $adaptiveTTL)); // Between 1 minute and 1 day

            return $adaptiveTTL;
        }

        return $safeTTL;
    }

    /**
     * Determine if item should be cached based on learning
     *
     * @param string $key
     * @param array $context
     * @param float $prediction
     * @return bool
     */
    private static function shouldCacheBasedOnLearning(string $key, array $context, float $prediction): bool
    {
        // Base decision on utility prediction
        if ($prediction < 0.3) {
            return false; // Low utility, don't cache
        }

        // Consider cache size pressure
        $cacheUtilization = count(self::$cache) / self::$adaptiveConfig['max_cache_size'];

        if ($cacheUtilization > 0.9) {
            // High pressure, be more selective
            return $prediction > 0.7;
        }

        if ($cacheUtilization > 0.7) {
            // Medium pressure, moderate threshold
            return $prediction > 0.5;
        }

        // Low pressure, cache more liberally
        return true;
    }

    /**
     * Adaptive cache size enforcement
     *
     * @return void
     */
    private static function enforceAdaptiveCacheSize(): void
    {
        $cacheSize = count(self::$cache);
        $maxSize = self::$adaptiveConfig['max_cache_size'];

        if ($cacheSize <= $maxSize) {
            return;
        }

        $toEvict = $cacheSize - (int)($maxSize * 0.9); // Evict to 90% capacity

        switch (self::$adaptiveConfig['eviction_strategy']) {
            case 'lru_adaptive':
                self::evictLRUAdaptive($toEvict);
                break;
            case 'utility_based':
                self::evictUtilityBased($toEvict);
                break;
            default:
                self::evictLRU($toEvict);
        }
    }

    /**
     * LRU eviction with adaptive considerations
     *
     * @param int $count
     * @return void
     */
    private static function evictLRUAdaptive(int $count): void
    {
        $candidates = [];

        foreach (self::$cache as $key => $entry) {
            $utility = $entry['predicted_utility'] ?? 0.5;
            $age = time() - $entry['last_access'];
            $adaptiveScore = $age / (1 + $utility); // Age weighted by inverse utility

            $candidates[$key] = $adaptiveScore;
        }

        arsort($candidates); // Highest scores (oldest with low utility) first

        $evicted = 0;
        foreach (array_keys($candidates) as $key) {
            if ($evicted >= $count) {
                break;
            }

            unset(self::$cache[$key]);
            $evicted++;
        }
    }

    /**
     * Utility-based eviction
     *
     * @param int $count
     * @return void
     */
    private static function evictUtilityBased(int $count): void
    {
        $utilities = [];

        foreach (self::$cache as $key => $entry) {
            $utilities[$key] = $entry['predicted_utility'] ?? 0.5;
        }

        asort($utilities); // Lowest utility first

        $evicted = 0;
        foreach (array_keys($utilities) as $key) {
            if ($evicted >= $count) {
                break;
            }

            unset(self::$cache[$key]);
            $evicted++;
        }
    }

    /**
     * Standard LRU eviction
     *
     * @param int $count
     * @return void
     */
    private static function evictLRU(int $count): void
    {
        $accessTimes = [];

        foreach (self::$cache as $key => $entry) {
            $accessTimes[$key] = $entry['last_access'];
        }

        asort($accessTimes); // Oldest first

        $evicted = 0;
        foreach (array_keys($accessTimes) as $key) {
            if ($evicted >= $count) {
                break;
            }

            unset(self::$cache[$key]);
            $evicted++;
        }
    }

    /**
     * Initialize feature extractors
     *
     * @return void
     */
    private static function initializeFeatureExtractors(): void
    {
        self::$featureExtractors = [
            'request_count' => function ($key, $context) {
                return self::$globalStats['total_requests'] % 1000;
            },
            'cache_pressure' => function ($key, $context) {
                $maxSize = self::$adaptiveConfig['max_cache_size'];
                return is_numeric($maxSize) && $maxSize > 0 ?
                    (float)(count(self::$cache) / $maxSize) : 0.0;
            },
            'global_hit_rate' => function ($key, $context) {
                $hits = is_numeric(self::$globalStats['cache_hits']) ? self::$globalStats['cache_hits'] : 0;
                $misses = is_numeric(self::$globalStats['cache_misses']) ? self::$globalStats['cache_misses'] : 0;
                $total = $hits + $misses;
                return $total > 0 ? $hits / $total : 0.5;
            }
        ];
    }

    /**
     * Load persisted learning models (placeholder)
     *
     * @return void
     */
    private static function loadPersistedModels(): void
    {
        // In a real implementation, load from persistent storage
        // For now, start with empty models
    }

    /**
     * Record prediction accuracy
     *
     * @param string $key
     * @param bool $actualResult
     * @param float $prediction
     * @return void
     */
    private static function recordSuccessfulPrediction(string $key, bool $actualResult, float $prediction): void
    {
        $accuracy = 1 - abs(($actualResult ? 1.0 : 0.0) - $prediction);

        // Update global accuracy with exponential moving average
        $alpha = 0.1;
        self::$globalStats['prediction_accuracy'] =
            $alpha * $accuracy + (1 - $alpha) * self::$globalStats['prediction_accuracy'];
    }

    /**
     * Update model accuracy
     *
     * @param string $key
     * @return void
     */
    private static function updateModelAccuracy(string $key): void
    {
        if (!isset(self::$learningModels[$key]['history'])) {
            return;
        }

        $history = self::$learningModels[$key]['history'];
        $recentHistory = array_slice($history, -50); // Last 50 predictions

        if (count($recentHistory) < 10) {
            return; // Need more data
        }

        $correct = 0;
        foreach ($recentHistory as $entry) {
            $predicted = $entry['prediction'] > 0.5 ? 1 : 0;
            $actual = $entry['target'];

            if ($predicted === $actual) {
                $correct++;
            }
        }

        self::$learningModels[$key]['accuracy'] = $correct / count($recentHistory);
    }

    /**
     * Adapt learning rate based on performance
     *
     * @param string $key
     * @return void
     */
    private static function adaptLearningRate(string $key): void
    {
        $model = &self::$learningModels[$key];

        if ($model['accuracy'] > 0.8) {
            // High accuracy, reduce learning rate for stability
            $model['learning_rate'] *= 0.95;
        } elseif ($model['accuracy'] < 0.6) {
            // Low accuracy, increase learning rate for faster adaptation
            $model['learning_rate'] *= 1.05;
        }

        // Bounds
        $model['learning_rate'] = max(0.001, min(0.1, $model['learning_rate']));
    }

    /**
     * Calculate average access interval from history
     *
     * @param array $history
     * @return float
     */
    private static function calculateAverageAccessInterval(array $history): float
    {
        if (count($history) < 2) {
            return 0;
        }

        $intervals = [];
        for ($i = 1; $i < count($history); $i++) {
            $intervals[] = $history[$i]['timestamp'] - $history[$i - 1]['timestamp'];
        }

        return array_sum($intervals) / count($intervals);
    }

    /**
     * Attempt cache retrieval
     *
     * @param string $key
     * @return array|null
     */
    private static function attemptCacheRetrieval(string $key): ?array
    {
        if (!isset(self::$cache[$key])) {
            return null;
        }

        $entry = &self::$cache[$key];

        if ($entry['expiry'] < time()) {
            unset(self::$cache[$key]);
            return null;
        }

        $entry['access_count']++;
        $entry['last_access'] = time();
        $entry['actual_utility'] += 1; // Increment utility on each access

        return $entry;
    }

    /**
     * Schedule model update (placeholder for async processing)
     *
     * @param string $key
     * @param array $context
     * @return void
     */
    private static function scheduleModelUpdate(string $key, array $context): void
    {
        // In a real implementation, this would queue an async job
        // For now, just mark that an update is needed
        self::$globalStats['adaptations_made']++;
    }

    /**
     * Add custom feature extractor
     *
     * @param string $name
     * @param callable $extractor
     * @return void
     */
    public static function addFeatureExtractor(string $name, callable $extractor): void
    {
        self::$featureExtractors[$name] = $extractor;
    }

    /**
     * Get learning statistics
     *
     * @return array
     */
    public static function getLearningStats(): array
    {
        return [
            'global_stats' => self::$globalStats,
            'models_count' => count(self::$learningModels),
            'cache_size' => count(self::$cache),
            'feature_extractors' => array_keys(self::$featureExtractors),
            'adaptive_config' => self::$adaptiveConfig
        ];
    }

    /**
     * Clear all cache and models
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$cache = [];
        self::$learningModels = [];
        self::$globalStats = [
            'total_requests' => 0,
            'cache_hits' => 0,
            'cache_misses' => 0,
            'adaptations_made' => 0,
            'prediction_accuracy' => 0.0,
            'learning_cycles' => 0
        ];
    }
}
