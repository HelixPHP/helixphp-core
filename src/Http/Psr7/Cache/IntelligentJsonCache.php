<?php

declare(strict_types=1);

namespace PivotPHP\Core\Http\Psr7\Cache;

/**
 * Intelligent JSON Cache with Structure-Based Optimization
 *
 * Optimizes JSON caching by analyzing data structures and creating
 * reusable templates for similar data patterns.
 *
 * @package PivotPHP\Core\Http\Psr7\Cache
 * @since 2.2.0
 */
class IntelligentJsonCache
{
    /**
     * Cache for JSON structure templates
     *
     * @var array<string, string>
     */
    private static array $structureTemplates = [];

    /**
     * Cache for data structure fingerprints
     *
     * @var array<string, string>
     */
    private static array $structureFingerprints = [];

    /**
     * Cache for complete JSON strings
     *
     * @var array<string, string>
     */
    private static array $jsonStringCache = [];

    /**
     * Template variable patterns
     *
     * @var array<string, string>
     */
    private static array $templatePatterns = [];

    /**
     * Performance statistics
     *
     * @var array<string, int>
     */
    private static array $stats = [
        'template_hits' => 0,
        'template_misses' => 0,
        'direct_hits' => 0,
        'direct_misses' => 0,
        'templates_created' => 0,
        'memory_saved_bytes' => 0
    ];

    /**
     * Maximum cache sizes
     */
    private const MAX_TEMPLATE_CACHE = 100;
    private const MAX_JSON_CACHE = 500;
    // private const MAX_FINGERPRINT_CACHE = 200; // Reserved for future use

    /**
     * Get cached JSON with intelligent structure analysis
     */
    public static function getCachedJson(array $data, ?string $cacheKey = null): string
    {
        // Try direct cache first
        $directKey = $cacheKey ?? md5(serialize($data));

        if (isset(self::$jsonStringCache[$directKey])) {
            self::$stats['direct_hits']++;
            return self::$jsonStringCache[$directKey];
        }

        // Analyze structure for template matching
        $structure = self::analyzeDataStructure($data);
        $structureKey = md5($structure);

        if (isset(self::$structureTemplates[$structureKey])) {
            // Use template
            self::$stats['template_hits']++;
            $json = self::populateTemplate(self::$structureTemplates[$structureKey], $data);
        } else {
            // Create new template and JSON
            self::$stats['template_misses']++;
            self::$stats['direct_misses']++;

            $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            if ($json === false) {
                throw new \InvalidArgumentException('Unable to encode data as JSON');
            }

            // Create template if structure is reusable
            if (self::isStructureReusable($data)) {
                self::createTemplate($structureKey, $data, $json);
            }
        }

        // Cache complete JSON
        self::cacheJsonString($directKey, $json);

        return $json;
    }

    /**
     * Analyze data structure to create fingerprint
     */
    private static function analyzeDataStructure(array $data): string
    {
        return self::getStructureFingerprint($data);
    }

    /**
     * Get recursive structure fingerprint
     */
    private static function getStructureFingerprint(mixed $data, int $depth = 0): string
    {
        if ($depth > 10) { // Prevent infinite recursion
            return 'deep';
        }

        if (is_array($data)) {
            if (empty($data)) {
                return '[]';
            }

            // Check if it's a sequential array
            $isSequential = array_keys($data) === range(0, count($data) - 1);

            if ($isSequential) {
                // For arrays, analyze first few elements
                $elementTypes = [];
                $sampleSize = min(3, count($data));

                for ($i = 0; $i < $sampleSize; $i++) {
                    $elementTypes[] = self::getStructureFingerprint($data[$i], $depth + 1);
                }

                return '[' . implode(',', array_unique($elementTypes)) . ']';
            } else {
                // For objects, get key structure
                $keys = array_keys($data);
                sort($keys);

                $keyStructure = [];
                foreach ($keys as $key) {
                    $valueType = self::getStructureFingerprint($data[$key], $depth + 1);
                    $keyStructure[] = $key . ':' . $valueType;
                }

                return '{' . implode(',', $keyStructure) . '}';
            }
        } elseif (is_object($data)) {
            return 'obj:' . get_class($data);
        } elseif (is_string($data)) {
            // Categorize strings by rough patterns
            if (is_numeric($data)) {
                return 'str:numeric';
            } elseif (filter_var($data, FILTER_VALIDATE_EMAIL)) {
                return 'str:email';
            } elseif (filter_var($data, FILTER_VALIDATE_URL)) {
                return 'str:url';
            } elseif (strlen($data) > 100) {
                return 'str:long';
            } else {
                return 'str:short';
            }
        } elseif (is_int($data)) {
            return 'int';
        } elseif (is_float($data)) {
            return 'float';
        } elseif (is_bool($data)) {
            return 'bool';
        } elseif (is_null($data)) {
            return 'null';
        } else {
            return 'unknown';
        }
    }

    /**
     * Check if structure is worth templating
     */
    private static function isStructureReusable(array $data): bool
    {
        // Template if it has multiple keys or nested structures
        if (count($data) >= 3) {
            return true;
        }

        // Template if it has nested arrays/objects
        foreach ($data as $value) {
            if (is_array($value) || is_object($value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create template from JSON and data
     */
    private static function createTemplate(
        string $structureKey,
        array $data,
        string $json
    ): void {
        if (count(self::$structureTemplates) >= self::MAX_TEMPLATE_CACHE) {
            self::evictOldTemplates();
        }

        // Create template by replacing values with placeholders
        $template = $json;
        $patterns = [];

        self::replaceValuesWithPlaceholders($data, $template, $patterns, '');

        self::$structureTemplates[$structureKey] = $template;
        self::$templatePatterns[$structureKey] = $patterns;
        self::$stats['templates_created']++;
    }

    /**
     * Replace values with placeholders recursively
     */
    private static function replaceValuesWithPlaceholders(
        mixed $data,
        string &$template,
        array &$patterns,
        string $path
    ): void {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $currentPath = $path ? $path . '.' . $key : (string)$key;

                if (is_scalar($value)) {
                    $placeholder = '{{' . $currentPath . '}}';
                    $jsonValue = json_encode($value);

                    if ($jsonValue !== false) {
                        $template = str_replace($jsonValue, $placeholder, $template);
                        $patterns[$currentPath] = $placeholder;
                    }
                } elseif (is_array($value)) {
                    self::replaceValuesWithPlaceholders($value, $template, $patterns, $currentPath);
                }
            }
        }
    }

    /**
     * Populate template with actual data
     */
    private static function populateTemplate(string $template, array $data): string
    {
        $json = $template;

        // Replace placeholders with actual values
        self::populatePlaceholders($data, $json, '');

        return $json;
    }

    /**
     * Populate placeholders recursively
     */
    private static function populatePlaceholders(
        mixed $data,
        string &$json,
        string $path
    ): void {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $currentPath = $path ? $path . '.' . $key : (string)$key;

                if (is_scalar($value)) {
                    $placeholder = '{{' . $currentPath . '}}';
                    $jsonValue = json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

                    if ($jsonValue !== false) {
                        $json = str_replace($placeholder, $jsonValue, $json);
                    }
                } elseif (is_array($value)) {
                    self::populatePlaceholders($value, $json, $currentPath);
                }
            }
        }
    }

    /**
     * Cache JSON string with size limit
     */
    private static function cacheJsonString(string $key, string $json): void
    {
        if (count(self::$jsonStringCache) >= self::MAX_JSON_CACHE) {
            // Remove oldest entries (simple FIFO)
            $keysToRemove = array_slice(array_keys(self::$jsonStringCache), 0, 50);
            foreach ($keysToRemove as $oldKey) {
                unset(self::$jsonStringCache[$oldKey]);
            }
        }

        self::$jsonStringCache[$key] = $json;
        self::$stats['memory_saved_bytes'] += strlen($json);
    }

    /**
     * Evict old templates to make room
     */
    private static function evictOldTemplates(): void
    {
        $keysToRemove = array_slice(array_keys(self::$structureTemplates), 0, 20);

        foreach ($keysToRemove as $key) {
            unset(self::$structureTemplates[$key], self::$templatePatterns[$key]);
        }
    }

    /**
     * Get performance statistics
     */
    public static function getStats(): array
    {
        $totalTemplateRequests = self::$stats['template_hits'] + self::$stats['template_misses'];
        $totalDirectRequests = self::$stats['direct_hits'] + self::$stats['direct_misses'];

        $templateHitRate = $totalTemplateRequests > 0 ?
            (self::$stats['template_hits'] / $totalTemplateRequests) * 100 : 0;

        $directHitRate = $totalDirectRequests > 0 ?
            (self::$stats['direct_hits'] / $totalDirectRequests) * 100 : 0;

        return [
            'template_hit_rate' => round($templateHitRate, 2),
            'direct_hit_rate' => round($directHitRate, 2),
            'templates_created' => self::$stats['templates_created'],
            'cache_sizes' => [
                'templates' => count(self::$structureTemplates),
                'json_strings' => count(self::$jsonStringCache),
                'fingerprints' => count(self::$structureFingerprints)
            ],
            'memory_saved' => \PivotPHP\Core\Utils\Utils::formatBytes(self::$stats['memory_saved_bytes']),
            'detailed_stats' => self::$stats
        ];
    }

    /**
     * Clear all caches
     */
    public static function clearAll(): void
    {
        self::$structureTemplates = [];
        self::$structureFingerprints = [];
        self::$jsonStringCache = [];
        self::$templatePatterns = [];

        self::$stats = [
            'template_hits' => 0,
            'template_misses' => 0,
            'direct_hits' => 0,
            'direct_misses' => 0,
            'templates_created' => 0,
            'memory_saved_bytes' => 0
        ];
    }

    /**
     * Get cache contents for debugging
     */
    public static function getDebugInfo(): array
    {
        return [
            'template_count' => count(self::$structureTemplates),
            'sample_templates' => array_slice(self::$structureTemplates, 0, 3, true),
            'sample_patterns' => array_slice(self::$templatePatterns, 0, 3, true),
            'json_cache_sample' => array_slice(self::$jsonStringCache, 0, 3, true)
        ];
    }
}
