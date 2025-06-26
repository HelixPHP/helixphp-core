<?php

namespace Express\Middleware\Security;

use Express\Middleware\Core\BaseMiddleware;

/**
 * Middleware de proteção CSRF para Express PHP.
 */
class CsrfMiddleware extends BaseMiddleware
{
    private array $options;

    public function __construct(array $options = [])
    {
        $this->options = array_merge([
            'tokenLength' => 32,
            'sessionKey' => '_csrf_token',
            'headerName' => 'X-CSRF-Token',
            'fieldName' => '_token',
            'excludeMethods' => ['GET', 'HEAD', 'OPTIONS']
        ], $options);
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
        if (in_array($method, $this->options['excludeMethods'])) {
            return $next();
        }

        // Validate token
        $token = $this->getTokenFromRequest($request);
        if (!$this->validateToken($token)) {
            return $response->status(403)->json([
                'error' => true,
                'message' => 'CSRF token mismatch'
            ]);
        }

        return $next();
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes($this->options['tokenLength']));
    }

    private function getTokenFromRequest($request): ?string
    {
        // Check header
        $headerName = 'HTTP_' . str_replace('-', '_', strtoupper($this->options['headerName']));
        if (isset($_SERVER[$headerName])) {
            return $_SERVER[$headerName];
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

    public function getToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION[$this->options['sessionKey']])) {
            $_SESSION[$this->options['sessionKey']] = $this->generateToken();
        }

        return $_SESSION[$this->options['sessionKey']];
    }
}
