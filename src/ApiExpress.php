<?php

namespace Express;

use Express\Core\Application;
use Express\Core\Container;
use Express\Core\Config;
use Express\Http\Request;
use Express\Http\Response;
use Express\Routing\Router;
use Express\Routing\RouterInstance;

/**
 * Classe principal do framework Express PHP.
 * Responsável por inicializar a aplicação, delegar rotas e executar handlers.
 *
 * Esta classe serve como fachada para a nova arquitetura modular.
 */
class ApiExpress
{
    /**
     * Instância da aplicação.
     */
    private Application $app;

    /**
     * Referência para o array $_SERVER.
     * @var array<string, mixed>
     */
    private array $server;

    /**
     * Lista de middlewares globais.
     * @var array<callable>
     */
    private array $middlewares = [];

    /**
     * URL base do app para uso em geração de links/documentação.
     */
    private ?string $baseUrl = null;

    /**
     * Lista de sub-routers registrados via use.
     * @var RouterInstance[]
     */
    private array $subRouters = [];

    /**
     * Construtor da aplicação Express PHP.
     * Inicializa o roteador e referencia o array $_SERVER.
     */
    public function __construct(?string $baseUrl = null)
    {
        if ($baseUrl) {
            $this->setBaseUrl($baseUrl);
        }

        // Inicializa a nova aplicação modular
        $this->app = new Application();
        $this->server = $_SERVER;
    }

    /**
     * Define a URL base da aplicação.
     */
    public function setBaseUrl(string $baseUrl): void
    {
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    /**
     * Obtém a URL base da aplicação.
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    /**
     * Registra um middleware global ou define um prefixo para rotas.
     */
    public function use(...$args): void
    {
        if (count($args) === 1 && is_callable($args[0])) {
            // Middleware global
            $this->middlewares[] = $args[0];
            $this->app->use($args[0]);
        } elseif (count($args) >= 2 && is_string($args[0])) {
            $path = $args[0];
            $handler = $args[1];

            if ($handler instanceof RouterInstance) {
                // Sub-router
                $this->subRouters[] = $handler;
                // Registrar rotas do sub-router no router principal
                foreach ($handler->getRoutes() as $route) {
                    Router::add(
                        $route['method'],
                        $route['path'],
                        ...array_merge($route['middlewares'], [$route['handler']])
                    );
                }
            } elseif (is_callable($handler)) {
                // Middleware para caminho específico
                Router::use($path, $handler);
            }
        }
    }

    /**
     * Registra uma rota GET.
     */
    public function get(string $path, ...$handlers): void
    {
        Router::get($path, ...$handlers);
    }

    /**
     * Registra uma rota POST.
     */
    public function post(string $path, ...$handlers): void
    {
        Router::post($path, ...$handlers);
    }

    /**
     * Registra uma rota PUT.
     */
    public function put(string $path, ...$handlers): void
    {
        Router::put($path, ...$handlers);
    }

    /**
     * Registra uma rota DELETE.
     */
    public function delete(string $path, ...$handlers): void
    {
        Router::delete($path, ...$handlers);
    }

    /**
     * Registra uma rota PATCH.
     */
    public function patch(string $path, ...$handlers): void
    {
        Router::patch($path, ...$handlers);
    }

    /**
     * Registra uma rota OPTIONS.
     */
    public function options(string $path, ...$handlers): void
    {
        Router::options($path, ...$handlers);
    }

    /**
     * Registra uma rota HEAD.
     */
    public function head(string $path, ...$handlers): void
    {
        Router::head($path, ...$handlers);
    }

    /**
     * Registra uma rota para qualquer método HTTP.
     */
    public function any(string $path, ...$handlers): void
    {
        Router::any($path, ...$handlers);
    }

    /**
     * Cria um router de grupo.
     */
    public function Router(): RouterInstance
    {
        return new RouterInstance();
    }

    /**
     * Inicia a aplicação e processa a requisição atual.
     */
    public function listen(?int $port = null): void
    {
        try {
            // Obter método e caminho da requisição atual
            $method = $this->server['REQUEST_METHOD'] ?? 'GET';
            $path = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

            // Encontrar rota correspondente
            $route = Router::identify($method, $path);

            if (!$route) {
                http_response_code(404);
                echo json_encode(['error' => 'Route not found', 'code' => 404]);
                return;
            }

            // Criar objetos Request e Response
            $request = new Request($method, $route['path'], $path);
            $response = new Response();

            // Executar middlewares e handler
            $this->executeRoute($route, $request, $response);

        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    /**
     * Executa uma rota com seus middlewares.
     */
    private function executeRoute(array $route, Request $request, Response $response): void
    {
        $middlewares = array_merge($this->middlewares, $route['middlewares'] ?? []);
        $handler = $route['handler'];

        // Criar pipeline de execução
        $pipeline = array_reverse($middlewares);

        $finalHandler = function($req, $resp) use ($handler) {
            return call_user_func($handler, $req, $resp);
        };

        // Executar pipeline
        $next = $finalHandler;
        foreach ($pipeline as $middleware) {
            $currentNext = $next;
            $next = function($req, $resp) use ($middleware, $currentNext) {
                return call_user_func($middleware, $req, $resp, $currentNext);
            };
        }

        $next($request, $response);
    }

    /**
     * Trata erros da aplicação.
     */
    private function handleError(\Throwable $e): void
    {
        http_response_code(500);

        $error = [
            'error' => 'Internal Server Error',
            'code' => 500
        ];

        // Em desenvolvimento, mostrar detalhes do erro
        if (getenv('APP_ENV') === 'development') {
            $error['message'] = $e->getMessage();
            $error['file'] = $e->getFile();
            $error['line'] = $e->getLine();
            $error['trace'] = $e->getTraceAsString();
        }

        header('Content-Type: application/json');
        echo json_encode($error);
    }

    /**
     * Obtém todas as rotas registradas.
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return Router::getRoutes();
    }

    /**
     * Obtém informações da aplicação.
     * @return array<string, mixed>
     */
    public function getAppInfo(): array
    {
        return [
            'name' => 'Express PHP',
            'version' => Application::VERSION,
            'baseUrl' => $this->baseUrl,
            'routes' => count($this->getRoutes()),
            'middlewares' => count($this->middlewares),
            'subRouters' => count($this->subRouters)
        ];
    }

    /**
     * Limpa todas as rotas e middlewares (útil para testes).
     */
    public function clear(): void
    {
        Router::clear();
        $this->middlewares = [];
        $this->subRouters = [];
    }

    /**
     * Proxy para métodos HTTP customizados.
     * @param mixed[] $args
     */
    public function __call(string $method, array $args): void
    {
        $path = array_shift($args);
        Router::add(strtoupper($method), $path, ...$args);
    }

    /**
     * Proxy para métodos estáticos.
     * @param mixed[] $args
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        return Router::__callStatic($method, $args);
    }
}
