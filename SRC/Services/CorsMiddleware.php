<?php
namespace Express\SRC\Services;

/**
 * Middleware padrão para configuração de CORS no Express PHP.
 * Permite customizar origens, métodos e headers permitidos.
 */
class CorsMiddleware
{
    private $options;

    /**
     * @param array $options Opções de configuração:
     *   - origin: string|array|null (origem permitida, '*' para todas)
     *   - methods: string (métodos permitidos, ex: 'GET,POST,PUT,DELETE')
     *   - headers: string (headers permitidos)
     *   - credentials: bool (permite credenciais)
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'origin' => '*',
            'methods' => 'GET,POST,PUT,DELETE,PATCH,OPTIONS',
            'headers' => 'Content-Type,Authorization',
            'credentials' => false,
        ], $options);
    }

    public function __invoke($request, $response, $next)
    {
        $origin = $this->options['origin'];
        if (is_array($origin)) {
            $origin = isset($_SERVER['HTTP_ORIGIN']) && in_array($_SERVER['HTTP_ORIGIN'], $origin)
                ? $_SERVER['HTTP_ORIGIN'] : 'null';
        }
        $response->header('Access-Control-Allow-Origin', $origin);
        $response->header('Access-Control-Allow-Methods', $this->options['methods']);
        $response->header('Access-Control-Allow-Headers', $this->options['headers']);
        if ($this->options['credentials']) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }
        // Responde pré-flight OPTIONS imediatamente
        if ($request->method === 'OPTIONS') {
            $response->status(204)->end();
            exit;
        }
        $next();
    }
}
