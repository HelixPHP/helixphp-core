<?php

namespace Express\Middlewares\Core;

/**
 * Middleware padrão para tratamento centralizado de erros na API Express PHP.
 *
 * - Captura qualquer exceção (Throwable) lançada durante o processamento da requisição.
 * - Retorna resposta JSON padronizada com status HTTP apropriado.
 * - Em ambiente de desenvolvimento (APP_ENV=dev ou APP_DEBUG), inclui detalhes do erro e stack trace.
 * - Deve ser registrado como o primeiro middleware global do app para capturar todos os erros subsequentes.
 *
 * Exemplo de uso:
 *
 *   use Express\Middlewares\Core\ErrorHandlerMiddleware;
 *   $app->use(new ErrorHandlerMiddleware());
 *
 * Resposta de erro (produção):
 *   {
 *     "error": true,
 *     "message": "Mensagem do erro",
 *     "type": "TipoDaExcecao"
 *   }
 *
 * Resposta de erro (desenvolvimento):
 *   {
 *     "error": true,
 *     "message": "Mensagem do erro",
 *     "type": "TipoDaExcecao",
 *     "file": "arquivo.php",
 *     "line": 42,
 *     "trace": [ ... ]
 *   }
 */
class ErrorHandlerMiddleware
{
    /**
     * @var callable|null Handler customizado para resposta de erro
     */
    private $customHandler;

    /**
     * @param callable|null $customHandler Função customizada para tratar erros (recebe $e, $request, $response)
     */
    public function __construct(callable $customHandler = null)
    {
        $this->customHandler = $customHandler;
    }

    public function __invoke(mixed $request, mixed $response, callable $next): mixed
    {
        try {
            return $next();
        } catch (\Throwable $e) {
            if ($this->customHandler) {
                // Permite resposta customizada
                ($this->customHandler)($e, $request, $response);
                exit;
            }
            $status = method_exists($e, 'getCode') && $e->getCode() ? $e->getCode() : 500;
            if ($status < 400 || $status > 599) {
                $status = 500;
            }
            $body = [
                'error' => true,
                'message' => $e->getMessage(),
                'type' => get_class($e),
            ];
            // Em ambiente de desenvolvimento, inclua detalhes do erro
            if (getenv('APP_ENV') === 'dev' || getenv('APP_DEBUG')) {
                $body['file'] = $e->getFile();
                $body['line'] = $e->getLine();
                $body['trace'] = explode("\n", $e->getTraceAsString());
            }
            $response->status($status)->json($body);
            exit;
        }
    }
}
