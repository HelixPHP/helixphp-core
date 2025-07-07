<?php

namespace PivotPHP\Core\Middleware\Core;

/**
 * Interface para middlewares.
 */
interface MiddlewareInterface
{
    /**
     * Executa o middleware.
     *
     * @param  \PivotPHP\Core\Http\Request  $request
     * @param  \PivotPHP\Core\Http\Response $response
     * @param  callable               $next
     * @return mixed
     */
    public function handle($request, $response, callable $next);
}
