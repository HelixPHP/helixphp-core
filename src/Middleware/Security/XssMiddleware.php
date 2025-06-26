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
            'reportUri' => null
        ], $options);
    }

    public function handle($request, $response, callable $next)
    {
        $headerValue = '1; mode=' . $this->options['mode'];

        if ($this->options['reportUri']) {
            $headerValue .= '; report=' . $this->options['reportUri'];
        }

        $response->header('X-XSS-Protection', $headerValue);

        return $next();
    }
}
