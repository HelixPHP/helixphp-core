<?php

namespace Express\Middleware\Security;

use Express\Middleware\Core\BaseMiddleware;

/**
 * Middleware de proteção XSS para Express PHP.
 */
class XssMiddleware extends BaseMiddleware
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'mode' => 'block',
            'reportUri' => null,
            'sanitizeInput' => false,
            'allowedTags' => '',
            'checkUrls' => false
        ], $options);
    }

    public function handle($request, $response, callable $next)
    {
        // Adiciona header de proteção XSS
        $headerValue = '1; mode=' . $this->options['mode'];

        if ($this->options['reportUri']) {
            $headerValue .= '; report=' . $this->options['reportUri'];
        }

        $response->header('X-XSS-Protection', $headerValue);

        // Sanitiza entradas se configurado
        if ($this->options['sanitizeInput']) {
            $this->sanitizeRequestData($request);
        }

        return $next();
    }

    /**
     * Verifica se uma string contém padrões XSS
     */
    public static function containsXss(string $input): bool
    {
        $patterns = [
            // Scripts básicos
            '/<script[^>]*>.*?<\/script>/is',
            '/javascript:/i',
            '/vbscript:/i',
            '/data:/i',

            // Event handlers
            '/on\w+\s*=/i',

            // Tags perigosas
            '/<iframe[^>]*>/i',
            '/<object[^>]*>/i',
            '/<embed[^>]*>/i',
            '/<applet[^>]*>/i',
            '/<meta[^>]*>/i',
            '/<link[^>]*>/i',

            // SVG com scripts
            '/<svg[^>]*onload/i',

            // Outras tentativas de XSS
            '/expression\s*\(/i',
            '/url\s*\(/i',
            '/import\s/i',
            '/@import/i',

            // Tentativas de bypass
            '/&lt;script/i',
            '/&amp;lt;script/i',
            '/&#60;script/i',
            '/&#x3c;script/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitiza string removendo ou escapando conteúdo XSS
     */
    public static function sanitize(string $input, string $allowedTags = ''): string
    {
        // Remove ou escapa tags perigosas
        $input = strip_tags($input, $allowedTags);

        // Remove javascript: e data: URLs
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/vbscript:/i', '', $input);
        $input = preg_replace('/data:/i', '', $input);

        // Remove event handlers
        $input = preg_replace('/on\w+\s*=\s*["\'][^"\']*["\']/i', '', $input);
        $input = preg_replace('/on\w+\s*=\s*[^>\s]+/i', '', $input);

        // Escapa caracteres perigosos
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $input;
    }

    /**
     * Limpa URLs removendo esquemas perigosos
     */
    public static function cleanUrl(string $url): string
    {
        // Lista de esquemas perigosos
        $dangerousSchemes = ['javascript', 'vbscript', 'data', 'file'];

        $parsedUrl = parse_url($url);

        if (isset($parsedUrl['scheme'])) {
            $scheme = strtolower($parsedUrl['scheme']);
            if (in_array($scheme, $dangerousSchemes)) {
                return '#'; // Retorna URL segura
            }
        }

        // Remove caracteres potencialmente perigosos
        $url = preg_replace('/[<>"\']/', '', $url);

        return $url;
    }

    /**
     * Sanitiza dados da requisição
     */
    private function sanitizeRequestData($request): void
    {
        // Sanitiza GET
        if (isset($_GET)) {
            foreach ($_GET as $key => $value) {
                if (is_string($value)) {
                    $_GET[$key] = self::sanitize($value, $this->options['allowedTags']);
                }
            }
        }

        // Sanitiza POST
        if (isset($_POST)) {
            foreach ($_POST as $key => $value) {
                if (is_string($value)) {
                    $_POST[$key] = self::sanitize($value, $this->options['allowedTags']);
                }
            }
        }
    }
}
