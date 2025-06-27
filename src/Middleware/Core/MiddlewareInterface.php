<?php

namespace Express\Middleware\Core;

/**
 * Interface para middlewares.
 */
interface MiddlewareInterface
{
    /**
     * Executa o middleware.
     *
     * @param  \Express\Http\Request  $request
     * @param  \Express\Http\Response $response
     * @param  callable               $next
     * @return mixed
     */
    public function handle($request, $response, callable $next);
}
