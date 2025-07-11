<?php

declare(strict_types=1);

namespace PivotPHP\Core\Middleware\Security;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * XSS Protection Middleware
 *
 * Provides protection against Cross-Site Scripting (XSS) attacks by
 * sanitizing request input and adding appropriate security headers.
 *
 * @package PivotPHP\Core\Middleware\Security
 * @since 1.1.2
 */
class XssMiddleware implements MiddlewareInterface
{
    private string $allowedTags;

    public function __construct(string $allowedTags = '')
    {
        $this->allowedTags = $allowedTags;
    }

    /**
     * Process the request
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            $parsedBody = $this->sanitizeArray($parsedBody, $this->allowedTags);
            $request = $request->withParsedBody($parsedBody);
        }
        return $handler->handle($request);
    }

    /**
     * Sanitize method
     */
    public static function sanitize(string $input, string $allowedTags = ''): string
    {
        // Remove <script> e conteúdo, depois strip_tags
        if ($input === '') {
            return '';
        }
        $input = trim($input);
        $input = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $input);
        if ($input === null) {
            $input = '';
        }
        $input = preg_replace('/<iframe\b[^>]*>(.*?)<\/iframe>/is', '', $input);
        if ($input === null) {
            $input = '';
        }
        $input = preg_replace('/<svg\b[^>]*>(.*?)<\/svg>/is', '', $input);
        if ($input === null) {
            $input = '';
        }
        $input = preg_replace('/<img\b[^>]*onerror=[^>]+>/is', '', $input);
        if ($input === null) {
            $input = '';
        }
        $input = preg_replace('/on\w+\s*=\s*(["\']).*?\1/is', '', $input);
        if ($input === null) {
            $input = '';
        }
        return strip_tags($input, $allowedTags);
    }

    /**
     * CleanUrl method
     */
    public static function cleanUrl(string $url): string
    {
        // Remove javascript: e outros protocolos perigosos
        if (preg_match('/^(javascript|data|vbscript):/i', $url)) {
            return '';
        }
        return $url;
    }

    /**
     * ContainsXss method
     */
    public static function containsXss(string $input): bool
    {
        // Detecta tags e atributos perigosos
        return preg_match('/<\s*script|on\w+\s*=|javascript:/i', $input) === 1;
    }

    private function sanitizeArray(array $data, string $allowedTags = ''): array
    {
        $sanitized = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeArray($value, $allowedTags);
            } else {
                $sanitized[$key] = self::sanitize((string)$value, $allowedTags);
            }
        }
        return $sanitized;
    }
}
