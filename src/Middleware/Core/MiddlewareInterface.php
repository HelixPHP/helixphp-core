<?php

namespace Helix\Middleware\Core;

/**
 * Interface para middlewares.
 */
interface MiddlewareInterface
{
    /**
     * Executa o middleware.
     *
     * @param  \Helix\Http\Request  $request
     * @param  \Helix\Http\Response $response
     * @param  callable               $next
     * @return mixed
     */
    public function handle($request, $response, callable $next);
}
