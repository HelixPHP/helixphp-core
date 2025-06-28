<?php

namespace Express\Middleware\Security;

use Express\Middleware\Core\BaseMiddleware;

/**
 * Middleware de proteção CSRF para Express PHP.
 */
class CsrfMiddleware extends BaseMiddleware
{
    /** @var array<string, mixed> */
    private array $options;

    /** @param array<string, mixed> $options */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(
            [
            'tokenLength' => 32,
            'sessionKey' => '_csrf_token',
            'headerName' => 'X-CSRF-Token',
            'fieldName' => '_token',
            'excludeMethods' => ['GET', 'HEAD', 'OPTIONS']
            ],
            $options
        );
    }

    public function handle($request, $response, callable $next)
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        // Generate token if not exists
        if (!isset($_SESSION[$this->options['sessionKey']])) {
            $_SESSION[$this->options['sessionKey']] = $this->generateToken();
        }

        // Skip validation for safe methods
        $excludeMethods = $this->options['excludeMethods'] ?? [];
        if (is_array($excludeMethods) && in_array($method, $excludeMethods)) {
            return $next();
        }

        // Validate token
        $token = $this->getTokenFromRequest($request);
        if (!$this->validateToken($token)) {
            return $response->status(403)->json(
                [
                'error' => true,
                'message' => 'CSRF token mismatch'
                ]
            );
        }

        return $next();
    }

    private function generateToken(): string
    {
        $length = (int) ($this->options['tokenLength'] / 2);
        if ($length < 1) {
            $length = 16;
        }
        return bin2hex(random_bytes($length));
    }

    /**
     * @param mixed $request
     */
    private function getTokenFromRequest($request): ?string
    {
        // Check header
        $headerName = $this->options['headerName'] ?? 'X-CSRF-Token';
        $headerName = is_string($headerName) ? $headerName : 'X-CSRF-Token';
        $headerKey = 'HTTP_' . str_replace('-', '_', strtoupper($headerName));
        if (isset($_SERVER[$headerKey])) {
            return $_SERVER[$headerKey];
        }

        // Check POST data
        if (isset($_POST[$this->options['fieldName']])) {
            return $_POST[$this->options['fieldName']];
        }

        return null;
    }

    private function validateToken(?string $token): bool
    {
        if (!$token || !isset($_SESSION[$this->options['sessionKey']])) {
            return false;
        }

        return hash_equals($_SESSION[$this->options['sessionKey']], $token);
    }

    /**
     * Obtém o token CSRF atual da sessão (método estático)
     */
    public static function getToken(string $sessionKey = '_csrf_token', int $tokenLength = 32): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$sessionKey])) {
            $length = (int) ($tokenLength / 2);
            if ($length < 1) {
                $length = 16;
            }
            $_SESSION[$sessionKey] = bin2hex(random_bytes($length));
        }

        return $_SESSION[$sessionKey];
    }

    /**
     * Gera um campo hidden HTML com o token CSRF
     */
    public static function hiddenField(string $fieldName = 'csrf_token', string $sessionKey = '_csrf_token'): string
    {
        $token = self::getToken($sessionKey);
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($fieldName, ENT_QUOTES),
            htmlspecialchars($token, ENT_QUOTES)
        );
    }

    /**
     * Gera uma meta tag HTML com o token CSRF
     */
    public static function metaTag(string $name = 'csrf-token', string $sessionKey = '_csrf_token'): string
    {
        $token = self::getToken($sessionKey);
        return sprintf(
            '<meta name="%s" content="%s">',
            htmlspecialchars($name, ENT_QUOTES),
            htmlspecialchars($token, ENT_QUOTES)
        );
    }
}
