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
        // Handle legacy 'origin' option
        if (isset($options['origin']) && !isset($options['origins'])) {
            $options['origins'] = is_array($options['origin']) ? $options['origin'] : [$options['origin']];
            unset($options['origin']);
        }

        $this->options = array_merge([
            'origins' => ['*'],
            'methods' => ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
            'headers' => ['Content-Type', 'Authorization'],
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
        $allowedOrigins = $this->options['origins'];

        // Se permite todas as origens
        if (in_array('*', $allowedOrigins)) {
            return true;
        }

        if (empty($origin)) {
            return false;
        }

        // Verifica se a origem específica é permitida
        return in_array($origin, $allowedOrigins);
    }

    /**
     * Adiciona os cabeçalhos CORS à resposta.
     */
    private function addCorsHeaders($response, ?string $origin): void
    {
        // Origin
        if (in_array('*', $this->options['origins'])) {
            $this->setHeader($response, 'Access-Control-Allow-Origin', '*');
        } elseif ($origin) {
            $this->setHeader($response, 'Access-Control-Allow-Origin', $origin);
        }

        // Methods
        $methods = is_array($this->options['methods']) ? $this->options['methods'] : explode(',', $this->options['methods']);
        $this->setHeader($response, 'Access-Control-Allow-Methods', implode(',', $methods));

        // Headers
        $headers = is_array($this->options['headers']) ? $this->options['headers'] : explode(',', $this->options['headers']);
        $this->setHeader($response, 'Access-Control-Allow-Headers', implode(',', $headers));

        // Credentials
        if ($this->options['credentials']) {
            $this->setHeader($response, 'Access-Control-Allow-Credentials', 'true');
        }

        // Max Age
        $this->setHeader($response, 'Access-Control-Max-Age', (string)$this->options['maxAge']);

        // Exposed Headers
        if (!empty($this->options['exposedHeaders'])) {
            $this->setHeader($response, 'Access-Control-Expose-Headers', implode(', ', $this->options['exposedHeaders']));
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

    /**
     * Set a header on response (supports both Response objects and test objects)
     */
    private function setHeader($response, string $name, string $value): void
    {
        if ($response instanceof \Express\Http\Response) {
            $this->setHeader($response, $name, $value);
        } elseif (is_object($response)) {
            // Handle test objects
            if (!isset($response->headers)) {
                $response->headers = [];
            }
            $response->headers[] = $name . ': ' . $value;
        }
    }
}
