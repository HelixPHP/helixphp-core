<?php
namespace Express\SRC\Services;

/**
 * Middleware de proteção XSS (Cross-Site Scripting) para Express PHP.
 * Sanitiza dados de entrada e adiciona cabeçalhos de segurança.
 */
class XssMiddleware
{
    private $options;

    /**
     * @param array $options Opções de configuração:
     *   - sanitizeInput: bool (sanitizar dados de entrada automaticamente)
     *   - securityHeaders: bool (adicionar cabeçalhos de segurança)
     *   - contentSecurityPolicy: string|null (política CSP personalizada)
     *   - excludeFields: array (campos que não devem ser sanitizados)
     *   - allowedTags: string (tags HTML permitidas para strip_tags)
     */
    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'sanitizeInput' => true,
            'securityHeaders' => true,
            'contentSecurityPolicy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self' https:; connect-src 'self'; frame-ancestors 'none';",
            'excludeFields' => [],
            'allowedTags' => '<p><br><strong><em><ul><ol><li><a><h1><h2><h3><h4><h5><h6>',
        ], $options);
    }

    public function __invoke($request, $response, $next)
    {
        // Adiciona cabeçalhos de segurança
        if ($this->options['securityHeaders']) {
            $this->addSecurityHeaders($response);
        }

        // Sanitiza dados de entrada
        if ($this->options['sanitizeInput']) {
            $this->sanitizeRequest($request);
        }

        $next();
    }

    /**
     * Adiciona cabeçalhos de segurança à resposta
     */
    private function addSecurityHeaders($response)
    {
        // Proteção XSS
        $response->header('X-XSS-Protection', '1; mode=block');
        
        // Impede que o browser "adivinhe" o tipo de conteúdo
        $response->header('X-Content-Type-Options', 'nosniff');
        
        // Impede que a página seja carregada em frame/iframe (proteção contra clickjacking)
        $response->header('X-Frame-Options', 'DENY');
        
        // Política de referrer
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');
        
        // Content Security Policy
        if ($this->options['contentSecurityPolicy']) {
            $response->header('Content-Security-Policy', $this->options['contentSecurityPolicy']);
        }
        
        // Força HTTPS (uncommente se usando HTTPS)
        // $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    /**
     * Sanitiza dados da requisição
     */
    private function sanitizeRequest($request)
    {
        // Sanitiza parâmetros GET
        if (isset($_GET) && !empty($_GET)) {
            $_GET = $this->sanitizeArray($_GET);
        }

        // Sanitiza dados POST
        if (isset($_POST) && !empty($_POST)) {
            $_POST = $this->sanitizeArray($_POST);
        }

        // Sanitiza cookies
        if (isset($_COOKIE) && !empty($_COOKIE)) {
            $_COOKIE = $this->sanitizeArray($_COOKIE);
        }

        // Sanitiza body da requisição se disponível
        if (isset($request->body) && is_array($request->body)) {
            $request->body = $this->sanitizeArray($request->body);
        }

        // Sanitiza query parameters se disponível
        if (isset($request->query) && is_array($request->query)) {
            $request->query = $this->sanitizeArray($request->query);
        }
    }

    /**
     * Sanitiza um array recursivamente
     */
    private function sanitizeArray($data)
    {
        if (!is_array($data)) {
            return $this->sanitizeValue($data);
        }

        $sanitized = [];
        foreach ($data as $key => $value) {
            $sanitizedKey = $this->sanitizeValue($key);
            
            if (is_array($value)) {
                $sanitized[$sanitizedKey] = $this->sanitizeArray($value);
            } else {
                // Verifica se o campo deve ser excluído da sanitização
                if (!in_array($key, $this->options['excludeFields'])) {
                    $sanitized[$sanitizedKey] = $this->sanitizeValue($value);
                } else {
                    $sanitized[$sanitizedKey] = $value;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitiza um valor individual
     */
    private function sanitizeValue($value)
    {
        if (!is_string($value)) {
            return $value;
        }

        // Remove caracteres de controle
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Converte caracteres especiais para entidades HTML
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        // Remove tags HTML perigosas (mantém apenas as permitidas)
        $value = strip_tags($value, $this->options['allowedTags']);
        
        // Remove scripts inline
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        // Remove event handlers JavaScript
        $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        
        // Remove javascript: URLs
        $value = preg_replace('/javascript\s*:/i', '', $value);
        
        return trim($value);
    }

    /**
     * Método estático para sanitizar uma string
     */
    public static function sanitize($value, $allowedTags = '')
    {
        if (!is_string($value)) {
            return $value;
        }

        // Remove caracteres de controle
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', $value);
        
        // Converte caracteres especiais para entidades HTML
        $value = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        
        // Remove tags HTML perigosas
        $value = strip_tags($value, $allowedTags);
        
        // Remove scripts inline
        $value = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $value);
        
        // Remove event handlers JavaScript
        $value = preg_replace('/\s*on\w+\s*=\s*["\'][^"\']*["\']/i', '', $value);
        
        // Remove javascript: URLs
        $value = preg_replace('/javascript\s*:/i', '', $value);
        
        return trim($value);
    }

    /**
     * Método estático para validar se uma string contém XSS
     */
    public static function containsXss($value)
    {
        if (!is_string($value)) {
            return false;
        }

        $patterns = [
            '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
            '/javascript\s*:/i',
            '/\s*on\w+\s*=/i',
            '/<iframe\b[^>]*>/i',
            '/<object\b[^>]*>/i',
            '/<embed\b[^>]*>/i',
            '/<form\b[^>]*>/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Método estático para limpar URLs de JavaScript
     */
    public static function cleanUrl($url)
    {
        if (!is_string($url)) {
            return $url;
        }

        // Remove javascript: e data: URLs maliciosos
        $url = preg_replace('/^(javascript|data|vbscript):/i', '', $url);
        
        // Sanitiza a URL
        $url = filter_var($url, FILTER_SANITIZE_URL);
        
        return $url;
    }
}
