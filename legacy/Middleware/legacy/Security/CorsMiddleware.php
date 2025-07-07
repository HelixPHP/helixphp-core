<?php

namespace PivotPHP\Core\Middleware\Security;

use PivotPHP\Core\Middleware\Core\BaseMiddleware;
use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;
use PivotPHP\Core\Utils\SerializationCache;

/**
 * Middleware CORS (Cross-Origin Resource Sharing) com otimizações de performance.
 * Incorpora cache de headers, configurações pré-compiladas e benchmarks.
 */
class CorsMiddleware extends BaseMiddleware
{
    private array $options;

    /**
     * Cache de headers CORS pré-compilados
     * @var array<string, array>
     */
    private static array $preCompiledHeaders = [];

    /**
     * Headers CORS compilados como string única para performance
     * @var array<string, string>
     */
    private static array $compiledHeaderStrings = [];

    /**
     * Cache para estatísticas de uso de memória
     * @var array<string, int>|null
     */
    private static ?array $memoryUsageCache = null;

    /**
     * Hash dos dados para invalidação do cache de memória
     */
    private static ?string $lastDataHash = null;

    /**
     * Configurações padrão otimizadas
     */
    private const DEFAULT_CONFIG = [
        'origins' => ['*'],
        'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH'],
        'headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
        'credentials' => false,
        'maxAge' => 86400, // 24 hours
        'expose' => []
    ];

    /**
     * @param array<string, mixed> $options Opções de configuração CORS
     */
    public function __construct(array $options = [])
    {
        // Handle legacy 'origin' option
        $wasOriginString = false;
        if (isset($options['origin']) && !isset($options['origins'])) {
            if (is_string($options['origin'])) {
                $wasOriginString = true;
            }
            $options['origins'] = is_array($options['origin']) ? $options['origin'] : [$options['origin']];
            unset($options['origin']);
        }

        $this->options = array_merge(
            [
                'origins' => ['*'],
                'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
                'headers' => ['Content-Type', 'Authorization'],
                'credentials' => false,
                'maxAge' => 86400, // 24 hours
                'expose' => [],
                '_wasOriginString' => $wasOriginString
            ],
            $options
        );

        // Pre-compila headers para otimização
        $this->precompileHeaders();
    }

    /**
     * Pre-compila headers para performance
     */
    private function precompileHeaders(): void
    {
        $configHash = $this->getConfigHash();

        if (!isset(self::$preCompiledHeaders[$configHash])) {
            self::$preCompiledHeaders[$configHash] = $this->buildOptimizedHeaders();
            self::$compiledHeaderStrings[$configHash] = $this->buildHeaderString();
            self::invalidateMemoryCache();
        }
    }

    /**
     * Cria middleware CORS otimizado (método estático para compatibilidade)
     *
     * @param array $config
     * @return callable
     */
    public static function create(array $config = []): callable
    {
        $finalConfig = array_merge(self::DEFAULT_CONFIG, $config);
        $configHash = self::_getConfigHashStatic($finalConfig);

        // Pre-compila headers se não existir no cache
        if (!isset(self::$preCompiledHeaders[$configHash])) {
            self::$preCompiledHeaders[$configHash] = self::_buildOptimizedHeadersStatic($finalConfig);
            self::$compiledHeaderStrings[$configHash] = self::_buildHeaderStringStatic($finalConfig);
            self::_invalidateMemoryCache();
        }

        return function (
            $request,
            $response,
            $next
        ) use (
            $configHash,
            $finalConfig
) {
            self::_applyOptimizedHeaders($response, $configHash, $finalConfig, $request);

            // Handle preflight requests
            if ($request->method === 'OPTIONS') {
                $response->status(200)->send();
                return;
            }

            $next();
        };
    }

    /**
     * Executa o middleware CORS.
     */
    public function handle($request, $response, callable $next)
    {
        $origin = $this->getHeader($request, 'Origin');

        // Sempre adiciona headers CORS, mas varia baseado na origem permitida
        $this->addCorsHeaders($response, $origin);

        // Para requisições OPTIONS (preflight), retorna imediatamente
        if ($request instanceof \Helix\Http\Request && $request->getMethod() === 'OPTIONS') {
            if ($response instanceof Response) {
                $response->status(200)->send();
            }
            return;
        }

        return $next($request, $response);
    }

    /**
     * Verifica se a origem é permitida.
     *
     * @phpstan-ignore-next-line
     * @param string|null $origin
     * @return bool
     */
    private function isOriginAllowed(?string $origin): bool
    {
        $allowedOrigins = $this->options['origins'];

        // DEBUG: Log origem recebida e permitidas
        error_log('[CorsMiddleware][isOriginAllowed] Origin recebida: ' . var_export($origin, true));
        error_log('[CorsMiddleware][isOriginAllowed] Origens permitidas: ' . var_export($allowedOrigins, true));

        // Se permite todas as origens
        if (in_array('*', $allowedOrigins)) {
            error_log('[CorsMiddleware][isOriginAllowed] Permite todas as origens (*)');
            return true;
        }

        // Se há uma origem específica na request, verifica se é permitida
        if (!empty($origin)) {
            $allowed = in_array($origin, $allowedOrigins);
            error_log('[CorsMiddleware][isOriginAllowed] Origin específica encontrada. Permitida? ' . ($allowed ? 'SIM' : 'NÃO'));
            return $allowed;
        }

        // Se não há origem na request mas há origens específicas configuradas, permite
        if (!empty($allowedOrigins) && !in_array('*', $allowedOrigins)) {
            error_log('[CorsMiddleware][isOriginAllowed] Não há origin na request, mas há origens específicas configuradas. Permitindo.');
            return true;
        }

        error_log('[CorsMiddleware][isOriginAllowed] Origin não permitida.');
        return false;
    }

    /**
     * Adiciona os cabeçalhos CORS à resposta.
     *
     * @param mixed $response
     * @param string|null $origin
     * @return void
     */
    private function addCorsHeaders($response, ?string $origin): void
    {
        // DEBUG: Log origem recebida e opções
        error_log('[CorsMiddleware][addCorsHeaders] Origin recebida: ' . var_export($origin, true));
        error_log('[CorsMiddleware][addCorsHeaders] Opções: ' . var_export($this->options, true));

        // Origin
        if (in_array('*', $this->options['origins'])) {
            $this->setHeader($response, 'Access-Control-Allow-Origin', '*');
        } elseif ($origin && in_array($origin, $this->options['origins'])) {
            $this->setHeader($response, 'Access-Control-Allow-Origin', $origin);
        } elseif (!$origin && count($this->options['origins']) === 1 && $this->options['_wasOriginString']) {
            // Se não há origin na request, há exatamente uma origem configurada,
            // e a configuração original era uma string (não array), use ela
            $this->setHeader($response, 'Access-Control-Allow-Origin', $this->options['origins'][0]);
        } else {
            // Para qualquer outro caso (origem não permitida, múltiplas origens sem match, etc.)
            $this->setHeader($response, 'Access-Control-Allow-Origin', 'null');
        }

        // Methods
        $methods = is_array($this->options['methods'])
            ? $this->options['methods']
            : explode(',', $this->options['methods']);
        $this->setHeader($response, 'Access-Control-Allow-Methods', implode(',', $methods));

        // Headers
        $headers = is_array($this->options['headers'])
            ? $this->options['headers']
            : explode(',', $this->options['headers']);
        $this->setHeader($response, 'Access-Control-Allow-Headers', implode(',', $headers));

        // Credentials
        if ($this->options['credentials']) {
            $this->setHeader($response, 'Access-Control-Allow-Credentials', 'true');
        }

        // Max Age
        $this->setHeader($response, 'Access-Control-Max-Age', (string)$this->options['maxAge']);

        // Exposed Headers
        if (!empty($this->options['expose'])) {
            $this->setHeader(
                $response,
                'Access-Control-Expose-Headers',
                implode(', ', $this->options['expose'])
            );
        }
    }

    /**
     * Cria uma instância com configuração padrão para desenvolvimento.
     */
    public static function development(): self
    {
        return new self(
            [
                'origins' => ['*'],
                'credentials' => true,
                'headers' => [
                    'Content-Type',
                    'Authorization',
                    'X-Requested-With',
                    'Accept',
                    'Origin',
                    'X-CSRF-Token'
                ]
            ]
        );
    }

    /**
     * Cria uma instância com configuração para produção.
     *
     * @param array<string> $allowedOrigins
     */
    public static function production(array $allowedOrigins): self
    {
        return new self(
            [
                'origins' => $allowedOrigins,
                'credentials' => true,
                'headers' => [
                    'Content-Type',
                    'Authorization',
                    'X-Requested-With',
                    'Accept',
                    'Origin'
                ]
            ]
        );
    }

    /**
     * Set a header on response (supports both Response objects and test objects)
     *
     * @param mixed $response
     * @param string $name
     * @param string $value
     * @return void
     */
    private function setHeader($response, string $name, string $value): void
    {
        if ($response instanceof \Helix\Http\Response) {
            $response->header($name, $value);
        } elseif (is_object($response)) {
            // Handle test objects
            if (!isset($response->headers)) {
                $response->headers = [];
            }
            $response->headers[] = $name . ': ' . $value;
        }
    }

    /**
     * Aplica headers CORS de forma otimizada
     *
     * @param Response $response
     * @param string $configHash
     * @param array $config
     * @param Request $request
     * @return void
     */
    private static function applyOptimizedHeaders(
        Response $response,
        string $configHash,
        array $config,
        Request $request
    ): void {
        $headers = self::$preCompiledHeaders[$configHash];

        // Aplica headers em batch para melhor performance
        foreach ($headers as $name => $value) {
            $response->header($name, $value);
        }

        // Handle dynamic origin se necessário
        if (isset($config['dynamic_origin'])) {
            $origin = self::calculateDynamicOrigin($request, $config);
            $response->header('Access-Control-Allow-Origin', $origin);
        }
    }

    /**
     * Constrói headers CORS otimizados
     * @return array
     */
    private function buildOptimizedHeaders(): array
    {
        $headers = [];

        // Origin
        if (in_array('*', $this->options['origins'])) {
            $headers['Access-Control-Allow-Origin'] = '*';
        } else {
            $headers['Access-Control-Allow-Origin'] = $this->options['origins'][0] ?? '*';
        }

        // Methods - usa string pré-concatenada
        $headers['Access-Control-Allow-Methods'] = is_array($this->options['methods'])
            ? implode(', ', $this->options['methods'])
            : $this->options['methods'];

        // Headers - usa string pré-concatenada
        $headers['Access-Control-Allow-Headers'] = is_array($this->options['headers'])
            ? implode(', ', $this->options['headers'])
            : $this->options['headers'];

        // Credentials
        if ($this->options['credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // Max Age
        $headers['Access-Control-Max-Age'] = (string) $this->options['maxAge'];

        // Expose Headers
        if (!empty($this->options['expose'])) {
            $headers['Access-Control-Expose-Headers'] = is_array($this->options['expose'])
                ? implode(', ', $this->options['expose'])
                : $this->options['expose'];
        }

        return $headers;
    }

    /**
     * Constrói headers CORS otimizados (método estático para configuração externa)
     * @param array $config
     * @return array
     */
    private static function _buildOptimizedHeadersStatic(array $config): array
    {
        $headers = [];

        // Origin
        if (is_array($config['origins'])) {
            // Para múltiplas origens, será tratado dinamicamente
            $headers['Access-Control-Allow-Origin'] = $config['origins'][0] ?? '*';
        } else {
            $headers['Access-Control-Allow-Origin'] = $config['origins'];
        }

        // Methods - usa string pré-concatenada
        $headers['Access-Control-Allow-Methods'] = is_array($config['methods'])
            ? implode(', ', $config['methods'])
            : $config['methods'];

        // Headers - usa string pré-concatenada
        $headers['Access-Control-Allow-Headers'] = is_array($config['headers'])
            ? implode(', ', $config['headers'])
            : $config['headers'];

        // Credentials
        if ($config['credentials']) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        // Max Age
        $headers['Access-Control-Max-Age'] = (string) $config['maxAge'];

        // Expose Headers
        if (!empty($config['expose'])) {
            $headers['Access-Control-Expose-Headers'] = is_array($config['expose'])
                ? implode(', ', $config['expose'])
                : $config['expose'];
        }

        return $headers;
    }

    /**
     * Constrói string única de headers para performance máxima
     * @return string
     */
    private function buildHeaderString(): string
    {
        $parts = [];

        $origin = in_array('*', $this->options['origins']) ? '*' : ($this->options['origins'][0] ?? '*');
        $parts[] = 'Access-Control-Allow-Origin: ' . $origin;

        $methods = is_array($this->options['methods'])
            ? implode(', ', $this->options['methods'])
            : $this->options['methods'];
        $parts[] = 'Access-Control-Allow-Methods: ' . $methods;

        $headers = is_array($this->options['headers'])
            ? implode(', ', $this->options['headers'])
            : $this->options['headers'];
        $parts[] = 'Access-Control-Allow-Headers: ' . $headers;

        if ($this->options['credentials']) {
            $parts[] = 'Access-Control-Allow-Credentials: true';
        }

        $parts[] = 'Access-Control-Max-Age: ' . $this->options['maxAge'];

        if (!empty($this->options['expose'])) {
            $exposed = is_array($this->options['expose'])
                ? implode(', ', $this->options['expose'])
                : $this->options['expose'];
            $parts[] = 'Access-Control-Expose-Headers: ' . $exposed;
        }

        return implode("\r\n", $parts);
    }

    /**
     * Constrói string única de headers para performance máxima (método estático para configuração externa)
     * @param array $config
     * @return string
     */
    private static function _buildHeaderStringStatic(array $config): string
    {
        $parts = [];

        $parts[] = 'Access-Control-Allow-Origin: ' . (is_array($config['origins'])
            ? $config['origins'][0]
            : $config['origins']);
        $parts[] = 'Access-Control-Allow-Methods: ' . (is_array($config['methods'])
            ? implode(', ', $config['methods'])
            : $config['methods']);
        $parts[] = 'Access-Control-Allow-Headers: ' . (is_array($config['headers'])
            ? implode(', ', $config['headers'])
            : $config['headers']);

        if ($config['credentials']) {
            $parts[] = 'Access-Control-Allow-Credentials: true';
        }

        $parts[] = 'Access-Control-Max-Age: ' . $config['maxAge'];

        if (!empty($config['expose'])) {
            $parts[] = 'Access-Control-Expose-Headers: ' . (is_array($config['expose'])
                ? implode(', ', $config['expose'])
                : $config['expose']);
        }

        return implode("\r\n", $parts);
    }

    /**
     * Calcula origin dinâmica (para casos complexos)
     * @param Request $request
     * @param array $config
     * @return string
     */
    private static function calculateDynamicOrigin(Request $request, array $config): string
    {
        if (!is_array($config['origins'])) {
            return (string) $config['origins'];
        }

        $originHeader = self::getHeader($request, 'Origin', '');
        $requestOrigin = is_string($originHeader) ? $originHeader : '';

        // Verifica se origin está na lista permitida
        if (in_array($requestOrigin, $config['origins'], true)) {
            return $requestOrigin;
        }

        // Verifica wildcard patterns
        foreach ($config['origins'] as $allowedOrigin) {
            if (!is_string($allowedOrigin)) {
                continue;
            }
            if (strpos($allowedOrigin, '*') !== false) {
                $pattern = str_replace('*', '.*', preg_quote($allowedOrigin, '/'));
                if (preg_match('/^' . $pattern . '$/', $requestOrigin)) {
                    return $requestOrigin;
                }
            }
        }
        return '';
    }

    /**
     * Gera hash da configuração para cache
     * @return string
     */
    private function getConfigHash(): string
    {
        // Remove campos dinâmicos do hash
        $cacheConfig = $this->options;
        unset($cacheConfig['dynamic_origin']);
        unset($cacheConfig['_wasOriginString']);

        return md5(serialize($cacheConfig));
    }

    /**
     * Gera hash da configuração para cache (método estático para configuração externa)
     * @param array $config
     * @return string
     */
    private static function _getConfigHashStatic(array $config): string
    {
        // Remove dynamic_origin do hash pois não afeta cache de headers estáticos
        $cacheConfig = $config;
        unset($cacheConfig['dynamic_origin']);

        return md5(serialize($cacheConfig));
    }

    /**
     * Middleware CORS ultra-otimizado para casos simples
     *
     * @param string $origin
     * @param array|null $methods
     * @param array|null $headers
     * @return callable
     */
    public static function simple(string $origin = '*', ?array $methods = null, ?array $headers = null): callable
    {
        $methods = $methods ?? self::DEFAULT_CONFIG['methods'];
        $headers = $headers ?? self::DEFAULT_CONFIG['headers'];

        // Pre-compila strings para máxima performance
        $methodsString = implode(', ', $methods);
        $headersString = implode(', ', $headers);

        return function (
            Request $request,
            Response $response,
            callable $next
        ) use (
            $origin,
            $methodsString,
            $headersString
        ) {
            // Aplica headers diretamente sem array intermediário
            if ($response instanceof Response) {
                $response->header('Access-Control-Allow-Origin', $origin);
                $response->header('Access-Control-Allow-Methods', $methodsString);
                $response->header('Access-Control-Allow-Headers', $headersString);
                $response->header('Access-Control-Max-Age', '86400');
            }

            if (
                method_exists($request, 'getMethod') &&
                $request->getMethod() === 'OPTIONS'
            ) {
                if ($response instanceof Response) {
                    $response->status(200)->send();
                }
                return;
            }

            $next();
        };
    }

    /**
     * Obtém estatísticas do cache CORS
     *
     * @return array
     */
    public static function getStats(): array
    {
        return [
            'cached_configs' => count(self::$preCompiledHeaders),
            'header_strings' => count(self::$compiledHeaderStrings),
            'memory_usage' => self::_getMemoryUsage()
        ];
    }

    /**
     * Calcula o uso de memória com cache para melhor performance
     * @return array<string, int>
     */
    private static function _getMemoryUsage(): array
    {
        // Gera hash dos dados atuais de forma mais eficiente
        $currentDataHash = md5(
            count(self::$preCompiledHeaders) . '|' .
                count(self::$compiledHeaderStrings) . '|' .
                serialize(array_keys(self::$preCompiledHeaders))
        );

        // Se o cache é válido, retorna os dados em cache
        if (self::$memoryUsageCache !== null && self::$lastDataHash === $currentDataHash) {
            return self::$memoryUsageCache;
        }

        // Recalcula o uso de memória usando cache de serialização otimizado
        $headersMemory = SerializationCache::getSerializedSize(
            self::$preCompiledHeaders,
            'cors_precompiled_headers'
        );
        $stringsMemory = SerializationCache::getSerializedSize(
            self::$compiledHeaderStrings,
            'cors_compiled_strings'
        );
        self::$memoryUsageCache = [
            'headers' => $headersMemory,
            'strings' => $stringsMemory,
            'total' => $headersMemory + $stringsMemory
        ];
        self::$lastDataHash = $currentDataHash;

        return self::$memoryUsageCache;
    }

    /**
     * Limpa cache CORS
     */
    public static function clearCache(): void
    {
        self::$preCompiledHeaders = [];
        self::$compiledHeaderStrings = [];
        self::$memoryUsageCache = null;
        self::$lastDataHash = null;

        // Limpa cache de serialização relacionado
        SerializationCache::clearCache();
    }

    /**
     * Invalida o cache de uso de memória
     * @return void
     */
    private static function _invalidateMemoryCache(): void
    {
        self::$memoryUsageCache = null;
        self::$lastDataHash = null;
    }

    /**
     * Benchmark da performance CORS
     */
    public static function benchmark(int $iterations = 10000): array
    {
        $configs = [
            'simple' => ['origins' => '*'],
            'multiple_origins' => ['origins' => ['https://example.com', 'https://api.example.com']],
            'complex' => [
                'origins' => ['https://*.example.com', 'http://localhost:*'],
                'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'headers' => ['Content-Type', 'Authorization', 'X-Custom-Header'],
                'credentials' => true,
                'expose' => ['X-Total-Count', 'X-Rate-Limit']
            ]
        ];

        $results = [];

        foreach ($configs as $name => $config) {
            $middleware = self::create($config);

            // Mock request/response
            $request = (object) ['method' => 'GET'];
            $response = new class
            {
                public function header(string $name, string $value): self
                {
                    return $this;
                }

                public function status(int $code): self
                {
                    return $this;
                }

                public function send(): self
                {
                    return $this;
                }
            };

            $start = microtime(true);

            for ($i = 0; $i < $iterations; $i++) {
                $middleware(
                    $request,
                    $response,
                    function () {
                    }
                );
            }

            $end = microtime(true);
            $time = $end - $start;

            $results[$name] = [
                'iterations' => $iterations,
                'total_time' => $time,
                'avg_time_microseconds' => ($time / $iterations) * 1000000,
                'ops_per_second' => $iterations / $time
            ];
        }

        return [
            'results' => $results,
            'cache_stats' => self::getStats()
        ];
    }
}
