<?php

declare(strict_types=1);

namespace Express\Http\Psr7\Factory;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Express\Http\Psr7\Response;

/**
 * PSR-17 Response Factory implementation
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create a new response.
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return new Response($code, [], null, '1.1', $reasonPhrase);
    }
}
