<?php

namespace Helix\Middleware\Security;

use Helix\Middleware\Core\BaseMiddleware;

/**
 * Middleware de segurança geral para HelixPHP.
 */
class SecurityMiddleware extends BaseMiddleware
{
    /** @var array<string, mixed> */
    private array $options;

    /** @param array<string, mixed> $options */
    public function __construct(array $options = [])
    {
        $this->options = array_merge(
            [
                'contentSecurityPolicy' => true,
                'xFrameOptions' => 'DENY',
                'xContentTypeOptions' => true,
                'referrerPolicy' => 'strict-origin-when-cross-origin',
                'permissionsPolicy' => true
            ],
            $options
        );
    }

    public function handle($request, $response, callable $next)
    {
        // Set security headers
        if ($this->options['contentSecurityPolicy']) {
            $response->header('Content-Security-Policy', "default-src 'self'");
        }

        if ($this->options['xFrameOptions']) {
            $response->header('X-Frame-Options', $this->options['xFrameOptions']);
        }

        if ($this->options['xContentTypeOptions']) {
            $response->header('X-Content-Type-Options', 'nosniff');
        }

        if ($this->options['referrerPolicy']) {
            $response->header('Referrer-Policy', $this->options['referrerPolicy']);
        }

        $response->header('X-XSS-Protection', '1; mode=block');

        return $next();
    }
}
