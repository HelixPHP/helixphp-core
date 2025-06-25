<?php
namespace Express\Controller;
use InvalidArgumentException;

/**
 * Classe Router responsável pelo registro e identificação de rotas HTTP.
 * Permite agrupar rotas, registrar handlers e identificar rotas para requisições.
 *
 * @property string $prev_path Prefixo/base para rotas agrupadas.
 * @property array $routes Lista de rotas registradas.
 */
class Router
{
  /**
   * Prefixo/base para rotas agrupadas.
   * @var string
   */
  private static $prev_path = '';
  private static $current_group_prefix = '';
  /**
   * Lista de rotas registradas.
   * @var array
   */
  private static $routes = [];
  /**
   * Caminho padrão.
   * @var string
   */
  const DEFAULT_PATH = '/';
  /**
   * Métodos HTTP aceitos.
   * @var string[]
   */
  private static $httpMethodsAccepted = ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'];

  /**
   * Permite adicionar métodos HTTP customizados.
   * @param string $method Método HTTP customizado.
   * @return void
   */
  public static function addHttpMethod($method)
  {
    $method = strtoupper($method);
    if (!in_array($method, self::$httpMethodsAccepted)) {
      self::$httpMethodsAccepted[] = $method;
    }
  }

  /**
   * Middlewares de grupo por prefixo de rota.
   * @var array
   */
  private static $groupMiddlewares = [];

  /**
   * Define um prefixo/base para rotas agrupadas OU registra middlewares para um grupo de rotas.
   * @param string $prev_path Prefixo/base para rotas.
   * @param callable ...$middlewares Middlewares para o grupo.
   * @throws InvalidArgumentException Se $prev_path não for string.
   * @return void
   */
  public static function use($prev_path, ...$middlewares)
  {
    if (!is_string($prev_path)) {
      throw new InvalidArgumentException('Previous path must be a string');
    }
    if (empty($prev_path)) {
      $prev_path = '/';
    }
    self::$prev_path = $prev_path;
    self::$current_group_prefix = $prev_path;
    // Se middlewares foram passados, registra para o grupo
    if (!empty($middlewares)) {
      foreach ($middlewares as $mw) {
        if (!is_callable($mw)) {
          throw new InvalidArgumentException('Group middleware must be callable');
        }
      }
      self::$groupMiddlewares[$prev_path] = $middlewares;
    }
  }

  /**
   * Adiciona uma nova rota com método, caminho, middlewares e handler.
   * @param string $method Método HTTP.
   * @param string $path Caminho da rota.
   * @param callable[] ...$middlewares Middlewares opcionais.
   * @param callable $handler Função handler da rota.
   * @throws InvalidArgumentException Se o método não for suportado.
   * @return void
   */
  private static function add($method, $path, ...$handlers)
  {
    if (empty($path)) {
      $path = self::DEFAULT_PATH;
    }
    if (!in_array(strtoupper($method), self::$httpMethodsAccepted)) {
      throw new InvalidArgumentException("Method {$method} is not supported");
    }
    $method = strtoupper($method);
    if (empty($handlers)) {
      throw new InvalidArgumentException('Handler must be provided');
    }
    // Suporte a metadados: se o último argumento for array associativo, é metadado
    $metadata = [];
    if (is_array(end($handlers)) && self::isAssoc(end($handlers))) {
      $metadata = self::sanitizeForJson(array_pop($handlers));
    }
    $handler = array_pop($handlers); // último argumento é o handler final
    if (!is_callable($handler)) {
      throw new InvalidArgumentException('Handler must be a callable function');
    }
    foreach ($handlers as $mw) {
      if (!is_callable($mw)) {
        throw new InvalidArgumentException('Middleware must be callable');
      }
    }
    // Corrigir: só aplica o prefixo do grupo atual, não acumula
    $prefix = self::$current_group_prefix;
    if (!empty($prefix) && $prefix !== '/' && strpos($path, $prefix) !== 0) {
      $path = $prefix . $path;
      $path = preg_replace('/\/+/', '/', $path); // Remove duplicate slashes
    }
    // Ensure the path starts with a slash
    if ($path[0] !== '/') {
      $path = '/' . $path;
    }
    // Adiciona middlewares de grupo se houver para o prefixo
    $groupMiddlewares = [];
    foreach (self::$groupMiddlewares as $prefix => $middlewares) {
      if (strpos($path, $prefix) === 0) {
        $groupMiddlewares = array_merge($groupMiddlewares, $middlewares);
      }
    }
    self::$routes[] = [
      'method'      => $method,
      'path'        => $path,
      'middlewares' => array_merge($groupMiddlewares, $handlers),
      'handler'     => $handler,
      'metadata'    => $metadata
    ];
  }

  // Verifica se array é associativo
  private static function isAssoc(array $arr)
  {
    if ([] === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  /**
   * Get routes based on method and path.
   * @param string $method The HTTP method (GET, POST, etc.).
   * @param string|null $path The path to match (optional).
   * @throws InvalidArgumentException if the method is not supported.
   * @return array The matching routes.
   */
  public static function identify($method, $path = null)
  {
    if (!in_array(strtoupper($method), self::$httpMethodsAccepted)) {
      throw new InvalidArgumentException("Method {$method} is not supported");
    }
    $method = strtoupper($method);
    if (is_null($path)) {
      $path = self::DEFAULT_PATH;
    }
    //> Filter routes based on method
    $routes = array_filter(self::$routes, function ($route) use ($method) {
      return $route['method'] === $method;
    });
    if (empty($routes)) {
      return null; // No routes found for the specified method
    }

    // 1. Tenta encontrar rota estática (exata)
    foreach ($routes as $route) {
      if ($route['path'] === $path) {
        return $route;
      }
    }
    // 2. Tenta encontrar rota dinâmica (com parâmetros)
    foreach ($routes as $route) {
      $pattern = preg_replace('/\/(:[^\/]+)/', '/([^/]+)', $route['path']);
      // Permitir barra final opcional
      $pattern = rtrim($pattern, '/');
      $pattern = '#^' . $pattern . '/?$#';
      if ($route['path'] === self::DEFAULT_PATH) {
        if ($path === self::DEFAULT_PATH) {
          return $route;
        }
      } elseif (preg_match($pattern, $path)) {
        return $route;
      }
    }
    return null; // Nenhuma rota encontrada
  }

  public static function __callStatic($method, $args)
  {
    if (in_array(strtoupper($method), self::$httpMethodsAccepted)) {
      $path = array_shift($args);
      self::add(strtoupper($method), $path, ...$args);
      return;
    }

    if (method_exists(self::class, $method)) {
      return call_user_func_array([self::class, $method], $args);
    }
    throw new \BadMethodCallException("Method {$method} does not exist in " . self::class);
  }

  public static function toString()
  {
    $output = '';
    foreach (self::$routes as $route) {
      $output .= sprintf(
        "%s %s => %s\n",
        $route['method'],
        $route['path'],
        is_callable($route['handler']) ? 'Callable' : 'Not Callable'
      );
    }
    return $output;
  }

  /**
   * Retorna todas as rotas registradas (para exportação/documentação).
   * @return array
   */
  public static function getRoutes()
  {
    return self::$routes;
  }

  /**
   * Retorna os métodos HTTP aceitos.
   * @return array
   */
  public static function getHttpMethodsAccepted()
  {
    return self::$httpMethodsAccepted;
  }

  // Remove closures, objetos e recursos de arrays recursivamente
  private static function sanitizeForJson($value) {
    if (is_array($value)) {
      $out = [];
      foreach ($value as $k => $v) {
        if (is_array($v)) {
          $out[$k] = self::sanitizeForJson($v);
        } elseif (is_scalar($v) || is_null($v)) {
          $out[$k] = $v;
        } elseif (is_object($v)) {
          // Permite stdClass convertendo para array
          if ($v instanceof \stdClass) {
            $out[$k] = self::sanitizeForJson((array)$v);
          } else {
            // Ignora closures e outros objetos
            $out[$k] = '[object]';
          }
        } elseif (is_resource($v)) {
          $out[$k] = '[resource]';
        } else {
          $out[$k] = '[unserializable]';
        }
      }
      return $out;
    }
    if (is_scalar($value) || is_null($value)) return $value;
    if (is_object($value)) {
      if ($value instanceof \stdClass) return self::sanitizeForJson((array)$value);
      return '[object]';
    }
    if (is_resource($value)) return '[resource]';
    return '[unserializable]';
  }
}
