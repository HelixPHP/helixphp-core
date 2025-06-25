<?php
namespace Express\Middlewares\Security;

/**
 * Middleware de segurança combinado para Express PHP.
 * Inclui proteções CSRF, XSS e outros cabeçalhos de segurança.
 */
class SecurityMiddleware
{
    private $csrfMiddleware;
    private $xssMiddleware;
    private $options;

    /**
     * @param array $options Opções de configuração:
     *   - csrf: array (opções para CSRF middleware)
     *   - xss: array (opções para XSS middleware)
     *   - enableCsrf: bool (habilitar proteção CSRF, default: true)
     *   - enableXss: bool (habilitar proteção XSS, default: true)
     *   - rateLimiting: bool (habilitar rate limiting básico, default: false)
     *   - sessionSecurity: bool (configurar segurança de sessão, default: true)
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'csrf' => [],
            'xss' => [],
            'enableCsrf' => true,
            'enableXss' => true,
            'rateLimiting' => false,
            'sessionSecurity' => true,
        ], $options);

        // Inicializa middlewares individuais
        if ($this->options['enableCsrf']) {
            $this->csrfMiddleware = new CsrfMiddleware($this->options['csrf']);
        }

        if ($this->options['enableXss']) {
            $this->xssMiddleware = new XssMiddleware($this->options['xss']);
        }
    }

    public function __invoke($request, $response, $next)
    {
        // Configura segurança de sessão
        if ($this->options['sessionSecurity']) {
            $this->configureSessionSecurity();
        }

        // Rate limiting básico
        if ($this->options['rateLimiting']) {
            if (!$this->checkRateLimit($request, $response)) {
                return;
            }
        }

        // Aplica proteção XSS primeiro
        if ($this->xssMiddleware) {
            $xssNext = function() use ($request, $response, $next) {
                // Aplica proteção CSRF depois
                if ($this->csrfMiddleware) {
                    $csrfNext = function() use ($next) {
                        $next();
                    };
                    call_user_func($this->csrfMiddleware, $request, $response, $csrfNext);
                } else {
                    $next();
                }
            };
            call_user_func($this->xssMiddleware, $request, $response, $xssNext);
        } else if ($this->csrfMiddleware) {
            call_user_func($this->csrfMiddleware, $request, $response, $next);
        } else {
            $next();
        }
    }

    /**
     * Configura parâmetros de segurança da sessão
     */
    private function configureSessionSecurity()
    {
        // Configura parâmetros de sessão seguros
        if (session_status() === PHP_SESSION_NONE) {
            // Usa apenas cookies
            ini_set('session.use_only_cookies', 1);
            
            // Regenera ID da sessão para prevenir fixation
            ini_set('session.use_strict_mode', 1);
            
            // HttpOnly cookies (não acessíveis via JavaScript)
            ini_set('session.cookie_httponly', 1);
            
            // Secure cookies (apenas HTTPS) - descomente se usando HTTPS
            // ini_set('session.cookie_secure', 1);
            
            // SameSite cookies
            ini_set('session.cookie_samesite', 'Strict');
            
            // Define tempo de vida da sessão
            ini_set('session.gc_maxlifetime', 3600); // 1 hora
            
            session_start();
            
            // Regenera ID da sessão periodicamente
            if (!isset($_SESSION['last_regeneration'])) {
                $_SESSION['last_regeneration'] = time();
            } else if (time() - $_SESSION['last_regeneration'] > 300) { // 5 minutos
                session_regenerate_id(true);
                $_SESSION['last_regeneration'] = time();
            }
        }
    }

    /**
     * Rate limiting básico baseado em IP
     */
    private function checkRateLimit($request, $response)
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $key = 'rate_limit_' . md5($ip);
        
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        
        $now = time();
        $window = 60; // 1 minuto
        $maxRequests = 100; // máximo de requests por minuto
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        $data = $_SESSION[$key];
        
        // Reset window se passou do tempo
        if ($now - $data['start'] > $window) {
            $_SESSION[$key] = ['count' => 1, 'start' => $now];
            return true;
        }
        
        // Incrementa contador
        $_SESSION[$key]['count']++;
        
        // Verifica se excedeu o limite
        if ($data['count'] > $maxRequests) {
            $response->status(429)->json([
                'error' => 'Too Many Requests',
                'message' => 'Rate limit exceeded. Try again later.',
                'retryAfter' => $window - ($now - $data['start'])
            ]);
            return false;
        }
        
        return true;
    }

    /**
     * Método estático para criar uma instância com configuração padrão
     */
    public static function create(array $options = [])
    {
        return new self($options);
    }

    /**
     * Método estático para criar uma instância apenas com CSRF
     */
    public static function csrfOnly(array $options = [])
    {
        return new self(array_merge($options, [
            'enableXss' => false,
            'enableCsrf' => true
        ]));
    }

    /**
     * Método estático para criar uma instância apenas com XSS
     */
    public static function xssOnly(array $options = [])
    {
        return new self(array_merge($options, [
            'enableCsrf' => false,
            'enableXss' => true
        ]));
    }

    /**
     * Método estático para criar uma instância com máxima segurança
     */
    public static function strict(array $options = [])
    {
        return new self(array_merge([
            'enableCsrf' => true,
            'enableXss' => true,
            'rateLimiting' => true,
            'sessionSecurity' => true,
            'csrf' => [
                'methods' => ['POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS']
            ],
            'xss' => [
                'sanitizeInput' => true,
                'securityHeaders' => true,
                'contentSecurityPolicy' => "default-src 'self'; script-src 'self'; style-src 'self'; img-src 'self' data:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';"
            ]
        ], $options));
    }
}
