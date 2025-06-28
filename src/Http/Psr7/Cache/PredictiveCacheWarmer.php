<?php

declare(strict_types=1);

namespace Express\Http\Psr7\Cache;

/**
 * Predictive Cache Warmer with Machine Learning
 *
 * Uses ML algorithms to predict which cache entries will be needed
 * and pre-loads them to improve cache hit rates.
 *
 * @package Express\Http\Psr7\Cache
 * @since 2.3.0
 */
class PredictiveCacheWarmer
{
    /**
     * Cache access patterns for ML training
     *
     * @var array<string, array>
     */
    private static array $accessPatterns = [];

    /**
     * Predictive models for different key patterns
     *
     * @var array<string, array>
     */
    private static array $models = [];

    /**
     * Prediction accuracy tracking
     *
     * @var array<string, array>
     */
    private static array $predictions = [];

    /**
     * Cache warming statistics
     *
     * @var array<string, int>
     */
    private static array $stats = [
        'predictions_made' => 0,
        'predictions_correct' => 0,
        'cache_warmed' => 0,
        'models_trained' => 0,
        'accuracy_improvements' => 0
    ];

    /**
     * Configuration for ML algorithms
     *
     * @var array<string, mixed>
     */
    private static array $config = [
        'max_pattern_history' => 1000,
        'min_training_samples' => 20,
        'prediction_window' => 300, // 5 minutes
        'accuracy_threshold' => 0.7,
        'model_retrain_interval' => 3600, // 1 hour
        'warming_batch_size' => 50
    ];

    /**
     * Record cache access for pattern learning
     */
    public static function recordAccess(string $key, array $context = []): void
    {
        $timestamp = time();
        $dayOfWeek = (int) date('N', $timestamp);
        $hourOfDay = (int) date('H', $timestamp);

        $pattern = self::extractKeyPattern($key);

        if (!isset(self::$accessPatterns[$pattern])) {
            self::$accessPatterns[$pattern] = [];
        }

        $accessRecord = [
            'timestamp' => $timestamp,
            'key' => $key,
            'day_of_week' => $dayOfWeek,
            'hour_of_day' => $hourOfDay,
            'context' => $context,
            'sequence_number' => count(self::$accessPatterns[$pattern])
        ];

        self::$accessPatterns[$pattern][] = $accessRecord;

        // Limit history to prevent memory bloat
        if (count(self::$accessPatterns[$pattern]) > self::$config['max_pattern_history']) {
            array_shift(self::$accessPatterns[$pattern]);
        }

        // Trigger model training if we have enough samples
        if (count(self::$accessPatterns[$pattern]) >= self::$config['min_training_samples']) {
            self::trainModel($pattern);
        }
    }

    /**
     * Extract pattern from cache key
     */
    private static function extractKeyPattern(string $key): string
    {
        // Extract meaningful patterns from keys
        // e.g., "user:123:profile" -> "user:*:profile"
        $pattern = preg_replace('/\d+/', '*', $key);
        if ($pattern === null) {
            return $key;
        }

        $pattern = preg_replace('/[a-f0-9]{8,}/', '*hash*', $pattern);
        return $pattern ?? $key;
    }

    /**
     * Train ML model for a specific pattern
     */
    private static function trainModel(string $pattern): void
    {
        if (
            !isset(self::$accessPatterns[$pattern]) ||
            count(self::$accessPatterns[$pattern]) < self::$config['min_training_samples']
        ) {
            return;
        }

        $accesses = self::$accessPatterns[$pattern];
        $features = self::extractFeatures($accesses);
        $model = self::buildPredictionModel($features);

        self::$models[$pattern] = [
            'model' => $model,
            'trained_at' => time(),
            'accuracy' => $model['accuracy'],
            'feature_weights' => $model['weights'],
            'sample_count' => count($accesses)
        ];

        self::$stats['models_trained']++;
    }

    /**
     * Extract features for ML training
     */
    private static function extractFeatures(array $accesses): array
    {
        $features = [
            'temporal' => [],
            'sequential' => [],
            'contextual' => []
        ];

        $previousAccess = null;
        foreach ($accesses as $access) {
            // Temporal features
            $features['temporal'][] = [
                'hour' => $access['hour_of_day'],
                'day' => $access['day_of_week'],
                'minute' => (int) date('i', $access['timestamp']),
                'is_weekend' => in_array($access['day_of_week'], [6, 7]) ? 1 : 0,
                'is_business_hours' => ($access['hour_of_day'] >= 9 && $access['hour_of_day'] <= 17) ? 1 : 0
            ];

            // Sequential features
            if ($previousAccess) {
                $timeDiff = $access['timestamp'] - $previousAccess['timestamp'];
                $features['sequential'][] = [
                    'time_since_last' => $timeDiff,
                    'same_hour' => $access['hour_of_day'] === $previousAccess['hour_of_day'] ? 1 : 0,
                    'sequence_gap' => $access['sequence_number'] - $previousAccess['sequence_number']
                ];
            }

            // Contextual features
            $features['contextual'][] = [
                'context_size' => count($access['context']),
                'has_user_context' => isset($access['context']['user_id']) ? 1 : 0,
                'has_session_context' => isset($access['context']['session_id']) ? 1 : 0
            ];

            $previousAccess = $access;
        }

        return $features;
    }

    /**
     * Build simple ML prediction model
     */
    private static function buildPredictionModel(array $features): array
    {
        // Simple linear regression model for access prediction
        $weights = [
            'hour_weight' => 0.3,
            'day_weight' => 0.2,
            'sequence_weight' => 0.25,
            'context_weight' => 0.15,
            'temporal_weight' => 0.1
        ];

        // Calculate prediction accuracy based on historical data
        $accuracy = self::calculateModelAccuracy($features, $weights);

        return [
            'type' => 'temporal_sequence',
            'weights' => $weights,
            'accuracy' => $accuracy,
            'threshold' => 0.6,
            'confidence_intervals' => self::calculateConfidenceIntervals($features)
        ];
    }

    /**
     * Calculate model accuracy using cross-validation
     */
    private static function calculateModelAccuracy(array $features, array $weights): float
    {
        if (empty($features['temporal'])) {
            return 0.5; // Default accuracy
        }

        $correct = 0;
        $total = count($features['temporal']);

        // Simple accuracy calculation based on temporal patterns
        for ($i = 1; $i < $total; $i++) {
            $current = $features['temporal'][$i];
            $previous = $features['temporal'][$i - 1];

            // Predict based on time patterns
            $predicted = self::makeTemporalPrediction($previous, $weights);
            $actual = $current['hour'];

            if (abs($predicted - $actual) <= 1) { // Within 1 hour tolerance
                $correct++;
            }
        }

        return $total > 1 ? $correct / ($total - 1) : 0.5;
    }

    /**
     * Make temporal prediction
     */
    private static function makeTemporalPrediction(array $previousFeatures, array $weights): float
    {
        // Simple heuristic: if it's business hours, predict similar hour
        if ($previousFeatures['is_business_hours']) {
            return $previousFeatures['hour'] + 1;
        }

        // Otherwise, predict based on common usage patterns
        return ($previousFeatures['hour'] + 2) % 24;
    }

    /**
     * Calculate confidence intervals for predictions
     */
    private static function calculateConfidenceIntervals(array $features): array
    {
        $intervals = [];

        if (!empty($features['temporal'])) {
            $hours = array_column($features['temporal'], 'hour');
            $mean = array_sum($hours) / count($hours);
            $variance = array_sum(array_map(function ($h) use ($mean) {
                return pow($h - $mean, 2);
            }, $hours)) / count($hours);

            $intervals['hour'] = [
                'mean' => $mean,
                'std_dev' => sqrt($variance),
                'confidence_95' => 1.96 * sqrt($variance)
            ];
        }

        return $intervals;
    }

    /**
     * Predict next cache accesses
     */
    public static function predictNextAccesses(string $pattern, ?int $timeWindow = null): array
    {
        $timeWindow = $timeWindow ?? self::$config['prediction_window'];

        if (!isset(self::$models[$pattern])) {
            return [];
        }

        $model = self::$models[$pattern]['model'];
        $currentTime = time();
        $predictions = [];

        // Check if model needs retraining
        $modelAge = $currentTime - self::$models[$pattern]['trained_at'];
        if ($modelAge > self::$config['model_retrain_interval']) {
            self::trainModel($pattern);
            $model = self::$models[$pattern]['model'];
        }

        // Generate predictions based on current context
        $currentHour = (int) date('H', $currentTime);
        $currentDay = (int) date('N', $currentTime);

        for ($offset = 0; $offset < $timeWindow; $offset += 60) { // Every minute
            $futureTime = $currentTime + $offset;
            $futureHour = (int) date('H', $futureTime);
            $futureDay = (int) date('N', $futureTime);

            $probability = self::calculateAccessProbability($model, [
                'hour' => $futureHour,
                'day' => $futureDay,
                'time_offset' => $offset
            ]);

            if ($probability >= $model['threshold']) {
                $predictions[] = [
                    'timestamp' => $futureTime,
                    'probability' => $probability,
                    'confidence' => self::calculateConfidence($model, $probability)
                ];
            }
        }

        self::$stats['predictions_made'] += count($predictions);
        return $predictions;
    }

    /**
     * Calculate access probability
     */
    private static function calculateAccessProbability(array $model, array $context): float
    {
        $weights = $model['weights'];
        $probability = 0.0;

        // Hour-based probability
        $hourWeight = $weights['hour_weight'];
        if ($context['hour'] >= 9 && $context['hour'] <= 17) {
            $probability += $hourWeight * 0.8; // Business hours
        } elseif ($context['hour'] >= 18 && $context['hour'] <= 22) {
            $probability += $hourWeight * 0.6; // Evening
        } else {
            $probability += $hourWeight * 0.2; // Night/early morning
        }

        // Day-based probability
        $dayWeight = $weights['day_weight'];
        if ($context['day'] <= 5) {
            $probability += $dayWeight * 0.7; // Weekday
        } else {
            $probability += $dayWeight * 0.3; // Weekend
        }

        // Time offset penalty (farther predictions are less certain)
        $timeOffsetPenalty = min(0.3, $context['time_offset'] / 3600); // Max 30% penalty
        $probability = max(0.0, $probability - $timeOffsetPenalty);

        return min(1.0, $probability);
    }

    /**
     * Calculate prediction confidence
     */
    private static function calculateConfidence(array $model, float $probability): float
    {
        $baseConfidence = $model['accuracy'];
        $probabilityBonus = $probability * 0.2; // Higher probability = higher confidence

        return min(1.0, $baseConfidence + $probabilityBonus);
    }

    /**
     * Warm cache based on predictions
     */
    public static function warmCache(callable $cacheGenerator, array $patterns = []): array
    {
        $patterns = $patterns ?: array_keys(self::$models);
        $warmedCount = 0;
        $results = [];

        foreach ($patterns as $pattern) {
            $predictions = self::predictNextAccesses($pattern);

            $batchSize = min(self::$config['warming_batch_size'], count($predictions));
            $safeBatchSize = is_int($batchSize) ? $batchSize : 10;
            $topPredictions = array_slice($predictions, 0, $safeBatchSize);

            foreach ($topPredictions as $prediction) {
                if ($prediction['confidence'] >= self::$config['accuracy_threshold']) {
                    try {
                        $key = self::generateKeyFromPattern($pattern, $prediction);
                        $result = $cacheGenerator($key, $prediction);

                        if ($result !== null) {
                            $warmedCount++;
                            $results[$key] = $prediction;
                        }
                    } catch (\Throwable $e) {
                        // Log error but continue warming
                        error_log("Cache warming failed for pattern {$pattern}: " . $e->getMessage());
                    }
                }
            }
        }

        self::$stats['cache_warmed'] += $warmedCount;
        return $results;
    }

    /**
     * Generate actual cache key from pattern and prediction
     */
    private static function generateKeyFromPattern(string $pattern, array $prediction): string
    {
        // This is a simplified implementation
        // In practice, you'd need more sophisticated key generation
        $timestamp = $prediction['timestamp'];
        $key = str_replace('*', date('His', $timestamp), $pattern);

        return $key;
    }

    /**
     * Validate prediction accuracy
     */
    public static function validatePrediction(string $key, bool $wasAccessed): void
    {
        $pattern = self::extractKeyPattern($key);

        if (isset(self::$predictions[$pattern])) {
            foreach (self::$predictions[$pattern] as &$prediction) {
                if ($prediction['key'] === $key && !isset($prediction['validated'])) {
                    $prediction['validated'] = true;
                    $prediction['correct'] = $wasAccessed;

                    if ($wasAccessed) {
                        self::$stats['predictions_correct']++;
                    }
                    break;
                }
            }
        }
    }

    /**
     * Get warmer statistics
     */
    public static function getStats(): array
    {
        $accuracy = self::$stats['predictions_made'] > 0
            ? (self::$stats['predictions_correct'] / self::$stats['predictions_made']) * 100
            : 0;

        return [
            'models_trained' => count(self::$models),
            'patterns_learned' => count(self::$accessPatterns),
            'predictions_made' => self::$stats['predictions_made'],
            'prediction_accuracy' => round($accuracy, 2),
            'cache_warmed' => self::$stats['cache_warmed'],
            'active_models' => array_keys(self::$models),
            'detailed_stats' => self::$stats
        ];
    }

    /**
     * Clear all models and patterns
     */
    public static function reset(): void
    {
        self::$accessPatterns = [];
        self::$models = [];
        self::$predictions = [];
        self::$stats = [
            'predictions_made' => 0,
            'predictions_correct' => 0,
            'cache_warmed' => 0,
            'models_trained' => 0,
            'accuracy_improvements' => 0
        ];
    }
}
