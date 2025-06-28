<?php

namespace Express\Core;

use Express\Http\Request;
use Express\Http\Response;
use Express\Routing\Router;
use Express\Middleware\MiddlewareStack;
use Express\Exceptions\HttpException;
use Express\Providers\Container;
use Express\Providers\ServiceProvider;
use Express\Providers\ContainerServiceProvider;
use Express\Providers\EventServiceProvider;
use Express\Providers\LoggingServiceProvider;
use Express\Providers\HookServiceProvider;
use Express\Providers\ExtensionServiceProvider;
use Express\Support\HookManager;
use Express\Events\ApplicationStarted;
use Express\Events\RequestReceived;
use Express\Events\ResponseSent;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
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
    public const VERSION = '2.1.0';

    /**
     * Container de dependências PSR-11.
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Configurações da aplicação.
     *
     * @var Config
     */
    protected Config $config;

    /**
     * Router da aplicação.
     *
     * @var Router
     */
    protected Router $router;

    /**
     * Stack de middlewares globais.
     *
     * @var MiddlewareStack
     */
    protected MiddlewareStack $middlewares;

    /**
     * Providers de serviços registrados.
     *
     * @var array<ServiceProvider>
     */
    protected array $serviceProviders = [];

    /**
     * Classes de providers para serem registrados.
     *
     * @var array<string>
     */
    protected array $providers = [
        ContainerServiceProvider::class,
        EventServiceProvider::class,
        LoggingServiceProvider::class,
        \Express\Providers\HookServiceProvider::class,
        \Express\Providers\ExtensionServiceProvider::class,
    ];

    /**
     * Indica se a aplicação foi inicializada.
     *
     * @var bool
     */
    protected bool $booted = false;

    /**
     * URL base da aplicação.
     */
    protected ?string $baseUrl = null;

    /**
     * Tempo de início da aplicação.
     */
    protected \DateTime $startTime;

    /**
     * Lista de listeners PSR-14 registrados.
     * @var array<string, array<int, callable>>
     */
    protected array $registeredListeners = [];

    /**
     * Construtor da aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     */
    public function __construct(?string $basePath = null)
    {
        $this->startTime = new \DateTime();
        $this->container = new Container();
        $this->registerBaseBindings();

        if ($basePath) {
            $this->setBasePath($basePath);
        }

        $this->registerCoreServices();
    }

    /**
     * Registra bindings básicos no container.
     *
     * @return void
     */
    protected function registerBaseBindings(): void
    {
        $this->container->instance(Application::class, $this);
        $this->container->alias('app', Application::class);
    }

    /**
     * Registra serviços core da aplicação.
     *
     * @return void
     */
    protected function registerCoreServices(): void
    {
        // Configuração
        $this->config = new Config();
        $this->container->instance(Config::class, $this->config);
        $this->container->alias('config', Config::class);

        // Router
        $this->router = new Router();
        $this->container->instance(Router::class, $this->router);
        $this->container->alias('router', Router::class);

        // Middleware Stack
        $this->middlewares = new MiddlewareStack();
        $this->container->instance(MiddlewareStack::class, $this->middlewares);
        $this->container->alias('middleware', MiddlewareStack::class);

        // Padronizar alias para hooks
        $this->alias('hooks', HookManager::class);
    }

    /**
     * Define o caminho base da aplicação.
     *
     * @param  string $basePath Caminho base
     * @return $this
     */
    public function setBasePath(string $basePath): self
    {
        $this->container->instance('path.base', rtrim($basePath, '\/'));
        $this->container->instance('path.config', $this->basePath('config'));
        $this->container->instance('path.storage', $this->basePath('storage'));
        $this->container->instance('path.public', $this->basePath('public'));
        $this->container->instance('path.logs', $this->basePath('logs'));

        return $this;
    }

    /**
     * Obtém um caminho relativo ao base path.
     *
     * @param  string $path Caminho relativo
     * @return string
     */
    public function basePath(string $path = ''): string
    {
        $basePath = $this->container->has('path.base') ? $this->container->get('path.base') : getcwd();
        return $basePath . ($path ? DIRECTORY_SEPARATOR . $path : '');
    }

    /**
     * Inicializa a aplicação.
     *
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
        $this->registerServiceProviders();

        // Fazer boot dos providers
        $this->bootServiceProviders();

        // Configurar error handling
        $this->configureErrorHandling();

        // Carregar middlewares padrão
        $this->loadDefaultMiddlewares();

        $this->booted = true;

        // Disparar evento de boot da aplicação
        $this->dispatchEvent(new ApplicationStarted($this->startTime, $this->config->all()));

        return $this;
    }

    /**
     * Carrega configurações da aplicação.
     *
     * @return void
     */
    protected function loadConfiguration(): void
    {
        $configPath = $this->container->has('path.config') ? $this->container->get('path.config') : null;

        if (is_string($configPath) && is_dir($configPath)) {
            $this->config->setConfigPath($configPath)->loadAll();
        }

        // Carregar .env se existir
        $envFile = $this->basePath('.env');
        if (file_exists($envFile)) {
            $this->config->loadEnvironment($envFile);
        }
    }

    /**
     * Registra service providers.
     *
     * @return void
     */
    protected function registerServiceProviders(): void
    {
        // Registrar providers básicos
        foreach ($this->providers as $provider) {
            $this->register($provider);
        }

        // Registrar providers adicionais do config
        $configProviders = $this->config->get('app.providers', []);
        if (is_array($configProviders)) {
            foreach ($configProviders as $provider) {
                if (is_string($provider)) {
                    $this->register($provider);
                }
            }
        }
    }

    /**
     * Faz boot dos service providers.
     *
     * @return void
     */
    protected function bootServiceProviders(): void
    {
        foreach ($this->serviceProviders as $provider) {
            if (method_exists($provider, 'boot')) {
                $provider->boot();
            }
        }
    }

    /**
     * Configura tratamento de erros.
     *
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
        /**
 * @phpstan-ignore-next-line
*/
        set_exception_handler([$this, 'handleException']);
    }

    /**
     * Carrega middlewares padrão.
     *
     * @return void
     */
    protected function loadDefaultMiddlewares(): void
    {
        $middlewares = $this->config->get('app.middleware', []);

        if (is_array($middlewares)) {
            foreach ($middlewares as $middleware) {
                if (is_string($middleware) || is_callable($middleware)) {
                    $this->middlewares->add($middleware);
                }
            }
        }
    }

    /**
     * Registra um service provider.
     *
     * @param  string|ServiceProvider $provider Classe ou instância do provider
     * @return $this
     */
    public function register(string|ServiceProvider $provider): self
    {
        $providerClass = is_string($provider) ? $provider : get_class($provider);
        foreach ($this->serviceProviders as $registered) {
            if (get_class($registered) === $providerClass) {
                return $this;
            }
        }

        // Criar instância se necessário
        if (is_string($provider)) {
            $provider = new $provider($this);
        }

        // Registrar o provider
        if (method_exists($provider, 'register')) {
            $provider->register();
        }

        if ($provider instanceof ServiceProvider) {
            $this->serviceProviders[] = $provider;
        }

        return $this;
    }

    /**
     * Adiciona um middleware global.
     *
     * @param  mixed $middleware Middleware a ser adicionado
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
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
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
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
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
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
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
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
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
     * @param  string $path    Caminho da rota
     * @param  mixed  $handler Handler da rota
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
     * @param  Request|null $request Requisição (se null, cria automaticamente)
     * @return Response
     */
    public function handle(?Request $request = null): Response
    {
        if (!$this->booted) {
            $this->boot();
        }

        $request = $request ?: Request::createFromGlobals();
        $response = new Response();
        $startTime = microtime(true);

        // Disparar evento de requisição recebida
        $this->dispatchEvent(new RequestReceived($request, new \DateTime()));

        try {
            // Encontrar rota
            $route = $this->router::identify($request->method, $request->pathCallable);

            if (!$route) {
                throw new HttpException(404, 'Route not found');
            }

            // Executar middlewares e handler
            $result = $this->middlewares->execute(
                $request,
                $response,
                function ($req, $res) use ($route) {
                    return $this->callRouteHandler($route, $req, $res);
                }
            );

            $finalResponse = $result instanceof Response ? $result : $response;

            // Disparar evento de resposta enviada
            $processingTime = microtime(true) - $startTime;
            $this->dispatchEvent(new ResponseSent($request, $finalResponse, new \DateTime(), $processingTime));

            return $finalResponse;
        } catch (Throwable $e) {
            $errorResponse = $this->handleException($e, $request, $response);

            // Disparar evento de resposta com erro
            $processingTime = microtime(true) - $startTime;
            $this->dispatchEvent(new ResponseSent($request, $errorResponse, new \DateTime(), $processingTime));

            return $errorResponse;
        }
    }

    /**
     * Executa o handler de uma rota.
     *
     * @param  array<string, mixed> $route    Dados da rota
     * @param  Request              $request  Requisição
     * @param  Response             $response Resposta
     * @return Response
     */
    protected function callRouteHandler(array $route, Request $request, Response $response): Response
    {
        $handler = $route['handler'];

        if (is_callable($handler)) {
            $result = $handler($request, $response);
        } else {
            throw new \InvalidArgumentException('Route handler is not callable');
        }

        return $result instanceof Response ? $result : $response;
    }

    /**
     * Bind a service to the container
     */
    public function bind(string $abstract, mixed $concrete = null, bool $shared = false): self
    {
        $this->container->bind($abstract, $concrete, $shared);
        return $this;
    }

    /**
     * Bind a singleton to the container
     */
    public function singleton(string $abstract, mixed $concrete = null): self
    {
        $this->container->singleton($abstract, $concrete);
        return $this;
    }

    /**
     * Register an existing instance in the container
     */
    public function instance(string $abstract, mixed $instance): self
    {
        $this->container->instance($abstract, $instance);
        return $this;
    }

    /**
     * Resolve a service from the container
     */
    public function make(string $abstract): mixed
    {
        return $this->container->get($abstract);
    }

    /**
     * Resolve a service from the container (alias for make)
     */
    public function resolve(string $id): mixed
    {
        return $this->container->get($id);
    }

    /**
     * Check if a service exists in the container (PSR-11)
     */
    public function has(string $id): bool
    {
        return $this->container->has($id);
    }

    /**
     * Create an alias for a service
     */
    public function alias(string $alias, string $abstract): self
    {
        $this->container->alias($alias, $abstract);
        return $this;
    }

    /**
     * Trata erros PHP.
     *
     * @param  int    $level   Nível
     *                         do erro
     * @param  string $message Mensagem do erro
     * @param  string $file    Arquivo do erro
     * @param  int    $line    Linha do erro
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
     * @param  Throwable     $e        Exceção
     * @param  Request|null  $request  Requisição
     *                                 (opcional)
     * @param  Response|null $response Resposta (opcional)
     * @return Response
     */
    public function handleException(Throwable $e, ?Request $request = null, ?Response $response = null): Response
    {
        $response = $response ?: new Response();
        $debug = $this->config->get('app.debug', false);

        // Log do erro usando PSR-3 logger
        $this->logException($e);

        // Determinar status code
        $statusCode = $e instanceof HttpException ? $e->getStatusCode() : 500;

        $response->status($statusCode);

        if ($debug) {
            return $response->json(
                [
                'error' => true,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
                ]
            );
        } else {
            return $response->json(
                [
                'error' => true,
                'message' => $statusCode === 404 ? 'Not Found' : 'Internal Server Error'
                ]
            );
        }
    }

    /**
     * Registra uma exceção no log usando PSR-3.
     *
     * @param  Throwable $e Exceção
     * @return void
     */
    protected function logException(Throwable $e): void
    {
        try {
            if ($this->container->has('logger')) {
                $logger = $this->container->get('logger');
                if ($logger instanceof LoggerInterface) {
                    $logger->error('Exception: {message}', [
                        'message' => $e->getMessage(),
                        'file' => $e->getFile(),
                        'line' => $e->getLine(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    return;
                }
            }
        } catch (\Throwable) {
            // Fallback se logger não disponível
        }

        // Fallback para error_log
        $message = sprintf(
            'Exception: %s in %s:%d',
            $e->getMessage(),
            $e->getFile(),
            $e->getLine()
        );

        error_log($message);
    }

    /**
     * Registra um listener de evento usando PSR-14.
     *
     * @param  string   $eventType Nome da classe do evento
     * @param  callable $listener  Listener
     * @return $this
     */
    public function addEventListener(string $eventType, callable $listener): self
    {
        if ($this->container->has('listeners')) {
            $listenerProvider = $this->container->get('listeners');
            if ($listenerProvider instanceof \Express\Providers\ListenerProvider) {
                $listenerProvider->addListener($eventType, $listener);
                // Rastrear listener
                $this->registeredListeners[$eventType][] = $listener;
            }
        }

        return $this;
    }

    /**
     * Remove todos os listeners PSR-14 previamente registrados.
     * @return void
     */
    public function clearEventListeners(): void
    {
        if ($this->container->has('listeners')) {
            $listenerProvider = $this->container->get('listeners');
            if ($listenerProvider instanceof \Express\Providers\ListenerProvider) {
                foreach ($this->registeredListeners as $eventType => $listeners) {
                    foreach ($listeners as $listener) {
                        $listenerProvider->removeListener($eventType, $listener);
                    }
                }
            }
        }
        $this->registeredListeners = [];
    }

    /**
     * Dispara um evento usando PSR-14.
     *
     * @param  object $event Evento a ser disparado
     * @return object
     */
    public function dispatchEvent(object $event): object
    {
        if ($this->container->has('events')) {
            $dispatcher = $this->container->get('events');
            if ($dispatcher instanceof EventDispatcherInterface) {
                return $dispatcher->dispatch($event);
            }
        }

        return $event;
    }

    /**
     * Alias para addEventListener (compatibilidade)
     *
     * @param  string   $event    Nome do evento
     * @param  callable $listener Listener
     * @return $this
     */
    public function on(string $event, callable $listener): self
    {
        return $this->addEventListener($event, $listener);
    }

    /**
     * Alias para dispatchEvent (compatibilidade)
     *
     * @param  string $event   Nome do evento
     * @param  mixed  ...$args Argumentos do evento
     * @return $this
     */
    public function fireEvent(string $event, ...$args): self
    {
        // Para compatibilidade, criar um evento simples
        $eventObject = new class ($event, $args) {
            /**
             * @param array<mixed> $data
             */
            public function __construct(
                public readonly string $name,
                public readonly array $data
            ) {
            }
        };

        $this->dispatchEvent($eventObject);
        return $this;
    }

    /**
     * Inicia o servidor de desenvolvimento.
     *
     * @param  int    $port Porta do servidor
     * @param  string $host Host do servidor
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
            if (is_string($name) && (is_string($value) || is_numeric($value))) {
                header("{$name}: {$value}");
            }
        }

        echo $response->getContent();
    }

    /**
     * Executa a aplicação e envia a resposta.
     *
     * @return void
     */
    public function run(): void
    {
        $response = $this->handle();

        // Enviar headers se ainda não foram enviados
        if (!headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $value) {
                if (is_string($name) && (is_string($value) || is_numeric($value))) {
                    header("{$name}: {$value}");
                }
            }
        }

        // Enviar conteúdo
        echo $response->getBody();
    }

    /**
     * Obtém o container de dependências.
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Obtém as configurações.
     *
     * @return Config
     */
    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Obtém o router.
     *
     * @return Router
     */
    public function getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Obtém o logger PSR-3.
     *
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        try {
            if ($this->container->has('logger')) {
                $logger = $this->container->get('logger');
                return $logger instanceof LoggerInterface ? $logger : null;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Obtém o event dispatcher PSR-14.
     *
     * @return EventDispatcherInterface|null
     */
    public function getEventDispatcher(): ?EventDispatcherInterface
    {
        try {
            if ($this->container->has('events')) {
                $dispatcher = $this->container->get('events');
                return $dispatcher instanceof EventDispatcherInterface ? $dispatcher : null;
            }
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Verifica se a aplicação foi inicializada.
     *
     * @return bool
     */
    public function isBooted(): bool
    {
        return $this->booted;
    }

    /**
     * Obtém a versão do framework.
     *
     * @return string
     */
    public function version(): string
    {
        return self::VERSION;
    }

    /**
     * Factory method para criar instância da aplicação (compatibilidade com ApiExpress).
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return self
     */
    public static function create(?string $basePath = null): self
    {
        return new self($basePath);
    }

    /**
     * Factory method estilo Express.js para criar aplicação.
     *
     * @param string|null $basePath Caminho base da aplicação
     * @return self
     */
    public static function express(?string $basePath = null): self
    {
        return new self($basePath);
    }

    /**
     * Configura múltiplas opções da aplicação de uma vez.
     *
     * @param array<string, mixed> $config Configurações
     * @return $this
     */
    public function configure(array $config): self
    {
        foreach ($config as $key => $value) {
            $this->config->set($key, $value);
        }

        return $this;
    }

    /**
     * Define a URL base da aplicação.
     *
     * @param string $baseUrl URL base
     * @return $this
     */
    public function setBaseUrl(string $baseUrl): self
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        return $this;
    }

    /**
     * Obtém a URL base da aplicação.
     *
     * @return string|null
     */
    public function getBaseUrl(): ?string
    {
        return $this->baseUrl;
    }

    // ==========================================
    // EXTENSION & HOOK MANAGEMENT METHODS
    // ==========================================

    /**
     * Get extension manager instance
     */
    public function extensions(): \Express\Providers\ExtensionManager
    {
        /** @var \Express\Providers\ExtensionManager */
        return $this->make(\Express\Providers\ExtensionManager::class);
    }

    /**
     * Get hook manager instance
     */
    public function hooks(): HookManager
    {
        /** @var HookManager */
        return $this->make(HookManager::class);
    }

    /**
     * Register an extension manually
     */
    public function registerExtension(string $name, string $provider, array $config = []): self
    {
        $this->extensions()->registerExtension($name, $provider, $config);
        return $this;
    }

    /**
     * Add an action hook
     */
    public function addAction(string $hook, callable $callback, int $priority = 10): self
    {
        $this->hooks()->addAction($hook, $callback, $priority);
        return $this;
    }

    /**
     * Add a filter hook
     */
    public function addFilter(string $hook, callable $callback, int $priority = 10): self
    {
        $this->hooks()->addFilter($hook, $callback, $priority);
        return $this;
    }

    /**
     * Execute an action hook
     */
    public function doAction(string $hook, array $context = []): self
    {
        $this->hooks()->doAction($hook, $context);
        return $this;
    }

    /**
     * Apply a filter hook
     *
     * @param mixed $data
     * @param array<string, mixed> $context
     * @return mixed
     */
    public function applyFilter(string $hook, mixed $data, array $context = []): mixed
    {
        return $this->hooks()->applyFilter($hook, $data, $context);
    }

    /**
     * Get extension statistics
     *
     * @return array{extensions: array, hooks: array}
     */
    public function getExtensionStats(): array
    {
        return [
            'extensions' => $this->extensions()->getStats(),
            'hooks' => $this->hooks()->getStats()
        ];
    }
}
