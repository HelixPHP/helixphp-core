<?php

namespace Express\Middleware\Security;

use Express\Middleware\Core\BaseMiddleware;
use Express\Http\Request;
use Express\Http\Response;

/**
 * Middleware CORS (Cross-Origin Resource Sharing).
 */
class CorsMiddleware extends BaseMiddleware
{
    private array $options;

    /**
     * @param array<string, mixed> $options Opções de configuração CORS
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'origins' => ['*'],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS', 'HEAD', 'PATCH'],
            'headers' => ['Content-Type', 'Authorization', 'X-Requested-With', 'Accept', 'Origin'],
            'credentials' => false,
            'maxAge' => 86400, // 24 hours
            'exposedHeaders' => []
        ], $options);
    }

    /**
     * Executa o middleware CORS.
     */
    public function handle($request, $response, callable $next)
    {
        $origin = $this->getHeader($request, 'Origin');

        // Verifica se a origem é permitida
        if ($this->isOriginAllowed($origin)) {
            $this->addCorsHeaders($response, $origin);
        }

        // Para requisições OPTIONS (preflight), retorna imediatamente
        if ($request->method === 'OPTIONS') {
            return $response->status(200)->text('');
        }

        return $next($request, $response);
    }

    /**
     * Verifica se a origem é permitida.
     */
    private function isOriginAllowed(?string $origin): bool
    {
        if (empty($origin)) {
            return false;
        }

        $allowedOrigins = $this->options['origins'];

        // Se permite todas as origens
        if (in_array('*', $allowedOrigins)) {
            return true;
        }

        // Verifica se a origem específica é permitida
        return in_array($origin, $allowedOrigins);
    }

    /**
     * Adiciona os cabeçalhos CORS à resposta.
     */
    private function addCorsHeaders(Response $response, ?string $origin): void
    {
        // Origin
        if ($origin && in_array('*', $this->options['origins'])) {
            $response->header('Access-Control-Allow-Origin', '*');
        } elseif ($origin) {
            $response->header('Access-Control-Allow-Origin', $origin);
        }

        // Methods
        $response->header('Access-Control-Allow-Methods', implode(', ', $this->options['methods']));

        // Headers
        $response->header('Access-Control-Allow-Headers', implode(', ', $this->options['headers']));

        // Credentials
        if ($this->options['credentials']) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        // Max Age
        $response->header('Access-Control-Max-Age', (string)$this->options['maxAge']);

        // Exposed Headers
        if (!empty($this->options['exposedHeaders'])) {
            $response->header('Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
        }
    }

    /**
     * Cria uma instância com configuração padrão para desenvolvimento.
     */
    public static function development(): self
    {
        return new self([
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
        ]);
    }

    /**
     * Cria uma instância com configuração para produção.
     *
     * @param array<string> $allowedOrigins
     */
    public static function production(array $allowedOrigins): self
    {
        return new self([
            'origins' => $allowedOrigins,
            'credentials' => true,
            'headers' => [
                'Content-Type',
                'Authorization',
                'X-Requested-With',
                'Accept',
                'Origin'
            ]
        ]);
    }
}
