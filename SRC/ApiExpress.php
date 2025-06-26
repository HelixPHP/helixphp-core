<?php

namespace Express;

use Express\Controller\Router;
use Express\Services\Request;
use Express\Services\Response;

/**
 * Classe principal do framework Express PHP.
 * Responsável por inicializar a aplicação, delegar rotas e executar handlers.
 *
 * @property Router $router Instância do roteador.
 * @property array $server Referência para o array $_SERVER.
 */
class ApiExpress
{
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
   * @var string|null
   */
  private ?string $baseUrl = null;

  /**
   * Lista de sub-routers registrados via use.
   * @var array<object>
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
    // Inicia o buffer de saída para capturar erros
    // Isso é necessário para evitar problemas com headers já enviados
    // e permitir que o erro seja tratado corretamente
    ob_start();
    $this->server =& $_SERVER;
  }

  /**
   * Registra um middleware global.
   * @param callable $middleware Middleware com assinatura function($request, $response, $next)
   * @return void
   */
  public function use($middleware)
  {
    // Suporte a sub-routers
    if (is_object($middleware) && method_exists($middleware, 'getRoutes')) {
      $this->subRouters[] = $middleware;
      $routes = $middleware->getRoutes();
      foreach ($routes as $route) {
        $method = strtolower($route['method']);
        if (method_exists(Router::class, $method)) {
          $handlers = array_merge($route['middlewares'], [$route['handler']]);
          if (!empty($route['metadata'])) {
            $handlers[] = $route['metadata'];
          }
          Router::{$method}($route['path'], ...$handlers);
        }
      }
      return;
    }

    if (is_callable($middleware)) {
      $this->middlewares[] = $middleware;
      return;
    }

    // Permite manter compatibilidade com agrupamento de rotas (ex: $app->use('/user'))
    // @phpstan-ignore-next-line
    if (is_string($middleware)) {
      Router::use($middleware);
      return;
    }

    // Se chegou até aqui, middleware tem tipo inválido
    throw new \InvalidArgumentException('Middleware deve ser callable, string ou array de rotas');
  }

  /**
   * Inicia o processamento da requisição, identifica a rota e executa o handler correspondente.
   * Em caso de erro, retorna status 404 ou 500.
   * @return void
   */
  public function run()
  {
    try {
      // Corrigir path para ambientes sem PATH_INFO
      $pathInfo = $this->server['PATH_INFO'] ?? parse_url($this->server['REQUEST_URI'], PHP_URL_PATH);
      if (!$pathInfo) {
        $scriptName = $this->server['SCRIPT_NAME'] ?? '';
        $requestUri = $this->server['REQUEST_URI'] ?? '';
        $pathInfo = $requestUri;
        if ($scriptName && strpos($requestUri, $scriptName) === 0) {
          $pathInfo = substr($requestUri, strlen($scriptName));
        } elseif ($scriptName) {
          $dir = dirname($scriptName);
          if ($dir !== '/' && strpos($requestUri, $dir) === 0) {
            $pathInfo = substr($requestUri, strlen($dir));
          }
        }
        $pathInfo = '/' . ltrim($pathInfo, '/');
      }
      // Log temporário para depuração
      error_log('[ExpressPHP] PATH_INFO: ' . $pathInfo);
      $config = Router::identify($this->server['REQUEST_METHOD'], $pathInfo);
      // Se não encontrou no router principal, tenta nos sub-routers
      if (!$config && !empty($this->subRouters)) {
        foreach ($this->subRouters as $subRouter) {
          foreach ($subRouter->getRoutes() as $route) {
            if (strtoupper($route['method']) === $this->server['REQUEST_METHOD']) {
              $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $route['path']);
              $pattern = rtrim($pattern, '/');
              $pattern = '#^' . $pattern . '/?$#';
              if (preg_match($pattern, $pathInfo)) {
                $config = $route;
                break 2;
              }
            }
          }
        }
      }
      if (!$config) {
        header("HTTP/1.0 404 Not Found");
        echo "<pre>404 Not Found. PATH: {$pathInfo}\n\nRotas registradas:\n";
        if (method_exists($this->router, 'toString')) {
          echo htmlspecialchars($this->router::toString());
        } else {
          echo 'N/A';
        }
        echo "</pre>";
        return;
      }
      $response = new Response();
      $request  = new Request(
        $this->server['REQUEST_METHOD'],
        $config['path'],
        $pathInfo
      );
      $middlewares = $this->middlewares;
      if (isset($config['middlewares']) && is_array($config['middlewares'])) {
        $middlewares = array_merge($middlewares, $config['middlewares']);
      }
      $middlewares[] = $config['handler'];
      $this->executeMiddlewares($middlewares, $request, $response);
    } catch (\Exception $e) {
      $errors = ob_get_contents();
      ob_end_clean();
      ob_end_flush();
      // Log the error if needed
      if (!empty($errors)) {
        // error_log($errors);
      }
      header("HTTP/1.0 500 Internal Server Error");
      echo "500 Internal Server Error: " . $e->getMessage();
    }
  }

  /**
   * Executa middlewares em cadeia, chamando next() para o próximo.
   * @param array $middlewares Lista de middlewares + handler final
   * @param Request $request
   * @param Response $response
   * @return void
   */
  private function executeMiddlewares($middlewares, $request, $response)
  {
    $runner = function($index) use (&$middlewares, &$request, &$response, &$runner) {
      if ($index < count($middlewares)) {
        $middleware = $middlewares[$index];
        $middleware($request, $response, function() use (&$runner, $index) {
          $runner($index + 1);
        });
      }
    };
    $runner(0);
  }

  /**
   * Permite chamar métodos do Router dinamicamente (get, post, etc).
   * @param string $method Nome do método chamado.
   * @param array $args Argumentos do método.
   * @return mixed
   * @throws \BadMethodCallException Se o método não existir no Router.
   */
  public function __call($method, $args)
  {
    // Corrigido: usar método público para obter métodos aceitos
    if (method_exists(Router::class, $method) || in_array(strtoupper($method), Router::getHttpMethodsAccepted())) {
      // Call the Router method with the provided arguments
      return Router::{$method}(...$args);
    } else {
      throw new \BadMethodCallException("Method {$method} does not exist in Router class");
    }
  }

  /**
   * Define a URL base do app.
   * @param string $url
   */
  public function setBaseUrl(string $url): void {
    $this->baseUrl = rtrim($url, '/');
  }
  /**
   * Obtém a URL base do app.
   * @return string|null
   */
  public function getBaseUrl(): ?string {
    return $this->baseUrl;
  }
}
