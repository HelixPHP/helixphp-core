<?php
namespace Express\SRC\Middlewares\Core;

/**
 * Middleware simples de rate limiting para Express PHP.
 * Limita o número de requisições por IP em um intervalo de tempo.
 *
 * Exemplo de uso:
 *   $app->use(new RateLimitMiddleware([
 *     'max' => 60, // máximo de requisições
 *     'window' => 60 // janela em segundos
 *   ]));
 */
class RateLimitMiddleware
{
    private $max;
    private $window;
    private static $requests = [];

    /**
     * @param array $options ['max' => int, 'window' => int]
     */
    public function __construct(array $options = [])
    {
        $this->max = $options['max'] ?? 60;
        $this->window = $options['window'] ?? 60;
    }

    public function __invoke($request, $response, $next)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'anon';
        $now = time();
        if (!isset(self::$requests[$ip])) {
            self::$requests[$ip] = [];
        }
        // Remove requisições fora da janela
        self::$requests[$ip] = array_filter(self::$requests[$ip], fn($t) => $t > $now - $this->window);
        self::$requests[$ip][] = $now;
        if (count(self::$requests[$ip]) > $this->max) {
            $response->status(429)->json([
                'error' => true,
                'message' => 'Rate limit exceeded',
                'limit' => $this->max,
                'window' => $this->window
            ]);
            exit;
        }
        $next();
    }
}
