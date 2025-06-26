<?php

namespace Express\Middleware\Core;

use Express\Http\Request;
use Express\Http\Response;

/**
 * Classe base para middlewares.
 */
abstract class BaseMiddleware implements MiddlewareInterface
{
    /**
     * Executa o middleware.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return mixed
     */
    abstract public function handle($request, $response, callable $next);

    /**
     * Obtém um valor do cabeçalho da requisição.
     *
     * @param Request $request
     * @param string $header
     * @param mixed $default
     * @return mixed
     */
    protected function getHeader(Request $request, string $header, $default = null)
    {
        return $request->headers->getHeader($header) ?? $default;
    }

    /**
     * Verifica se a requisição é AJAX.
     *
     * @param Request $request
     * @return bool
     */
    protected function isAjaxRequest(Request $request): bool
    {
        return $request->isAjax();
    }

    /**
     * Obtém o IP do cliente.
     *
     * @param Request $request
     * @return string
     */
    protected function getClientIp(Request $request): string
    {
        return $request->ip();
    }

    /**
     * Verifica se a requisição é HTTPS.
     *
     * @param Request $request
     * @return bool
     */
    protected function isSecureRequest(Request $request): bool
    {
        return $request->isSecure();
    }

    /**
     * Responde com erro JSON.
     *
     * @param Response $response
     * @param int $statusCode
     * @param string $message
     * @param array<string, mixed> $data
     * @return Response
     */
    protected function respondWithError(Response $response, int $statusCode, string $message, array $data = []): Response
    {
        $error = ['error' => $message, 'code' => $statusCode];
        if (!empty($data)) {
            $error['data'] = $data;
        }

        return $response->status($statusCode)->json($error);
    }

    /**
     * Responde com sucesso JSON.
     *
     * @param Response $response
     * @param mixed $data
     * @param string $message
     * @return Response
     */
    protected function respondWithSuccess(Response $response, $data = null, string $message = 'Success'): Response
    {
        $result = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $result['data'] = $data;
        }

        return $response->json($result);
    }
}
