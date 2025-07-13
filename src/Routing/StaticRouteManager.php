<?php

declare(strict_types=1);

namespace PivotPHP\Core\Routing;

use PivotPHP\Core\Http\Request;
use PivotPHP\Core\Http\Response;

/**
 * Static Route Manager
 *
 * Implementação simplificada e segura para rotas estáticas.
 * Elimina complexidade desnecessária da análise automática de código.
 *
 * Filosofia: Se o desenvolvedor quer pré-compilação, ele DECLARA explicitamente
 * usando $app->static() em vez de deixar o sistema "adivinhar".
 *
 * @package PivotPHP\Core\Routing
 * @since 1.1.3
 */
class StaticRouteManager
{
    /**
     * Cache de rotas estáticas pré-compiladas
     * @var array<string, string>
     */
    private static array $staticCache = [];

    /**
     * Estatísticas simples
     * @var array<string, mixed>
     */
    private static array $stats = [
        'static_routes_count' => 0,
        'total_hits' => 0,
        'memory_usage_bytes' => 0
    ];

    /**
     * Configurações
     * @var array<string, mixed>
     */
    private static array $config = [
        'max_response_size' => 10240,  // 10KB máximo
        'validate_json' => true,       // Valida JSON no registro
        'enable_compression' => false   // Compressão para responses grandes
    ];

    /**
     * Registra uma rota estática
     *
     * @param string $path Caminho da rota
     * @param callable $handler Handler que DEVE retornar dados estáticos
     * @param array $options Opções adicionais
     * @return callable Handler otimizado
     */
    public static function register(string $path, callable $handler, array $options = []): callable
    {
        // Executa handler UMA VEZ para capturar response estática
        $response = self::captureStaticResponse($handler);

        if ($response === null) {
            throw new \InvalidArgumentException(
                "Static route handler for '{$path}' must return a static response"
            );
        }

        // Valida tamanho
        if (strlen($response) > self::$config['max_response_size']) {
            throw new \InvalidArgumentException(
                "Static response too large: " . strlen($response) .
                " bytes (max: " . self::$config['max_response_size'] . ")"
            );
        }

        // Valida JSON se habilitado
        if (self::$config['validate_json'] && !self::isValidJson($response)) {
            throw new \InvalidArgumentException(
                "Static route handler for '{$path}' must return valid JSON"
            );
        }

        // Aplica compressão se habilitada e benéfica
        if (self::$config['enable_compression'] && strlen($response) > 1024) {
            $compressed = gzcompress($response, 6);
            if ($compressed !== false && strlen($compressed) < strlen($response) * 0.8) { // Só usa se reduzir >20%
                $response = $compressed;
                $options['compressed'] = true;
            }
        }

        // Armazena no cache
        self::$staticCache[$path] = $response;
        self::$stats['static_routes_count']++;
        self::$stats['memory_usage_bytes'] += strlen($response);

        // Retorna handler ultra-otimizado
        return self::createOptimizedHandler($path, $response, $options);
    }

    /**
     * Captura response estática executando handler uma vez
     */
    private static function captureStaticResponse(callable $handler): ?string
    {
        try {
            // Cria objetos mock para capturar response
            $mockRequest = new MockRequest();
            $mockResponse = new MockResponse();

            // Executa handler
            $result = $handler($mockRequest, $mockResponse);

            // Se handler retornou response object, extrai conteúdo
            if ($result instanceof MockResponse) {
                return $result->getContent();
            }

            // Se retornou string diretamente
            if (is_string($result)) {
                return $result;
            }

            // Se retornou array, converte para JSON
            if (is_array($result)) {
                return json_encode($result); // @phpstan-ignore-line
            }

            // Verifica se mockResponse foi usado
            $content = $mockResponse->getContent();
            if ($content !== '') {
                return $content;
            }
        } catch (\Throwable $e) {
            // Se handler falhou, não é estático
            return null;
        }

        return null;
    }

    /**
     * Cria handler otimizado para runtime
     */
    private static function createOptimizedHandler(string $path, string $response, array $options): callable
    {
        $isCompressed = $options['compressed'] ?? false;

        return function (Request $req, Response $res) use ($response, $isCompressed) {
            self::$stats['total_hits']++;

            // Se está comprimido, descomprime
            if ($isCompressed) {
                $decompressed = gzuncompress($response);
                $content = $decompressed !== false ? $decompressed : $response;
            } else {
                $content = $response;
            }

            // Retorna response diretamente - zero overhead
            return $res->withHeader('Content-Type', 'application/json')
                      ->withHeader('X-Static-Route', 'true')
                      ->write($content);
        };
    }

    /**
     * Verifica se string é JSON válido
     */
    private static function isValidJson(string $data): bool
    {
        json_decode($data);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Obtém estatísticas
     */
    public static function getStats(): array
    {
        return self::$stats;
    }

    /**
     * Configura o manager
     */
    public static function configure(array $config): void
    {
        self::$config = array_merge(self::$config, $config);
    }

    /**
     * Limpa cache
     */
    public static function clearCache(): void
    {
        self::$staticCache = [];
        self::$stats = [
            'static_routes_count' => 0,
            'total_hits' => 0,
            'memory_usage_bytes' => 0
        ];
    }

    /**
     * Lista todas as rotas estáticas
     */
    public static function getStaticRoutes(): array
    {
        return array_keys(self::$staticCache);
    }

    /**
     * Obtém response estática cached
     */
    public static function getCachedResponse(string $path): ?string
    {
        return self::$staticCache[$path] ?? null;
    }

    /**
     * Pré-aquece todas as rotas estáticas
     */
    public static function warmup(): void
    {
        // Já aquecidas no registro - nada a fazer
        // Esta é a vantagem da abordagem explícita
    }
}
