<?php

namespace PivotPHP\Core\Legacy\Utils;

/**
 * Cache otimizado para serialização de dados intensivos.
 *
 * Reduz o impacto de performance causado por múltiplas serializações
 * dos mesmos dados através de cache inteligente baseado em hash.
 *
 * @deprecated This class adds unnecessary complexity for a microframework.
 * Use simple caching or external cache solutions instead. Will be removed in v1.2.0.
 * Following 'Simplicidade sobre Otimização Prematura' principle.
 */
class SerializationCache
{
    /**
     * Cache de dados serializados
     *
     * @var array<string, array{data: string, size: int, hash: string}>
     */
    private static array $cache = [];

    /**
     * Cache de tamanhos calculados
     *
     * @var array<string, int>
     */
    private static array $sizeCache = [];

    /**
     * Cache de hashes de objetos
     *
     * @var array<string, string>
     */
    private static array $hashCache = [];

    /**
     * Limite máximo de entradas no cache
     */
    private static int $maxCacheSize = 100;

    /**
     * Contador de hits do cache para métricas
     */
    private static int $cacheHits = 0;

    /**
     * Contador de misses do cache para métricas
     */
    private static int $cacheMisses = 0;

    /**
     * Calcula o tamanho de um objeto usando cache inteligente
     */
    public static function getSerializedSize(mixed $data, string|null $cacheKey = null): int
    {
        // Gera chave de cache baseada no tipo e conteúdo dos dados
        $key = $cacheKey ?? self::generateCacheKey($data);

        // Verifica se já temos o tamanho em cache
        if (isset(self::$sizeCache[$key])) {
            $dataHash = self::generateDataHash($data);

            // Verifica se os dados não mudaram
            if (isset(self::$hashCache[$key]) && self::$hashCache[$key] === $dataHash) {
                self::$cacheHits++;
                return self::$sizeCache[$key];
            }
        }

        // Cache miss - precisa calcular
        self::$cacheMisses++;

        // Serializa e calcula tamanho
        $serialized = serialize($data);
        $size = strlen($serialized);

        // Armazena no cache se não estiver no limite
        if (count(self::$sizeCache) < self::$maxCacheSize) {
            self::$sizeCache[$key] = $size;
            self::$hashCache[$key] = self::generateDataHash($data);

            // Também armazena a string serializada por um tempo
            self::$cache[$key] = [
                'data' => $serialized,
                'size' => $size,
                'hash' => self::$hashCache[$key]
            ];
        } else {
            // Se o cache está cheio, limpa algumas entradas antigas
            self::evictOldEntries();
        }

        return $size;
    }

    /**
     * Calcula tamanho total de múltiplos objetos com cache otimizado
     */
    public static function getTotalSerializedSize(array $objects, array $cacheKeys = []): int
    {
        $totalSize = 0;

        foreach ($objects as $index => $object) {
            $key = $cacheKeys[$index] ?? "obj_$index";
            $totalSize += self::getSerializedSize($object, $key);
        }

        return $totalSize;
    }

    /**
     * Obtém dados serializados com cache
     */
    public static function getSerializedData(mixed $data, string|null $cacheKey = null): string
    {
        $key = $cacheKey ?? self::generateCacheKey($data);

        // Verifica cache
        if (isset(self::$cache[$key])) {
            $dataHash = self::generateDataHash($data);

            if (self::$cache[$key]['hash'] === $dataHash) {
                self::$cacheHits++;
                return self::$cache[$key]['data'];
            }
        }

        // Cache miss
        self::$cacheMisses++;
        $serialized = serialize($data);

        // Armazena no cache
        if (count(self::$cache) < self::$maxCacheSize) {
            self::$cache[$key] = [
                'data' => $serialized,
                'size' => strlen($serialized),
                'hash' => self::generateDataHash($data)
            ];
        }

        return $serialized;
    }

    /**
     * Gera chave de cache baseada no tipo e estrutura dos dados
     */
    private static function generateCacheKey(mixed $data): string
    {
        if (is_array($data)) {
            // Para arrays, usa estrutura de chaves e tipos
            $keyStructure = array_map('gettype', $data);
            return 'array_' . md5(
                serialize(
                    [
                        'keys' => array_keys($data),
                        'types' => $keyStructure,
                        'count' => count($data)
                    ]
                )
            );
        }

        if (is_object($data)) {
            return 'object_' . get_class($data) . '_' . spl_object_hash($data);
        }

        return 'scalar_' . gettype($data) . '_' . md5(serialize($data));
    }

    /**
     * Gera hash rápido dos dados para verificar mudanças
     */
    private static function generateDataHash(mixed $data): string
    {
        if (is_array($data)) {
            // Para arrays grandes, usa apenas uma amostra para performance
            if (count($data) > 50) {
                $sample = array_slice($data, 0, 10, true) +
                         array_slice($data, -10, 10, true);
                return md5(serialize($sample) . count($data));
            }
        }

        // Para dados menores, usa hash completo
        return md5(serialize($data));
    }

    /**
     * Remove entradas antigas do cache
     */
    private static function evictOldEntries(): void
    {
        // Remove 25% das entradas mais antigas (estratégia simples)
        $entriesToRemove = (int) (self::$maxCacheSize * 0.25);

        $keys = array_keys(self::$cache);
        for ($i = 0; $i < $entriesToRemove && !empty($keys); $i++) {
            $key = array_shift($keys);
            unset(self::$cache[$key], self::$sizeCache[$key], self::$hashCache[$key]);
        }
    }

    /**
     * Limpa todo o cache
     */
    public static function clearCache(): void
    {
        self::$cache = [];
        self::$sizeCache = [];
        self::$hashCache = [];
        self::$cacheHits = 0;
        self::$cacheMisses = 0;
    }

    /**
     * Obtém estatísticas do cache
     */
    public static function getStats(): array
    {
        $totalRequests = self::$cacheHits + self::$cacheMisses;
        $hitRate = $totalRequests > 0 ? (self::$cacheHits / $totalRequests) * 100 : 0;

        return [
            'cache_entries' => count(self::$cache),
            'size_cache_entries' => count(self::$sizeCache),
            'hash_cache_entries' => count(self::$hashCache),
            'cache_hits' => self::$cacheHits,
            'cache_misses' => self::$cacheMisses,
            'hit_rate_percent' => round($hitRate, 2),
            'memory_usage' => self::getMemoryUsage()
        ];
    }

    /**
     * Calcula uso de memória do próprio cache
     */
    private static function getMemoryUsage(): string
    {
        $size = strlen(serialize(self::$cache)) +
                strlen(serialize(self::$sizeCache)) +
                strlen(serialize(self::$hashCache));

        if ($size < 1024) {
            return $size . ' B';
        } elseif ($size < 1048576) {
            return round($size / 1024, 2) . ' KB';
        } else {
            return round($size / 1048576, 2) . ' MB';
        }
    }

    /**
     * Define o tamanho máximo do cache
     */
    public static function setMaxCacheSize(int $size): void
    {
        self::$maxCacheSize = max(10, $size); // Mínimo de 10 entradas
    }
}
