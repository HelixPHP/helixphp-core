<?php

namespace Express\Middleware;

use Express\Http\Request;
use Express\Http\Response;

/**
 * Classe para gerenciar e executar uma stack de middlewares.
 */
class MiddlewareStack
{
    /**
     * @var array<callable>
     */
    private array $middlewares = [];

    /**
     * Adiciona um middleware à stack.
     *
     * @param callable $middleware
     * @return void
     */
    public function add(callable $middleware): void
    {
        $this->middlewares[] = $middleware;
    }

    /**
     * Executa todos os middlewares na stack.
     *
     * @param Request $request
     * @param Response $response
     * @param callable $finalHandler
     * @return mixed
     */
    public function execute(Request $request, Response $response, callable $finalHandler)
    {
        // Se não há middlewares, executa o handler final
        if (empty($this->middlewares)) {
            return $finalHandler($request, $response);
        }

        // Criar pipeline de execução
        $pipeline = array_reverse($this->middlewares);

        // Começar com o handler final
        $next = $finalHandler;

        // Construir pipeline
        foreach ($pipeline as $middleware) {
            $currentNext = $next;
            $next = function($req, $resp) use ($middleware, $currentNext) {
                return $middleware($req, $resp, $currentNext);
            };
        }

        // Executar pipeline
        return $next($request, $response);
    }

    /**
     * Obtém todos os middlewares.
     *
     * @return array<callable>
     */
    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }

    /**
     * Limpa todos os middlewares.
     *
     * @return void
     */
    public function clear(): void
    {
        $this->middlewares = [];
    }

    /**
     * Conta o número de middlewares.
     *
     * @return int
     */
    public function count(): int
    {
        return count($this->middlewares);
    }

    /**
     * Verifica se a stack está vazia.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return empty($this->middlewares);
    }
}
