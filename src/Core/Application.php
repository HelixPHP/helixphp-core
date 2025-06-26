<?php

namespace Express\Core;

use Express\Http\Request;
use Express\Http\Response;
use Express\Routing\Router;
use Express\Middleware\MiddlewareStack;
use Express\Exceptions\HttpException;
use Express\Exceptions\NotFoundException;
use Throwable;

/**
 * Classe principal da aplicação Express-PHP.
 *
 * Gerencia o ciclo de vida da aplicação, incluindo:
 * - Inicialização e configuração
 * - Roteamento de requisições
 * - Execução de middlewares
 * - Tratamento de erros
 * - Resposta HTTP
 */
class Application
{
    /**
     * Versão do framework.
     */
    public const VERSION = '2.0.0';

    /**
     * Container de dependências.
     * @var Container
     */
    protected Container $container;

    /**
     * Configurações da aplicação.
     * @var Config
     */
    protected Config $config;

    /**
     * Router da aplicação.
     * @var Router
     */
    protected Router $router;

    /**
     * Stack de middlewares globais.
     * @var MiddlewareStack
     */
    protected MiddlewareStack $middlewares;

    /**
     * Indica se a aplicação foi inicializada.
     * @var bool
     */
    protected bool $booted = false;

    /**
     * Providers de serviços registrados.
     * @var array<string>
     */
    protected array $providers = [];

    /**
     * Listeners de eventos registrados.
     * @var array<string, array<callable>>
     */
    protected array $listeners = [];

    /**
     * Construtor da aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     */
    public function __construct(?string $basePath = null)
    {
        $this->container = Container::getInstance();
        $this->registerBaseBindings();

        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerCoreServices();
    }

    /**
     * Registra bindings básicos no container.
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        $this->container->instance(Application::class, $this);
        $this->container->alias(Application::class, 'app');
    }

    /**
     * Registra serviços core da aplicação.
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // Configuração
        $this->config = new Config();
        $this->container->instance(Config::class, $this->config);
        $this->container->alias(Config::class, 'config');

        // Router
        $this->router = new Router($this->container);
        $this->container->instance(Router::class, $this->router);
        $this->container->alias(Router::class, 'router');

        // Middleware Stack
        $this->middlewares = new MiddlewareStack($this->container);
        $this->container->instance(MiddlewareStack::class, $this->middlewares);
        $this->container->alias(MiddlewareStack::class, 'middleware');
    }

    /**
     * Define o caminho base da aplicação.
     *
     * @param string $basePath Caminho base
     * @return $this
     */
    public function setBasePath(string $basePath): self
    {
        $this->container->instance('path.base', rtrim($basePath, '\/'));
        $this->container->instance('path.config', $this->basePath('config'));
        $this->container->instance('path.storage', $this->basePath('storage'));
        $this->container->instance('path.public', $this->basePath('public'));

        return $this;
    }

    /**
     * Obtém um caminho relativo ao base path.
     *
     * @param string $path Caminho relativo
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        $basePath = $this->container->make('path.base');
        return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Inicializa a aplicação.
     * @return $this
     */
    public function boot(): self
    {
        if ($this->booted) {
            return $this;
        }

        // Carregar configurações
        $this->loadConfiguration();

        // Registrar providers de serviços
        $this->registerProviders();

        // Configurar error handling
        $this->configureErrorHandling();

        // Carregar middlewares padrão
        $this->loadDefaultMiddlewares();

        $this->booted = true;

        // Disparar evento de boot
        $this->fireEvent('application.booted', $this);

        return $this;
    }

    /**
     * Carrega configurações da aplicação.
     * @return void
     */
    protected function loadConfiguration(): void
    {
        $configPath = $this->container->make('path.config');

        if (is_dir($configPath)) {
            $this->config->setConfigPath($configPath)->loadAll();
        }

        // Carregar .env se existir
        $envFile = $this->basePath('.env');
        if (file_exists($envFile)) {
            $this->config->loadEnvironment($envFile);
        }
    }

    /**
     * Registra providers de serviços.
     * @return void
     */
    protected function registerProviders(): void
    {
        $providers = $this->config->get('app.providers', []);

        foreach ($providers as $provider) {
            $this->register($provider);
        }
    }

    /**
     * Configura tratamento de erros.
     * @return void
     */
    protected function configureErrorHandling(): void
    {
        $debug = $this->config->get('app.debug', false);

        if ($debug) {
            error_reporting(E_ALL);
            ini_set('display_errors', '1');
        } else {
            error_reporting(0);
            ini_set('display_errors', '0');
        }

        set_error_handler([$this, 'handleError']);
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Carrega middlewares padrão.
     * @return void
     */
    protected function loadDefaultMiddlewares(): void
    {
        $middlewares = $this->config->get('app.middleware', []);

        foreach ($middlewares as $middleware) {
            $this->middlewares->add($middleware);
        }
    }

    /**
     * Registra um provider de serviços.
     *
     * @param string $provider Classe do provider
     * @return $this
     */
    public function register(string $provider): self
    {
        if (in_array($provider, $this->providers)) {
            return $this;
        }

        $instance = $this->container->make($provider);

        if (method_exists($instance, 'register')) {
            $instance->register($this);
        }

        $this->providers[] = $provider;

        return $this;
    }

    /**
     * Adiciona um middleware global.
     *
     * @param mixed $middleware Middleware a ser adicionado
     * @return $this
     */
    public function use($middleware): self
    {
        $this->middlewares->add($middleware);
        return $this;
    }

    /**
     * Registra uma rota GET.
     *
     * @param string $path Caminho da rota
     * @param mixed $handler Handler da rota
     * @return $this
     */
    public function get(string $path, $handler): self
    {
        $this->router->get($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota POST.
     *
     * @param string $path Caminho da rota
     * @param mixed $handler Handler da rota
     * @return $this
     */
    public function post(string $path, $handler): self
    {
        $this->router->post($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota PUT.
     *
     * @param string $path Caminho da rota
     * @param mixed $handler Handler da rota
     * @return $this
     */
    public function put(string $path, $handler): self
    {
        $this->router->put($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota DELETE.
     *
     * @param string $path Caminho da rota
     * @param mixed $handler Handler da rota
     * @return $this
     */
    public function delete(string $path, $handler): self
    {
        $this->router->delete($path, $handler);
        return $this;
    }

    /**
     * Registra uma rota PATCH.
     *
     * @param string $path Caminho da rota
     * @param mixed $handler Handler da rota
     * @return $this
     */
    public function patch(string $path, $handler): self
    {
        $this->router->patch($path, $handler);
        return $this;
    }

    /**
     * Processa uma requisição HTTP.
     *
     * @param Request|null $request Requisição (se null, cria automaticamente)
     * @return Response
     */
    public function handle(?Request $request = null): Response
    {
        if (!$this->booted) {
            $this->boot();
        }

        $request = $request ?: Request::createFromGlobals();
        $response = new Response();

        try {
            // Encontrar rota
            $route = $this->router->resolve($request);

            if (!$route) {
                throw new NotFoundException('Route not found');
            }

            // Executar middlewares e handler
            return $this->middlewares->handle($request, $response, function($req, $res) use ($route) {
                return $this->callRouteHandler($route, $req, $res);
            });

        } catch (Throwable $e) {
            return $this->handleException($e, $request, $response);
        }
    }

    /**
     * Executa o handler de uma rota.
     *
     * @param array<string, mixed> $route Dados da rota
     * @param Request $request Requisição
     * @param Response $response Resposta
     * @return Response
     */
    protected function callRouteHandler(array $route, Request $request, Response $response): Response
    {
        $handler = $route['handler'];

        if (is_callable($handler)) {
            $result = $this->container->call($handler, [$request, $response]);
        } else {
            throw new \InvalidArgumentException('Route handler is not callable');
        }

        return $result instanceof Response ? $result : $response;
    }

    /**
     * Trata erros PHP.
     *
     * @param int $level Nível do erro
     * @param string $message Mensagem do erro
     * @param string $file Arquivo do erro
     * @param int $line Linha do erro
     * @return bool
     */
    public function handleError(int $level, string $message, string $file, int $line): bool
    {
        if (error_reporting() & $level) {
            throw new \ErrorException($message, 0, $level, $file, $line);
        }

        return false;
    }

    /**
     * Trata exceções não capturadas.
     *
     * @param Throwable $e Exceção
     * @param Request|null $request Requisição (opcional)
     * @param Response|null $response Resposta (opcional)
     * @return Response
     */
    public function handleException(Throwable $e, ?Request $request = null, ?Response $response = null): Response
    {
        $response = $response ?: new Response();
        $debug = $this->config->get('app.debug', false);

        // Log do erro
        $this->logException($e);

        // Determinar status code
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        $response->status($statusCode);

        if ($debug) {
            return $response->json([
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
        } else {
            return $response->json([
                'error' => true,
                'message' => $statusCode === 404 ? 'Not Found' : 'Internal Server Error'
            ]);
        }
    }

    /**
     * Registra uma exceção no log.
     *
     * @param Throwable $e Exceção
     * @return void
     */
    protected function logException(Throwable $e): void
    {
        $message = sprintf(
            'Exception: %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        error_log($message);
    }

    /**
     * Registra um listener de evento.
     *
     * @param string $event Nome do evento
     * @param callable $listener Listener
     * @return $this
     */
    public function on(string $event, callable $listener): self
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = [];
        }

        $this->listeners[$event][] = $listener;
        return $this;
    }

    /**
     * Dispara um evento.
     *
     * @param string $event Nome do evento
     * @param mixed ...$args Argumentos do evento
     * @return $this
     */
    public function fireEvent(string $event, ...$args): self
    {
        if (isset($this->listeners[$event])) {
            foreach ($this->listeners[$event] as $listener) {
                call_user_func($listener, ...$args);
            }
        }

        return $this;
    }

    /**
     * Inicia o servidor de desenvolvimento.
     *
     * @param int $port Porta do servidor
     * @param string $host Host do servidor
     * @return void
     */
    public function listen(int $port = 8000, string $host = 'localhost'): void
    {
        echo "Express-PHP v" . self::VERSION . " server started at http://{$host}:{$port}\n";
        echo "Press Ctrl+C to stop\n\n";

        // Processar requisição
        $response = $this->handle();

        // Enviar resposta (em ambiente real, isso seria feito pelo servidor web)
        http_response_code($response->getStatusCode());

        foreach ($response->getHeaders() as $name => $value) {
            header("{$name}: {$value}");
        }

        echo $response->getContent();
    }

    /**
     * Executa a aplicação e envia a resposta.
     * @return void
     */
    public function run(): void
    {
        $response = $this->handle();
        $response->send();
    }

    /**
     * Obtém o container de dependências.
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Obtém as configurações.
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Obtém o router.
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Verifica se a aplicação foi inicializada.
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Obtém a versão do framework.
     * @return string
     */
    public function version(): string
    {
        return self::VERSION;
    }
}
