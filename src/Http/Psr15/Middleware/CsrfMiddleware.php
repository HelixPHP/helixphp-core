<?php

declare(strict_types=1);

namespace Express\Http\Psr15\Middleware;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Express\Http\Psr7\Response;
use Express\Http\Psr7\Stream;

class CsrfMiddleware implements MiddlewareInterface
{
    private string $fieldName;

    public function __construct(string $fieldName = '_csrf_token')
    {
        $this->fieldName = $fieldName;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (strtoupper($request->getMethod()) === 'POST') {
            $parsedBody = $request->getParsedBody();
            $token = is_array($parsedBody) ? ($parsedBody[$this->fieldName] ?? null) : null;
            $sessionToken = $_SESSION[$this->fieldName] ?? null;
            if (!$token || !$sessionToken || !hash_equals($sessionToken, $token)) {
                $error = ['error' => 'CSRF token inválido ou ausente'];
                $body = Stream::createFromString((string)json_encode($error, JSON_UNESCAPED_UNICODE));
                return (new Response(403))
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($body);
            }
        }
        // Gera novo token para próxima requisição
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION[$this->fieldName] = bin2hex(random_bytes(32));
        return $handler->handle($request);
    }

    public static function getToken(string $fieldName = '_csrf_token'): string
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        if (empty($_SESSION[$fieldName])) {
            $_SESSION[$fieldName] = bin2hex(random_bytes(32));
        }
        return $_SESSION[$fieldName];
    }

    public static function hiddenField(string $fieldName = '_csrf_token'): string
    {
        $token = self::getToken($fieldName);
        return
            '<input type="hidden" name="' .
            htmlspecialchars($fieldName) .
            '" value="' .
            htmlspecialchars($token) .
            '">';
    }

    public static function metaTag(string $fieldName = '_csrf_token'): string
    {
        $token = self::getToken($fieldName);
        return '<meta name="csrf-token" content="' . htmlspecialchars($token) . '">';
    }
}
