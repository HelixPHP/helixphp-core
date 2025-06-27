<?php

namespace Express;

use Express\Core\Application;
use Express\Core\Container;
use Express\Core\Config;
use Express\Http\Request;
use Express\Http\Response;
use Express\Routing\Router;
use Express\Routing\RouteCache;
use Express\Routing\RouterInstance;
use Express\Middleware\MiddlewareStack;
use Express\Middleware\Security\CorsMiddleware;
use BadMethodCallException;
use Exception;

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
     *
     * @var array<string, mixed>
     */
    private array $server;

    /**
     * Lista de middlewares globais.
     *
     * @var array<callable>
     */
    private array $middlewares = [];

    /**
     * URL base do app para uso em geração de links/documentação.
     */
    private ?string $baseUrl = null;

    /**
     * Lista de sub-routers registrados via use.
     *
     * @var RouterInstance[]
     */
    private array $subRouters = [];

    /**
     * Cache para instâncias de classes anônimas
     */
    private ?object $routerInstance = null;
    private ?object $middlewareStackInstance = null;

    /**
     * Propriedade para acessar o Router estático.
     */
    public function __get(string $name): mixed
    {
        if ($name === 'router') {
            if ($this->routerInstance === null) {
                $this->routerInstance = new class
                {
                    /**
                     * @return array<string, mixed>
                     */
                    public function getGroupStats(): array
                    {
                        return Router::getGroupStats();
                    }

                    public function warmupGroups(): void
                    {
                        Router::warmupGroups();
                    }

                    /**
                     * @return array<string, mixed>|null
                     */
                    public function identifyByGroup(string $method, string $path): ?array
                    {
                        return Router::identifyByGroup($method, $path);
                    }

                    /**
                     * @return array<string, mixed>
                     */
                    public function benchmarkGroupAccess(string $prefix, int $iterations = 1000): array
                    {
                        return Router::benchmarkGroupAccess($prefix, $iterations);
                    }
                };
            }
            return $this->routerInstance;
        }

        if ($name === 'middlewareStack') {
            if ($this->middlewareStackInstance === null) {
                $this->middlewareStackInstance = new class
                {
                    /**
                     * @return array<string, mixed>
                     */
                    public function getStats(): array
                    {
                        return MiddlewareStack::getStats();
                    }

                    /**
                     * @param array<callable> $middlewares
                     * @return array<string, mixed>
                     */
                    public function benchmarkPipeline(array $middlewares, int $iterations = 1000): array
                    {
                        return MiddlewareStack::benchmarkPipeline($middlewares, $iterations);
                    }
                };
            }
            return $this->middlewareStackInstance;
        }

        throw new BadMethodCallException("Property $name does not exist");
    }

    /**
     * Construtor da aplicação Express PHP (versão otimizada).
     * Inicializa o roteador e referencia o array $_SERVER com lazy loading.
     */
    public function __construct(?string $baseUrl = null)
    {
        // OTIMIZAÇÃO: Lazy loading para componentes não essenciais
        if ($baseUrl) {
            $this->setBaseUrl($baseUrl);
        }

        // OTIMIZAÇÃO: Inicialização lazy garantida - Application criado sob demanda
        $this->initializeApp();

        // OTIMIZAÇÃO: Referência direta sem operações custosas
        $this->server = &$_SERVER;

        // OTIMIZAÇÃO: Warmup apenas se houver middlewares registrados
        // Movido para primeiro uso real
    }

    /**
     * Inicialização lazy da aplicação (thread-safe, uma única inicialização)
     */
    private function initializeApp(): void
    {
        // Guard clause para evitar múltiplas inicializações
        if (isset($this->app)) {
            return;
        }

        // Otimização: basePath calculado uma vez e cacheado estaticamente
        static $basePath = null;
        if ($basePath === null) {
            $basePath = dirname(__DIR__);
        }

        // Inicialização única garantida
        $this->app = new Application($basePath);
    }

    /**
     * Warmup de middlewares (lazy loading)
     */
    private function ensureMiddlewareWarmup(): void
    {
        static $warmedUp = false;
        if (!$warmedUp && !empty($this->middlewares)) {
            MiddlewareStack::warmupCommonPipelines();
            $warmedUp = true;
        }
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
     * Registra um middleware global ou define um prefixo para rotas (otimizado).
     *
     * @param mixed ...$args
     */
    public function use(...$args): void
    {
        if (count($args) === 1 && is_callable($args[0])) {
            // Middleware global
            $this->middlewares[] = $args[0];

            // OTIMIZAÇÃO: Garantir que app está inicializado (fail-safe)
            $this->ensureAppInitialized();
            $this->app->use($args[0]);

            // OTIMIZAÇÃO: Warmup apenas quando necessário
            $this->ensureMiddlewareWarmup();
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
     * Registra uma rota GET com otimização.
     *
     * @param mixed ...$handlers
     */
    public function get(string $path, ...$handlers): void
    {
        Router::get($path, ...$handlers);
    }

    /**
     * Registra uma rota POST com otimização.
     *
     * @param mixed ...$handlers
     */
    public function post(string $path, ...$handlers): void
    {
        Router::post($path, ...$handlers);
    }

    /**
     * Registra uma rota PUT com otimização.
     *
     * @param mixed ...$handlers
     */
    public function put(string $path, ...$handlers): void
    {
        Router::put($path, ...$handlers);
    }

    /**
     * Registra uma rota DELETE com otimização.
     *
     * @param mixed ...$handlers
     */
    public function delete(string $path, ...$handlers): void
    {
        Router::delete($path, ...$handlers);
    }

    /**
     * Registra uma rota PATCH com otimização.
     *
     * @param mixed ...$handlers
     */
    public function patch(string $path, ...$handlers): void
    {
        Router::patch($path, ...$handlers);
    }

    /**
     * Registra uma rota OPTIONS com otimização.
     *
     * @param mixed ...$handlers
     */
    public function options(string $path, ...$handlers): void
    {
        Router::options($path, ...$handlers);
    }

    /**
     * Registra uma rota HEAD com otimização.
     *
     * @param mixed ...$handlers
     */
    public function head(string $path, ...$handlers): void
    {
        Router::head($path, ...$handlers);
    }

    /**
     * Registra uma rota para qualquer método HTTP.
     *
     * @param mixed ...$handlers
     */
    public function any(string $path, ...$handlers): void
    {
        Router::any($path, ...$handlers);
    }

    /**
     * Cria um router de grupo otimizado.
     */
    /**
     * Cria um router de grupo otimizado.
     */
    public function group(string $prefix, callable $callback, array $middlewares = []): void
    {
        Router::group($prefix, $callback, $middlewares);
    }

    /**
     * Cria um router de instância.
     */
    public function router(): RouterInstance
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
            $parsedPath = parse_url($this->server['REQUEST_URI'] ?? '/', PHP_URL_PATH);
            $path = $parsedPath !== false ? $parsedPath : '/';

            // Encontrar rota correspondente usando sistema otimizado de grupos primeiro
            $route = Router::identifyByGroup($method, $path);

            // Se não encontrou por grupo, usa o router otimizado padrão
            if (!$route) {
                $route = Router::identify($method, $path);
            }

            if (!$route) {
                http_response_code(404);
                echo json_encode(['error' => 'Route not found', 'code' => 404]);
                return;
            }

            // Criar objetos Request e Response
            $request = new Request($method, $route['path'], $path ?? '/');
            $response = new Response();

            // Executar middlewares e handler
            $this->executeRoute($route, $request, $response);
        } catch (\Throwable $e) {
            $this->handleError($e);
        }
    }

    /**
     * Executa uma rota com seus middlewares usando pipeline otimizado.
     */
    private function executeRoute(array $route, Request $request, Response $response): void
    {
        $middlewares = array_merge($this->middlewares, $route['middlewares'] ?? []);
        $handler = $route['handler'];

        // Otimiza middlewares removendo redundantes
        $optimizedMiddlewares = MiddlewareStack::optimize($middlewares);

        // Cria chave de cache baseada na rota
        $cacheKey = $route['method'] . ':' . $route['path'];

        // Compila pipeline otimizado usando MiddlewareStack
        $middlewareStack = new MiddlewareStack();
        foreach ($optimizedMiddlewares as $middleware) {
            $middlewareStack->add($middleware);
        }

        // Define handler final
        $finalHandler = function ($req, $resp) use ($handler) {
            return call_user_func($handler, $req, $resp);
        };

        // Executa pipeline otimizado
        $middlewareStack->execute($request, $response, $finalHandler, $cacheKey);
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
     *
     * @return array<int, array<string, mixed>>
     */
    public function getRoutes(): array
    {
        return Router::getRoutes();
    }

    /**
     * Obtém informações da aplicação.
     *
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
     * Mantém consistência com a inicialização lazy garantindo novo estado limpo.
     */
    public function clear(): void
    {
        Router::clear();
        $this->middlewares = [];
        $this->subRouters = [];

        // Limpar caches relacionados
        RouteCache::clearCache();
        MiddlewareStack::clearCache();
        CorsMiddleware::clearCache();

        // Reinicializar aplicação para estado limpo
        unset($this->app);
        $this->initializeApp();
    }

    /**
     * Executa a aplicação.
     */
    public function run(): void
    {
        // Implementação simplificada para funcionar
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $parsedUri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path = $parsedUri !== false ? $parsedUri : null;

        // Se estamos rodando via CLI ou servidor built-in do PHP
        if (php_sapi_name() === 'cli-server' || php_sapi_name() === 'cli') {
            echo "Express PHP Server running\n";
        }

        // Identificar rota usando o Router
        $route = Router::identify($method, $path);

        if ($route) {
            // Criar objetos Request e Response simples
            $request = Request::createFromGlobals();
            $response = new Response();

            // Executar handler
            try {
                call_user_func($route['handler'], $request, $response);
            } catch (Exception $e) {
                header('Content-Type: application/json');
                echo json_encode(['error' => true, 'message' => $e->getMessage()]);
            }
        } else {
            // Rota não encontrada
            header('HTTP/1.1 404 Not Found');
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Route not found', 'path' => $path]);
        }
    }

    /**
     * Proxy para métodos HTTP customizados.
     *
     * @param mixed[] $args
     */
    public function __call(string $method, array $args): void
    {
        // Lista de métodos HTTP que devem ser delegados ao Router
        $httpMethods = ['get', 'post', 'put', 'delete', 'patch', 'head', 'options'];

        if (in_array(strtolower($method), $httpMethods)) {
            // Delega para os métodos estáticos do Router
            $callback = [Router::class, $method];
            if (is_callable($callback)) {
                call_user_func_array($callback, $args);
            }
        } else {
            throw new BadMethodCallException("Method {$method} does not exist");
        }
    }

    /**
     * Proxy para métodos estáticos.
     *
     * @param mixed[] $args
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        return Router::__callStatic($method, $args);
    }

    /**
     * Realiza warmup dos caches após registro das rotas
     */
    public function warmupCaches(): void
    {
        // Aquece cache de rotas
        Router::warmupCache();

        // Aquece grupos
        Router::warmupGroups();
    }

    /**
     * Garantia de que a aplicação está inicializada (fail-safe).
     * Método de segurança para evitar estados inconsistentes.
     */
    private function ensureAppInitialized(): void
    {
        if (!isset($this->app)) {
            $this->initializeApp();
        }
    }
}
